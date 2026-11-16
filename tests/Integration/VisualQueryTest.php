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
use Piwik\DataAccess\LogAggregator;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\NoAccessException;
use Piwik\Period\Factory as PeriodFactory;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\API;
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

    public function test_incompatibleCombinations_areRefused(): void
    {
        $cases = [
            'row' => ['rows' => [self::URL], 'metrics' => ['nb_conversions']],
            'second row' => ['rows' => [self::COUNTRY, self::URL], 'metrics' => ['sum_visit_length']],
            'pivot' => ['rows' => [self::COUNTRY], 'column' => self::URL, 'metrics' => ['sum_visit_length']],
            'filter' => ['rows' => [self::COUNTRY], 'metrics' => ['nb_conversions'], 'filters' => [['dimension' => self::URL, 'operator' => 'equals', 'value' => 'x']]],
        ];

        foreach ($cases as $case => $definition) {
            try {
                $this->explore($definition);
                $this->fail("$case: expected a refusal");
            } catch (\InvalidArgumentException $e) {
                $this->assertStringContainsString('cannot be combined', $e->getMessage(), $case);
            }
        }
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

    public function test_rowsBeyondThePreviewLimit_areSummedIntoOthers(): void
    {
        $runner = new VisualQueryRunner();
        $definition = ReportDefinition::fromArray(['name' => 'preview', 'rows' => [self::URL], 'metrics' => ['nb_visits', 'nb_actions']]);
        $table = $runner->run($definition, self::$fixture->idSite2, 'day', '2024-03-02', '');

        $this->assertSame(1001, $table->getRowsCount());
        $others = $table->getRowFromId(DataTable::ID_SUMMARY_ROW);
        $this->assertNotFalse($others);
        $this->assertSame(Piwik::translate('General_Others'), $others->getColumn('label'));
        $this->assertSame(1, $others->getColumn('nb_actions'));
    }

    public function test_tableWithoutRows_returnsTotals(): void
    {
        $table = $this->explore(['report_type' => 'kpicard', 'metrics' => ['nb_visits']]);

        $this->assertSame(1, $table->getRowsCount());
        $this->assertSame(6, $table->getFirstRow()->getColumn('nb_visits'));
    }

    public function test_statementTimeout_isResetAfterTheRun(): void
    {
        $this->explore(['rows' => [self::COUNTRY], 'metrics' => ['nb_visits']]);

        $db = Db::getReader();
        $variable = stripos((string) $db->fetchOne('SELECT VERSION()'), 'mariadb') !== false ? 'max_statement_time' : 'max_execution_time';
        $this->assertEquals(0, $db->fetchOne('SELECT @@SESSION.' . $variable));
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

    private function explore(array $definition, string $segment = ''): DataTable
    {
        $definition = ReportDefinition::fromArray($definition + ['name' => 'preview']);

        return (new VisualQueryRunner())->run($definition, self::$fixture->idSite, 'range', self::RANGE, $segment);
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
