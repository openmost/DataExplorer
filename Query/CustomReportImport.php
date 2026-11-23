<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Segment\SegmentsList;

/**
 * A custom report configuration as a visual definition, the reverse of CustomReportExport. Its segment becomes
 * filter groups: a segment ORs with "," inside the parts it ANDs with ";", which are exactly the groups. A part
 * that cannot be a group (a segment without a dimension of the catalogue, a coded value, a test for an empty
 * value) stays in the segment of the definition, which ANDs with the filters like the part did.
 */
final class CustomReportImport
{
    private const OPERATORS = [
        '==' => 'equals',
        '!=' => 'not_equals',
        '>=' => 'gte',
        '<=' => 'lte',
        '=@' => 'contains',
        '!@' => 'not_contains',
        '=^' => 'starts_with',
        '!^' => 'not_starts_with',
        '=$' => 'ends_with',
        '!$' => 'not_ends_with',
        '>' => 'gt',
        '<' => 'lt',
    ];

    private const METRIC_ALIASES = ['hits' => 'nb_actions'];

    /** @var callable(string): bool */
    private $isCoded;

    /** @var callable(string): bool */
    private $isPluginActive;

    /** @var array<string, string[]> plugin => names of its ids, for the plugins that are not active */
    private $inactive = [];

    /** @var array{id: string, name: string, kind: string, reason: string}[] what the explorer shows struck through */
    private $entries = [];

    /** @var string dimension or metric, the kind of the ids mapIds() reads */
    private $kind = '';

    /**
     * @param callable(string): bool|null $isCoded whether a segment turns its value into a stored code
     * @param callable(string): bool|null $isPluginActive whether a plugin is activated
     */
    public function __construct(?callable $isCoded = null, ?callable $isPluginActive = null)
    {
        $this->isCoded = $isCoded ?: [self::class, 'segmentStoresCode'];
        $this->isPluginActive = $isPluginActive
            ?: fn (string $plugin) => \Piwik\Plugin\Manager::getInstance()->isPluginActivated($plugin);
    }

    /**
     * @param array $report CustomReports.getConfiguredReport
     * @param DimensionEntry[] $dimensions the explorer catalogue, keyed by id
     * @param array<string, string> $metrics id => name of the explorer metrics
     * @param array<string, string> $reportDimensions id => name of the custom reports dimensions
     * @param array<string, string> $reportMetrics id => name of the custom reports metrics
     * @return array{definition: array, unsupported: string[], unsupportedEntries: array[]}
     */
    public function convert(array $report, array $dimensions, array $metrics, array $reportDimensions, array $reportMetrics): array
    {
        $unsupported = [];
        $this->inactive = [];
        $this->entries = [];
        $dimensionNames = [];
        foreach ($dimensions as $id => $entry) {
            $dimensionNames[$id] = $entry->name;
        }

        $reportType = ($report['report_type'] ?? '') === ReportDefinition::REPORT_TYPE_EVOLUTION
            ? ReportDefinition::REPORT_TYPE_EVOLUTION
            : ReportDefinition::REPORT_TYPE_TABLE;

        $this->kind = 'dimension';
        $rows = $reportType === ReportDefinition::REPORT_TYPE_TABLE
            ? $this->mapIds((array) ($report['dimensions'] ?? []), $reportDimensions, $dimensionNames, [], $unsupported)
            : [];
        $this->kind = 'metric';
        $metricIds = $this->mapIds((array) ($report['metrics'] ?? []), $reportMetrics, $metrics, self::METRIC_ALIASES, $unsupported);

        [$filters, $segment] = $this->filters((string) ($report['segment_filter'] ?? ''), $dimensions);

        foreach ($this->inactive as $plugin => $names) {
            $unsupported[] = Piwik::translate('DataExplorer_ImportPluginInactive', [implode(', ', array_unique($names)), $plugin]);
        }

        return [
            'definition' => [
                'technique' => ReportDefinition::TECHNIQUE_FREEFORM,
                'report_type' => $reportType,
                'rows' => $rows,
                'column' => null,
                'metrics' => $metricIds,
                'filters' => $filters,
                'segment' => $segment,
            ],
            'unsupported' => array_values(array_unique($unsupported)),
            'unsupportedEntries' => $this->entries,
        ];
    }

    /**
     * @param DimensionEntry[] $dimensions
     * @return array{0: array[], 1: string} the filters and the segment parts kept as they are
     */
    private function filters(string $segment, array $dimensions): array
    {
        // like core Segment: a definition stored urlencoded as a whole (values then read %252F) is decoded once
        // before its values, unless decoding it loses conditions
        $decoded = urldecode($segment);
        if ($decoded !== $segment && self::conditionCount($decoded) >= self::conditionCount($segment)) {
            $segment = $decoded;
        }

        $bySegment = [];
        foreach ($dimensions as $entry) {
            if ($entry->segment !== '' && !isset($bySegment[$entry->segment])) {
                $bySegment[$entry->segment] = $entry;
            }
        }

        $filters = [];
        $kept = [];
        $group = 0;
        foreach (explode(';', $segment) as $part) {
            if (trim($part) === '') {
                continue;
            }
            $conditions = [];
            foreach (explode(',', $part) as $expression) {
                $condition = $this->condition($expression, $bySegment);
                if ($condition === null) {
                    $conditions = null;
                    break;
                }
                $conditions[] = $condition + ['group' => $group];
            }
            if ($conditions === null) {
                $kept[] = $part;
                continue;
            }
            array_push($filters, ...$conditions);
            $group++;
        }

        return [$filters, implode(';', $kept)];
    }

    private static function conditionCount(string $segment): int
    {
        return count(array_filter(preg_split('/[;,]/', $segment), fn (string $part) => trim($part) !== ''));
    }

    /**
     * @param DimensionEntry[] $bySegment
     */
    private function condition(string $expression, array $bySegment): ?array
    {
        $pattern = '/^([A-Za-z0-9_.]+)(' . implode('|', array_map(fn ($op) => preg_quote($op, '/'), array_keys(self::OPERATORS))) . ')(.*)$/s';
        if (!preg_match($pattern, $expression, $match) || !isset($bySegment[$match[1]])) {
            return null;
        }

        $value = urldecode($match[3]);
        // an empty value tests for a missing one, and a segment that turns its value into a code compares a value
        // the filter would not find among the labels
        if ($value === '' || ($this->isCoded)($match[1])) {
            return null;
        }

        return [
            'dimension' => $bySegment[$match[1]]->id,
            'operator' => self::OPERATORS[$match[2]],
            'value' => $value,
        ];
    }

    /**
     * An id neither catalogue knows, most often a dimension of a plugin that is not active here
     * (Plugin.ColumnName): its words, listed once per plugin to activate.
     */
    private function addUnknown(string $id, array &$unsupported): void
    {
        if (!preg_match('/^([A-Za-z0-9]+)\.([A-Za-z0-9_]+)$/', $id, $match)) {
            $unsupported[] = $id;
            $this->addEntry($id, $id, Piwik::translate('DataExplorer_ImportReasonUnknown'));

            return;
        }

        $name = trim((string) preg_replace('/(?<=[a-z0-9])(?=[A-Z])|_/', ' ', $match[2]));
        if (($this->isPluginActive)($match[1])) {
            $unsupported[] = $name;
            $this->addEntry($id, $name, Piwik::translate('DataExplorer_ImportReasonUnknown'));
        } else {
            $this->inactive[$match[1]][] = $name;
            $this->addEntry($id, $name, Piwik::translate('DataExplorer_ImportReasonPluginInactive', $match[1]));
        }
    }

    private function addEntry(string $id, string $name, string $reason): void
    {
        $this->entries[] = ['id' => $id, 'name' => $name, 'kind' => $this->kind, 'reason' => $reason];
    }

    public static function segmentStoresCode(string $segmentName): bool
    {
        $segment = SegmentsList::get()->getSegment($segmentName);

        // an action segment's sqlFilter only looks the action up by its text, which a filter compares too
        return $segment !== null && is_callable($segment->getSqlFilterValue());
    }

    /**
     * The explorer ids of custom report ids: the same id, a known alias, else the entry of the same name.
     *
     * @param array<string, string> $names id => name in the custom reports catalogue
     * @param array<string, string> $available id => name in the explorer catalogue
     * @return string[]
     */
    private function mapIds(array $ids, array $names, array $available, array $aliases, array &$unsupported): array
    {
        $byName = [];
        foreach ($available as $id => $name) {
            $byName[mb_strtolower(trim((string) $name))] = (string) $id;
        }

        $mapped = [];
        foreach ($ids as $id) {
            $id = (string) $id;
            $name = isset($names[$id]) ? mb_strtolower(trim($names[$id])) : null;
            if (isset($available[$id])) {
                $mapped[] = $id;
            } elseif (isset($aliases[$id], $available[$aliases[$id]])) {
                $mapped[] = $aliases[$id];
            } elseif ($name !== null && isset($byName[$name])) {
                $mapped[] = $byName[$name];
            } elseif (isset($names[$id])) {
                $unsupported[] = $names[$id];
                $this->addEntry($id, $names[$id], Piwik::translate('DataExplorer_ImportReasonUnknown'));
            } else {
                $this->addUnknown($id, $unsupported);
            }
        }

        return array_values(array_unique($mapped));
    }
}
