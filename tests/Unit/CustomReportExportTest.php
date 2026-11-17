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
use Piwik\Plugins\DataExplorer\Catalog\DimensionEntry;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\CustomReportExport;

/**
 * @group DataExplorer
 * @group CustomReportExportTest
 * @group Plugins
 */
class CustomReportExportTest extends TestCase
{
    private function dimension(string $id, string $segment): DimensionEntry
    {
        return new DimensionEntry($id, $id . ' name', 'cat', 'action', 'log_action', 'name', null, null, $segment, 'text');
    }

    private function definition(array $override = []): ReportDefinition
    {
        return ReportDefinition::fromArray($override + [
            'name' => 'R',
            'rows' => ['Actions.PageUrl'],
            'metrics' => ['nb_visits'],
            'segment' => 'visitorType==returning',
        ]);
    }

    public function test_filtersBecomeTheSegment_andTheStoredSegmentIsKept(): void
    {
        $filters = [
            ['dimension' => $this->dimension('Actions.PageUrl', 'pageUrl'), 'operator' => 'contains', 'value' => 'a/b c'],
            ['dimension' => $this->dimension('Actions.PageTitle', 'pageTitle'), 'operator' => 'ends_with', 'value' => 'x'],
        ];

        $all = (new CustomReportExport())->convert($this->definition(), $filters, 1);
        $any = (new CustomReportExport())->convert($this->definition(['filters' => [
            ['dimension' => 'Actions.PageUrl', 'operator' => 'contains', 'value' => 'a'],
            ['dimension' => 'Actions.PageTitle', 'operator' => 'contains', 'value' => 'b'],
        ], 'filter_match' => 'any']), $filters, 1);

        $this->assertSame('visitorType==returning;pageUrl=@a%2Fb+c;pageTitle=$x', $all['segmentFilter']);
        $this->assertSame('visitorType==returning;pageUrl=@a%2Fb+c,pageTitle=$x', $any['segmentFilter']);
        $this->assertSame(['Actions.PageUrl'], $all['dimensionIds']);
        $this->assertSame([], $all['unsupported']);
    }

    public function test_whatACustomReportCannotHold_isListed(): void
    {
        $filters = [
            ['dimension' => $this->dimension('Actions.PageUrl', 'pageUrl'), 'operator' => 'regex', 'value' => '^a'],
            ['dimension' => $this->dimension('X.NoSegment', ''), 'operator' => 'equals', 'value' => 'v'],
        ];

        $result = (new CustomReportExport())->convert($this->definition(['column' => 'UserCountry.Country']), $filters, 1);

        $this->assertSame('visitorType==returning', $result['segmentFilter']);
        $this->assertCount(3, $result['unsupported']);
    }

    public function test_idsMapToTheCustomReportsCatalogue_byIdAliasOrName(): void
    {
        $parameters = ['metricIds' => ['nb_visits', 'nb_actions', 'nb_rare'], 'dimensionIds' => ['Mine.Country'], 'unsupported' => []];

        $mapped = (new CustomReportExport())->mapToCatalogue(
            $parameters,
            ['nb_visits' => 'Visits', 'nb_actions' => 'Actions', 'nb_rare' => 'Rare metric'],
            ['Mine.Country' => 'Country'],
            ['nb_visits' => 'Visits', 'hits' => 'Hits'],
            ['UserCountry.Country' => 'country']
        );

        $this->assertSame(['nb_visits', 'hits'], $mapped['metricIds']);
        $this->assertSame(['UserCountry.Country'], $mapped['dimensionIds']);
        $this->assertSame(['Rare metric'], $mapped['unsupported']);
    }
}
