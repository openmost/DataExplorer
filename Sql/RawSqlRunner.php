<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use PhpMyAdmin\SqlParser\Token;
use Piwik\Cache;
use Piwik\DataTable;
use Piwik\Date;
use Piwik\Db\AdapterInterface;
use Piwik\Log;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Site;

/**
 * Runs a raw SQL query accepted by SqlGuard on the read-only connection, in a read-only transaction, under the
 * time and row limits.
 */
final class RawSqlRunner
{
    /**
     * Pinned so the session reads quotes and backslashes exactly as the guard's lexer did: no ANSI_QUOTES, no
     * NO_BACKSLASH_ESCAPES, whatever the server default is.
     */
    public const SQL_MODE = 'ONLY_FULL_GROUP_BY,STRICT_TRANS_TABLES,NO_ZERO_IN_DATE,NO_ZERO_DATE,ERROR_FOR_DIVISION_BY_ZERO,NO_ENGINE_SUBSTITUTION';

    private const WRAPPER_ALIAS = 'dataexplorer_q';

    /** @var callable(): AdapterInterface */
    private $connect;

    /**
     * @param callable(): AdapterInterface|null $connect the read-only connection, ReadOnlyConnection::get() by default
     */
    public function __construct(?callable $connect = null)
    {
        $this->connect = $connect ?: function () {
            return ReadOnlyConnection::get();
        };
    }

    /**
     * @throws NotConfiguredException|NotReadOnlyException|RejectedQueryException
     * @throws \InvalidArgumentException with a translated message when the query fails or times out
     */
    public function run(string $sql, int $idSite, string $period, string $date, bool $useCache = false): DataTable
    {
        (new ReportPermissions())->checkCanRunRawSql();

        return $this->runChecked($sql, $this->validate($sql), $idSite, $period, $date, $useCache);
    }

    /**
     * Runs a saved raw SQL report for whoever may view it on $idSite (visibility follows placement, no super user
     * needed), through the cache. The stored query is checked again by the guard and must still be proven
     * site-scoped, {idsite} is bound to $idSite.
     *
     * @param array $report a hydrated row of ReportsDao
     * @param int|null $maxSeconds statement time limit, max_execution_time_seconds by default
     * @throws NotConfiguredException|NotReadOnlyException|RejectedQueryException
     * @throws \InvalidArgumentException with a translated message when the query fails or times out
     */
    public function runSaved(array $report, int $idSite, string $period, string $date, ?int $maxSeconds = null): DataTable
    {
        (new ReportPermissions())->checkCanView($report, $idSite);

        if (($report['type'] ?? '') !== ReportDefinition::TYPE_SQL) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorReportNotAvailable'));
        }

        $sql = is_array($report['definition'] ?? null) && is_string($report['definition']['sql'] ?? null) ? $report['definition']['sql'] : '';
        $parsed = $this->validate($sql);
        $unproven = SiteScopeProof::check($parsed);
        if ($unproven !== null) {
            throw new RejectedQueryException(Piwik::translate('DataExplorer_SqlWarningNotSiteScoped', [$unproven]), $unproven);
        }

        return $this->runChecked($sql, $parsed, $idSite, $period, $date, true, $maxSeconds);
    }

    private function validate(string $sql): ParsedQuery
    {
        $config = Configuration::getReadOnlyDbConfig();
        if ($config === null) {
            throw new NotConfiguredException();
        }

        return SqlGuard::validate($sql, $config['tables_prefix'], $config['dbname']);
    }

    private function runChecked(string $sql, ParsedQuery $parsed, int $idSite, string $period, string $date, bool $useCache, ?int $maxSeconds = null): DataTable
    {
        [$executable, $placeholders] = self::prepare($parsed, Configuration::getRawSqlRowLimit());
        $values = ['{idsite}' => $idSite] + self::dateBounds($idSite, $period, $date);
        $bind = array_map(function ($placeholder) use ($values) {
            return $values[$placeholder];
        }, $placeholders);

        $ttl = Configuration::getRawSqlCacheTtl();
        $cacheId = 'DataExplorer_rawSql_' . md5($sql . '|' . $idSite . '|' . $period . '|' . $date);
        $cache = Cache::getLazyCache();
        if ($useCache && $ttl > 0) {
            $rows = $cache->fetch($cacheId);
            if (is_array($rows)) {
                return DataTable::makeFromSimpleArray($rows);
            }
        }

        $db = ($this->connect)();
        ReadOnlyConnection::assertReadOnly($db);

        $started = microtime(true);
        $status = 'error';
        $rows = null;
        try {
            $rows = $this->execute($db, $executable, $bind, $maxSeconds ?? Configuration::getMaxExecutionTimeSeconds());
            $status = 'ok';
        } catch (\InvalidArgumentException $e) {
            if ($e->getPrevious() instanceof \Exception && SessionLimits::isTimeout($db, $e->getPrevious())) {
                $status = 'timeout';
            }
            throw $e;
        } finally {
            $this->audit(md5($sql), $idSite, $status, (int) round((microtime(true) - $started) * 1000), $rows === null ? null : count($rows));
        }

        if ($useCache && $ttl > 0) {
            $cache->save($cacheId, $rows, $ttl);
        }

        return DataTable::makeFromSimpleArray($rows);
    }

    /**
     * The SQL to run, rebuilt from the guard's tokens: each placeholder becomes "?" (one written inside a string or
     * a comment is never bound), and the rows are capped at $cap. Without a LIMIT of its own the query gets
     * " LIMIT <cap>" appended, a top-level LIMIT with literal numbers is lowered to the cap. Only another LIMIT
     * form is wrapped in a derived table, which would lose the order on MariaDB and refuse duplicate column names.
     *
     * @return array{0: string, 1: string[]} the SQL and the placeholder of each "?" in order
     */
    public static function prepare(ParsedQuery $parsed, int $cap): array
    {
        $tokens = $parsed->getTokens();
        $count = count($tokens);
        [$limit, $rowCount] = self::topLevelLimit($tokens);

        $sql = '';
        $original = '';
        $placeholders = [];
        for ($i = 0; $i < $count; $i++) {
            $token = $tokens[$i];
            if ($token->type === Token::TYPE_DELIMITER) {
                continue;
            }
            if ($token->type === Token::TYPE_COMMENT) {
                $sql .= ' ';
                $original .= ' ';
                continue;
            }
            if ($token->token === '{' && $i + 2 < $count) {
                $text = $token->token . $tokens[$i + 1]->token . $tokens[$i + 2]->token;
                if (in_array($text, SqlGuard::PLACEHOLDERS, true)) {
                    $sql .= '?';
                    $original .= $text;
                    $placeholders[] = $text;
                    $i += 2;
                    continue;
                }
            }
            $sql .= $i === $rowCount ? (string) min((int) $token->token, $cap) : $token->token;
            $original .= $token->token;
        }

        // Fails closed if the tokens no longer rebuild the text the guard validated.
        if (trim($original) . "\n" !== $parsed->getExecutableSql()) {
            throw new \LogicException('The placeholders of the query could not be bound.');
        }

        $sql = trim($sql) . "\n";
        if ($limit === null) {
            return [$sql . 'LIMIT ' . $cap, $placeholders];
        }
        if ($rowCount === null) {
            return ['SELECT * FROM (' . $sql . ') ' . self::WRAPPER_ALIAS . ' LIMIT ' . $cap, $placeholders];
        }

        return [$sql, $placeholders];
    }

    /**
     * The LIMIT that bounds the whole query: at depth 0, after the last top-level UNION.
     *
     * @param Token[] $tokens
     * @return array{0: ?int, 1: ?int} index of that LIMIT keyword, index of its row count when it is a literal integer
     */
    private static function topLevelLimit(array $tokens): array
    {
        $depth = 0;
        $limit = null;
        foreach ($tokens as $i => $token) {
            if ($token->type === Token::TYPE_OPERATOR && $token->token === '(') {
                $depth++;
            } elseif ($token->type === Token::TYPE_OPERATOR && $token->token === ')') {
                $depth--;
            } elseif ($depth === 0 && $token->type === Token::TYPE_KEYWORD) {
                $keyword = strtoupper((string) $token->keyword);
                if (strpos($keyword, 'UNION') === 0) {
                    $limit = null;
                } elseif ($keyword === 'LIMIT') {
                    $limit = $i;
                }
            }
        }
        if ($limit === null) {
            return [null, null];
        }

        $next = self::meaningful($tokens, $limit + 1);
        $first = $next[0] ?? null;
        $second = $next[1] ?? null;
        if ($first === null || !self::isInteger($tokens[$first])) {
            return [$limit, null];
        }
        if ($second !== null && $tokens[$second]->type === Token::TYPE_OPERATOR && $tokens[$second]->token === ',') {
            $third = $next[2] ?? null;

            return [$limit, $third !== null && self::isInteger($tokens[$third]) ? $third : null];
        }

        return [$limit, $first];
    }

    /**
     * @param Token[] $tokens
     * @return int[] indexes of the next three tokens that are not whitespace or comments
     */
    private static function meaningful(array $tokens, int $from): array
    {
        $found = [];
        $count = count($tokens);
        for ($i = $from; $i < $count && count($found) < 3; $i++) {
            if (!in_array($tokens[$i]->type, [Token::TYPE_WHITESPACE, Token::TYPE_COMMENT], true)) {
                $found[] = $i;
            }
        }

        return $found;
    }

    private static function isInteger(Token $token): bool
    {
        return $token->type === Token::TYPE_NUMBER && ctype_digit((string) $token->token);
    }

    /**
     * Start and end of the period in the site's timezone, as UTC datetimes, like the bounds core archiving puts
     * on visit_last_action_time.
     *
     * @return array{'{date_start}': string, '{date_end}': string}
     */
    public static function dateBounds(int $idSite, string $period, string $date): array
    {
        $timezone = Site::getTimezoneFor($idSite);
        $periodObject = PeriodFactory::makePeriodFromQueryParams($timezone, $period, $date);

        return [
            '{date_start}' => $periodObject->getDateTimeStart()->setTimezone($timezone)->toString(Date::DATE_TIME_FORMAT),
            '{date_end}' => $periodObject->getDateTimeEnd()->setTimezone($timezone)->toString(Date::DATE_TIME_FORMAT),
        ];
    }

    private function execute(AdapterInterface $db, string $sql, array $bind, int $maxSeconds): array
    {
        $restore = SessionLimits::restoreStatement($db);
        $failure = null;
        $inTransaction = false;
        try {
            $db->query("SET SESSION sql_mode = '" . self::SQL_MODE . "'");
            $db->query(SessionLimits::forCurrentServer($db, $maxSeconds));
            $db->query('SET SESSION TRANSACTION READ ONLY');
            $db->query('START TRANSACTION READ ONLY');
            $inTransaction = true;

            return $db->fetchAll($sql, $bind);
        } catch (\Exception $e) {
            $failure = $e;
            if (SessionLimits::isTimeout($db, $e)) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorRawSqlTimeout', [Configuration::getMaxExecutionTimeSeconds()]), 0, $e);
            }
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorRawSqlFailed', [$e->getMessage()]), 0, $e);
        } finally {
            $cleanupFailure = null;
            if ($inTransaction) {
                try {
                    $db->query('ROLLBACK');
                } catch (\Exception $rollbackFailure) {
                    $cleanupFailure = $rollbackFailure;
                }
            }
            try {
                $db->query($restore);
            } catch (\Exception $restoreFailure) {
                $cleanupFailure = $cleanupFailure ?: $restoreFailure;
            }
            if ($cleanupFailure !== null && $failure === null) {
                throw $cleanupFailure;
            }
        }
    }

    /**
     * @param string $status ok, error or timeout
     * @param int|null $rowCount null when the query failed
     */
    private function audit(string $queryHash, int $idSite, string $status, int $durationMs, ?int $rowCount): void
    {
        $login = Piwik::getCurrentUserLogin();
        Log::info('DataExplorer: raw SQL query %s run by %s on site %d, %s, %d ms, %s rows.', $queryHash, $login, $idSite, $status, $durationMs, $rowCount === null ? '-' : (string) $rowCount);

        /**
         * Triggered after each raw SQL query run on the database, failed or not (not when served from the cache).
         * Carries no query text, bound value or row, only who ran which query (md5 of its text), how it ended, for
         * how long, with how many rows.
         *
         * @param array $info login, queryHash, idSite, status (ok, error or timeout), durationMs, rowCount (null unless ok)
         */
        Piwik::postEvent('DataExplorer.rawSqlExecuted', [[
            'login' => $login,
            'queryHash' => $queryHash,
            'idSite' => $idSite,
            'status' => $status,
            'durationMs' => $durationMs,
            'rowCount' => $rowCount,
        ]]);
    }
}
