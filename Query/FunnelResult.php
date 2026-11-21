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
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;

/**
 * Columns of a funnel table. The stored ones add up across days, so archives of days aggregate into weeks, months
 * and ranges; the rates and the average time are computed when the table is read.
 */
final class FunnelResult
{
    // stored
    public const STARTED = 'nb_started';
    public const ENTERED = 'nb_entered';
    public const CONTINUED = 'nb_continued';
    public const SUM_TIME = 'sum_time_to_step';
    public const TIMED = 'nb_timed';

    // computed
    public const ABANDONED = 'nb_abandoned';
    public const ABANDON_RATE = 'abandon_rate';
    public const STEP_RATE = 'step_rate';
    public const OVERALL_RATE = 'overall_rate';
    public const AVG_TIME = 'avg_time_to_step';

    public const STORED = [self::STARTED, self::ENTERED, self::CONTINUED, self::SUM_TIME, self::TIMED];

    /**
     * Formats the breakdown labels and adds the computed columns to every step row and to the breakdown rows of
     * its subtable: the rates of a breakdown value compare the passes of that value across the steps.
     */
    public static function present(DataTable $table, ReportDefinition $definition, ?DimensionEntry $breakdown, int $idSite, ?LabelFormatter $labels = null): void
    {
        $steps = $definition->getSteps();
        $rows = array_values($table->getRows());

        self::decorate($rows, $steps);

        if ($breakdown === null) {
            return;
        }
        $labels = $labels ?: new LabelFormatter();

        $byKey = [];
        foreach ($rows as $index => $row) {
            $subtable = $row->getSubtable();
            if (!$subtable instanceof DataTable) {
                continue;
            }
            foreach ($subtable->getRows() as $subRow) {
                $byKey[(string) $subRow->getColumn('label')][$index] = $subRow;
            }
        }
        foreach ($byKey as $key => $stepRows) {
            $series = [];
            foreach (array_keys($steps) as $index) {
                $series[$index] = $stepRows[$index] ?? new Row([Row::COLUMNS => array_fill_keys(self::STORED, 0)]);
            }
            self::decorate($series, $steps);
            foreach ($stepRows as $subRow) {
                $subRow->setMetadata('value', $key === VisualQueryRunner::ARCHIVED_NOT_DEFINED ? null : $key);
                $subRow->setColumn('label', $key === (string) DataTable::LABEL_SUMMARY_ROW
                    ? Piwik::translate('General_Others')
                    : $labels->format($breakdown, $key, $idSite));
            }
        }
    }

    /**
     * @param Row[] $rows one per step, in step order
     */
    private static function decorate(array $rows, array $steps): void
    {
        $started = 0;
        foreach ($rows as $row) {
            $started += (int) $row->getColumn(self::STARTED);
        }

        $last = count($steps) - 1;
        $previousRequired = null;
        foreach ($rows as $index => $row) {
            $entered = (int) $row->getColumn(self::ENTERED);
            $abandoned = $index === $last ? 0 : $entered - (int) $row->getColumn(self::CONTINUED);
            $reference = $previousRequired === null ? $entered : (int) $rows[$previousRequired]->getColumn(self::ENTERED);
            $timed = (int) $row->getColumn(self::TIMED);

            $row->setColumn(self::ABANDONED, $abandoned);
            $row->setColumn(self::ABANDON_RATE, self::percent($abandoned, $entered));
            $row->setColumn(self::STEP_RATE, $index === 0 ? ($entered > 0 ? 100 : 0) : self::percent($entered, $reference));
            $row->setColumn(self::OVERALL_RATE, self::percent($entered, $started));
            $row->setColumn(self::AVG_TIME, $timed > 0 ? (int) round((int) $row->getColumn(self::SUM_TIME) / $timed) : 0);

            if (empty($steps[$index]['optional'])) {
                $previousRequired = $index;
            }
        }
    }

    private static function percent(int $part, int $whole): float
    {
        return $whole > 0 ? round($part * 100 / $whole, 1) : 0.0;
    }
}
