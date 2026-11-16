<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer;

use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlySetup;

class Controller extends \Piwik\Plugin\ControllerAdmin
{
    /**
     * Setup of the read-only database user raw SQL runs with (super user only).
     */
    public function setup(): string
    {
        Piwik::checkUserHasSuperUserAccess();

        $setup = new ReadOnlySetup();

        return $this->renderTemplate('setup', [
            'status' => $setup->status(),
            'sqlSnippet' => $setup->sqlSnippet(),
            'iniBlock' => $setup->iniBlock(),
            'isAnyHost' => $setup->isAnyHost(),
            'hasTables' => $setup->tables() !== [],
        ]);
    }
}
