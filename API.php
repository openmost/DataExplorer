<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer;

use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\ScopeCompatibility;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\VisualQueryRunner;
use Piwik\Plugins\SegmentEditor\API as SegmentEditorAPI;

/**
 * API of the DataExplorer plugin.
 *
 * @method static API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Dimensions and metrics grouped by category (for the "+" pickers) and the stored segments of the site.
     *
     * @param int $idSite
     * @return array{dimensions: array, metrics: array, segments: array}
     */
    public function getCatalog(int $idSite): array
    {
        (new ReportPermissions())->checkCanExplore($idSite);

        $compatibility = new ScopeCompatibility();

        $dimensions = [];
        foreach ((new DimensionCatalog())->all($idSite) as $entry) {
            $dimensions[] = $entry->toArray();
        }

        $metrics = [];
        foreach ((new MetricCatalog())->all($idSite) as $entry) {
            $row = $entry->toArray();
            $row['allowedScopes'] = $compatibility->allowedScopes($entry);
            $metrics[] = $row;
        }

        $segments = [];
        foreach (SegmentEditorAPI::getInstance()->getAll($idSite) as $segment) {
            $segments[] = ['definition' => (string) $segment['definition'], 'name' => (string) $segment['name']];
        }

        return [
            'dimensions' => $this->groupByCategory($dimensions),
            'metrics' => $this->groupByCategory($metrics),
            'segments' => $segments,
        ];
    }

    /**
     * Runs a visual exploration live (the explorer canvas). Nothing is saved.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string $definition JSON of the exploration, same shape as a saved visual definition
     * @param string $segment combined (AND) with the segment stored in the definition
     * @return DataTable
     */
    public function runVisual(int $idSite, string $period, string $date, string $definition, string $segment = ''): DataTable
    {
        (new ReportPermissions())->checkCanExplore($idSite);

        $raw = json_decode($definition, true);
        if (!is_array($raw)) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorDefinitionInvalid'));
        }
        $raw['type'] = ReportDefinition::TYPE_VISUAL;
        if (!isset($raw['name']) || !is_string($raw['name']) || trim($raw['name']) === '') {
            $raw['name'] = 'preview';
        }

        return (new VisualQueryRunner())->run(ReportDefinition::fromArray($raw), $idSite, $period, $date, $segment);
    }

    private function groupByCategory(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['category']][] = $item;
        }

        $result = [];
        foreach ($groups as $category => $groupItems) {
            usort($groupItems, fn (array $a, array $b) => strcmp($a['name'], $b['name']));
            $result[] = ['category' => Piwik::translate($category), 'items' => $groupItems];
        }
        usort($result, fn (array $a, array $b) => strcmp($a['category'], $b['category']));

        return $result;
    }
}
