<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Integration;

use Piwik\NoAccessException;
use Piwik\Plugins\DataExplorer\API;
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Catalog\LogTables;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Tests\Framework\Fixture;
use Piwik\Tests\Framework\Mock\FakeAccess;
use Piwik\Tests\Framework\TestCase\IntegrationTestCase;

/**
 * @group DataExplorer
 * @group CatalogTest
 * @group Plugins
 */
class CatalogTest extends IntegrationTestCase
{
    public static function setUpBeforeClass(): void
    {
        self::$fixture->extraPluginsToLoad[] = 'DataExplorer';

        parent::setUpBeforeClass();
    }

    public function setUp(): void
    {
        parent::setUp();

        Fixture::createSuperUser();
        Fixture::createWebsite('2024-01-01 00:00:00');
    }

    public function provideContainerConfig()
    {
        return ['Piwik\\Access' => new FakeAccess()];
    }

    private function as(array $view = [], bool $super = false): void
    {
        FakeAccess::clearAccess($super, [], $view, 'alice', []);
    }

    public function test_dimensionCatalogContainsKnownDimensions(): void
    {
        $dimensions = (new DimensionCatalog())->all(1);

        $this->assertArrayHasKey('UserCountry.Country', $dimensions);
        $this->assertArrayHasKey('Actions.ActionUrl', $dimensions);
        $this->assertArrayHasKey('Goals.IdGoal', $dimensions);
        $this->assertSame('visit', $dimensions['UserCountry.Country']->scope);
        $this->assertSame('action', $dimensions['Actions.ActionUrl']->scope);
        $this->assertSame('conversion', $dimensions['Goals.IdGoal']->scope);
    }

    public function test_dimensionCatalogHasNoDimensionWithoutColumnOrOutsideScopes(): void
    {
        foreach ((new DimensionCatalog())->all(1) as $id => $entry) {
            $this->assertSame($id, $entry->id);
            $this->assertNotSame('', $entry->column);
            // any log table the active plugins declare and the explorer can join to a visit
            $this->assertTrue(LogTables::get()->has($entry->table), $entry->table);
        }
    }

    public function test_technicalIdDimensionsAreExcludedButTheirMetricsStay(): void
    {
        $dimensions = (new DimensionCatalog())->all(1);

        $this->assertArrayNotHasKey('CoreHome.LinkVisitActionId', $dimensions);
        $this->assertArrayNotHasKey('CoreHome.LinkVisitActionIdPages', $dimensions);
        $this->assertArrayHasKey('nb_actions', (new MetricCatalog())->all(1));
    }

    public function test_metricCatalogContainsCoreCounters(): void
    {
        $metrics = (new MetricCatalog())->all(1);

        foreach (['nb_visits', 'nb_uniq_visitors', 'nb_actions', 'nb_conversions', 'revenue', 'sum_visit_length', 'bounce_count'] as $id) {
            $this->assertArrayHasKey($id, $metrics);
        }
        $this->assertSame('COUNT(DISTINCT log_visit.idvisit)', $metrics['nb_visits']->sql);
        $this->assertSame('uniq', $metrics['nb_uniq_visitors']->aggregation);
    }

    public function test_eventMetrics_countTheEventActions(): void
    {
        $metrics = (new MetricCatalog())->all(1);

        $this->assertSame('action', $metrics['nb_events']->scope);
        $this->assertSame('sum', $metrics['nb_events']->aggregation);
        $this->assertSame('action', $metrics['sum_event_value']->scope);
    }

    public function test_additiveScopes_followTheTableOfTheMetric(): void
    {
        $metrics = (new MetricCatalog())->all(1);

        $this->assertSame(['visit'], $metrics['nb_visits']->additiveScopes());
        $this->assertSame(['visit'], $metrics['sum_visit_length']->additiveScopes());
        $this->assertSame(['visit', 'action'], $metrics['nb_actions']->additiveScopes());
        $this->assertSame(['visit', 'conversion'], $metrics['nb_conversions']->additiveScopes());
        $this->assertSame([], $metrics['nb_uniq_visitors']->additiveScopes());
    }

    private function configureCustomDimensionOnSite1(): void
    {
        Fixture::createWebsite('2024-01-01 00:00:00');
        $_GET['idSite'] = '2';
        $this->as([], true);
        \Piwik\Plugins\CustomDimensions\API::getInstance()->configureNewCustomDimension(1, 'Plan', 'visit', true);
    }

    public function test_customDimensionFollowsIdSiteArgumentNotRequest(): void
    {
        $this->configureCustomDimensionOnSite1();
        $custom = fn (int $site) => array_filter(
            array_keys((new DimensionCatalog())->all($site)),
            fn ($id) => strpos($id, 'CustomDimension.CustomDimension') === 0
        );

        // request names site 2, site 1 asked first
        $this->assertCount(1, $custom(1));
        $this->assertCount(0, $custom(2));
        $this->assertSame('2', $_GET['idSite']);

        \Piwik\Cache::getTransientCache()->flushAll();
        $this->assertCount(0, $custom(2));
        $this->assertCount(1, $custom(1));
    }

    public function test_getCatalog_viewUserGetsGroupedCatalog(): void
    {
        $this->as([1]);

        $catalog = API::getInstance()->getCatalog(1);

        $this->assertArrayHasKey('dimensions', $catalog);
        $this->assertArrayHasKey('metrics', $catalog);
        $this->assertArrayHasKey('segments', $catalog);
        $found = false;
        foreach ($catalog['dimensions'] as $group) {
            $this->assertArrayHasKey('category', $group);
            foreach ($group['items'] as $item) {
                $found = $found || $item['id'] === 'UserCountry.Country';
            }
        }
        $this->assertTrue($found);

        $nbVisits = null;
        foreach ($catalog['metrics'] as $group) {
            foreach ($group['items'] as $item) {
                if ($item['id'] === 'nb_visits') {
                    $nbVisits = $item;
                }
            }
        }
        $this->assertNotNull($nbVisits);
        $this->assertSame(['visit'], $nbVisits['additiveScopes']);
        $this->assertArrayNotHasKey('sql', $nbVisits);
    }

    public function test_getCatalog_segmentsExposeOnlyDefinitionAndName(): void
    {
        $this->as([], true);
        \Piwik\Plugins\SegmentEditor\API::getInstance()->add('My segment', 'countryCode==fr', 1, false, true);

        $catalog = API::getInstance()->getCatalog(1);

        $this->assertSame([['definition' => 'countryCode==fr', 'name' => 'My segment']], $catalog['segments']);
    }

    public function test_getCatalog_exposesTheLimitsOfTheExplorer(): void
    {
        $this->as([1]);
        $original = \Piwik\Config::getInstance()->DataExplorer;

        try {
            $this->assertSame(['maxDimensions' => 3, 'maxMetrics' => 10, 'maxColumns' => 1], API::getInstance()->getCatalog(1)['limits']);

            \Piwik\Config::getInstance()->DataExplorer = ['dataexplorer_max_dimensions' => 5];
            $this->assertSame(5, API::getInstance()->getCatalog(1)['limits']['maxDimensions']);
        } finally {
            \Piwik\Config::getInstance()->DataExplorer = $original;
        }
    }

    public function test_getCatalog_withoutAccessRefused(): void
    {
        $this->as([]);
        $this->expectException(NoAccessException::class);
        API::getInstance()->getCatalog(1);
    }
}
