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

        $config = Configuration::getReadOnlyDbConfig();
        if ($config === null) {
            throw new NotConfiguredException();
        }

        $parsed = SqlGuard::validate($sql, $config['tables_prefix'], $config['dbname']);
        [$bounded, $placeholders] = self::bindPlaceholders($parsed);
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

        $wrapped = 'SELECT * FROM (' . $bounded . ') ' . self::WRAPPER_ALIAS . ' LIMIT ' . Configuration::getRawSqlRowLimit();

        $db = ($this->connect)();
        ReadOnlyConnection::assertReadOnly($db);

        $started = microtime(true);
        $rows = $this->execute($db, $wrapped, $bind);
        $durationMs = (int) round((microtime(true) - $started) * 1000);

        $this->audit(md5($sql), $idSite, $durationMs, count($rows));

        if ($useCache && $ttl > 0) {
            $cache->save($cacheId, $rows, $ttl);
        }

        return DataTable::makeFromSimpleArray($rows);
    }

    /**
     * The executable SQL with each placeholder replaced by "?", rebuilt from the guard's tokens so a placeholder
     * written inside a string or a comment is never bound.
     *
     * @return array{0: string, 1: string[]} the SQL and the placeholder of each "?" in order
     */
    public static function bindPlaceholders(ParsedQuery $parsed): array
    {
        $tokens = $parsed->getTokens();
        $count = count($tokens);
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
            $sql .= $token->token;
            $original .= $token->token;
        }

        // Fails closed if the tokens no longer rebuild the text the guard validated.
        if (trim($original) . "\n" !== $parsed->getExecutableSql()) {
            throw new \LogicException('The placeholders of the query could not be bound.');
        }

        return [trim($sql) . "\n", $placeholders];
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

    private function execute(AdapterInterface $db, string $sql, array $bind): array
    {
        $restore = SessionLimits::restoreStatement($db);
        $failure = null;
        $inTransaction = false;
        try {
            $db->query("SET SESSION sql_mode = '" . self::SQL_MODE . "'");
            $db->query(SessionLimits::forCurrentServer($db, Configuration::getMaxExecutionTimeSeconds()));
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
            try {
                if ($inTransaction) {
                    $db->query('ROLLBACK');
                }
                $db->query($restore);
            } catch (\Exception $restoreFailure) {
                if ($failure === null) {
                    throw $restoreFailure;
                }
            }
        }
    }

    private function audit(string $queryHash, int $idSite, int $durationMs, int $rowCount): void
    {
        $login = Piwik::getCurrentUserLogin();
        Log::info('DataExplorer: raw SQL query %s run by %s on site %d, %d ms, %d rows.', $queryHash, $login, $idSite, $durationMs, $rowCount);

        /**
         * Triggered after each raw SQL query run on the database (not when served from the cache). Carries no
         * query text, bound value or row, only who ran which query (md5 of its text), for how long, with how many rows.
         *
         * @param array $info login, queryHash, idSite, durationMs, rowCount
         */
        Piwik::postEvent('DataExplorer.rawSqlExecuted', [[
            'login' => $login,
            'queryHash' => $queryHash,
            'idSite' => $idSite,
            'durationMs' => $durationMs,
            'rowCount' => $rowCount,
        ]]);
    }
}
