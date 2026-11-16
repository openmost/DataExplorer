<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer;

use Piwik\Plugins\DataExplorer\Dao\ReportsDao;

class DataExplorer extends \Piwik\Plugin
{
    public function registerEvents()
    {
        return [
            'Template.afterEventsReport' => 'renderOpenmostCommunicationAfterEvents',
            'Widget.filterWidgets' => 'addOpenmostCommunicationWidgets',
            'Template.beforeContent' => 'renderOpenmostCommunication',
            'Db.getTablesInstalled' => 'getTablesInstalled',
            'SitesManager.deleteSite.end' => 'onSiteDeleted',
        ];
    }

    public function install()
    {
        ReportsDao::install();
        Configuration::install();
    }

    public function uninstall()
    {
        ReportsDao::uninstall();
    }

    public function getTablesInstalled(&$allTablesInstalled)
    {
        $allTablesInstalled = array_merge($allTablesInstalled, ReportsDao::getTableNames());
    }

    public function onSiteDeleted($idSite)
    {
        (new ReportsDao())->removeSite((int) $idSite);
    }

    public function renderOpenmostCommunication(&$out, $layout, $module = '', $action = '')
    {
        OpenmostCommunication::beforeContent($out, (string) $layout, (string) $module, (string) $action, $this->getPluginName());
    }

    public function addOpenmostCommunicationWidgets($list)
    {
        OpenmostCommunication::filterWidgets($list, $this->getPluginName());
    }

    public function renderOpenmostCommunicationAfterEvents(&$out, $dataTable = null)
    {
        OpenmostCommunication::afterEventsReport($out, $this->getPluginName());
    }
}
