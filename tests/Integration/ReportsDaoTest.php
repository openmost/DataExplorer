<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DataExplorer
 * @group ReportsDaoTest
 * @group Plugins
 */
class ReportsDaoTest extends IntegrationTestCase
{
    /** @var ReportsDao */
    private $dao;

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();
        $this->dao = new ReportsDao();
    }

    private function row(string $name = 'R', int $order = 0): array
    {
        return [
            'name' => $name,
            'type' => 'visual',
            'report_type' => 'table',
            'definition' => ['rows' => ['Actions.PageUrl'], 'metrics' => ['nb_visits'], 'name' => $name],
            'category' => 'General_Actions',
            'subcategory' => 'Explorations',
            'order' => $order,
            'created_by' => 'admin',
        ];
    }

    private function ids(int $idSite): array
    {
        return array_column($this->dao->getForSite($idSite), 'idreport');
    }

    public function testInsertAndGetForSite(): void
    {
        $id = $this->dao->insert($this->row(), Placement::fromArray(['sites' => [1, 2]]));

        $this->assertSame([$id], $this->ids(1));
        $this->assertSame([$id], $this->ids(2));
        $this->assertSame([], $this->ids(3));

        $row = $this->dao->get($id);
        $this->assertSame(1, $row['revision']);
        $this->assertSame(['Actions.PageUrl'], $row['definition']['rows']);
        $this->assertSame([1, 2], $row['placement']->getSiteIds());
    }

    public function testAllWebsites(): void
    {
        $id = $this->dao->insert($this->row(), Placement::fromArray(['all' => true]));

        $this->assertSame([$id], $this->ids(3));
        $this->assertTrue($this->dao->get($id)['placement']->isAllWebsites());
    }

    public function testUpdateBumpsRevisionAndMovesPlacement(): void
    {
        $id = $this->dao->insert($this->row(), Placement::fromArray(['sites' => [1]]));
        $this->dao->update($id, $this->row('Renamed'), Placement::fromArray(['sites' => [2]]));

        $row = $this->dao->get($id);
        $this->assertSame(2, $row['revision']);
        $this->assertSame('Renamed', $row['name']);
        $this->assertSame([], $this->ids(1));
        $this->assertSame([$id], $this->ids(2));
    }

    public function testSoftDeletedIsNeverReturnedForASite(): void
    {
        $id = $this->dao->insert($this->row(), Placement::fromArray(['all' => true]));
        $this->dao->softDelete($id);

        $this->assertSame([], $this->ids(1));
    }

    public function testRemoveSiteLeavesReportStoredButUnplaced(): void
    {
        $id = $this->dao->insert($this->row(), Placement::fromArray(['sites' => [2]]));
        $this->dao->removeSite(2);

        foreach ([1, 2, 3, 4] as $idSite) {
            $this->assertSame([], $this->ids($idSite));
        }
        $row = $this->dao->get($id);
        $this->assertNotNull($row);
        $this->assertFalse($row['placement']->isAllWebsites());
        $this->assertSame([], $row['placement']->getSiteIds());
    }

    public function testDeletedUser_losesTheAuthorshipOfItsReports(): void
    {
        $mine = $this->dao->insert($this->row(), Placement::fromArray(['sites' => [1]]));
        $other = $this->dao->insert(['created_by' => 'bob'] + $this->row(), Placement::fromArray(['sites' => [1]]));

        Piwik::postEvent('UsersManager.deleteUser', ['admin']);

        $this->assertSame('', $this->dao->get($mine)['created_by'], 'a login created again must not inherit the reports');
        $this->assertSame('bob', $this->dao->get($other)['created_by']);
    }

    public function testOrdering(): void
    {
        $b = $this->dao->insert($this->row('B', 2), Placement::fromArray(['all' => true]));
        $a = $this->dao->insert($this->row('A', 1), Placement::fromArray(['all' => true]));

        $this->assertSame([$a, $b], $this->ids(1));
    }
}
