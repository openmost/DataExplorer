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
use Piwik\Plugins\DataExplorer\Query\CustomReportImport;

/**
 * @group DataExplorer
 * @group CustomReportImportTest
 * @group Plugins
 */
class CustomReportImportTest extends TestCase
{
    /** @var DimensionEntry[] */
    private $dimensions;

    protected function setUp(): void
    {
        parent::setUp();
        $this->dimensions = [];
        foreach (['Actions.PageUrl' => 'pageUrl', 'Actions.PageTitle' => 'pageTitle', 'DevicesDetection.DeviceType' => 'deviceType', 'X.Plain' => ''] as $id => $segment) {
            $this->dimensions[$id] = new DimensionEntry($id, $id . ' name', 'cat', 'action', 'log_action', 'name', null, null, $segment, 'text');
        }
    }

    private function import(array $report, array $reportDimensions = [], array $reportMetrics = []): array
    {
        $import = new CustomReportImport(fn (string $segment) => $segment === 'deviceType', fn (string $plugin) => $plugin !== 'MarketingCampaignsReporting');

        return $import->convert($report + ['report_type' => 'table'], $this->dimensions, ['nb_visits' => 'Visits', 'nb_actions' => 'Actions'], $reportDimensions, $reportMetrics);
    }

    public function test_dimensionsAndMetrics_mapById_alias_orName(): void
    {
        $result = $this->import([
            'dimensions' => ['Actions.PageUrl', 'Other.Title', 'Unknown.Dim'],
            'metrics' => ['nb_visits', 'hits', 'conversion_rate'],
        ], ['Other.Title' => 'actions.pagetitle name', 'Unknown.Dim' => 'Unknown dimension'], ['conversion_rate' => 'Conversion rate']);

        $this->assertSame(['Actions.PageUrl', 'Actions.PageTitle'], $result['definition']['rows']);
        $this->assertSame(['nb_visits', 'nb_actions'], $result['definition']['metrics']);
        $this->assertSame(['Unknown dimension', 'Conversion rate'], $result['unsupported']);
    }

    public function test_segmentParts_becomeFilterGroups(): void
    {
        $result = $this->import(['segment_filter' => 'pageUrl=@a%2Fb,pageTitle!$x;pageTitle>=3;pageUrl!^y']);

        $this->assertSame([
            ['dimension' => 'Actions.PageUrl', 'operator' => 'contains', 'value' => 'a/b', 'group' => 0],
            ['dimension' => 'Actions.PageTitle', 'operator' => 'not_ends_with', 'value' => 'x', 'group' => 0],
            ['dimension' => 'Actions.PageTitle', 'operator' => 'gte', 'value' => '3', 'group' => 1],
            ['dimension' => 'Actions.PageUrl', 'operator' => 'not_starts_with', 'value' => 'y', 'group' => 2],
        ], $result['definition']['filters']);
        $this->assertSame('', $result['definition']['segment']);
    }

    public function test_valuesOfADefinitionEncodedTwice_areDecodedLikeCore(): void
    {
        $result = $this->import(['segment_filter' => 'pageUrl=@%252Fladeinfrastruktur%252F,pageUrl=@a%252Cb']);

        $this->assertSame([
            ['dimension' => 'Actions.PageUrl', 'operator' => 'contains', 'value' => '/ladeinfrastruktur/', 'group' => 0],
            ['dimension' => 'Actions.PageUrl', 'operator' => 'contains', 'value' => 'a,b', 'group' => 0],
        ], $result['definition']['filters']);
    }

    public function test_partsAFilterCannotHold_stayInTheSegment(): void
    {
        $result = $this->import(['segment_filter' => 'deviceType==smartphone;pageUrl=@a,visitorType==new;pageTitle==;pageTitle==t']);

        $this->assertSame('deviceType==smartphone;pageUrl=@a,visitorType==new;pageTitle==', $result['definition']['segment']);
        $this->assertSame([
            ['dimension' => 'Actions.PageTitle', 'operator' => 'equals', 'value' => 't', 'group' => 0],
        ], $result['definition']['filters']);
    }

    public function test_anEvolutionReport_hasNoRows_andTheDefinitionIsValid(): void
    {
        $result = $this->import(['report_type' => 'evolution', 'dimensions' => ['Actions.PageUrl'], 'metrics' => ['nb_visits'], 'segment_filter' => 'pageUrl=@a']);

        $this->assertSame('evolution', $result['definition']['report_type']);
        $this->assertSame([], $result['definition']['rows']);

        $definition = ReportDefinition::fromArray($result['definition'] + ['name' => 'Imported']);
        $this->assertSame([0], array_column($definition->getFilters(), 'group'));
    }

    public function test_idsOfAnInactivePlugin_areNamedOnceWithThePlugin(): void
    {
        $result = $this->import([
            'dimensions' => ['MarketingCampaignsReporting.CampaignName', 'MarketingCampaignsReporting.CampaignSource', 'Other.Some_thing'],
            'metrics' => ['nb_visits'],
        ]);

        $this->assertCount(2, $result['unsupported']);
        $this->assertSame('Some thing', $result['unsupported'][0]);
        // both campaign dimensions in one entry naming the plugin (translations are not loaded here)
        $this->assertSame('DataExplorer_ImportPluginInactive', $result['unsupported'][1]);
    }
}
