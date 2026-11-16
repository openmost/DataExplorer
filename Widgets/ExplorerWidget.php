<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Widgets;

use Piwik\Access;
use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Site;
use Piwik\Url;
use Piwik\Widget\Widget;
use Piwik\Widget\WidgetConfig;

/**
 * The explorer, on the "Data Explorer > Explore" page.
 */
class ExplorerWidget extends Widget
{
    public static function configure(WidgetConfig $config)
    {
        $config->setCategoryId(ExploreSubcategory::CATEGORY);
        $config->setSubcategoryId(ExploreSubcategory::ID);
        $config->setName('DataExplorer_ExploreWidget');
        $config->setOrder(1);
        $config->setIsWide();

        $idSite = Common::getRequestVar('idSite', 0, 'int');
        $config->setIsEnabled($idSite > 0 && Piwik::isUserHasViewAccess($idSite));
    }

    public function render()
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');
        Piwik::checkUserHasViewAccess($idSite);

        $isSuperUser = Piwik::hasUserSuperUserAccess();

        $writableSites = [];
        foreach (Access::getInstance()->getSitesIdWithAtLeastWriteAccess() as $id) {
            $writableSites[] = ['id' => (int) $id, 'name' => Common::unsanitizeInputValue(Site::getNameFor((int) $id))];
        }
        usort($writableSites, fn (array $a, array $b) => $a['id'] <=> $b['id']);

        return $this->renderTemplate('explorer', [
            'explorerProps' => [
                'is-super-user' => $isSuperUser,
                'can-save' => Piwik::isUserHasWriteAccess($idSite),
                'writable-sites' => $writableSites,
                'setup-url' => $isSuperUser ? 'index.php?' . Url::getQueryStringFromParameters([
                    'module' => 'DataExplorer',
                    'action' => 'setup',
                    'idSite' => $idSite,
                    'period' => Common::getRequestVar('period', 'day', 'string'),
                    'date' => Common::getRequestVar('date', 'today', 'string'),
                ]) : '',
            ],
        ]);
    }
}
