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
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\ScopeCompatibility;
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
            $this->assertContains($entry->table, ['log_visit', 'log_link_visit_action', 'log_conversion']);
        }
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

    public function test_scopeCompatibilityMatrix(): void
    {
        $metrics = (new MetricCatalog())->all(1);
        $compat = new ScopeCompatibility();

        $this->assertTrue($compat->isAllowed('action', $metrics['nb_visits']));
        $this->assertTrue($compat->isAllowed('action', $metrics['nb_uniq_visitors']));
        $this->assertFalse($compat->isAllowed('action', $metrics['sum_visit_length']));
        $this->assertFalse($compat->isAllowed('action', $metrics['bounce_count']));
        $this->assertTrue($compat->isAllowed('visit', $metrics['nb_actions']));
        $this->assertTrue($compat->isAllowed('visit', $metrics['nb_conversions']));
        $this->assertTrue($compat->isAllowed('visit', $metrics['sum_visit_length']));
        $this->assertTrue($compat->isAllowed('conversion', $metrics['nb_conversions']));
        $this->assertFalse($compat->isAllowed('conversion', $metrics['nb_actions']));
        $this->assertFalse($compat->isAllowed('conversion', $metrics['nb_visits']));
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
        $this->assertSame(['visit', 'action'], $nbVisits['allowedScopes']);
        $this->assertArrayNotHasKey('sql', $nbVisits);
    }

    public function test_getCatalog_segmentsExposeOnlyDefinitionAndName(): void
    {
        $this->as([], true);
        \Piwik\Plugins\SegmentEditor\API::getInstance()->add('My segment', 'countryCode==fr', 1, false, true);

        $catalog = API::getInstance()->getCatalog(1);

        $this->assertSame([['definition' => 'countryCode==fr', 'name' => 'My segment']], $catalog['segments']);
    }

    public function test_getCatalog_withoutAccessRefused(): void
    {
        $this->as([]);
        $this->expectException(NoAccessException::class);
        API::getInstance()->getCatalog(1);
    }
}
