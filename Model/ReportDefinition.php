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

    public const TYPE_VISUAL = 'visual';
    public const TYPE_SQL = 'sql';
    public const TECHNIQUE_FREEFORM = 'freeform';

    public const REPORT_TYPE_TABLE = 'table';
    public const REPORT_TYPE_EVOLUTION = 'evolution';
    public const REPORT_TYPE_KPICARD = 'kpicard';

    /** @var string */
    private $name;
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

        $def->technique = self::string($raw, 'technique', self::TECHNIQUE_FREEFORM);
        if ($def->technique !== self::TECHNIQUE_FREEFORM) {
            throw self::error('DataExplorer_ErrorTechniqueInvalid');
        }

        $def->type = self::string($raw, 'type', self::TYPE_VISUAL);
        if (!in_array($def->type, [self::TYPE_VISUAL, self::TYPE_SQL], true)) {
            throw self::error('DataExplorer_ErrorTypeInvalid');
        }

        $def->reportType = self::string($raw, 'report_type', self::REPORT_TYPE_TABLE);
        if (!in_array($def->reportType, [self::REPORT_TYPE_TABLE, self::REPORT_TYPE_EVOLUTION, self::REPORT_TYPE_KPICARD], true)) {
            throw self::error('DataExplorer_ErrorReportTypeInvalid');
        }

        $def->rows = self::stringList($raw, 'rows');
        $def->metrics = self::stringList($raw, 'metrics');
        $column = $raw['column'] ?? null;
        $def->column = is_string($column) && $column !== '' ? $column : null;
        $def->filters = self::filters($raw['filters'] ?? []);
        $def->segment = self::string($raw, 'segment', '');
        $def->sql = trim(self::string($raw, 'sql', ''));

        if ($def->type === self::TYPE_SQL) {
            if ($def->sql === '') {
                throw self::error('DataExplorer_ErrorSqlEmpty');
            }

            return $def;
        }

        $def->validateVisual();

        return $def;
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

    private static function filters($filters): array
    {
        if (!is_array($filters)) {
            throw self::error('DataExplorer_ErrorFiltersInvalid');
        }

        $result = [];
        foreach ($filters as $filter) {
            if (
                !is_array($filter)
                || !isset($filter['dimension'], $filter['operator'])
                || !is_string($filter['dimension']) || $filter['dimension'] === ''
                || !is_string($filter['operator']) || $filter['operator'] === ''
            ) {
                throw self::error('DataExplorer_ErrorFiltersInvalid');
            }
            $result[] = [
                'dimension' => $filter['dimension'],
                'operator' => $filter['operator'],
                'value' => $filter['value'] ?? '',
            ];
        }

        return $result;
    }

    public function getName(): string
    {
        return $this->name;
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

    public function getSql(): string
    {
        return $this->sql;
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'type' => $this->type,
            'technique' => $this->technique,
            'report_type' => $this->reportType,
            'rows' => $this->rows,
            'column' => $this->column,
            'metrics' => $this->metrics,
            'filters' => $this->filters,
            'segment' => $this->segment,
            'sql' => $this->sql,
        ];
    }
}
