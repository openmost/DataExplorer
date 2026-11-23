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
        $url = $this->dimension('Actions.PageUrl', 'pageUrl');
        $title = $this->dimension('Actions.PageTitle', 'pageTitle');
        $separate = [
            ['dimension' => $url, 'operator' => 'contains', 'value' => 'a/b c', 'group' => 0],
            ['dimension' => $title, 'operator' => 'ends_with', 'value' => 'x', 'group' => 1],
        ];
        $together = [
            ['dimension' => $url, 'operator' => 'contains', 'value' => 'a/b c', 'group' => 0],
            ['dimension' => $title, 'operator' => 'ends_with', 'value' => 'x', 'group' => 0],
        ];

        $all = (new CustomReportExport())->convert($this->definition(), $separate, 1);
        $any = (new CustomReportExport())->convert($this->definition(), $together, 1);

        $this->assertSame('visitorType==returning;pageUrl=@a%252Fb%2520c;pageTitle=$x', $all['segmentFilter']);
        $this->assertSame('visitorType==returning;pageUrl=@a%252Fb%2520c,pageTitle=$x', $any['segmentFilter']);
        $this->assertSame(['Actions.PageUrl'], $all['dimensionIds']);
        $this->assertSame([], $all['unsupported']);
    }

    public function test_groupsAreAndedAndTheirFiltersOred(): void
    {
        $url = $this->dimension('Actions.PageUrl', 'pageUrl');
        $title = $this->dimension('Actions.PageTitle', 'pageTitle');
        $filters = [
            ['dimension' => $url, 'operator' => 'starts_with', 'value' => 'a', 'group' => 0],
            ['dimension' => $url, 'operator' => 'not_contains', 'value' => 'b', 'group' => 0],
            ['dimension' => $title, 'operator' => 'not_ends_with', 'value' => 'c', 'group' => 1],
            ['dimension' => $title, 'operator' => 'not_starts_with', 'value' => 'd', 'group' => 2],
        ];

        $result = (new CustomReportExport())->convert($this->definition(['segment' => '']), $filters, 1);

        $this->assertSame('pageUrl=^a,pageUrl!@b;pageTitle!$c;pageTitle!^d', $result['segmentFilter']);
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
