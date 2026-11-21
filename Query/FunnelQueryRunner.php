<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\ArchiveProcessor\Parameters;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Db;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Sql\SessionLimits;
use Piwik\Segment;
use Piwik\Site;

/**
 * Runs a funnel over the log tables: the actions and conversions matching a step are read in visit order, then
 * FunnelMatcher walks each visit. The result has one row per step (columns of FunnelResult::STORED) and, with a
 * breakdown, a subtable per step with one row per breakdown value, raw values as labels and the values past the
 * kept ones summed into a row labelled DataTable::LABEL_SUMMARY_ROW, like an archived table.
 */
class FunnelQueryRunner
{
    /** @var VisualQueryBuilder */
    private $builder;
    /** @var callable(): float */
    private $clock;
    /** @var LabelFormatter */
    private $labels;
    /** @var float|null */
    private $deadline = null;
    /** @var int */
    private $seconds = 0;

    /**
     * @param callable(): float|null $clock seconds as a float, microtime(true) by default
     */
    public function __construct(?VisualQueryBuilder $builder = null, ?callable $clock = null)
    {
        $this->builder = $builder ?: new VisualQueryBuilder();
        $this->clock = $clock ?: fn () => microtime(true);
        $this->labels = new LabelFormatter();
    }

    /**
     * The funnel of a period, live, within the time budget of the explorer.
     *
     * @throws \InvalidArgumentException with a translated message when the funnel cannot be run
     */
    public function run(ReportDefinition $definition, int $idSite, string $period, string $date, string $segment): DataTable
    {
        $site = new Site($idSite);
        $periodObject = PeriodFactory::makePeriodFromQueryParams($site->getTimezone(), $period, $date);
        $segmentObject = new Segment(VisualQueryRunner::combineSegments($definition->getSegment(), $segment), [$idSite], $periodObject->getDateTimeStart(), $periodObject->getDateTimeEnd());
        $aggregator = new LogAggregator(new Parameters($site, $periodObject, $segmentObject));
        $aggregator->setQueryOriginHint('DataExplorer');

        $this->seconds = Configuration::getMaxExecutionTimeSeconds();
        $this->deadline = ($this->clock)() + $this->seconds;

        return $this->collect($definition, $aggregator, $idSite);
    }

    /**
     * The funnel of an archive: the aggregator carries the site, period and segment, core manages the time.
     */
    public function runForArchive(ReportDefinition $definition, LogAggregator $aggregator, int $idSite): DataTable
    {
        $this->deadline = null;

        return $this->collect($definition, $aggregator, $idSite);
    }

    private function collect(ReportDefinition $definition, LogAggregator $aggregator, int $idSite): DataTable
    {
        $steps = $definition->getSteps();
        $byTable = [];
        $matcherSteps = [];
        foreach ($steps as $index => $step) {
            $conditions = $this->resolveLabelConditions($this->builder->resolveConditions($step['conditions'], $idSite), $aggregator, $idSite);
            $byTable[$this->builder->funnelStepTable($conditions, $step['name'])][$index] = $conditions;
            $matcherSteps[] = [
                'optional' => $step['optional'],
                'direct' => $step['follow'] === ReportDefinition::FOLLOW_DIRECT,
                'within' => ReportDefinition::withinSeconds($step),
            ];
        }

        $breakdown = null;
        if ($definition->getBreakdown() !== null) {
            $breakdown = $this->builder->resolveDimension($definition->getBreakdown(), $idSite);
            if (!in_array($breakdown->table, ['log_visit', 'log_link_visit_action'], true)) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorFunnelBreakdown', [$breakdown->name]));
            }
        }
        $withPrevious = in_array(true, array_column($matcherSteps, 'direct'), true);

        $visits = [];
        foreach (['log_link_visit_action' => 0, 'log_conversion' => 1] as $table => $kind) {
            if (empty($byTable[$table])) {
                continue;
            }
            $query = $this->builder->funnelQuery($aggregator, $table, $byTable[$table], $breakdown, $withPrevious);
            foreach ($this->fetch($query) as $row) {
                $matched = [];
                foreach (array_keys($byTable[$table]) as $index) {
                    if ((int) $row['step_' . $index] === 1) {
                        $matched[] = $index;
                    }
                }
                if (!$matched) {
                    continue;
                }
                $visits[(int) $row['idvisit']][] = [
                    'pos' => (int) $row['pos'],
                    'prev' => isset($row['prev']) ? (int) $row['prev'] : null,
                    'time' => (int) $row['ts'],
                    'steps' => $matched,
                    'key' => $breakdown === null ? '' : ($row['bkey'] === null || $row['bkey'] === '' ? VisualQueryRunner::ARCHIVED_NOT_DEFINED : (string) $row['bkey']),
                    'kind' => $kind,
                ];
            }
        }

        $matcher = new FunnelMatcher($matcherSteps, $definition->isOpen());
        foreach ($visits as $candidates) {
            usort($candidates, fn (array $a, array $b) => [$a['time'], $a['pos'], $a['kind']] <=> [$b['time'], $b['pos'], $b['kind']]);
            $matcher->addVisit($candidates);
        }

        return $this->table($definition, $matcher->totals(), $breakdown !== null);
    }

    /**
     * @param array<string, array> $totals FunnelMatcher::totals()
     */
    private function table(ReportDefinition $definition, array $totals, bool $withBreakdown): DataTable
    {
        $steps = $definition->getSteps();
        $all = self::sum(array_values($totals), count($steps));

        $kept = [];
        $others = [];
        if ($withBreakdown) {
            uksort($totals, function ($a, $b) use ($totals) {
                return [array_sum($totals[$b]['started']), (string) $a] <=> [array_sum($totals[$a]['started']), (string) $b];
            });
            $kept = array_slice($totals, 0, $definition->getBreakdownRows(), true);
            $rest = array_slice($totals, $definition->getBreakdownRows(), null, true);
            if ($rest) {
                $others = self::sum(array_values($rest), count($steps));
            }
        }

        $table = new DataTable();
        foreach ($steps as $index => $step) {
            $row = new Row([Row::COLUMNS => ['label' => $step['name']] + self::columns($all, $index)]);
            if ($withBreakdown) {
                $subtable = new DataTable();
                foreach ($kept as $key => $values) {
                    $subtable->addRow(new Row([Row::COLUMNS => ['label' => (string) $key] + self::columns($values, $index)]));
                }
                if ($others) {
                    $subtable->addRow(new Row([Row::COLUMNS => ['label' => DataTable::LABEL_SUMMARY_ROW] + self::columns($others, $index)]));
                }
                $row->setSubtable($subtable);
            }
            $table->addRow($row);
        }

        return $table;
    }

    private static function columns(array $totals, int $index): array
    {
        return [
            FunnelResult::STARTED => $totals['started'][$index] ?? 0,
            FunnelResult::ENTERED => $totals['entered'][$index] ?? 0,
            FunnelResult::CONTINUED => $totals['continued'][$index] ?? 0,
            FunnelResult::SUM_TIME => $totals['time'][$index] ?? 0,
            FunnelResult::TIMED => $totals['timed'][$index] ?? 0,
        ];
    }

    private static function sum(array $list, int $count): array
    {
        $zeros = array_fill(0, $count, 0);
        $sum = ['started' => $zeros, 'entered' => $zeros, 'continued' => $zeros, 'time' => $zeros, 'timed' => $zeros];
        foreach ($list as $totals) {
            foreach (array_keys($sum) as $key) {
                foreach ($totals[$key] as $index => $value) {
                    $sum[$key][$index] += $value;
                }
            }
        }

        return $sum;
    }

    /**
     * A condition on a dimension stored as a code (country, device type) compares its label, in the language of
     * the user or in English, or the code itself, like explorer filters: it becomes the list of stored values
     * whose label matches on the period.
     */
    private function resolveLabelConditions(array $conditions, LogAggregator $aggregator, int $idSite): array
    {
        foreach ($conditions as $index => $condition) {
            $negated = VisualQueryBuilder::NEGATIONS[$condition['operator']] ?? null;
            $operator = $negated ?? $condition['operator'];
            if (!in_array($operator, ['equals', 'contains', 'starts_with', 'ends_with', 'regex'], true)) {
                continue;
            }

            $rows = $this->fetch($this->builder->distinctValuesQuery($aggregator, $condition['dimension'], VisualQueryRunner::MAX_FILTER_VALUES + 1));
            if (count($rows) > VisualQueryRunner::MAX_FILTER_VALUES) {
                continue;
            }

            $matching = [];
            $formatted = false;
            foreach ($rows as $row) {
                $raw = $row['label_0'] ?? null;
                if ($raw === null) {
                    continue;
                }
                $label = $this->labels->format($condition['dimension'], $raw, $idSite);
                $formatted = $formatted || $label !== (string) $raw;
                $english = $this->labels->formatInEnglish($condition['dimension'], $raw, $idSite);
                if (LabelFilter::matches($operator, (string) $condition['value'], $label, $english, (string) $raw)) {
                    $matching[] = $raw;
                }
            }
            if (!$formatted) {
                continue;
            }

            $conditions[$index] = [
                'dimension' => $condition['dimension'],
                'operator' => $negated !== null ? VisualQueryBuilder::OPERATOR_NOT_IN : VisualQueryBuilder::OPERATOR_IN,
                'value' => $matching,
                'group' => $condition['group'],
            ];
        }

        return $conditions;
    }

    private function fetch(array $query): array
    {
        $db = Db::getReader();
        if ($this->deadline !== null) {
            $remaining = (int) floor(($this->deadline - ($this->clock)()) * 1000);
            if ($remaining < 1) {
                throw $this->timeout();
            }
            $db->query(SessionLimits::forCurrentServerMilliseconds($db, $remaining));
        }

        try {
            return $db->fetchAll($query['sql'], $query['bind']);
        } catch (\Exception $e) {
            if ($this->deadline !== null && ($this->clock)() >= $this->deadline) {
                throw $this->timeout($e);
            }
            throw $e;
        }
    }

    private function timeout(?\Throwable $previous = null): \InvalidArgumentException
    {
        return new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorQueryTimeout', [$this->seconds]), 0, $previous);
    }
}
