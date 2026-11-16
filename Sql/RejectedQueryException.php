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

    /** @var int|null */
    private $offset;

    public function __construct(string $translatedMessage, string $offendingToken, ?int $offset = null)
    {
        parent::__construct($translatedMessage);
        $this->offendingToken = $offendingToken;
        $this->offset = $offset;
    }

    /**
     * 0-based character offset (Unicode code points, not bytes) of the offending token in the submitted SQL,
     * null when the refusal concerns the whole query (length, number of subqueries, encoding).
     */
    public function getOffset(): ?int
    {
        return $this->offset;
    }

    /**
     * The part of the query that made the guard refuse it, as written by the user (empty for an empty query).
     */
    public function getOffendingToken(): string
    {
        return $this->offendingToken;
    }
}
