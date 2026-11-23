<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Model;

use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Configuration;

/**
 * Validated definition of a saved exploration (visual) or raw SQL report.
 */
class ReportDefinition
{
    public const MAX_METRICS = 10;
    public const MAX_NAME_LENGTH = 255;
    public const MAX_DESCRIPTION_LENGTH = 1000;

    public const TYPE_VISUAL = 'visual';
    public const TYPE_SQL = 'sql';
    public const TYPE_FUNNEL = 'funnel';
    public const TECHNIQUE_FREEFORM = 'freeform';
    public const TECHNIQUE_FUNNEL = 'funnel';

    public const REPORT_TYPE_TABLE = 'table';
    public const REPORT_TYPE_EVOLUTION = 'evolution';
    public const REPORT_TYPE_KPICARD = 'kpicard';
    public const REPORT_TYPE_FUNNEL = 'funnel';

    public const MIN_FUNNEL_STEPS = 2;
    public const MAX_FUNNEL_STEPS = 10;
    public const MAX_STEP_NAME_LENGTH = 100;
    public const DEFAULT_BREAKDOWN_ROWS = 5;
    public const MAX_BREAKDOWN_ROWS = 20;
    public const FOLLOW_INDIRECT = 'indirect';
    public const FOLLOW_DIRECT = 'direct';
    public const TIME_UNITS = ['seconds' => 1, 'minutes' => 60, 'hours' => 3600, 'days' => 86400];

    // how a table report opens, its footer still offers the other views
    public const CHARTS = ['', 'bar', 'pie', 'treemap'];

    // definitions saved before filter groups: every filter applies (AND) or any of them is enough (OR)
    public const FILTER_MATCH_ALL = 'all';
    public const FILTER_MATCH_ANY = 'any';

    public const TABLE_VIEW_FLAT = 'flat';
    public const TABLE_VIEW_HIERARCHICAL = 'hierarchical';

    /** @var string */
    private $name;
    /** @var string */
    private $description = '';
    /** @var bool false: archiving skips the report for segments, only the whole traffic is processed */
    private $processSegments = true;
    /** @var string */
    private $type;
    /** @var string */
    private $technique;
    /** @var string */
    private $reportType;
    /** @var string[] */
    private $rows;
    /** @var string|null */
    private $column;
    /** @var string[] */
    private $metrics;
    /** @var array */
    private $filters;
    /** @var string */
    private $segment;
    /** @var string */
    private $sql;
    /** @var string */
    private $chart = '';
    /** @var string */
    private $tableView = self::TABLE_VIEW_FLAT;
    /** @var array */
    private $steps = [];
    /** @var bool */
    private $open = false;
    /** @var string|null */
    private $breakdown = null;
    /** @var int */
    private $breakdownRows = self::DEFAULT_BREAKDOWN_ROWS;

    private function __construct()
    {
    }

    /**
     * @throws \InvalidArgumentException with a translated message
     */
    public static function fromArray(array $raw): ReportDefinition
    {
        $def = new ReportDefinition();

        $name = isset($raw['name']) && is_string($raw['name']) ? trim($raw['name']) : '';
        if ($name === '' || mb_strlen($name) > self::MAX_NAME_LENGTH) {
            throw self::error('DataExplorer_ErrorNameInvalid', self::MAX_NAME_LENGTH);
        }
        $def->name = $name;

        $def->description = trim(self::string($raw, 'description', ''));
        $def->processSegments = !array_key_exists('process_segments', $raw) || !empty($raw['process_segments']);
        if (mb_strlen($def->description) > self::MAX_DESCRIPTION_LENGTH) {
            throw self::error('DataExplorer_ErrorDescriptionTooLong', self::MAX_DESCRIPTION_LENGTH);
        }

        $def->type = self::string($raw, 'type', self::TYPE_VISUAL);
        if (!in_array($def->type, [self::TYPE_VISUAL, self::TYPE_SQL, self::TYPE_FUNNEL], true)) {
            throw self::error('DataExplorer_ErrorTypeInvalid');
        }

        $technique = $def->type === self::TYPE_FUNNEL ? self::TECHNIQUE_FUNNEL : self::TECHNIQUE_FREEFORM;
        $def->technique = self::string($raw, 'technique', $technique);
        if ($def->technique !== $technique) {
            throw self::error('DataExplorer_ErrorTechniqueInvalid');
        }

        if ($def->type === self::TYPE_FUNNEL) {
            $def->reportType = self::REPORT_TYPE_FUNNEL;
            $def->rows = [];
            $def->column = null;
            $def->metrics = [];
            $def->filters = [];
            $def->sql = '';
            $def->segment = self::string($raw, 'segment', '');
            $def->steps = self::steps($raw['steps'] ?? null);
            $def->open = !empty($raw['open']);
            $breakdown = $raw['breakdown'] ?? null;
            $def->breakdown = is_string($breakdown) && $breakdown !== '' ? $breakdown : null;
            $rows = $raw['breakdown_rows'] ?? self::DEFAULT_BREAKDOWN_ROWS;
            $def->breakdownRows = is_numeric($rows)
                ? max(1, min(self::MAX_BREAKDOWN_ROWS, (int) $rows))
                : self::DEFAULT_BREAKDOWN_ROWS;

            return $def;
        }

        $def->reportType = self::string($raw, 'report_type', self::REPORT_TYPE_TABLE);
        if (!in_array($def->reportType, [self::REPORT_TYPE_TABLE, self::REPORT_TYPE_EVOLUTION, self::REPORT_TYPE_KPICARD], true)) {
            throw self::error('DataExplorer_ErrorReportTypeInvalid');
        }

        $def->rows = self::stringList($raw, 'rows');
        $def->metrics = self::stringList($raw, 'metrics');
        $column = $raw['column'] ?? null;
        $def->column = is_string($column) && $column !== '' ? $column : null;
        $def->filters = self::filters($raw['filters'] ?? [], self::string($raw, 'filter_match', self::FILTER_MATCH_ALL));
        $def->segment = self::string($raw, 'segment', '');
        $def->sql = trim(self::string($raw, 'sql', ''));
        $def->tableView = self::string($raw, 'table_view', self::TABLE_VIEW_FLAT) === self::TABLE_VIEW_HIERARCHICAL
            ? self::TABLE_VIEW_HIERARCHICAL
            : self::TABLE_VIEW_FLAT;
        $chart = self::string($raw, 'chart', '');
        $def->chart = $def->reportType === self::REPORT_TYPE_TABLE && in_array($chart, self::CHARTS, true) ? $chart : '';

        if ($def->type === self::TYPE_SQL) {
            if ($def->sql === '') {
                throw self::error('DataExplorer_ErrorSqlEmpty');
            }
            // a raw SQL report is its query only, nothing unvalidated rides along in storage
            $def->rows = [];
            $def->column = null;
            $def->metrics = [];
            $def->filters = [];
            $def->segment = '';

            return $def;
        }

        // the SQL text of a visual report would be stored unguarded, write users can save visual reports
        $def->sql = '';
        $def->validateVisual();

        return $def;
    }

    /**
     * The definition of a stored report: the columns of the row win over the same keys of its JSON definition.
     *
     * @param array $report a hydrated row of ReportsDao
     * @throws \InvalidArgumentException with a translated message
     */
    public static function fromReport(array $report): ReportDefinition
    {
        $raw = is_array($report['definition'] ?? null) ? $report['definition'] : [];

        return self::fromArray(array_merge($raw, [
            'name' => (string) ($report['name'] ?? ''),
            'type' => (string) ($report['type'] ?? ''),
            'report_type' => (string) ($report['report_type'] ?? ''),
        ]));
    }

    private function validateVisual(): void
    {
        $maxRows = Configuration::getMaxDimensions();
        if (count($this->rows) > $maxRows) {
            throw self::error('DataExplorer_ErrorTooManyRows', $maxRows);
        }
        if (count($this->metrics) > self::MAX_METRICS) {
            throw self::error('DataExplorer_ErrorTooManyMetrics', self::MAX_METRICS);
        }
        if (!$this->metrics) {
            throw self::error('DataExplorer_ErrorMetricRequired');
        }

        if ($this->reportType === self::REPORT_TYPE_TABLE) {
            if (!$this->rows) {
                throw self::error('DataExplorer_ErrorRowRequired');
            }

            return;
        }

        if ($this->column !== null) {
            throw self::error('DataExplorer_ErrorColumnOnlyForTable');
        }
        if ($this->rows) {
            throw self::error('DataExplorer_ErrorRowsOnlyForTable');
        }
        if ($this->reportType === self::REPORT_TYPE_KPICARD && count($this->metrics) !== 1) {
            throw self::error('DataExplorer_ErrorKpiCardOneMetric');
        }
    }

    /**
     * The steps of a funnel: a name, conditions grouped like filters, and how the step follows the previous one.
     * The first step is never optional and follows nothing, the last one is never optional.
     *
     * @return array<int, array{name: string, conditions: array, optional: bool, follow: string, within: ?array{value: int, unit: string}}>
     */
    private static function steps($steps): array
    {
        if (!is_array($steps) || count($steps) < self::MIN_FUNNEL_STEPS || count($steps) > self::MAX_FUNNEL_STEPS) {
            throw self::error('DataExplorer_ErrorFunnelStepsCount', self::MIN_FUNNEL_STEPS, self::MAX_FUNNEL_STEPS);
        }

        $result = [];
        foreach (array_values($steps) as $index => $step) {
            if (!is_array($step)) {
                throw self::error('DataExplorer_ErrorFunnelStepInvalid', $index + 1);
            }
            $name = isset($step['name']) && is_string($step['name']) ? trim($step['name']) : '';
            if ($name === '' || mb_strlen($name) > self::MAX_STEP_NAME_LENGTH) {
                throw self::error('DataExplorer_ErrorFunnelStepName', $index + 1, self::MAX_STEP_NAME_LENGTH);
            }
            $conditions = self::filters($step['conditions'] ?? null, self::FILTER_MATCH_ALL);
            if (!$conditions) {
                throw self::error('DataExplorer_ErrorFunnelStepConditions', $name);
            }

            $within = null;
            if ($index > 0 && is_array($step['within'] ?? null)) {
                $value = $step['within']['value'] ?? null;
                $unit = $step['within']['unit'] ?? null;
                if (!is_numeric($value) || (int) $value < 1 || !is_string($unit) || !isset(self::TIME_UNITS[$unit])) {
                    throw self::error('DataExplorer_ErrorFunnelStepWithin', $name);
                }
                $within = ['value' => (int) $value, 'unit' => $unit];
            }

            $result[] = [
                'name' => $name,
                'conditions' => $conditions,
                'optional' => $index > 0 && !empty($step['optional']),
                'follow' => $index > 0 && ($step['follow'] ?? '') === self::FOLLOW_DIRECT ? self::FOLLOW_DIRECT : self::FOLLOW_INDIRECT,
                'within' => $within,
            ];
        }
        if ($result[count($result) - 1]['optional']) {
            throw self::error('DataExplorer_ErrorFunnelLastStepOptional');
        }

        return $result;
    }

    private static function error(string $key, ...$params): \InvalidArgumentException
    {
        return new \InvalidArgumentException(Piwik::translate($key, $params));
    }

    private static function string(array $raw, string $key, string $default): string
    {
        $value = $raw[$key] ?? $default;

        return is_string($value) ? $value : $default;
    }

    /**
     * @return string[]
     */
    private static function stringList(array $raw, string $key): array
    {
        $list = $raw[$key] ?? [];
        if (!is_array($list)) {
            throw self::error('DataExplorer_ErrorListInvalid', $key);
        }

        $result = [];
        foreach ($list as $item) {
            if (!is_string($item) || $item === '') {
                throw self::error('DataExplorer_ErrorListInvalid', $key);
            }
            $result[] = $item;
        }

        return $result;
    }

    /**
     * The filters ordered by group, groups numbered from 0 in their order of appearance. A definition saved before
     * groups has none: "any" puts every filter in one group, "all" each filter in its own.
     */
    private static function filters($filters, string $legacyMatch): array
    {
        if (!is_array($filters)) {
            throw self::error('DataExplorer_ErrorFiltersInvalid');
        }

        $result = [];
        $legacy = true;
        foreach (array_values($filters) as $index => $filter) {
            if (
                !is_array($filter)
                || !isset($filter['dimension'], $filter['operator'])
                || !is_string($filter['dimension']) || $filter['dimension'] === ''
                || !is_string($filter['operator']) || $filter['operator'] === ''
                || (isset($filter['group']) && (!is_numeric($filter['group']) || (int) $filter['group'] < 0))
            ) {
                throw self::error('DataExplorer_ErrorFiltersInvalid');
            }
            $legacy = $legacy && !isset($filter['group']);
            $result[] = [
                'dimension' => $filter['dimension'],
                'operator' => $filter['operator'],
                'value' => $filter['value'] ?? '',
                'group' => isset($filter['group']) ? (int) $filter['group'] : $index,
            ];
        }

        if ($legacy && $legacyMatch === self::FILTER_MATCH_ANY) {
            foreach ($result as &$filter) {
                $filter['group'] = 0;
            }
            unset($filter);
        }

        $order = [];
        foreach ($result as $filter) {
            $order[$filter['group']] = $order[$filter['group']] ?? count($order);
        }
        $grouped = array_fill(0, count($order), []);
        foreach ($result as $filter) {
            $filter['group'] = $order[$filter['group']];
            $grouped[$filter['group']][] = $filter;
        }

        return $grouped ? array_merge(...$grouped) : [];
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function getTechnique(): string
    {
        return $this->technique;
    }

    public function getReportType(): string
    {
        return $this->reportType;
    }

    /**
     * @return string[]
     */
    public function getRows(): array
    {
        return $this->rows;
    }

    public function getColumn(): ?string
    {
        return $this->column;
    }

    /**
     * @return string[]
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function getFilters(): array
    {
        return $this->filters;
    }

    public function getSegment(): string
    {
        return $this->segment;
    }

    public function getChart(): string
    {
        return $this->chart;
    }

    public function getTableView(): string
    {
        return $this->tableView;
    }

    public function getSql(): string
    {
        return $this->sql;
    }

    public function isFunnel(): bool
    {
        return $this->type === self::TYPE_FUNNEL;
    }

    /**
     * @return array<int, array{name: string, conditions: array, optional: bool, follow: string, within: ?array{value: int, unit: string}}>
     */
    public function getSteps(): array
    {
        return $this->steps;
    }

    /**
     * The time limit of a funnel step in seconds, null without one.
     */
    public static function withinSeconds(array $step): ?int
    {
        return $step['within'] === null ? null : $step['within']['value'] * self::TIME_UNITS[$step['within']['unit']];
    }

    public function processesSegments(): bool
    {
        return $this->type === self::TYPE_SQL || $this->processSegments;
    }

    public function isOpen(): bool
    {
        return $this->open;
    }

    public function getBreakdown(): ?string
    {
        return $this->breakdown;
    }

    public function getBreakdownRows(): int
    {
        return $this->breakdownRows;
    }

    public function toArray(): array
    {
        if ($this->type === self::TYPE_FUNNEL) {
            return [
                'name' => $this->name,
                'description' => $this->description,
                'type' => $this->type,
                'technique' => $this->technique,
                'report_type' => $this->reportType,
                'steps' => $this->steps,
                'open' => $this->open,
                'breakdown' => $this->breakdown,
                'breakdown_rows' => $this->breakdownRows,
                'segment' => $this->segment,
                'process_segments' => $this->processSegments,
            ];
        }

        if ($this->type === self::TYPE_SQL) {
            return [
                'name' => $this->name,
                'description' => $this->description,
                'type' => $this->type,
                'technique' => $this->technique,
                'report_type' => $this->reportType,
                'sql' => $this->sql,
                'chart' => $this->chart,
            ];
        }

        return [
            'name' => $this->name,
            'description' => $this->description,
            'type' => $this->type,
            'technique' => $this->technique,
            'report_type' => $this->reportType,
            'rows' => $this->rows,
            'column' => $this->column,
            'metrics' => $this->metrics,
            'filters' => $this->filters,
            'segment' => $this->segment,
            'sql' => $this->sql,
            'chart' => $this->chart,
            'table_view' => $this->tableView,
            'process_segments' => $this->processSegments,
        ];
    }
}
