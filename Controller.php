<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer;

use Piwik\Common;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Reports\GetSavedReport;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlySetup;
use Piwik\ViewDataTable\Factory as ViewDataTableFactory;

class Controller extends \Piwik\Plugin\ControllerAdmin
{
    /**
     * The exploration of the explorer canvas in the native visualization of its report type, like the saved
     * report it would become. The data table reloads (sort, page, opened row) come back here with the definition.
     */
    public function preview(): string
    {
        $idSite = Common::getRequestVar('idSite', 0, 'int');
        (new ReportPermissions())->checkCanExplore($idSite);

        // an invalid definition stops here with its message; the report built by core reads the same parameter
        GetSavedReport::previewDefinition(Common::unsanitizeInputValue(Common::getRequestVar('definition', '', 'string')));
        $report = new GetSavedReport();
        if (!$report->isPreview()) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorDefinitionInvalid'));
        }

        // the visualization is chosen in the tab settings, never from a footer icon or a stored preference
        $view = ViewDataTableFactory::build($report->getDefaultTypeViewDataTable(), 'DataExplorer.getReport', 'DataExplorer.preview', true, false);
        $view->config->footer_icons = [];
        $view->config->show_title = false;

        return $view->render();
    }

    /**
     * Setup of the read-only database user raw SQL runs with (super user only).
     */
    public function setup(): string
    {
        Piwik::checkUserHasSuperUserAccess();

        $setup = new ReadOnlySetup();

        return $this->renderTemplate('setup', [
            'setupProps' => [
                'status' => $setup->status(),
                'sql-snippet' => $setup->sqlSnippet(),
                'ini-block' => $setup->iniBlock(),
                'missing-grant-lines' => $setup->missingGrantLines(),
                'is-any-host' => $setup->isAnyHost(),
                'has-tables' => $setup->tables() !== [],
            ],
        ]);
    }
}
