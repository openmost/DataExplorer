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
use Piwik\Config;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;

/**
 * @group DataExplorer
 * @group ReportDefinitionTest
 * @group Plugins
 */
class ReportDefinitionTest extends TestCase
{
    private $originalSection;

    protected function setUp(): void
    {
        parent::setUp();
        $this->originalSection = Config::getInstance()->DataExplorer;
        Config::getInstance()->DataExplorer = [];
    }

    protected function tearDown(): void
    {
        Config::getInstance()->DataExplorer = $this->originalSection;
        parent::tearDown();
    }

    public function testMaxRowDimensionsFollowsTheConfiguration(): void
    {
        $rows = fn (int $n) => $this->valid(['rows' => array_map(fn ($i) => 'Dim.D' . $i, range(1, $n))]);

        Config::getInstance()->DataExplorer = ['dataexplorer_max_dimensions' => 5];
        $this->assertCount(5, ReportDefinition::fromArray($rows(5))->getRows());
        $this->assertTooManyRows($rows(6), 5);

        Config::getInstance()->DataExplorer = ['dataexplorer_max_dimensions' => 12];
        $this->assertCount(9, ReportDefinition::fromArray($rows(9))->getRows());
        $this->assertTooManyRows($rows(10), 9);
    }

    private function assertTooManyRows(array $raw, int $max): void
    {
        try {
            ReportDefinition::fromArray($raw);
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertSame(Piwik::translate('DataExplorer_ErrorTooManyRows', [$max]), $e->getMessage());
        }
    }

    private function valid(array $override = []): array
    {
        return array_merge([
            'name' => 'Pages by country',
            'type' => 'visual',
            'technique' => 'freeform',
            'report_type' => 'table',
            'rows' => ['Actions.PageUrl'],
            'column' => null,
            'metrics' => ['nb_visits'],
            'filters' => [['dimension' => 'UserCountry.Country', 'operator' => 'eq', 'value' => 'fr']],
            'segment' => '',
            'sql' => '',
        ], $override);
    }

    public function testValidVisualTable(): void
    {
        $def = ReportDefinition::fromArray($this->valid(['column' => 'UserCountry.Country']));

        $this->assertSame('visual', $def->getType());
        $this->assertSame('freeform', $def->getTechnique());
        $this->assertSame('table', $def->getReportType());
        $this->assertSame(['Actions.PageUrl'], $def->getRows());
        $this->assertSame('UserCountry.Country', $def->getColumn());
        $this->assertSame(['nb_visits'], $def->getMetrics());
        $this->assertSame('fr', $def->getFilters()[0]['value']);
        $this->assertSame($def->toArray(), ReportDefinition::fromArray($def->toArray())->toArray());
    }

    public function testChart_isKeptForTables_only(): void
    {
        $this->assertSame('pie', ReportDefinition::fromArray($this->valid(['chart' => 'pie']))->getChart());
        $this->assertSame('bar', ReportDefinition::fromArray($this->valid(['chart' => 'bar']))->toArray()['chart']);
        $this->assertSame('', ReportDefinition::fromArray($this->valid(['chart' => 'radar']))->getChart());
        $this->assertSame('', ReportDefinition::fromArray($this->valid())->getChart());
        $this->assertSame('', ReportDefinition::fromArray($this->valid(['report_type' => 'evolution', 'rows' => [], 'chart' => 'pie']))->getChart());
    }

    public function testFilterMatch_anyNeedsTwoFilters(): void
    {
        $two = [
            ['dimension' => 'UserCountry.Country', 'operator' => 'equals', 'value' => 'fr'],
            ['dimension' => 'UserCountry.Country', 'operator' => 'equals', 'value' => 'de'],
        ];
        $this->assertSame('any', ReportDefinition::fromArray($this->valid(['filters' => $two, 'filter_match' => 'any']))->getFilterMatch());
        $this->assertSame('all', ReportDefinition::fromArray($this->valid(['filters' => $two, 'filter_match' => 'or 1=1']))->getFilterMatch());
        $this->assertSame('all', ReportDefinition::fromArray($this->valid(['filter_match' => 'any']))->getFilterMatch(), 'one filter is always all');
        $this->assertSame('any', ReportDefinition::fromArray($this->valid(['filters' => $two, 'filter_match' => 'any']))->toArray()['filter_match']);
    }

    public function testValidEvolutionAndKpiCard(): void
    {
        $evo = ReportDefinition::fromArray($this->valid(['report_type' => 'evolution', 'rows' => [], 'metrics' => ['a', 'b']]));
        $kpi = ReportDefinition::fromArray($this->valid(['report_type' => 'kpicard', 'rows' => []]));
        $this->assertSame('evolution', $evo->getReportType());
        $this->assertSame('kpicard', $kpi->getReportType());
    }

    public function testValidSql(): void
    {
        $def = ReportDefinition::fromArray($this->valid(['type' => 'sql', 'rows' => [], 'metrics' => [], 'sql' => 'SELECT 1']));
        $this->assertSame('SELECT 1', $def->getSql());
    }

    public function testVisualDefinitionDropsSqlText(): void
    {
        $def = ReportDefinition::fromArray($this->valid(['sql' => 'SELECT login FROM user']));

        $this->assertSame('', $def->getSql());
        $this->assertSame('', $def->toArray()['sql']);
    }

    public function testSqlDefinitionKeepsOnlyTheQuery(): void
    {
        $def = ReportDefinition::fromArray($this->valid(['type' => 'sql', 'sql' => 'SELECT 1', 'segment' => 'visitCount>1']));

        $this->assertSame([], $def->getRows());
        $this->assertSame([], $def->getMetrics());
        $this->assertSame([], $def->getFilters());
        $this->assertSame('', $def->getSegment());
        $this->assertSame(
            ['name' => 'Pages by country', 'type' => 'sql', 'technique' => 'freeform', 'report_type' => 'table', 'sql' => 'SELECT 1'],
            $def->toArray()
        );
    }

    /**
     * @dataProvider invalidProvider
     */
    public function testInvalidDefinitionsAreRefused(array $override, string $key, array $params = []): void
    {
        try {
            ReportDefinition::fromArray($this->valid($override));
            $this->fail('Expected InvalidArgumentException');
        } catch (\InvalidArgumentException $e) {
            $this->assertNotSame('', $e->getMessage());
            $this->assertSame(Piwik::translate($key, $params), $e->getMessage());
        }
    }

    public function invalidProvider(): array
    {
        $ev = ['report_type' => 'evolution'];
        $kpi = ['report_type' => 'kpicard'];

        return [
            'no row' => [['rows' => []], 'DataExplorer_ErrorRowRequired'],
            'four rows' => [['rows' => ['a', 'b', 'c', 'd']], 'DataExplorer_ErrorTooManyRows', [3]],
            'pivot on evolution' => [$ev + ['rows' => [], 'column' => 'a'], 'DataExplorer_ErrorColumnOnlyForTable'],
            'pivot on kpicard' => [$kpi + ['rows' => [], 'column' => 'a'], 'DataExplorer_ErrorColumnOnlyForTable'],
            'eleven metrics' => [['metrics' => array_map('strval', range(1, 11))], 'DataExplorer_ErrorTooManyMetrics', [10]],
            'no metric' => [['metrics' => []], 'DataExplorer_ErrorMetricRequired'],
            'kpicard two metrics' => [$kpi + ['rows' => [], 'metrics' => ['a', 'b']], 'DataExplorer_ErrorKpiCardOneMetric'],
            'evolution with row' => [$ev + ['rows' => ['a']], 'DataExplorer_ErrorRowsOnlyForTable'],
            'other technique' => [['technique' => 'funnel'], 'DataExplorer_ErrorTechniqueInvalid'],
            'sql empty' => [['type' => 'sql', 'sql' => '  '], 'DataExplorer_ErrorSqlEmpty'],
            'empty name' => [['name' => ''], 'DataExplorer_ErrorNameInvalid', [255]],
            'long name' => [['name' => str_repeat('a', 256)], 'DataExplorer_ErrorNameInvalid', [255]],
            'bad type' => [['type' => 'xyz'], 'DataExplorer_ErrorTypeInvalid'],
            'bad report type' => [['report_type' => 'pie'], 'DataExplorer_ErrorReportTypeInvalid'],
        ];
    }

    public function testPlacement(): void
    {
        $all = Placement::fromArray(['all' => true]);
        $this->assertTrue($all->isAllWebsites());
        $this->assertTrue($all->includes(9));

        $sites = Placement::fromArray(['sites' => [1, '2', 1]]);
        $this->assertFalse($sites->isAllWebsites());
        $this->assertSame([1, 2], $sites->getSiteIds());
        $this->assertTrue($sites->includes(2));
        $this->assertFalse($sites->includes(3));
    }

    public function testPlacementInvalid(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        Placement::fromArray(['sites' => []]);
    }
}
