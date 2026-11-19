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
     * Scopes of the row dimensions across whose rows the metric adds up (an "Others" total is the sum of its
     * rows). An action or a conversion has one value of each dimension of its own table and of its visit, so it
     * lands in one row only there; under a dimension of another table a visit, an action or a conversion is counted
     * in every row it has a value of, once per row. Distinct counts of other columns, maximums and minimums never
     * add up.
     *
     * @return string[]
     */
    public function additiveScopes(): array
    {
        if ($this->aggregation === 'sum') {
            return array_values(array_unique(['visit', $this->scope]));
        }
        if ($this->aggregation !== 'uniq') {
            return [];
        }
        if (preg_match('/\.idvisit\)$/i', $this->sql)) {
            return ['visit'];
        }
        if (preg_match('/\.idlink_va\)$/i', $this->sql)) {
            return ['visit', 'action'];
        }

        return [];
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
            'additiveScopes' => $this->additiveScopes(),
        ];
    }
}
