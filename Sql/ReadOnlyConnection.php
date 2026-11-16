<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use Piwik\Config;
use Piwik\Db\Adapter;
use Piwik\Db\AdapterInterface;
use Piwik\Plugins\DataExplorer\Configuration;
use Zend_Db_Table;

/**
 * The connection of the read-only database user raw SQL runs with, never the main Matomo connection.
 */
final class ReadOnlyConnection
{
    private const QUOTED = '`(?:[^`]|``)*`|\'(?:[^\'\\\\]|\\\\.|\'\')*\'';
    private const NAME = '(?:`(?:[^`]|``)+`|\'(?:[^\'\\\\]|\\\\.|\'\')+\'|[A-Za-z0-9_$]+)';
    private const GRANTEE = '(?:`(?:[^`]|``)*`|\'(?:[^\'\\\\]|\\\\.|\'\')*\'|[^\s@]+)@(?:`(?:[^`]|``)*`|\'(?:[^\'\\\\]|\\\\.|\'\')*\'|\S+)';

    /** What may follow the grantee of the USAGE line: authentication, TLS and resource limits, no privilege. */
    private const USAGE_TAIL = '/^(?:\s+IDENTIFIED\s+(?:BY\s+PASSWORD\s+\'[^\']*\'|VIA\s+\S+(?:\s+(?:USING|AS)\s+(?:PASSWORD\(\s*\'[^\']*\'\s*\)|\'[^\']*\'))?))?'
        . '(?:\s+REQUIRE\s+(?:NONE|SSL|X509|[A-Z]+\s+\'[^\']*\'(?:\s+(?:AND\s+)?[A-Z]+\s+\'[^\']*\')*))?'
        . '(?:\s+WITH(?:\s+MAX_[A-Z_]+\s+\d+)+)?\s*$/i';

    private const SSL_KEYS = ['enable_ssl', 'ssl_ca', 'ssl_cert', 'ssl_key', 'ssl_ca_path', 'ssl_cipher', 'ssl_no_verify'];

    /** @var array<string, AdapterInterface> connection settings hash => connection */
    private static $connections = [];

    /** @var \SplObjectStorage|null connection => array<string, true|NotReadOnlyException> per database and prefix */
    private static $checked;

    /**
     * @throws NotConfiguredException when no read-only user is configured
     */
    public static function get(): AdapterInterface
    {
        $config = self::config();
        $key = md5(serialize($config));
        if (!isset(self::$connections[$key])) {
            self::$connections[$key] = self::connect($config);
        }

        return self::$connections[$key];
    }

    /**
     * Refuses a connection whose user holds any privilege besides USAGE and SELECT on allowed tables of the
     * Matomo database, checked once per connection and request.
     *
     * @throws NotConfiguredException|NotReadOnlyException
     */
    public static function assertReadOnly(?AdapterInterface $db = null): void
    {
        self::grantedTables($db);
    }

    /**
     * Tables the read-only user may SELECT, as granted, once assertReadOnly() holds.
     *
     * @return string[]
     * @throws NotConfiguredException|NotReadOnlyException
     */
    public static function grantedTables(?AdapterInterface $db = null): array
    {
        $config = self::config();
        $db = $db ?? self::get();
        self::$checked = self::$checked ?: new \SplObjectStorage();
        $results = self::$checked->contains($db) ? self::$checked[$db] : [];
        $key = $config['dbname'] . '|' . $config['tables_prefix'];

        if (!isset($results[$key])) {
            $grants = array_map(function ($row) {
                return (string) reset($row);
            }, $db->fetchAll('SHOW GRANTS FOR CURRENT_USER()'));
            // Unreadable means case sensitive: the stricter comparison.
            $lowerCaseNames = $db->fetchOne('SELECT @@lower_case_table_names');
            $caseInsensitive = is_numeric($lowerCaseNames) && (int) $lowerCaseNames !== 0;
            try {
                $results[$key] = self::checkGrants($grants, $config['dbname'], $config['tables_prefix'], $caseInsensitive);
            } catch (NotReadOnlyException $e) {
                $results[$key] = $e;
            }
            self::$checked[$db] = $results;
        }

        if ($results[$key] instanceof NotReadOnlyException) {
            throw $results[$key];
        }

        return $results[$key];
    }

    /**
     * @param string[] $grants lines of SHOW GRANTS
     * @param string $database the Matomo database, the only one a SELECT may be granted on
     * @param string $prefix Matomo table prefix
     * @param bool $caseInsensitive the server's lower_case_table_names is not 0, so "Matomo" names "matomo"
     * @return string[] the tables granted SELECT, lowercase when names are case insensitive
     * @throws NotReadOnlyException
     */
    public static function checkGrants(array $grants, string $database, string $prefix, bool $caseInsensitive = false): array
    {
        $pattern = '/^GRANT\s+(.+?)\s+ON\s+(' . self::NAME . '|\*)\.(' . self::NAME . '|\*)\s+TO\s+' . self::GRANTEE . '(.*)$/s';
        $fold = function (string $name) use ($caseInsensitive): string {
            return $caseInsensitive ? strtolower($name) : $name;
        };

        $tables = [];
        foreach ($grants as $grant) {
            $grant = trim($grant);
            $unquoted = (string) preg_replace('/' . self::QUOTED . '/', "''", $grant);
            if (preg_match('/\b(?:GRANT|ADMIN)\s+OPTION\b/i', $unquoted) || !preg_match($pattern, $grant, $m)) {
                throw new NotReadOnlyException($grant, false);
            }

            $privileges = strtoupper((string) preg_replace('/\s+/', ' ', trim($m[1])));
            $schema = $m[2];
            $table = $m[3];
            $tail = $m[4];

            if ($privileges === 'USAGE' && $schema === '*' && $table === '*' && preg_match(self::USAGE_TAIL, $tail)) {
                continue;
            }
            if ($privileges !== 'SELECT' || trim($tail) !== '') {
                throw new NotReadOnlyException($grant, false);
            }
            if ($table === '*') {
                throw new NotReadOnlyException($grant, true);
            }

            // Matomo creates its tables in lowercase: on a case-sensitive server another case is another table.
            $table = $fold(self::unquote($table));
            $prefix = $fold($prefix);
            $name = substr($table, strlen($prefix));
            if (
                $fold(self::unquote($schema)) !== $fold($database)
                || substr($table, 0, strlen($prefix)) !== $prefix
                || $name !== strtolower($name)
                || !SqlGuard::isAllowedTable($name)
            ) {
                throw new NotReadOnlyException($grant, false);
            }
            $tables[] = $table;
        }

        return array_values(array_unique($tables));
    }

    /**
     * Closes the connections and forgets the checks, for tests that change the database users.
     *
     * @internal
     */
    public static function reset(): void
    {
        foreach (self::$connections as $connection) {
            if (method_exists($connection, 'closeConnection')) {
                $connection->closeConnection();
            }
        }
        self::$connections = [];
        self::$checked = null;
    }

    private static function config(): array
    {
        $config = Configuration::getReadOnlyDbConfig();
        if ($config === null) {
            throw new NotConfiguredException();
        }

        return $config;
    }

    private static function connect(array $config): AdapterInterface
    {
        $main = Config::getInstance()->database;
        $main = is_array($main) ? $main : [];

        $infos = ['enable_ssl' => 0];
        foreach (self::SSL_KEYS as $key) {
            if (isset($main[$key]) && $main[$key] !== '') {
                $infos[$key] = $main[$key];
            }
        }
        $infos['username'] = $config['username'];
        $infos['password'] = $config['password'];
        foreach (['host', 'port', 'dbname', 'unix_socket', 'charset', 'collation'] as $key) {
            if ($config[$key] !== '') {
                $infos[$key] = $config[$key];
            }
        }

        // The factory makes the new connection the default adapter of every Zend table, Matomo's is kept.
        $previous = Zend_Db_Table::getDefaultAdapter();
        try {
            return @Adapter::factory($config['adapter'], $infos);
        } finally {
            Zend_Db_Table::setDefaultAdapter($previous);
        }
    }

    private static function unquote(string $name): string
    {
        if ($name !== '' && $name[0] === '`') {
            return str_replace('``', '`', substr($name, 1, -1));
        }
        if ($name !== '' && $name[0] === "'") {
            return str_replace(["''", "\\'"], "'", substr($name, 1, -1));
        }

        return $name;
    }
}
