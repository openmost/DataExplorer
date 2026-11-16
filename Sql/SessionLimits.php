<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use Piwik\Db\AdapterInterface;

/**
 * Statement timeout of the current session, MySQL and MariaDB name it differently.
 */
final class SessionLimits
{
    /**
     * MySQL error 3024 and MariaDB error 1969 are raised when the statement timeout is reached.
     */
    public const TIMEOUT_ERROR_CODES = [3024, 1969];

    /**
     * @param int $seconds 0 or less removes the limit
     */
    public static function forCurrentServer(AdapterInterface $db, int $seconds): string
    {
        $seconds = max(0, $seconds);

        if (self::isMariaDb($db)) {
            return 'SET SESSION max_statement_time=' . $seconds;
        }

        return 'SET SESSION MAX_EXECUTION_TIME=' . ($seconds * 1000);
    }

    public static function isTimeout(AdapterInterface $db, \Throwable $e): bool
    {
        foreach (self::TIMEOUT_ERROR_CODES as $code) {
            if ($e instanceof \Exception && $db->isErrNo($e, $code)) {
                return true;
            }
        }

        return false;
    }

    private static function isMariaDb(AdapterInterface $db): bool
    {
        $version = method_exists($db, 'fetchOne') ? (string) $db->fetchOne('SELECT VERSION()') : '';

        return stripos($version, 'mariadb') !== false;
    }
}
