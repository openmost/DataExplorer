<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Catalog;

final class MetricEntry
{
    public const AGGREGATIONS = ['sum', 'max', 'min', 'uniq'];

    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $category,
        public readonly string $scope,
        public readonly string $sql,
        public readonly string $aggregation,
        public readonly bool $isNumeric
    ) {
    }

    /**
     * DataTable aggregation operation of the metric: a distinct count is summed like core does across rows and
     * periods, the real distinct count of a merged period is only known from the logs.
     */
    public function aggregationOperation(): string
    {
        return ['sum' => 'sum', 'uniq' => 'sum', 'max' => 'max', 'min' => 'min'][$this->aggregation] ?? 'sum';
    }

    /**
     * The SQL expression is deliberately left out, it never leaves the server.
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'category' => $this->category,
            'scope' => $this->scope,
            'aggregation' => $this->aggregation,
            'isNumeric' => $this->isNumeric,
        ];
    }
}
