<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\Columns\DimensionsProvider;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Catalog\SiteContext;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Segment\SegmentsList;

/**
 * An exploration as the parameters of CustomReports.addCustomReport: the same dimension and metric ids, the
 * stored segment and the filters as a segment definition. What a custom report cannot hold (pivot column,
 * regular expression, a filter whose dimension has no segment) is listed in "unsupported".
 */
final class CustomReportExport
{
    private const SEGMENT_OPERATORS = [
        'equals' => '==',
        'not_equals' => '!=',
        'contains' => '=@',
        'starts_with' => '=^',
        'ends_with' => '=$',
        'gt' => '>',
        'lt' => '<',
    ];

    /** @var int */
    private $idSite = 0;

    /** @var LabelFormatter */
    private $labels;

    /**
     * @param array[] $filters VisualQueryRunner::storedFilters() of the definition
     * @return array{reportType: string, dimensionIds: string[], metricIds: string[], segmentFilter: string, unsupported: string[]}
     */
    public function convert(ReportDefinition $definition, array $filters, int $idSite): array
    {
        $this->idSite = $idSite;
        $this->labels = new LabelFormatter();

        $unsupported = [];
        if ($definition->getColumn() !== null) {
            $unsupported[] = Piwik::translate('DataExplorer_Columns');
        }

        $any = $definition->getFilterMatch() === ReportDefinition::FILTER_MATCH_ANY;
        $groups = [];
        foreach ($filters as $filter) {
            $group = $this->condition($filter, $any, $unsupported);
            if ($group !== null && $group !== '') {
                $groups[] = $group;
            }
        }

        $parts = array_values(array_filter([trim($definition->getSegment()), implode($any ? ',' : ';', $groups)], 'strlen'));

        return [
            'reportType' => $definition->getReportType(),
            'dimensionIds' => $definition->getReportType() === ReportDefinition::REPORT_TYPE_TABLE ? $definition->getRows() : [],
            'metricIds' => $definition->getMetrics(),
            'segmentFilter' => implode(';', $parts),
            'unsupported' => array_values(array_unique($unsupported)),
        ];
    }

    private function condition(array $filter, bool $any, array &$unsupported): ?string
    {
        /** @var DimensionEntry $dimension */
        $dimension = $filter['dimension'];
        $segment = $dimension->segment;
        $operator = $filter['operator'];

        if ($segment === '' || $operator === 'regex') {
            $unsupported[] = $dimension->name;

            return null;
        }

        if ($operator === VisualQueryBuilder::OPERATOR_IN) {
            if (!$filter['value']) {
                $unsupported[] = $dimension->name;

                return null;
            }

            $values = $this->segmentValues($dimension, $filter['value']);
            if ($values === null) {
                $unsupported[] = $dimension->name;

                return null;
            }

            return implode(',', array_map(fn ($value) => $segment . '==' . self::encode($value), $values));
        }

        if ($operator === VisualQueryBuilder::OPERATOR_NOT_IN) {
            // "none of these values" is an AND of differences, it cannot sit inside an OR of filters
            if ($any && count($filter['value']) > 1) {
                $unsupported[] = $dimension->name;

                return null;
            }

            $values = $this->segmentValues($dimension, $filter['value']);
            if ($values === null) {
                $unsupported[] = $dimension->name;

                return null;
            }

            return implode(';', array_map(fn ($value) => $segment . '!=' . self::encode($value), $values));
        }

        if (!isset(self::SEGMENT_OPERATORS[$operator])) {
            $unsupported[] = $dimension->name;

            return null;
        }

        return $segment . self::SEGMENT_OPERATORS[$operator] . self::encode($filter['value']);
    }

    // the logs and the filters hold sanitized text, a segment value is the plain text, URL encoded
    private static function encode($value): string
    {
        return urlencode(Common::unsanitizeInputValue((string) $value));
    }

    /**
     * A segment takes the value its sqlFilterValue turns into the stored one: the label for some coded dimensions
     * (deviceType==smartphone), the stored code for others (countryCode==fr). Null when neither works.
     *
     * @return string[]|null
     */
    private function segmentValues(DimensionEntry $dimension, array $stored): ?array
    {
        $segment = SegmentsList::get()->getSegment($dimension->segment);
        $toStored = $segment ? $segment->getSqlFilterValue() : null;
        if (!is_callable($toStored) && $segment && is_callable($segment->getSqlFilter())) {
            $sqlFilter = $segment->getSqlFilter();
            $toStored = fn ($value) => $sqlFilter($value, $segment->getSqlSegment());
        }

        // the short names some segments take (referrerType==search) are the enum values of the dimension
        $object = SiteContext::run($this->idSite, fn () => (new DimensionsProvider())->factory($dimension->id));
        $enum = $object ? (array) $object->getEnumColumnValues() : [];

        $values = [];
        foreach ($stored as $raw) {
            if (!is_callable($toStored)) {
                $values[] = (string) $raw;
                continue;
            }
            $found = null;
            $candidates = [$this->labels->format($dimension, $raw, $this->idSite), (string) $raw];
            if (isset($enum[$raw]) && is_scalar($enum[$raw])) {
                $candidates[] = (string) $enum[$raw];
            }
            foreach ($candidates as $candidate) {
                try {
                    $mapped = $toStored($candidate);
                } catch (\Throwable $e) {
                    continue;
                }
                if ((string) $mapped === (string) $raw) {
                    $found = $candidate;
                    break;
                }
            }
            if ($found === null) {
                return null;
            }
            $values[] = $found;
        }

        return $values;
    }

    // the same measure under another id in the Custom Reports catalogue
    private const METRIC_ALIASES = ['nb_actions' => 'hits'];

    /**
     * The ids the Custom Reports catalogue knows: the same id, a known alias, else the entry of the same name.
     * Ids without a match are dropped and their names added to "unsupported".
     *
     * @param array $parameters convert() output
     * @param array<string, string> $metricNames our metric id => name
     * @param array<string, string> $dimensionNames our dimension id => name
     * @param array<string, string> $availableMetrics Custom Reports metric id => name
     * @param array<string, string> $availableDimensions Custom Reports dimension id => name
     */
    public function mapToCatalogue(array $parameters, array $metricNames, array $dimensionNames, array $availableMetrics, array $availableDimensions): array
    {
        $parameters['metricIds'] = $this->mapIds($parameters['metricIds'], $metricNames, $availableMetrics, self::METRIC_ALIASES, $parameters['unsupported']);
        $parameters['dimensionIds'] = $this->mapIds($parameters['dimensionIds'], $dimensionNames, $availableDimensions, [], $parameters['unsupported']);
        $parameters['unsupported'] = array_values(array_unique($parameters['unsupported']));

        return $parameters;
    }

    private function mapIds(array $ids, array $names, array $available, array $aliases, array &$unsupported): array
    {
        $byName = [];
        foreach ($available as $id => $name) {
            $byName[mb_strtolower(trim((string) $name))] = (string) $id;
        }

        $mapped = [];
        foreach ($ids as $id) {
            if (isset($available[$id])) {
                $mapped[] = $id;
            } elseif (isset($aliases[$id], $available[$aliases[$id]])) {
                $mapped[] = $aliases[$id];
            } elseif (isset($names[$id], $byName[mb_strtolower(trim($names[$id]))])) {
                $mapped[] = $byName[mb_strtolower(trim($names[$id]))];
            } else {
                $unsupported[] = $names[$id] ?? $id;
            }
        }

        return array_values(array_unique($mapped));
    }
}
