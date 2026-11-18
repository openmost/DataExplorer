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
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Plugins\DataExplorer\Query\PreviewTree;

/**
 * @group DataExplorer
 * @group PreviewTreeTest
 * @group Plugins
 */
class PreviewTreeTest extends TestCase
{
    public function test_toTable_rebuildsTheTreeWithItsSubtablesInMemory(): void
    {
        $table = PreviewTree::toTable(PreviewTree::export($this->countriesByDevice()));

        $this->assertSame('labelColumn', $table->getMetadata('label_column'));
        $this->assertSame(['France', 'Germany'], $table->getColumn('label'));
        $france = $table->getRowFromLabel('France')->getSubtable();
        $this->assertInstanceOf(DataTable::class, $france);
        $this->assertSame(['Desktop', 'Smartphone'], $france->getColumn('label'));
        $this->assertSame([3, 1], $france->getColumn('nb_visits'));
    }

    public function test_level_numbersTheRowsWithChildren_parentsFirst(): void
    {
        $tree = PreviewTree::export($this->countriesByDevice());

        $first = PreviewTree::level($tree);

        $this->assertSame(1, $first->getRowFromLabel('France')->getIdSubDataTable());
        $this->assertSame(2, $first->getRowFromLabel('Germany')->getIdSubDataTable());
        $this->assertFalse($first->getRowFromLabel('France')->getSubtable());
    }

    public function test_level_returnsTheChildrenOfANumberedRow(): void
    {
        $tree = PreviewTree::export($this->countriesByDevice());

        $germany = PreviewTree::level($tree, 2);

        $this->assertSame(['Smartphone'], $germany->getColumn('label'));
        $this->assertNull(PreviewTree::level($tree, 9));
    }

    public function test_level_givesTheSameNumbersOnEveryRequest(): void
    {
        $tree = PreviewTree::export($this->countriesByDevice());

        $this->assertSame(
            PreviewTree::level($tree)->getRowFromLabel('Germany')->getIdSubDataTable(),
            PreviewTree::level($tree)->getRowFromLabel('Germany')->getIdSubDataTable()
        );
    }

    private function countriesByDevice(): DataTable
    {
        $table = new DataTable();
        $table->setMetadata('label_column', 'labelColumn');

        $france = new Row([Row::COLUMNS => ['label' => 'France', 'nb_visits' => 4]]);
        $france->setSubtable($this->devices(['Desktop' => 3, 'Smartphone' => 1]));
        $germany = new Row([Row::COLUMNS => ['label' => 'Germany', 'nb_visits' => 4]]);
        $germany->setSubtable($this->devices(['Smartphone' => 4]));
        $table->addRow($france);
        $table->addRow($germany);

        return $table;
    }

    private function devices(array $visits): DataTable
    {
        $table = new DataTable();
        foreach ($visits as $label => $count) {
            $table->addRow(new Row([Row::COLUMNS => ['label' => $label, 'nb_visits' => $count]]));
        }

        return $table;
    }
}
