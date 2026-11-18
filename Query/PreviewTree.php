<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Plugins\DataExplorer\Query\SavedReportReader;

/**
 * The result of a live exploration as a plain array, so it can be cached between the requests of the native
 * data table of the canvas (sort, limit, page, opening a row), and read back as a DataTable.
 *
 * The rows of the hierarchical view are opened one level at a time by subtable id. The ids of a live result
 * only live for one request, so the rows that have children are numbered in tree order instead (1, 2, ...):
 * the same cached result always gives the same numbers.
 */
class PreviewTree
{
    /**
     * @return array{metadata: array, rows: array[]}
     */
    public static function export(DataTable $table): array
    {
        $metadata = [];
        foreach ($table->getAllTableMetadata() as $name => $value) {
            if (is_scalar($value) || is_array($value)) {
                $metadata[$name] = $value;
            }
        }

        $rows = [];
        foreach ($table->getRows() as $row) {
            $subtable = $row->getSubtable();
            $rows[] = [
                'columns' => $row->getColumns(),
                'metadata' => $row->getMetadata(),
                'children' => $subtable instanceof DataTable ? self::export($subtable) : null,
            ];
        }

        return ['metadata' => $metadata, 'rows' => $rows];
    }

    /**
     * The whole result with its subtables in memory (flat view, export).
     */
    public static function toTable(array $tree): DataTable
    {
        $table = self::emptyTable($tree);
        foreach ($tree['rows'] as $node) {
            $row = self::row($node);
            if (is_array($node['children'])) {
                $row->setSubtable(self::toTable($node['children']));
            }
            $table->addRow($row);
        }

        return $table;
    }

    /**
     * One level of the hierarchical view: the first level, or the children of the row numbered $idSubtable.
     * Each row with children carries its number as subtable id, its children are not loaded.
     *
     * @return DataTable|null null when no row has this number
     */
    public static function level(array $tree, int $idSubtable = 0): ?DataTable
    {
        $next = 1;
        $numbered = self::number($tree, $next);

        [$node, $depth] = $idSubtable > 0 ? self::find($numbered, $idSubtable, 1) : [$numbered, 0];
        if ($node === null) {
            return null;
        }

        $table = self::emptyTable($node);
        $table->setMetadata(SavedReportReader::DEPTH_METADATA, $depth);
        foreach ($node['rows'] as $child) {
            $row = self::row($child);
            if (isset($child['id'])) {
                $row->setNonLoadedSubtableId($child['id']);
            }
            $table->addRow($row);
        }

        return $table;
    }

    /**
     * Numbers the rows that have children, parents before their children.
     */
    private static function number(array $tree, int &$next): array
    {
        foreach ($tree['rows'] as $index => $node) {
            if (is_array($node['children'])) {
                $tree['rows'][$index]['id'] = $next++;
            }
        }
        foreach ($tree['rows'] as $index => $node) {
            if (is_array($node['children'])) {
                $tree['rows'][$index]['children'] = self::number($node['children'], $next);
            }
        }

        return $tree;
    }

    /**
     * @return array{0: array|null, 1: int} the children of the row numbered $id and their level
     */
    private static function find(array $tree, int $id, int $depth): array
    {
        foreach ($tree['rows'] as $node) {
            if (!is_array($node['children'])) {
                continue;
            }
            if (($node['id'] ?? 0) === $id) {
                return [$node['children'], $depth];
            }
            $found = self::find($node['children'], $id, $depth + 1);
            if ($found[0] !== null) {
                return $found;
            }
        }

        return [null, $depth];
    }

    private static function emptyTable(array $tree): DataTable
    {
        $table = new DataTable();
        foreach ($tree['metadata'] as $name => $value) {
            $table->setMetadata($name, $value);
        }

        return $table;
    }

    private static function row(array $node): Row
    {
        return new Row([
            Row::COLUMNS => $node['columns'],
            Row::METADATA => $node['metadata'],
        ]);
    }
}
