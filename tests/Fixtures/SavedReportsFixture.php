<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer\tests\Fixtures;

use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;

/**
 * ExplorerFixture plus three saved reports of site 1 in a new "Explorations" subcategory of Visitors: a table
 * (countries by device), an evolution chart (visits and actions) and a KPI card (visits).
 */
class SavedReportsFixture extends ExplorerFixture
{
    public const SUBCATEGORY = 'Explorations';

    public function setUp(): void
    {
        parent::setUp();

        $dao = new ReportsDao();
        if ($dao->getForSite($this->idSite)) {
            return;
        }

        $reports = [
            ['Countries by device', 'table', ['rows' => ['UserCountry.Country', 'DevicesDetection.DeviceType'], 'metrics' => ['nb_visits', 'nb_actions']]],
            ['Visits over time', 'evolution', ['metrics' => ['nb_visits', 'nb_actions']]],
            ['Visits card', 'kpicard', ['metrics' => ['nb_visits']]],
        ];
        foreach ($reports as $order => [$name, $reportType, $definition]) {
            $dao->insert([
                'name' => $name,
                'type' => 'visual',
                'report_type' => $reportType,
                'definition' => $definition,
                'category' => 'General_Visitors',
                'subcategory' => self::SUBCATEGORY,
                'order' => $order,
                'created_by' => 'superUserLogin',
            ], Placement::fromArray(['sites' => [$this->idSite]]));
        }
    }
}
