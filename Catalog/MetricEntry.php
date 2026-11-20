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
    public const AGGREGATIONS = ['sum', 'max', 'min', 'uniq', 'processed'];

    // a ratio or an average computed from other metrics once they are aggregated, never queried itself
    public const PROCESSED = 'processed';

    /**
     * @param string[] $dependents metrics a processed metric is computed from
     */
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly string $category,
        public readonly string $scope,
        public readonly string $sql,
        public readonly string $aggregation,
        public readonly bool $isNumeric,
        public readonly array $dependents = []
    ) {
    }

    public function isProcessed(): bool
    {
        return $this->aggregation === self::PROCESSED;
    }

    /**
     * The column a distinct count counts, also when a discriminator wraps it in CASE WHEN ... END.
     */
    public function distinctColumn(): ?string
    {
        if ($this->aggregation !== 'uniq' || !preg_match('/\.`?([a-zA-Z0-9_]+)`?(?:\s+END)?\s*\)$/i', $this->sql, $match)) {
            return null;
        }

        return strtolower($match[1]);
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
            return $this->ownAndParentScopes();
        }
        if ($this->aggregation !== 'uniq') {
            return [];
        }
        if ($this->distinctColumn() === 'idvisit') {
            return ['visit'];
        }
        if ($this->distinctColumn() === 'idlink_va') {
            return ['visit', 'action'];
        }
        // a distinct count of the rows of its own table adds up like a count of them
        $tables = LogTables::get();
        $table = $tables->tableOf($this->scope);
        if ($table !== null && $tables->grain($table) === [$this->distinctColumn()]) {
            return $this->ownAndParentScopes();
        }

        return [];
    }

    /**
     * The scope of the metric and of the tables having one row per row of it (its visit), the visit first as before
     * other tables were queryable.
     *
     * @return string[]
     */
    private function ownAndParentScopes(): array
    {
        $tables = LogTables::get();
        $table = $tables->tableOf($this->scope);
        if ($table === null || isset(LogTables::SCOPE_BY_TABLE[$table])) {
            return array_values(array_unique(['visit', $this->scope]));
        }
        $scopes = [$this->scope];
        foreach ($tables->parentsOf($table) as $parent) {
            $scopes[] = (string) $tables->scopeOf($parent);
        }
        if (in_array('visit', $scopes, true)) {
            $scopes = array_merge(['visit'], array_diff($scopes, ['visit']));
        }

        return array_values(array_unique($scopes));
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
            'repeatedBy' => $this->scope === '' ? [] : LogTables::get()->repeatingScopes($this->scope),
        ];
    }
}
