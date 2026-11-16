<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

/**
 * A raw SQL query the guard cannot prove read-only and limited to the allowed Matomo tables.
 */
class RejectedQueryException extends \Exception
{
    /** @var string */
    private $offendingToken;

    public function __construct(string $translatedMessage, string $offendingToken)
    {
        parent::__construct($translatedMessage);
        $this->offendingToken = $offendingToken;
    }

    /**
     * The part of the query that made the guard refuse it, as written by the user (empty for an empty query).
     */
    public function getOffendingToken(): string
    {
        return $this->offendingToken;
    }
}
