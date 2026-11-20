<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Columns\Dimension;
use Piwik\Columns\Discriminator;
use Piwik\DataTable;
use Piwik\DataTable\Row;
use Piwik\Plugin\ArchivedMetric;
use Piwik\Plugins\CoreHome\Columns\Metrics\BounceRate;
use Piwik\Cache;
use Piwik\Plugins\CoreHome\Tracker\LogTable\Conversion;
use Piwik\Plugins\CoreHome\Tracker\LogTable\ConversionItem;
use Piwik\Plugins\CoreHome\Tracker\LogTable\LinkVisitAction;
use Piwik\Plugins\CoreHome\Tracker\LogTable\Visit;
use Piwik\Plugins\DataExplorer\Catalog\LogTables;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricEntry;
use Piwik\Plugins\DataExplorer\Query\LabelFormatter;
use Piwik\Plugins\DataExplorer\Query\ProcessedMetrics;

/**
 * @group DataExplorer
 * @group MetricCatalogTest
 * @group Plugins
 */
class MetricCatalogTest extends TestCase
{
    // the registry is read from the database at run time: the core log tables stand in for it here
    protected function setUp(): void
    {
        parent::setUp();
        Cache::getTransientCache()->save('DataExplorer.logTables', LogTables::fromLogTables([
            new Visit(),
            new LinkVisitAction(),
            new Conversion(),
            new ConversionItem(),
        ]));
    }

    protected function tearDown(): void
    {
        Cache::getTransientCache()->delete('DataExplorer.logTables');
        parent::tearDown();
    }

    private function dimension(string $table, string $column, ?Discriminator $discriminator = null): Dimension
    {
        return new class ($table, $column, $discriminator) extends Dimension {
            private $discriminator;

            public function __construct(string $table, string $column, ?Discriminator $discriminator)
            {
                $this->dbTableName = $table;
                $this->columnName = $column;
                $this->discriminator = $discriminator;
            }

            public function getDbDiscriminator()
            {
                return $this->discriminator;
            }
        };
    }

    private function metric(string $name, Dimension $dimension, string $aggregation): ArchivedMetric
    {
        $metric = new ArchivedMetric($dimension, $aggregation);
        $metric->setName($name);
        $metric->setTranslatedName($name);

        return $metric;
    }

    /**
     * @dataProvider aggregations
     */
    public function test_aggregationOf_readsTheOuterFunction(string $pattern, ?string $expected): void
    {
        $this->assertSame($expected, MetricCatalog::aggregationOf($pattern));
    }

    public function aggregations(): array
    {
        return [
            ['count(distinct %s)', 'uniq'],
            ['sum(%s)', 'sum'],
            ['sum(case %s when 1 then 1 else 0 end)', 'sum'],
            ['sum(ifnull(%s, 0))', 'sum'],
            ['count(%s)', 'sum'],
            ['COUNT( DISTINCT %s)', 'uniq'],
            ['max(%s)', 'max'],
            ['min(%s)', 'min'],
            ['avg(%s)', null],
            ['%s', null],
        ];
    }

    public function test_aCustomAggregation_isKept(): void
    {
        $entry = MetricCatalog::archivedEntry($this->metric('nb_visits_converted', $this->dimension('log_visit', 'visit_goal_converted'), 'sum(case %s when 1 then 1 else 0 end)'));

        $this->assertNotNull($entry);
        $this->assertSame('visit', $entry->scope);
        $this->assertSame('sum', $entry->aggregation);
        $this->assertSame('sum(case log_visit.visit_goal_converted when 1 then 1 else 0 end)', $entry->sql);
    }

    public function test_aDiscriminatorOfTheSameTable_goesInsideTheAggregate(): void
    {
        $entry = MetricCatalog::archivedEntry($this->metric('goal_2_revenue', $this->dimension('log_conversion', 'revenue', new Discriminator('log_conversion', 'idgoal', 2)), 'sum(%s)'));

        $this->assertSame('sum(CASE WHEN log_conversion.idgoal = 2 THEN log_conversion.revenue END)', $entry->sql);
    }

    public function test_anActionTypeDiscriminator_readsTheTypeOfTheUrlAction(): void
    {
        $entry = MetricCatalog::archivedEntry($this->metric('pageviews', $this->dimension('log_link_visit_action', 'idlink_va', new Discriminator('log_action', 'type', 1)), 'count(distinct %s)'));

        $this->assertNotNull($entry);
        $this->assertSame('count(distinct CASE WHEN ' . MetricCatalog::actionAlias('idaction_url') . '.type = 1 THEN log_link_visit_action.idlink_va END)', $entry->sql);
        $this->assertSame('idlink_va', $entry->distinctColumn());
        $this->assertSame(['visit', 'action'], $entry->additiveScopes());
    }

    public function test_metricsTheExplorerCannotQuery_areLeftOut(): void
    {
        $this->assertNull(MetricCatalog::archivedEntry($this->metric('item', $this->dimension('log_unknown', 'price'), 'max(%s)')), 'table not queried');
        $this->assertNull(MetricCatalog::archivedEntry($this->metric('y', $this->dimension('log_visit', 'a', new Discriminator('log_conversion', 'idgoal', 1)), 'sum(%s)')), 'discriminator of another table');
        $this->assertNull(MetricCatalog::archivedEntry($this->metric('z', $this->dimension('log_visit', 'a'), 'avg(%s)')), 'aggregation not understood');
    }

    public function test_processedMetrics_areComputedPerPlainAndPivotColumn(): void
    {
        $table = new DataTable();
        $separator = LabelFormatter::PIVOT_SEPARATOR;
        $table->addRowFromSimpleArray([
            'label' => 'France',
            'bounce_count' => 1,
            'nb_visits' => 4,
            'bounce_count' . $separator . 'Desktop' => 1,
            'nb_visits' . $separator . 'Desktop' => 2,
        ]);
        $pivotOnly = new DataTable();
        $pivotOnly->addRowFromSimpleArray(['label' => 'Spain', 'bounce_count' . $separator . 'Desktop' => 0, 'nb_visits' . $separator . 'Desktop' => 3]);

        $processed = ProcessedMetrics::of(['bounce_rate' => new BounceRate()]);
        $processed->apply($table);
        $processed->apply($pivotOnly);

        $row = $table->getFirstRow();
        $this->assertEquals(0.25, $row->getColumn('bounce_rate'));
        $this->assertEquals(0.5, $row->getColumn('bounce_rate' . $separator . 'Desktop'));
        $this->assertFalse($pivotOnly->getFirstRow()->getColumn('bounce_rate'), 'no plain column without the plain metrics');
        $this->assertEquals(0, $pivotOnly->getFirstRow()->getColumn('bounce_rate' . $separator . 'Desktop'));
    }

    public function test_aProcessedEntry_neverAddsUp(): void
    {
        $entry = new MetricEntry('bounce_rate', 'Bounce rate', 'General_Visitors', '', '', MetricEntry::PROCESSED, true, ['bounce_count', 'nb_visits']);

        $this->assertTrue($entry->isProcessed());
        $this->assertSame([], $entry->additiveScopes());
        $this->assertNull($entry->distinctColumn());
    }
}
