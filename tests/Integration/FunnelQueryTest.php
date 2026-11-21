<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\DataTable;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\FunnelQueryRunner;
use Piwik\Plugins\DataExplorer\Query\FunnelResult;
use Piwik\Plugins\DataExplorer\tests\Fixtures\ExplorerFixture;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * Funnels over the visits of ExplorerFixture, 2024-03-01 to 2024-03-03 on site 1:
 * 1 /home, /pricing, /home then goal 1; 2 /home; 3 /blog, /home; 4 /contact then goal 1; 5 /home, /blog;
 * 6 /pricing. Every /home starts a pass of a funnel starting on /home: five passes.
 *
 * @group DataExplorer
 * @group FunnelQueryTest
 * @group Plugins
 */
class FunnelQueryTest extends IntegrationTestCase
{
    /** @var ExplorerFixture */
    public static $fixture = null;

    private const RANGE = '2024-03-01,2024-03-03';
    private const URL = 'Actions.ActionUrl';

    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    protected static function configureFixture($fixture)
    {
        parent::configureFixture($fixture);

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

    public function test_everyHomeStartsAPass_andAHomeLaterInTheVisitIsTheNextStep(): void
    {
        $table = $this->funnel([$this->page('Home', '/home'), $this->page('Home again', '/home')]);

        $this->assertSame([5, 1], $this->stepColumn($table, FunnelResult::ENTERED));
        $this->assertSame([5, 0], $this->stepColumn($table, FunnelResult::STARTED));
    }

    public function test_aDirectlyFollowedStep_needsTheNextAction(): void
    {
        $table = $this->funnel([$this->page('Home', '/home'), $this->page('Home again', '/home', ['follow' => 'direct'])]);

        $this->assertSame([5, 0], $this->stepColumn($table, FunnelResult::ENTERED), 'visit 1 views /pricing in between');
    }

    public function test_aGoalStep_readsTheConversionsOfThePass(): void
    {
        $table = $this->funnel([
            $this->page('Home', '/home'),
            ['name' => 'Signup', 'conditions' => [['dimension' => 'Goals.IdGoal', 'operator' => 'equals', 'value' => (string) self::$fixture->idGoal]]],
        ]);

        $this->assertSame([5, 1], $this->stepColumn($table, FunnelResult::ENTERED), 'the second pass of visit 1 converts');
        $this->assertSame([0, 60], $this->stepColumn($table, FunnelResult::SUM_TIME));
    }

    public function test_anOpenFunnel_startsAPassAtALaterStep(): void
    {
        $table = $this->funnel([$this->page('Home', '/home'), $this->page('Blog', '/blog')], ['open' => true]);

        $this->assertSame([5, 1], $this->stepColumn($table, FunnelResult::STARTED), 'visit 3 starts on /blog');
        $this->assertSame([5, 2], $this->stepColumn($table, FunnelResult::ENTERED));
    }

    public function test_aBreakdown_splitsThePassesByTheValueTheyStartedWith(): void
    {
        $table = $this->funnel(
            [$this->page('Home', '/home'), $this->page('Home again', '/home')],
            ['breakdown' => 'UserCountry.Country']
        );

        $first = $table->getFirstRow()->getSubtable();
        $this->assertInstanceOf(DataTable::class, $first);
        $values = [];
        foreach ($first->getRows() as $row) {
            $values[$row->getColumn('label')] = $row->getColumn(FunnelResult::STARTED);
        }
        $this->assertSame(['fr' => 3, 'de' => 1, 'us' => 1], $values, 'raw values, formatted when read');
    }

    public function test_theApi_returnsTheRatesWithFormattedBreakdownValues(): void
    {
        $definition = $this->definition([$this->page('Home', '/home'), $this->page('Home again', '/home')], ['breakdown' => 'UserCountry.Country']);
        $table = API::getInstance()->runFunnel(self::$fixture->idSite, 'range', self::RANGE, json_encode($definition));

        $this->assertSame([100.0, 20.0], array_map('floatval', $this->stepColumn($table, FunnelResult::STEP_RATE)));
        $this->assertSame([4, 0], $this->stepColumn($table, FunnelResult::ABANDONED));
        $labels = [];
        foreach ($table->getFirstRow()->getSubtable()->getRows() as $row) {
            $labels[] = $row->getColumn('label');
        }
        $this->assertSame(['France', 'Germany', 'United States'], $labels);
    }

    public function test_aSavedFunnel_readsTheSameNumbersFromItsArchives(): void
    {
        $definition = $this->definition([$this->page('Home', '/home'), $this->page('Home again', '/home')]);
        $id = API::getInstance()->addReport('Home funnel', 'funnel', 'funnel', json_encode($definition), '{"sites":[1]}', 'General_Actions');

        $table = API::getInstance()->getReport(self::$fixture->idSite, 'range', self::RANGE, $id);

        $this->assertSame([5, 1], $this->stepColumn($table, FunnelResult::ENTERED));
        $this->assertSame(['Home', 'Home again'], $this->stepColumn($table, 'label'));
    }

    public function test_aStepMixingAnActionAndAConversion_isRefused(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->funnel([
            ['name' => 'Mixed', 'conditions' => [
                ['dimension' => self::URL, 'operator' => 'contains', 'value' => 'home', 'group' => 0],
                ['dimension' => 'Goals.IdGoal', 'operator' => 'equals', 'value' => '1', 'group' => 1],
            ]],
            $this->page('Blog', '/blog'),
        ]);
    }

    private function page(string $name, string $path, array $extra = []): array
    {
        return ['name' => $name, 'conditions' => [['dimension' => self::URL, 'operator' => 'ends_with', 'value' => $path]]] + $extra;
    }

    private function definition(array $steps, array $extra = []): array
    {
        return ['name' => 'Funnel', 'type' => 'funnel', 'steps' => $steps] + $extra;
    }

    private function funnel(array $steps, array $extra = []): DataTable
    {
        $definition = ReportDefinition::fromArray($this->definition($steps, $extra));

        return (new FunnelQueryRunner())->run($definition, self::$fixture->idSite, 'range', self::RANGE, '');
    }

    private function stepColumn(DataTable $table, string $column): array
    {
        $values = [];
        foreach ($table->getRows() as $row) {
            $values[] = $row->getColumn($column);
        }

        return $values;
    }
}

FunnelQueryTest::$fixture = new ExplorerFixture();
