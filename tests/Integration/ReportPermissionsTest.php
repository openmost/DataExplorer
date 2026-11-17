<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\NoAccessException;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DataExplorer
 * @group ReportPermissionsTest
 * @group Plugins
 */
class ReportPermissionsTest extends IntegrationTestCase
{
    /** @var ReportPermissions */
    private $permissions;

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        for ($i = 0; $i < 3; $i++) {
            Fixture::createWebsite('2024-01-01 00:00:00');
        }
        $this->permissions = new ReportPermissions();
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    private function as(array $view = [], array $write = [], array $admin = [], string $login = 'alice', bool $super = false): void
    {
        FakeAccess::clearAccess($super, $admin, $view, $login, $write);
    }

    private function report(array $sites, string $type = 'visual', string $author = 'alice', bool $deleted = false, bool $all = false): array
    {
        return [
            'type' => $type,
            'created_by' => $author,
            'deleted' => $deleted ? 1 : 0,
            'placement' => $all ? Placement::fromArray(['all' => true]) : ($sites ? Placement::fromArray(['sites' => $sites]) : Placement::none()),
        ];
    }

    private function assertDenied(callable $call): void
    {
        try {
            $call();
        } catch (NoAccessException $e) {
            $this->assertTrue(true);
            return;
        }
        $this->fail('NoAccessException expected');
    }

    public function test_explore_viewAllowed_otherSiteDenied(): void
    {
        $this->as([1]);
        $this->permissions->checkCanExplore(1);
        $this->assertDenied(function () { $this->permissions->checkCanExplore(2); });
    }

    public function test_explore_anonymousWithViewAccess_isDenied(): void
    {
        $this->as([1], [], [], 'anonymous');
        $this->assertDenied(function () { $this->permissions->checkCanExplore(1); });
    }

    public function test_rawSql_superUserOnly(): void
    {
        $this->as([1], [1], [1]);
        $this->assertDenied(function () { $this->permissions->checkCanRunRawSql(); });
        $this->as([], [], [], 'root', true);
        $this->permissions->checkCanRunRawSql();
        $this->assertTrue(true);
    }

    public function test_save_viewCannotSave_writeCanOnItsSites(): void
    {
        $this->as([1]);
        $this->assertDenied(function () { $this->permissions->checkCanSave(Placement::fromArray(['sites' => [1]]), 'visual'); });

        $this->as([1, 2], [1]);
        $this->permissions->checkCanSave(Placement::fromArray(['sites' => [1]]), 'visual');
        $this->assertDenied(function () { $this->permissions->checkCanSave(Placement::fromArray(['sites' => [1, 2]]), 'visual'); });
    }

    public function test_save_sqlAndAllWebsitesNeedSuperUser(): void
    {
        $this->as([], [1], [1]);
        $this->assertDenied(function () { $this->permissions->checkCanSave(Placement::fromArray(['sites' => [1]]), 'sql'); });
        $this->assertDenied(function () { $this->permissions->checkCanSave(Placement::fromArray(['all' => true]), 'visual'); });

        $this->as([], [], [], 'root', true);
        $this->permissions->checkCanSave(Placement::fromArray(['all' => true]), 'sql');
        $this->assertTrue(true);
    }

    public function test_save_unplacedRefused(): void
    {
        $this->as([], [1], [1]);
        $this->assertDenied(function () { $this->permissions->checkCanSave(Placement::none(), 'visual'); });
    }

    public function test_edit_authorWithWriteOnEverySite(): void
    {
        $this->as([1, 2], [1, 2]);
        $this->permissions->checkCanEdit($this->report([1, 2]));

        $this->as([1, 2], [1]);
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([1, 2])); });
    }

    public function test_edit_notAuthorWriteOnlyDenied_adminAllowed(): void
    {
        $this->as([1], [1], [], 'bob');
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([1])); });

        $this->as([1], [], [1], 'bob');
        $this->permissions->checkCanEdit($this->report([1]));

        $this->as([1, 2], [], [1], 'bob');
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([1, 2])); });
        $this->assertTrue(true);
    }

    public function test_edit_allWebsitesAndUnplacedSuperUserOnly(): void
    {
        $this->as([1], [1], [1]);
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([], 'visual', 'alice', false, true)); });
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([])); });

        $this->as([], [], [], 'root', true);
        $this->permissions->checkCanEdit($this->report([], 'visual', 'alice', false, true));
        $this->permissions->checkCanEdit($this->report([]));
        $this->assertTrue(true);
    }

    public function test_view_requiresPlacementAndViewAccessOnTheRequestedSite(): void
    {
        $this->as([1]);
        $report = $this->report([2]);
        $this->assertDenied(function () use ($report) { $this->permissions->checkCanView($report, 1); });
        $this->assertDenied(function () use ($report) { $this->permissions->checkCanView($report, 2); });

        $this->as([1, 2]);
        $this->permissions->checkCanView($report, 2);
        $this->assertDenied(function () use ($report) { $this->permissions->checkCanView($report, 1); });
        $this->assertTrue(true);
    }

    public function test_view_allWebsitesNeedsViewOnRequestedSite(): void
    {
        $report = $this->report([], 'visual', 'alice', false, true);
        $this->as([3]);
        $this->permissions->checkCanView($report, 3);
        $this->assertDenied(function () use ($report) { $this->permissions->checkCanView($report, 1); });
    }

    public function test_view_deletedReportRefused(): void
    {
        $this->as([], [], [], 'root', true);
        $this->assertDenied(function () { $this->permissions->checkCanView($this->report([1], 'visual', 'alice', true), 1); });
    }

    public function test_edit_deletedReportRefused_evenForSuperUser(): void
    {
        $this->as([], [], [], 'root', true);
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([1], 'visual', 'alice', true)); });

        $this->as([1], [1], [1]);
        $this->assertDenied(function () { $this->permissions->checkCanEdit($this->report([1], 'visual', 'alice', true)); });
    }
}
