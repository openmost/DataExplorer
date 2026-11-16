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

    /** @var \SplObjectStorage|null keeps the connections it knows, so an id is never reused for another one */
    private static $isMariaDb;

    /**
     * @param int $seconds 0 or less removes the limit
     */
    public static function forCurrentServer(AdapterInterface $db, int $seconds): string
    {
        return self::forCurrentServerMilliseconds($db, max(0, $seconds) * 1000);
    }

    /**
     * @param int $milliseconds 0 or less removes the limit
     */
    public static function forCurrentServerMilliseconds(AdapterInterface $db, int $milliseconds): string
    {
        $milliseconds = max(0, $milliseconds);

        if (self::isMariaDb($db)) {
            $seconds = $milliseconds % 1000 === 0 ? (string) ($milliseconds / 1000) : sprintf('%.3F', $milliseconds / 1000);

            return 'SET SESSION max_statement_time=' . $seconds;
        }

        return 'SET SESSION MAX_EXECUTION_TIME=' . $milliseconds;
    }

    /**
     * The statement setting back the timeout the session has now, to run once the limited queries are done: the
     * connection is shared with the rest of the request.
     */
    public static function restoreStatement(AdapterInterface $db): string
    {
        if (self::isMariaDb($db)) {
            $value = $db->fetchOne('SELECT @@SESSION.max_statement_time');
            $milliseconds = is_numeric($value) ? (int) round((float) $value * 1000) : 0;

            return self::forCurrentServerMilliseconds($db, $milliseconds);
        }

        $value = $db->fetchOne('SELECT @@SESSION.max_execution_time');

        return self::forCurrentServerMilliseconds($db, is_numeric($value) ? (int) $value : 0);
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
        self::$isMariaDb = self::$isMariaDb ?: new \SplObjectStorage();
        if (!self::$isMariaDb->contains($db)) {
            $version = method_exists($db, 'fetchOne') ? (string) $db->fetchOne('SELECT VERSION()') : '';
            self::$isMariaDb[$db] = stripos($version, 'mariadb') !== false;
        }

        return self::$isMariaDb[$db];
    }
}
