<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use Piwik\Common;
use Piwik\Config;
use Piwik\Db;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Configuration;

/**
 * What the setup page shows: the state of the read-only connection and the statements creating it. The configured
 * password is never part of it.
 */
final class ReadOnlySetup
{
    public const DEFAULT_USERNAME = 'matomo_readonly';

    private const LOCAL_HOSTS = ['', 'localhost', '127.0.0.1', '::1'];

    /**
     * @return array{state: string, message: string} state is ready, not_configured, not_read_only, too_wide or connection_failed
     */
    public function status(): array
    {
        $config = Configuration::getReadOnlyDbConfig();
        if ($config === null) {
            return ['state' => 'not_configured', 'message' => Piwik::translate('DataExplorer_RawSqlNotConfigured')];
        }

        try {
            ReadOnlyConnection::assertReadOnly();
        } catch (NotReadOnlyException $e) {
            return ['state' => $e->isTooWide() ? 'too_wide' : 'not_read_only', 'message' => $e->getMessage()];
        } catch (\Exception $e) {
            return ['state' => 'connection_failed', 'message' => Piwik::translate('DataExplorer_SetupConnectionFailed', [$e->getMessage()])];
        }

        return ['state' => 'ready', 'message' => Piwik::translate('DataExplorer_SetupReady', [$config['username']])];
    }

    /**
     * Prefixed names of the allowed tables that exist in the Matomo database.
     *
     * @return string[]
     */
    public function tables(): array
    {
        $prefix = strtolower($this->prefix());
        $tables = [];
        foreach (Db::fetchAll('SHOW TABLES') as $row) {
            $table = (string) reset($row);
            $lower = strtolower($table);
            if (substr($lower, 0, strlen($prefix)) === $prefix && SqlGuard::isAllowedTable(substr($lower, strlen($prefix)))) {
                $tables[] = $table;
            }
        }
        sort($tables);

        return $tables;
    }

    /**
     * CREATE USER and one GRANT SELECT per allowed table, never a grant on the whole database.
     */
    public function sqlSnippet(): string
    {
        $account = $this->quote($this->username()) . '@' . $this->quote($this->host());
        $database = $this->database();

        $lines = ['CREATE USER ' . $account . ' IDENTIFIED BY ' . $this->quote($this->passwordPlaceholder()) . ';'];
        foreach ($this->tables() as $table) {
            $lines[] = 'GRANT SELECT ON ' . $this->identifier($database) . '.' . $this->identifier($table) . ' TO ' . $account . ';';
        }

        return implode("\n", $lines);
    }

    public function iniBlock(): string
    {
        return "[DataExplorer]\n"
            . 'readonly_username = "' . str_replace('"', '', $this->username()) . "\"\n"
            . 'readonly_password = "' . $this->passwordPlaceholder() . "\"\n";
    }

    /**
     * True when the snippet lets the user connect from any host, the page then says how to narrow it.
     */
    public function isAnyHost(): bool
    {
        return $this->host() === '%';
    }

    private function host(): string
    {
        $config = Configuration::getReadOnlyDbConfig();
        $database = Config::getInstance()->database;
        $host = $config !== null ? $config['host'] : (string) ($database['host'] ?? '');
        $socket = $config !== null ? $config['unix_socket'] : (string) ($database['unix_socket'] ?? '');

        return $socket !== '' || in_array(strtolower($host), self::LOCAL_HOSTS, true) ? 'localhost' : '%';
    }

    private function username(): string
    {
        $config = Configuration::getReadOnlyDbConfig();

        return $config !== null ? $config['username'] : self::DEFAULT_USERNAME;
    }

    private function database(): string
    {
        $config = Configuration::getReadOnlyDbConfig();

        return $config !== null ? $config['dbname'] : (string) Config::getInstance()->database['dbname'];
    }

    private function prefix(): string
    {
        $config = Configuration::getReadOnlyDbConfig();

        return $config !== null ? $config['tables_prefix'] : Common::prefixTable('');
    }

    private function passwordPlaceholder(): string
    {
        return Piwik::translate('DataExplorer_SetupPasswordPlaceholder');
    }

    private function quote(string $value): string
    {
        return "'" . str_replace(['\\', "'"], ['\\\\', "''"], $value) . "'";
    }

    private function identifier(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }
}
