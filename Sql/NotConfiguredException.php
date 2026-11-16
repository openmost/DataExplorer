<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use Piwik\Piwik;

/**
 * Raw SQL is disabled: no read-only database user is set in the [DataExplorer] section of config.ini.php.
 */
class NotConfiguredException extends \Exception
{
    public function __construct()
    {
        parent::__construct(Piwik::translate('DataExplorer_RawSqlNotConfigured'));
    }
}
