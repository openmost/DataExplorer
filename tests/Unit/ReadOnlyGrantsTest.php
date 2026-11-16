<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\DataExplorer\Sql\NotReadOnlyException;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlyConnection;

/**
 * @group DataExplorer
 * @group ReadOnlyGrantsTest
 * @group Plugins
 */
class ReadOnlyGrantsTest extends TestCase
{
    private const TO = ' TO `ro`@`localhost`';

    /**
     * @dataProvider acceptedGrants
     */
    public function test_selectOnAllowlistedTablesAndUsage_areAccepted(array $grants, string $prefix, bool $caseInsensitive = false): void
    {
        ReadOnlyConnection::checkGrants($grants, 'matomo', $prefix, $caseInsensitive);
        $this->addToAssertionCount(1);
    }

    public function test_grantedTables_areReturned(): void
    {
        $this->assertSame(['mt_site', 'mt_log_visit'], ReadOnlyConnection::checkGrants([
            'GRANT USAGE ON *.*' . self::TO,
            'GRANT SELECT ON `matomo`.`mt_site`' . self::TO,
            'GRANT SELECT ON `matomo`.`mt_log_visit`' . self::TO,
        ], 'matomo', 'mt_'));
    }

    public function test_caseSensitiveServer_namesInAnotherCaseAreOtherObjects(): void
    {
        foreach (['GRANT SELECT ON `Matomo`.`site`' . self::TO, 'GRANT SELECT ON `matomo`.`SITE`' . self::TO] as $grant) {
            try {
                ReadOnlyConnection::checkGrants([$grant], 'matomo', '');
                $this->fail('expected a refusal of ' . $grant);
            } catch (NotReadOnlyException $e) {
                $this->assertFalse($e->isTooWide());
            }
        }
    }

    public function acceptedGrants(): array
    {
        return [
            'nothing' => [[], ''],
            'usage only' => [['GRANT USAGE ON *.*' . self::TO], ''],
            'usage and select' => [[
                'GRANT USAGE ON *.*' . self::TO,
                'GRANT SELECT ON `matomo`.`log_visit`' . self::TO,
                'GRANT SELECT ON `matomo`.`site`' . self::TO,
            ], ''],
            'prefixed tables' => [['GRANT SELECT ON `matomo`.`mt_log_action`' . self::TO, 'GRANT SELECT ON `matomo`.`mt_goal`' . self::TO], 'mt_'],
            'other case on a case-insensitive server' => [['GRANT SELECT ON `Matomo`.`MT_GOAL`' . self::TO], 'mt_', true],
            'single quotes of older servers' => [["GRANT SELECT ON matomo.mt_site TO 'ro'@'%'"], 'mt_'],
            'mariadb usage with password and limits' => [[
                "GRANT USAGE ON *.* TO `ro`@`%` IDENTIFIED BY PASSWORD '*2470C0C06DEE42FD1618BB99005ADCA2EC9D1E19'",
                "GRANT USAGE ON *.* TO `ro`@`%` IDENTIFIED VIA ed25519 USING PASSWORD('secret')",
                'GRANT USAGE ON *.* TO `ro`@`%` REQUIRE SSL WITH MAX_QUERIES_PER_HOUR 10 MAX_USER_CONNECTIONS 2',
                'GRANT SELECT ON `matomo`.`segment` TO `ro`@`%`',
            ], ''],
        ];
    }

    /**
     * @dataProvider refusedGrants
     */
    public function test_anyOtherPrivilege_isRefused(string $grant, string $prefix = ''): void
    {
        $this->expectException(NotReadOnlyException::class);

        ReadOnlyConnection::checkGrants(['GRANT USAGE ON *.*' . self::TO, $grant], 'matomo', $prefix);
    }

    public function refusedGrants(): array
    {
        return [
            'insert' => ['GRANT INSERT ON `matomo`.`log_visit`' . self::TO],
            'select and insert' => ['GRANT SELECT, INSERT ON `matomo`.`log_visit`' . self::TO],
            'show view' => ['GRANT SELECT, SHOW VIEW ON `matomo`.`log_visit`' . self::TO],
            'process' => ['GRANT PROCESS ON *.*' . self::TO],
            'file' => ['GRANT FILE ON *.*' . self::TO],
            'dynamic privilege' => ['GRANT BACKUP_ADMIN ON *.*' . self::TO],
            'proxy' => ['GRANT PROXY ON ``@``' . self::TO],
            'role' => ['GRANT `reporting`@`%`' . self::TO],
            'role with admin option' => ['GRANT `reporting`@`%`' . self::TO . ' WITH ADMIN OPTION'],
            'grant option' => ['GRANT SELECT ON `matomo`.`site`' . self::TO . ' WITH GRANT OPTION'],
            'usage with grant option' => ['GRANT USAGE ON *.*' . self::TO . " IDENTIFIED BY PASSWORD '*AB' WITH GRANT OPTION"],
            'column privilege' => ['GRANT SELECT (`idsite`) ON `matomo`.`site`' . self::TO],
            'not allowlisted' => ['GRANT SELECT ON `matomo`.`user`' . self::TO],
            'near miss' => ['GRANT SELECT ON `matomo`.`site_setting`' . self::TO],
            'archive invalidations' => ['GRANT SELECT ON `matomo`.`archive_invalidations`' . self::TO],
            'numeric archive' => ['GRANT SELECT ON `matomo`.`archive_numeric_2024_01`' . self::TO],
            'blob archive' => ['GRANT SELECT ON `matomo`.`archive_blob_2024_01`' . self::TO],
            'other database' => ['GRANT SELECT ON `other`.`log_visit`' . self::TO],
            'missing prefix' => ['GRANT SELECT ON `matomo`.`log_visit`' . self::TO, 'mt_'],
            'all privileges' => ['GRANT ALL PRIVILEGES ON `matomo`.`log_visit`' . self::TO],
            'routine' => ['GRANT EXECUTE ON PROCEDURE `matomo`.`p`' . self::TO],
            'revoke line' => ['REVOKE INSERT ON `matomo`.* FROM `ro`@`localhost`'],
            'unknown line' => ['SOMETHING ELSE'],
        ];
    }

    public function test_selectOnTheWholeDatabase_isRefusedAsTooWide(): void
    {
        foreach (['GRANT SELECT ON `matomo`.*' . self::TO, 'GRANT SELECT ON *.*' . self::TO] as $grant) {
            try {
                ReadOnlyConnection::checkGrants([$grant], 'matomo', '');
                $this->fail('expected a refusal of ' . $grant);
            } catch (NotReadOnlyException $e) {
                $this->assertTrue($e->isTooWide(), $grant);
            }
        }
    }
}
