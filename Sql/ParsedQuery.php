<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use PhpMyAdmin\SqlParser\Statements\SelectStatement;

/**
 * A raw SQL query accepted by SqlGuard.
 */
final class ParsedQuery
{
    /** @var SelectStatement */
    private $statement;

    /** @var SelectStatement[] */
    private $statements;

    /** @var string[] */
    private $tables;

    /**
     * @param SelectStatement[] $statements
     * @param string[] $tables
     */
    public function __construct(SelectStatement $statement, array $statements, array $tables)
    {
        $this->statement = $statement;
        $this->statements = $statements;
        $this->tables = $tables;
    }

    /**
     * The top-level statement: the first SELECT, its other UNION branches in `$statement->union`
     * as `[UnionKeyword|string, SelectStatement]` pairs.
     */
    public function getStatement(): SelectStatement
    {
        return $this->statement;
    }

    /**
     * Every SELECT of the query once, the top-level statement first: each UNION branch, each subquery
     * (derived table, scalar, IN, EXISTS, in any clause) and the UNION branches of those subqueries.
     * Each one carries its own `from` (Expression[]), `join` (JoinKeyword[], table in `->expr`, condition in `->on`)
     * and `where` (Condition[]). A derived table appears in `from` or `join` with `subquery === 'SELECT'` and its
     * own SELECT is listed separately here. The `union` property of an entry must not be walked again: its
     * branches are already in this list.
     *
     * @return SelectStatement[]
     */
    public function getStatements(): array
    {
        return $this->statements;
    }

    /**
     * Unique table names read by the query, without the Matomo table prefix, lowercase.
     *
     * @return string[]
     */
    public function getTables(): array
    {
        return $this->tables;
    }
}
