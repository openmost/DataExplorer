<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\ArchiveProcessor\Parameters;
use Piwik\Common;
use Piwik\Config;
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\NoAccessException;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Configuration;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\VisualQueryBuilder;
use Piwik\Plugins\DataExplorer\Query\VisualQueryRunner;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Segment;
use Piwik\Site;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DataExplorer
 * @group VisualQueryTest
 * @group Plugins
 */
class VisualQueryTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

    private const RANGE = '2024-03-01,2024-03-03';
    private const COUNTRY = 'UserCountry.Country';
    private const DEVICE = 'DevicesDetection.DeviceType';
    private const URL = 'Actions.ActionUrl';
    private const TITLE = 'Actions.PageTitle';

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);

        // labels go through the dimension formatters, which return translated country and device names
        $fixture->extraTestEnvVars['loadRealTranslations'] = true;
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::loadAllTranslations();
        FakeAccess::clearAccess(true);
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    public function test_countryVisits_matchTheFixture_andIgnoreTheOtherSite(): void
    {
        $table = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $this->assertSame(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_pageUrlVisits_countDistinctVisits(): void
    {
        $visits = $this->column($this->explore(['rows' => [self::URL], 'metrics' => ['nb_visits', 'nb_actions']]), 'nb_visits');
        $actions = $this->column($this->explore(['rows' => [self::URL], 'metrics' => ['nb_visits', 'nb_actions']]), 'nb_actions');

        $this->assertSame(4, $this->findBySuffix($visits, '/home'));
        $this->assertSame(5, $this->findBySuffix($actions, '/home'));
        $this->assertSame(2, $this->findBySuffix($visits, '/pricing'));
        $this->assertSame(2, $this->findBySuffix($visits, '/blog'));
        $this->assertSame(1, $this->findBySuffix($visits, '/contact'));
    }

    public function test_metricsOfSeveralScopes_areQueriedSeparately_andNotMultiplied(): void
    {
        $table = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits', 'nb_actions', 'nb_conversions']]);

        $this->assertSame(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));
        $this->assertSame(['France' => 5, 'United States' => 3, 'Germany' => 2], $this->column($table, 'nb_actions'));
        $this->assertSame(['France' => 1, 'United States' => 1, 'Germany' => 0], $this->column($table, 'nb_conversions'));
    }

    public function test_twoRowDimensions_returnSubtables(): void
    {
        $table = $this->explore(['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits']]);

        $france = $table->getRowFromLabel('France');
        $this->assertNotFalse($france);
        $this->assertSame(3, $france->getColumn('nb_visits'));
        $subtable = $france->getSubtable();
        $this->assertInstanceOf(DataTable::class, $subtable);
        $this->assertSame(['Desktop' => 2, 'Smartphone' => 1], $this->column($subtable, 'nb_visits'));

        $germany = $table->getRowFromLabel('Germany')->getSubtable();
        $this->assertSame(['Desktop' => 1], $this->column($germany, 'nb_visits'));
    }

    public function test_pivotColumn_rendersOneColumnPerColumnValue(): void
    {
        $table = $this->explore(['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => ['nb_visits']]);

        $this->assertSame(['France' => 2, 'United States' => 1, 'Germany' => 1], $this->column($table, 'nb_visits | Desktop'));
        $this->assertSame(['France' => 1, 'United States' => 1, 'Germany' => 0], $this->column($table, 'nb_visits | Smartphone'));
        $this->assertSame(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_segment_restrictsRows(): void
    {
        $table = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']], 'countryCode==fr');

        $this->assertSame(['France' => 3], $this->column($table, 'nb_visits'));
    }

    public function test_storedSegment_isCombinedWithTheRequestSegment(): void
    {
        $table = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits'], 'segment' => 'deviceType==smartphone'], 'countryCode==fr');

        $this->assertSame(['France' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_equalsFilter_matchesAQuoteAndPercentValueExactly(): void
    {
        $table = $this->explore([
            'rows' => [self::TITLE],
            'metrics' => ['nb_visits'],
            'filters' => [['dimension' => self::TITLE, 'operator' => 'equals', 'value' => ExplorerFixture::OBRIEN_TITLE]],
        ]);

        $this->assertSame([ExplorerFixture::OBRIEN_TITLE => 2], $this->column($table, 'nb_visits', true));
    }

    public function test_containsFilter_escapesLikeWildcards(): void
    {
        $table = $this->explore([
            'rows' => [self::TITLE],
            'metrics' => ['nb_visits'],
            'filters' => [['dimension' => self::TITLE, 'operator' => 'contains', 'value' => '100%']],
        ]);

        $this->assertSame([ExplorerFixture::OBRIEN_TITLE => 2], $this->column($table, 'nb_visits', true));
    }

    public function test_otherFilterOperators(): void
    {
        $base = ['rows' => [self::TITLE], 'metrics' => ['nb_visits']];

        $startsWith = $this->explore($base + ['filters' => [['dimension' => self::TITLE, 'operator' => 'starts_with', 'value' => 'Con']]]);
        $this->assertSame(['Contact 1000' => 1], $this->column($startsWith, 'nb_visits', true));

        $regex = $this->explore($base + ['filters' => [['dimension' => self::TITLE, 'operator' => 'regex', 'value' => '^(Home|Pricing)$']]]);
        $this->assertSame(['Home' => 4, 'Pricing' => 2], $this->column($regex, 'nb_visits', true));

        $notEquals = $this->explore($base + ['filters' => [['dimension' => self::TITLE, 'operator' => 'not_equals', 'value' => 'Home']]]);
        $this->assertArrayNotHasKey('Home', $this->column($notEquals, 'nb_visits', true));

        $countries = ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']];
        $gt = $this->explore($countries + ['filters' => [['dimension' => self::DEVICE, 'operator' => 'gt', 'value' => '0']]]);
        $this->assertEquals(['France' => 1, 'United States' => 1], $this->column($gt, 'nb_visits'));
        $lt = $this->explore($countries + ['filters' => [['dimension' => self::DEVICE, 'operator' => 'lt', 'value' => '1']]]);
        $this->assertEquals(['France' => 2, 'United States' => 1, 'Germany' => 1], $this->column($lt, 'nb_visits'));
    }

    public function test_filtersOnCodedDimensions_matchTheDisplayedLabel(): void
    {
        $countries = ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']];
        $byLabel = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'France']]]);
        $byCode = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'fr']]]);
        $this->assertSame(['France'], array_keys($this->column($byLabel, 'nb_visits')));
        $this->assertEquals($this->column($byCode, 'nb_visits'), $this->column($byLabel, 'nb_visits'));

        $contains = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'contains', 'value' => 'ERMAN']]]);
        $this->assertSame(['Germany'], array_keys($this->column($contains, 'nb_visits')));

        $notEquals = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'not_equals', 'value' => 'France']]]);
        $this->assertEqualsCanonicalizing(['United States', 'Germany'], array_keys($this->column($notEquals, 'nb_visits')));

        $none = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'Atlantis']]]);
        $this->assertSame([], $this->column($none, 'nb_visits'));
    }

    public function test_filters_matchAll_orAny(): void
    {
        $filters = [
            ['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'France'],
            ['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'Germany'],
        ];
        $base = ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits'], 'filters' => $filters];

        $all = $this->explore($base);
        $any = $this->explore($base + ['filter_match' => 'any']);

        $this->assertSame([], $this->column($all, 'nb_visits'));
        $this->assertEqualsCanonicalizing(['France', 'Germany'], array_keys($this->column($any, 'nb_visits')));
    }

    public function test_metricsOfCustomReports_customAggregation_actionType_andRates(): void
    {
        $table = $this->explore([
            'rows' => [self::COUNTRY],
            'metrics' => ['pageviews', 'nb_visits_converted', 'bounce_rate', 'visits_conversion_rate'],
        ]);

        $this->assertEquals(['France' => 5, 'United States' => 3, 'Germany' => 2], $this->column($table, 'pageviews'));
        $this->assertEquals(['France' => 1, 'United States' => 1, 'Germany' => 0], $this->column($table, 'nb_visits_converted'));
        // France: visits 2 and 6 of one page out of 3, United States: visit 4 out of 2; the rounding is core's
        $this->assertEqualsWithDelta(['France' => 0.667, 'United States' => 0.5, 'Germany' => 0], $this->column($table, 'bounce_rate'), 0.005);
        $this->assertEqualsWithDelta(['France' => 0.333, 'United States' => 0.5, 'Germany' => 0], $this->column($table, 'visits_conversion_rate'), 0.005);
    }

    public function test_filterGroups_orInside_andBetween(): void
    {
        $table = $this->explore([
            'rows' => [self::COUNTRY],
            'metrics' => ['nb_visits'],
            'filters' => [
                ['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'France', 'group' => 0],
                ['dimension' => self::COUNTRY, 'operator' => 'equals', 'value' => 'United States', 'group' => 0],
                ['dimension' => self::DEVICE, 'operator' => 'equals', 'value' => 'Smartphone', 'group' => 1],
            ],
        ]);

        $this->assertEquals(['France' => 1, 'United States' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_negativeAndInclusiveOperators(): void
    {
        $countries = ['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']];

        $notContains = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'not_contains', 'value' => 'ANCE']]]);
        $this->assertEqualsCanonicalizing(['Germany', 'United States'], array_keys($this->column($notContains, 'nb_visits')));

        $notStarts = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'not_starts_with', 'value' => 'united']]]);
        $this->assertEqualsCanonicalizing(['France', 'Germany'], array_keys($this->column($notStarts, 'nb_visits')));

        $notEnds = $this->explore($countries + ['filters' => [['dimension' => self::COUNTRY, 'operator' => 'not_ends_with', 'value' => 'many']]]);
        $this->assertEqualsCanonicalizing(['France', 'United States'], array_keys($this->column($notEnds, 'nb_visits')));

        // device type codes: desktop 0, smartphone 1
        $gte = $this->explore($countries + ['filters' => [['dimension' => self::DEVICE, 'operator' => 'gte', 'value' => '1']]]);
        $this->assertEquals(['France' => 1, 'United States' => 1], $this->column($gte, 'nb_visits'));
        $lte = $this->explore($countries + ['filters' => [['dimension' => self::DEVICE, 'operator' => 'lte', 'value' => '0']]]);
        $this->assertEquals(['France' => 2, 'United States' => 1, 'Germany' => 1], $this->column($lte, 'nb_visits'));
    }

    public function test_endsWithFilter(): void
    {
        $table = $this->explore([
            'rows' => [self::COUNTRY],
            'metrics' => ['nb_visits'],
            'filters' => [['dimension' => self::COUNTRY, 'operator' => 'ends_with', 'value' => 'MANY']],
        ]);

        $this->assertSame(['Germany'], array_keys($this->column($table, 'nb_visits')));
    }

    public function test_deviceTypeFilter_byName_neverMatchesDesktop(): void
    {
        // compared as stored, MySQL casts "Smartphone" to 0, the id of Desktop
        $table = $this->explore([
            'rows' => [self::DEVICE],
            'metrics' => ['nb_visits'],
            'filters' => [['dimension' => self::DEVICE, 'operator' => 'equals', 'value' => 'smartphone']],
        ]);

        $this->assertSame(['Smartphone'], array_keys($this->column($table, 'nb_visits')));
    }

    public function test_urlFilter_ignoresTheProtocolAndWww(): void
    {
        $base = ['rows' => [self::URL], 'metrics' => ['nb_visits']];

        $withProtocol = $this->column($this->explore($base + ['filters' => [['dimension' => self::URL, 'operator' => 'starts_with', 'value' => 'https://www.example.com/pri']]]), 'nb_visits');
        $withoutProtocol = $this->column($this->explore($base + ['filters' => [['dimension' => self::URL, 'operator' => 'starts_with', 'value' => 'example.com/pri']]]), 'nb_visits');

        $this->assertSame(2, $this->findBySuffix($withProtocol, '/pricing'));
        $this->assertSame($withoutProtocol, $withProtocol);
    }

    public function test_filterValues_areBoundParameters(): void
    {
        $definition = ReportDefinition::fromArray([
            'name' => 'preview',
            'rows' => [self::TITLE],
            'metrics' => ['nb_visits'],
            'filters' => [['dimension' => self::TITLE, 'operator' => 'contains', 'value' => "x' OR 1=1 -- 100%"]],
        ]);

        $query = (new VisualQueryBuilder())->build($definition, $this->aggregator(''));

        $this->assertStringNotContainsString('OR 1=1', $query['sql']);
        $this->assertContains("%x&#039; OR 1=1 -- 100\\%%", $query['bind']);
    }

    public function test_conversionsUnderAPageUrl_countTheConversionsOfTheVisitsThatViewedIt_once(): void
    {
        $table = $this->explore(['rows' => [self::URL], 'metrics' => ['nb_conversions', 'revenue']]);
        $conversions = $this->column($table, 'nb_conversions');
        $revenue = $this->column($table, 'revenue');

        // the converting visit viewed /home twice, its conversion counts once in that row
        $this->assertEquals(1, $this->findBySuffix($conversions, '/home'));
        $this->assertEquals(10, $this->findBySuffix($revenue, '/home'));
        $this->assertEquals(1, $this->findBySuffix($conversions, '/pricing'));
        $this->assertEquals(1, $this->findBySuffix($conversions, '/contact'));
    }

    public function test_visitSumUnderAnActionFilter_countsEachVisitOnce_likeAVisitSegment(): void
    {
        $filtered = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['sum_visit_length', 'bounce_count'],
            'filters' => [['dimension' => self::URL, 'operator' => 'contains', 'value' => 'home']]]);
        $segmented = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['sum_visit_length', 'bounce_count']], 'pageUrl=@home');

        $this->assertEquals($this->column($segmented, 'sum_visit_length'), $this->column($filtered, 'sum_visit_length'));
        $this->assertEquals($this->column($segmented, 'bounce_count'), $this->column($filtered, 'bounce_count'));
    }

    public function test_visitSumUnderAPageUrl_countsEachVisitOncePerRow(): void
    {
        $table = $this->explore(['rows' => [self::URL], 'metrics' => ['bounce_count', 'sum_visit_length']]);
        $bounces = $this->column($table, 'bounce_count');

        // /home: one bounce (the visit of one page), the visit of three pages is counted once
        $this->assertEquals(1, $this->findBySuffix($bounces, '/home'));
        $this->assertEquals(0, $this->findBySuffix($bounces, '/blog'));
        $this->assertEquals(1, $this->findBySuffix($bounces, '/contact'));

        $byCountry = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['sum_visit_length']], 'pageUrl=@contact');
        $this->assertEquals(array_sum($this->column($byCountry, 'sum_visit_length')), $this->findBySuffix($this->column($table, 'sum_visit_length'), '/contact'));
    }

    public function test_actionsUnderAGoal_countEachActionOnce_evenWithSeveralConversionsInTheVisit(): void
    {
        $definition = ReportDefinition::fromArray(['name' => 'preview', 'rows' => ['Goals.IdGoal'], 'metrics' => ['nb_actions']]);
        $table = (new VisualQueryRunner())->run($definition, self::$fixture->idSite, 'day', '2024-03-05', '');

        $this->assertSame([1], array_values($this->column($table, 'nb_actions')));
    }

    public function test_actionAndConversionScopes_combineInOneExploration(): void
    {
        $table = $this->explore(['rows' => [self::URL], 'column' => 'Goals.IdGoal', 'metrics' => ['nb_actions', 'nb_conversions']]);

        $this->assertEquals(5, $this->findBySuffix($this->column($table, 'nb_actions'), '/home'));
        $this->assertEquals(1, $this->findBySuffix($this->column($table, 'nb_conversions'), '/home'));
    }

    public function test_unknownIdsAndOperators_areRefused(): void
    {
        $cases = [
            'dimension' => [['rows' => ['Nope.Nope'], 'metrics' => ['nb_visits']], 'DataExplorer_ErrorUnknownDimension'],
            'metric' => [['rows' => [self::COUNTRY], 'metrics' => ['nb_visits); DROP TABLE x; --']], 'DataExplorer_ErrorUnknownMetric'],
            'operator' => [['rows' => [self::COUNTRY], 'metrics' => ['nb_visits'], 'filters' => [['dimension' => self::COUNTRY, 'operator' => 'like', 'value' => 'x']]], 'DataExplorer_ErrorFilterOperatorInvalid'],
            'number' => [['rows' => [self::COUNTRY], 'metrics' => ['nb_visits'], 'filters' => [['dimension' => self::DEVICE, 'operator' => 'gt', 'value' => '1 OR 1']]], 'DataExplorer_ErrorFilterValueNotNumeric'],
        ];

        foreach ($cases as $case => [$definition, $key]) {
            try {
                $this->explore($definition);
                $this->fail("$case: expected a refusal");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringStartsWith(strtok(Piwik::translate($key), '%'), $e->getMessage(), $case);
            }
        }
    }

    public function test_rowsBeyondTheDefaultCap_areSummedIntoOthers(): void
    {
        $runner = new VisualQueryRunner();
        $definition = ReportDefinition::fromArray(['name' => 'preview', 'rows' => [self::URL], 'metrics' => ['nb_visits', 'nb_actions']]);
        $table = $runner->run($definition, self::$fixture->idSite2, 'day', '2024-03-02', '');

        $cap = Configuration::getMaxRowsInMainTable(1);
        $this->assertSame($cap + 1, $table->getRowsCount());
        $others = $table->getRowFromId(DataTable::ID_SUMMARY_ROW);
        $this->assertNotFalse($others);
        $this->assertSame($this->others(), $others->getColumn('label'));
        $this->assertSame(ExplorerFixture::SITE2_URL_COUNT - $cap, $others->getColumn('nb_actions'));
        // distinct counts of "Others" are counted over the rows not kept, not summed: all of them are one visit
        $this->assertSame(1, $others->getColumn('nb_visits'));
    }

    public function test_tableWithoutRows_returnsTotals(): void
    {
        $table = $this->explore(['report_type' => 'kpicard', 'metrics' => ['nb_visits']]);

        $this->assertSame(1, $table->getRowsCount());
        $this->assertSame(6, $table->getFirstRow()->getColumn('nb_visits'));
    }

    public function test_rowCaps_limitMainTablesAndSubtables_andSumTheRestIntoOthers(): void
    {
        Config::getInstance()->DataExplorer = [
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 2,
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_2' => 2,
            'datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_2' => 1,
        ];

        $single = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits', 'nb_actions', 'nb_conversions']]);
        $this->assertSame(3, $single->getRowsCount());
        $this->assertSame(['France' => 3, 'United States' => 2, $this->others() => 1], $this->column($single, 'nb_visits'));
        $this->assertSame(['France' => 5, 'United States' => 3, $this->others() => 2], $this->column($single, 'nb_actions'));
        $this->assertSame(['France' => 1, 'United States' => 1, $this->others() => 0], $this->column($single, 'nb_conversions'));

        $table = $this->explore(['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits', 'nb_actions']]);

        $this->assertSame(3, $table->getRowsCount());
        $this->assertSame(['France' => 3, 'United States' => 2, $this->others() => 1], $this->column($table, 'nb_visits'));
        $this->assertSame(['France' => 5, 'United States' => 3, $this->others() => 2], $this->column($table, 'nb_actions'));

        $france = $table->getRowFromLabel('France')->getSubtable();
        $this->assertSame(['Desktop' => 2, $this->others() => 1], $this->column($france, 'nb_visits'));
        $this->assertSame(['Desktop' => 4, $this->others() => 1], $this->column($france, 'nb_actions'));

        $unitedStates = $table->getRowFromLabel('United States')->getSubtable();
        $this->assertSame(2, $unitedStates->getRowsCount());
        $this->assertSame(1, $unitedStates->getRowFromId(DataTable::ID_SUMMARY_ROW)->getColumn('nb_visits'));
    }

    public function test_subtableCap_appliesPerParent_whenOneParentDominates(): void
    {
        Config::getInstance()->DataExplorer = ['datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_2' => 5];
        $definition = ReportDefinition::fromArray(['name' => 'preview', 'rows' => [self::COUNTRY, self::URL], 'metrics' => ['nb_actions']]);

        $table = (new VisualQueryRunner())->run($definition, self::$fixture->idSite2, 'range', self::RANGE, '');

        $france = $table->getRowFromLabel('France')->getSubtable();
        $this->assertSame(6, $france->getRowsCount());
        $this->assertSame(ExplorerFixture::SITE2_URL_COUNT - 5, $france->getRowFromId(DataTable::ID_SUMMARY_ROW)->getColumn('nb_actions'));

        $germany = $table->getRowFromLabel('Germany')->getSubtable();
        $this->assertSame(2, $germany->getRowsCount());
        $this->assertFalse($germany->getRowFromId(DataTable::ID_SUMMARY_ROW));
        $this->assertSame(3, $this->findBySuffix($this->column($germany, 'nb_actions'), '/de/a'));
        $this->assertSame(1, $this->findBySuffix($this->column($germany, 'nb_actions'), '/de/b'));
    }

    public function test_secondScopeLabelsMissingFromTheFirstScope_landInOthers(): void
    {
        $actions = Common::prefixTable('log_link_visit_action');
        Db::query("DELETE FROM $actions WHERE idsite = 1 AND idvisit = 3");

        $table = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_actions', 'nb_visits']]);

        $this->assertSame(['France' => 5, 'United States' => 3, $this->others() => 0], $this->column($table, 'nb_actions'));
        $this->assertSame(['France' => 3, 'United States' => 2, $this->others() => 1], $this->column($table, 'nb_visits'));
    }

    public function test_fourRowDimensions_nestFourLevels(): void
    {
        Config::getInstance()->DataExplorer = ['dataexplorer_max_dimensions' => 4];

        $table = $this->explore(['rows' => [self::COUNTRY, self::DEVICE, self::TITLE, self::URL], 'metrics' => ['nb_visits']]);

        $titles = $table->getRowFromLabel('France')->getSubtable()->getRowFromLabel('Desktop')->getSubtable();
        $this->assertEquals(['Home' => 1, 'Pricing' => 2], $this->column($titles, 'nb_visits'));
        $urls = $titles->getRowFromLabel('Pricing')->getSubtable();
        $this->assertSame(2, $this->findBySuffix($this->column($urls, 'nb_visits'), '/pricing'));
        $this->assertSame(1, $urls->getRowsCount());
    }

    public function test_pivotUnderLimits_keepsCellsOfKeptRowsAndOthers(): void
    {
        Config::getInstance()->DataExplorer = ['datatable_archiving_maximum_rows_dataexplorer_dimensions_1' => 2];

        $table = $this->explore(['rows' => [self::COUNTRY], 'column' => self::DEVICE, 'metrics' => ['nb_visits']]);

        $this->assertSame(['France' => 2, 'United States' => 1, $this->others() => 1], $this->column($table, 'nb_visits | Desktop'));
        $this->assertSame(['France' => 1, 'United States' => 1, $this->others() => 0], $this->column($table, 'nb_visits | Smartphone'));
    }

    /**
     * @dataProvider chunkedDefinitions
     */
    public function test_childLevels_areQueriedInChunks_withTheSameResult(array $definition): void
    {
        $caps = [
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_2' => 2,
            'datatable_archiving_maximum_rows_dataexplorer_dimensions_3' => 2,
            'datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_2' => 2,
            'datatable_archiving_maximum_rows_subtable_dataexplorer_dimensions_3' => 2,
        ];
        $definition = ReportDefinition::fromArray($definition + ['name' => 'preview']);

        Config::getInstance()->DataExplorer = $caps;
        $whole = (new VisualQueryRunner())->run($definition, self::$fixture->idSite, 'range', self::RANGE, '');

        Config::getInstance()->DataExplorer = $caps + ['dataexplorer_tuple_chunk_size' => 3];
        $spy = new class extends VisualQueryBuilder {
            /** @var int */
            public $largest = 0;
            /** @var int */
            public $statements = 0;

            public function buildQuery(ReportDefinition $definition, \Piwik\DataAccess\LogAggregator $aggregator, array $spec, ?array $resolved = null): array
            {
                $this->statements++;
                $tuples = count($spec['include'][1] ?? []) + count($spec['exclude'][1] ?? []);
                $this->largest = max($this->largest, $tuples);

                return parent::buildQuery($definition, $aggregator, $spec, $resolved);
            }
        };
        $chunked = (new VisualQueryRunner($spy))->run($definition, self::$fixture->idSite, 'range', self::RANGE, '');

        $this->assertLessThanOrEqual(3, $spy->largest, 'no statement may carry more tuples than the chunk size');
        $this->assertEquals($this->tree($whole), $this->tree($chunked));
        $this->assertNotEmpty($this->tree($whole));
    }

    public function chunkedDefinitions(): array
    {
        return [
            'three dimensions, two scopes' => [['rows' => [self::COUNTRY, self::DEVICE, self::TITLE], 'metrics' => ['nb_visits', 'nb_actions']]],
            'two dimensions with a pivot' => [['rows' => [self::COUNTRY, self::TITLE], 'column' => self::DEVICE, 'metrics' => ['nb_visits']]],
        ];
    }

    /**
     * Every row of the table and of its subtables, keyed by its label path.
     */
    private function tree(DataTable $table, string $path = ''): array
    {
        $result = [];
        foreach ($table->getRows() as $row) {
            $key = $path . '/' . $row->getColumn('label');
            $columns = $row->getColumns();
            ksort($columns);
            $result[$key] = $columns;
            $subtable = $row->getSubtable();
            if ($subtable instanceof DataTable) {
                $result += $this->tree($subtable, $key);
            }
        }
        ksort($result);

        return $result;
    }

    public function test_nullAndEmptyValues_formOneNotDefinedRow(): void
    {
        $visits = Common::prefixTable('log_visit');
        Db::query("UPDATE $visits SET referer_name = NULL WHERE idsite = 1");
        Db::query("UPDATE $visits SET referer_name = '' WHERE idsite = 1 AND idvisit IN (1, 3)");
        Db::query("UPDATE $visits SET referer_name = 'google' WHERE idsite = 1 AND idvisit = 2");

        $table = $this->explore(['rows' => ['Referrers.ReferrerName'], 'metrics' => ['nb_visits']]);

        $notDefined = Piwik::translate('General_NotDefined', Piwik::translate('Referrers_ReferrerName'));
        $this->assertSame([$notDefined => 5, 'google' => 1], $this->column($table, 'nb_visits'));
    }

    public function test_actionSegment_appliesToVisitAndActionQueries(): void
    {
        $countries = $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits', 'nb_actions']], 'pageUrl=@pricing');
        $this->assertSame(['France' => 2], $this->column($countries, 'nb_visits'));

        $urls = $this->explore(['rows' => [self::URL], 'metrics' => ['nb_actions']], 'pageUrl=@pricing');
        $this->assertSame(2, $this->findBySuffix($this->column($urls, 'nb_actions'), '/pricing'));
    }

    public function test_exhaustedTimeBudget_throwsTheTimeoutError(): void
    {
        $calls = 0;
        $clock = function () use (&$calls) {
            return $calls++ === 0 ? 1000.0 : 2000.0;
        };
        $definition = ReportDefinition::fromArray(['name' => 'preview', 'rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        try {
            (new VisualQueryRunner(null, $clock))->run($definition, self::$fixture->idSite, 'range', self::RANGE, '');
            $this->fail('expected a timeout');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorQueryTimeout', [30]), $e->getMessage());
        }
    }

    public function test_statementTimeout_isResetAfterTheRun(): void
    {
        $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $db = Db::getReader();
        $variable = stripos((string) $db->fetchOne('SELECT VERSION()'), 'mariadb') !== false ? 'max_statement_time' : 'max_execution_time';
        $this->assertEquals(0, $db->fetchOne('SELECT @@SESSION.' . $variable));
    }

    public function test_statementTimeout_previousSessionValueIsRestored(): void
    {
        $db = Db::getReader();
        $variable = stripos((string) $db->fetchOne('SELECT VERSION()'), 'mariadb') !== false ? 'max_statement_time' : 'max_execution_time';
        $db->query('SET SESSION ' . $variable . ' = ' . ($variable === 'max_statement_time' ? '7' : '7000'));

        try {
            $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);
            $this->assertEquals($variable === 'max_statement_time' ? 7 : 7000, $db->fetchOne('SELECT @@SESSION.' . $variable));
        } finally {
            $db->query('SET SESSION ' . $variable . ' = 0');
        }
    }

    public function test_api_runVisual_checksAccess_andReturnsTheTable(): void
    {
        $definition = json_encode(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $table = API::getInstance()->runVisual(1, 'range', self::RANGE, $definition);
        $this->assertSame(['France' => 3, 'United States' => 2, 'Germany' => 1], $this->column($table, 'nb_visits'));

        FakeAccess::clearAccess(false, [], [2], 'alice', []);
        $this->expectException(NoAccessException::class);
        API::getInstance()->runVisual(1, 'range', self::RANGE, $definition);
    }

    public function test_api_runVisual_opensTheHierarchicalViewOneLevelAtATime_andTheFlatViewWhole(): void
    {
        $definition = json_encode(['rows' => [self::COUNTRY, self::DEVICE], 'metrics' => ['nb_visits']]);

        $first = API::getInstance()->runVisual(1, 'range', self::RANGE, $definition);
        $france = $first->getRowFromLabel('France');
        $this->assertFalse($france->getSubtable(), 'children are not loaded with the first level');
        $this->assertGreaterThan(0, $france->getIdSubDataTable());

        $children = API::getInstance()->runVisual(1, 'range', self::RANGE, $definition, '', (int) $france->getIdSubDataTable());
        $this->assertNotEmpty($children->getRowsCount());
        $this->assertFalse($children->getRowFromLabel('France'));

        $flat = API::getInstance()->runVisual(1, 'range', self::RANGE, $definition, '', 0, true);
        $this->assertInstanceOf(DataTable::class, $flat->getRowFromLabel('France')->getSubtable());
    }

    public function test_api_runVisual_evolutionOverADateRange_returnsOneTotalPerDay(): void
    {
        $definition = json_encode(['report_type' => 'evolution', 'metrics' => ['nb_visits', 'nb_actions']]);

        $map = API::getInstance()->runVisual(1, 'range', self::RANGE, $definition);

        $this->assertInstanceOf(DataTable\Map::class, $map);
        $values = [];
        foreach ($map->getDataTables() as $table) {
            $values[] = $table->getFirstRow()->getColumn('nb_visits');
        }
        $this->assertSame([2, 2, 2], $values);
    }

    public function test_api_runVisual_evolutionOfOnePeriod_endsWithThatPeriod(): void
    {
        $definition = json_encode(['report_type' => 'evolution', 'metrics' => ['nb_visits']]);

        $map = API::getInstance()->runVisual(1, 'day', '2024-03-03', $definition);

        $tables = $map->getDataTables();
        $this->assertCount(Configuration::getRawSqlMaxPoints(), $tables);
        $this->assertSame(2, end($tables)->getFirstRow()->getColumn('nb_visits'));
        $this->assertSame(0, reset($tables)->getFirstRow()->getColumn('nb_visits'));
    }

    public function test_api_runVisual_evolutionPoints_followTheRawSqlPointCap(): void
    {
        $original = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = ['raw_sql_max_points' => 5];

        try {
            $definition = json_encode(['report_type' => 'evolution', 'metrics' => ['nb_visits']]);
            $this->assertCount(5, API::getInstance()->runVisual(1, 'day', '2024-03-03', $definition)->getDataTables());
            $this->assertCount(5, API::getInstance()->runVisual(1, 'day', '2024-01-01,2024-03-03', $definition)->getDataTables());
        } finally {
            Config::getInstance()->DataExplorer = $original;
        }
    }

    public function test_api_runVisual_evolutionOfAMultiplePeriod_keepsTheMostRecentPoints(): void
    {
        $definition = json_encode(['report_type' => 'evolution', 'metrics' => ['nb_visits']]);

        $map = API::getInstance()->runVisual(1, 'day', '2023-01-01,2024-03-03', $definition);

        $tables = $map->getDataTables();
        $this->assertCount(Configuration::getRawSqlMaxPoints(), $tables);
        $this->assertSame(2, end($tables)->getFirstRow()->getColumn('nb_visits'));
    }

    private function explore(array $definition, string $segment = ''): DataTable
    {
        $definition = ReportDefinition::fromArray($definition + ['name' => 'preview']);

        return (new VisualQueryRunner())->run($definition, self::$fixture->idSite, 'range', self::RANGE, $segment);
    }

    private function others(): string
    {
        return Piwik::translate('General_Others');
    }

    private function aggregator(string $segment): LogAggregator
    {
        $site = new Site(self::$fixture->idSite);
        $period = PeriodFactory::build('range', self::RANGE);

        return new LogAggregator(new Parameters($site, $period, new Segment($segment, [self::$fixture->idSite])));
    }

    private function column(DataTable $table, string $column, bool $unsanitize = false): array
    {
        $result = [];
        foreach ($table->getRows() as $row) {
            $label = (string) $row->getColumn('label');
            $result[$unsanitize ? Common::unsanitizeInputValue($label) : $label] = $row->getColumn($column);
        }

        return $result;
    }

    private function findBySuffix(array $values, string $suffix)
    {
        foreach ($values as $label => $value) {
            if (substr((string) $label, -strlen($suffix)) === $suffix) {
                return $value;
            }
        }

        $this->fail("No label ending with $suffix in " . implode(', ', array_keys($values)));
    }
}

VisualQueryTest::$fixture = new ExplorerFixture();
