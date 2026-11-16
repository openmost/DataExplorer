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
 * Raw SQL is disabled: the configured database user may do more than read the allowed Matomo tables.
 */
class NotReadOnlyException extends \Exception
{
    /** @var bool */
    private $tooWide;

    public function __construct(string $grant, bool $tooWide)
    {
        parent::__construct(Piwik::translate($tooWide ? 'DataExplorer_RawSqlGrantTooWide' : 'DataExplorer_RawSqlNotReadOnly', [$grant]));
        $this->tooWide = $tooWide;
    }

    /**
     * True when the only fault is a SELECT granted on a whole database instead of table by table.
     */
    public function isTooWide(): bool
    {
        return $this->tooWide;
    }
}
