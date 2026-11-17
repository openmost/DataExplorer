<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Widgets;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Plugins\DataExplorer\Categories\ManageSubcategory;
use Piwik\Widget\Widget;
use Piwik\Widget\WidgetConfig;

/**
 * The saved reports of the website, on the "Data Explorer > Manage explorations" page.
 */
class ManageWidget extends Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId(ExploreSubcategory::CATEGORY);
        $config->setSubcategoryId(ManageSubcategory::ID);
        $config->setName('DataExplorer_ManageReports');
        $config->setOrder(1);
        $config->setIsWide();
        $config->setIsNotWidgetizable();

        $idSite = Common::getRequestVar('idSite', 0, 'int');
        $config->setIsEnabled($idSite > 0 && !Piwik::isUserIsAnonymous() && Piwik::isUserHasViewAccess($idSite));
    }

    public function render()
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');
        (new ReportPermissions())->checkCanExplore($idSite);

        return $this->renderTemplate('manage', [
            'managerProps' => [
                'is-super-user' => Piwik::hasUserSuperUserAccess(),
            ],
        ]);
    }
}
