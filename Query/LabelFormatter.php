<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\Columns\Dimension;
use Piwik\Columns\DimensionsProvider;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Metrics\Formatter;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Catalog\SiteContext;
use Piwik\Translation\Translator;

/**
 * Turns raw dimension values into display labels with the dimension's own formatter (country codes to names,
 * device type ids to names, ...), for the live explorer and for archived tables read at display time.
 */
class LabelFormatter
{
    public const PIVOT_SEPARATOR = ' | ';

    /** @var array<string, Dimension|null> */
    private $dimensions = [];
    /** @var Formatter */
    private $formatter;

    public function __construct()
    {
        $this->formatter = new Formatter();
    }

    /**
     * @param mixed $value a raw value, null (or the archived marker) for "not defined"
     */
    public function format(DimensionEntry $entry, $value, int $idSite): string
    {
        if ($value === null || $value === VisualQueryRunner::ARCHIVED_NOT_DEFINED) {
            return Piwik::translate('General_NotDefined', $entry->name);
        }

        $dimension = $this->dimensionObject($entry->id, $idSite);
        if (!$dimension) {
            return (string) $value;
        }

        $formatted = $dimension->formatValue($value, $idSite, $this->formatter);

        return $formatted === null || $formatted === '' ? (string) $value : (string) $formatted;
    }

    /**
     * The label in English, whatever the language of the user: a filter saved by a user reading English labels
     * keeps matching for a user reading them in another language.
     *
     * @param mixed $value
     */
    public function formatInEnglish(DimensionEntry $entry, $value, int $idSite): string
    {
        $translator = StaticContainer::get(Translator::class);
        $language = $translator->getCurrentLanguage();
        if ($language === 'en') {
            return $this->format($entry, $value, $idSite);
        }

        $translator->setCurrentLanguage('en');
        try {
            return $this->format($entry, $value, $idSite);
        } finally {
            $translator->setCurrentLanguage($language);
        }
    }

    /**
     * Formats the labels of an archived table and of its loaded subtables, one row dimension per level, and the
     * raw value part of its pivot columns ("<metric> | <raw value>"). The raw value stays in the row metadata.
     *
     * @param DimensionEntry[] $rows row dimensions, from the level of $table on
     */
    public function formatArchivedTable(DataTable $table, array $rows, ?DimensionEntry $column, int $idSite): void
    {
        $entry = $rows[0] ?? null;
        $pivotNames = [];

        foreach ($table->getRows() as $row) {
            $this->renamePivotColumns($row, $column, $idSite, $pivotNames);

            $label = $row->getColumn('label');
            if ($entry !== null && $label !== false && $label !== DataTable::LABEL_SUMMARY_ROW && (string) $label !== (string) DataTable::LABEL_SUMMARY_ROW) {
                $row->setMetadata('value', $label === VisualQueryRunner::ARCHIVED_NOT_DEFINED ? null : $label);
                $row->setColumn('label', $this->format($entry, $label, $idSite));
            }

            $subtable = $row->getSubtable();
            if ($subtable instanceof DataTable) {
                $this->formatArchivedTable($subtable, array_slice($rows, 1), $column, $idSite);
            }
        }

        $summary = $table->getRowFromId(DataTable::ID_SUMMARY_ROW);
        if ($summary instanceof Row) {
            $this->renamePivotColumns($summary, $column, $idSite, $pivotNames);
        }
    }

    private function renamePivotColumns(Row $row, ?DimensionEntry $column, int $idSite, array &$names): void
    {
        if ($column === null) {
            return;
        }

        $columns = [];
        foreach ($row->getColumns() as $name => $value) {
            $name = (string) $name;
            if (!isset($names[$name])) {
                $names[$name] = $name;
                $position = strpos($name, self::PIVOT_SEPARATOR);
                if ($position !== false) {
                    $raw = substr($name, $position + strlen(self::PIVOT_SEPARATOR));
                    $names[$name] = substr($name, 0, $position) . self::PIVOT_SEPARATOR . $this->format($column, $raw, $idSite);
                }
            }
            // two raw values with the same display label keep their raw column apart
            $target = isset($columns[$names[$name]]) ? $name : $names[$name];
            $columns[$target] = $value;
        }

        $row->setColumns($columns);
    }

    private function dimensionObject(string $id, int $idSite): ?Dimension
    {
        $cacheKey = $idSite . '|' . $id;
        if (!array_key_exists($cacheKey, $this->dimensions)) {
            $this->dimensions[$cacheKey] = SiteContext::run($idSite, fn () => (new DimensionsProvider())->factory($id));
        }

        return $this->dimensions[$cacheKey];
    }
}
