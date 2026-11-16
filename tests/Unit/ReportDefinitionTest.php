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
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;

/**
 * @group DataExplorer
 * @group ReportDefinitionTest
 * @group Plugins
 */
class ReportDefinitionTest extends TestCase
{
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

    /**
     * @dataProvider invalidProvider
     */
    public function testInvalidDefinitionsAreRefused(array $override): void
    {
        $this->expectException(\InvalidArgumentException::class);
        ReportDefinition::fromArray($this->valid($override));
    }

    public function invalidProvider(): array
    {
        return [
            'no row' => [['rows' => []]],
            'four rows' => [['rows' => ['a', 'b', 'c', 'd']]],
            'pivot on evolution' => [['report_type' => 'evolution', 'rows' => [], 'column' => 'a']],
            'pivot on kpicard' => [['report_type' => 'kpicard', 'rows' => [], 'column' => 'a']],
            'eleven metrics' => [['metrics' => range(1, 11)]],
            'no metric' => [['metrics' => []]],
            'kpicard two metrics' => [['report_type' => 'kpicard', 'rows' => [], 'metrics' => ['a', 'b']]],
            'evolution with row' => [['report_type' => 'evolution', 'rows' => ['a']]],
            'other technique' => [['technique' => 'funnel']],
            'sql empty' => [['type' => 'sql', 'sql' => '  ']],
            'empty name' => [['name' => '']],
            'long name' => [['name' => str_repeat('a', 256)]],
            'bad type' => [['type' => 'xyz']],
            'bad report type' => [['report_type' => 'pie']],
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
