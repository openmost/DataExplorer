<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Visualizations;

use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Plugin\ViewDataTable;
use Piwik\Plugin\Visualization;
use Piwik\Plugins\DataExplorer\Query\FunnelResult;

/**
 * A saved or explored funnel: one bar per step with its abandonment, then the table of the steps, each with its
 * breakdown values. Only a funnel report opens it, it is never offered in the footer of another report.
 */
class Funnel extends Visualization
{
    public const ID = 'dataExplorerFunnel';
    public const TEMPLATE_FILE = '@DataExplorer/_dataTableViz_funnel.twig';
    public const FOOTER_ICON = 'icon-funnel';
    public const FOOTER_ICON_TITLE = 'DataExplorer_FunnelTab';

    public static function canDisplayViewDataTable(ViewDataTable $view)
    {
        return false;
    }

    public function beforeLoadDataTable()
    {
        $this->requestConfig->request_parameters_to_modify['expanded'] = 1;
        $this->requestConfig->filter_limit = -1;
        $this->requestConfig->filter_sort_column = '';
        $this->config->show_pagination_control = false;
        $this->config->show_offset_information = false;
        $this->config->show_limit_control = false;
        $this->config->show_search = false;
        $this->config->show_exclude_low_population = false;
        $this->config->show_table_all_columns = false;
        $this->config->show_export_as_image_icon = false;
    }

    public function afterAllFiltersAreApplied()
    {
        $table = $this->dataTable;
        if ($table instanceof DataTable\Map) {
            $tables = $table->getDataTables();
            $table = $tables ? end($tables) : null;
        }

        $steps = [];
        if ($table instanceof DataTable) {
            foreach ($table->getRows() as $row) {
                $step = self::values($row);
                $step['breakdown'] = [];
                $subtable = $row->getSubtable();
                if ($subtable instanceof DataTable) {
                    foreach ($subtable->getRows() as $subRow) {
                        $step['breakdown'][] = self::values($subRow);
                    }
                }
                $steps[] = $step;
            }
        }

        $this->assignTemplateVar('funnelSteps', $steps);
    }

    private static function values(Row $row): array
    {
        $values = ['label' => (string) $row->getColumn('label')];
        foreach ([FunnelResult::STARTED, FunnelResult::ENTERED, FunnelResult::ABANDONED, FunnelResult::ABANDON_RATE, FunnelResult::STEP_RATE, FunnelResult::OVERALL_RATE, FunnelResult::AVG_TIME] as $column) {
            $value = $row->getColumn($column);
            $values[$column] = $value === false ? 0 : $value + 0;
        }

        return $values;
    }
}
