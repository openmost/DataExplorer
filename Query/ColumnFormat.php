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
use Piwik\Metrics\Formatter;
use Piwik\Plugin\Metric;
use Piwik\Plugin\ProcessedMetric;

/**
 * Formats a column like a core metric when the column is not named after it: a pivot column ("bounce_rate | Desktop")
 * or a metric core only knows by its id. Core formats the metrics a table lists in its metadata by column name;
 * this one computes nothing, the value is already there.
 */
final class ColumnFormat extends ProcessedMetric
{
    /** @var string */
    private $column;

    /** @var Metric */
    private $metric;

    public function __construct(string $column, Metric $metric)
    {
        $this->column = $column;
        $this->metric = $metric;
    }

    public function getName()
    {
        return $this->column;
    }

    public function getTranslatedName()
    {
        return $this->metric->getTranslatedName();
    }

    public function getSemanticType(): ?string
    {
        return $this->metric->getSemanticType();
    }

    public function compute(Row $row)
    {
        return false;
    }

    public function getDependentMetrics()
    {
        return [];
    }

    public function beforeFormat($report, DataTable $table)
    {
        return $this->metric->beforeFormat($report, $table);
    }

    public function format($value, Formatter $formatter)
    {
        return $this->metric->format($value, $formatter);
    }
}
