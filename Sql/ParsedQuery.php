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
use PhpMyAdmin\SqlParser\Token;

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

    /** @var string */
    private $executableSql;

    /** @var Token[] */
    private $tokens;

    /** @var array<int, array<string, mixed>> */
    private $tableInstances;

    /**
     * @param SelectStatement[] $statements
     * @param string[] $tables
     * @param Token[] $tokens
     * @param array<int, array<string, mixed>> $tableInstances
     */
    public function __construct(
        SelectStatement $statement,
        array $statements,
        array $tables,
        string $executableSql,
        array $tokens,
        array $tableInstances
    ) {
        $this->statement = $statement;
        $this->statements = $statements;
        $this->tables = $tables;
        $this->executableSql = $executableSql;
        $this->tokens = $tokens;
        $this->tableInstances = $tableInstances;
    }

    /**
     * Every lexer token of the query, whitespace and comments included, in source order. The token ranges of
     * getTableInstances() index this list.
     *
     * @return Token[]
     */
    public function getTokens(): array
    {
        return $this->tokens;
    }

    /**
     * Every table reference of every SELECT, read from the token stream (not from the parser), each once:
     * - unit: "(" token index of the parenthesized SELECT holding it, -1 for the top level;
     * - branch: index of its UNION branch within that unit, so unit + branch name one SELECT and its scope;
     * - table: unprefixed lowercase table name, null for a derived table;
     * - name: the table name as written, unquoted (empty for a derived table);
     * - alias: the alias as written, unquoted, or null;
     * - position: character offset of the table name (or of the derived table's "(");
     * - on: first and last token index of the ON condition right after it, which the guard only accepts as its own
     *   join's condition (never after a NATURAL join target, always after an outer join target), or null;
     * - preserved: true when it is the target of a RIGHT JOIN, so its ON condition does not filter its rows;
     * - where: first and last token index of the WHERE condition of its SELECT, or null.
     *
     * @return array<int, array{unit: int, branch: int, table: ?string, name: string, alias: ?string, position: int, on: ?int[], preserved: bool, where: ?int[]}>
     */
    public function getTableInstances(): array
    {
        return $this->tableInstances;
    }

    /**
     * The only text to execute or wrap: the validated tokens without comments (a trailing "-- x" would swallow
     * a wrapper), without the trailing delimiter, ending with a newline. Placeholders are kept for binding.
     */
    public function getExecutableSql(): string
    {
        return $this->executableSql;
    }

    /**
     * The top-level statement: the first SELECT, its other UNION branches in `$statement->union`
     * as `[UnionKeyword|string, SelectStatement]` pairs. A parenthesized branch, "(SELECT ...) UNION (SELECT ...)",
     * appears here as a "SELECT 1" stub: its real statement is in getStatements().
     */
    public function getStatement(): SelectStatement
    {
        return $this->statement;
    }

    /**
     * Every SELECT of the query once: the top-level statement first (unless it is a parenthesized branch), each
     * UNION branch, each subquery (derived table, scalar, IN, EXISTS, in any clause) and their UNION branches.
     * Each one carries its own `from` (Expression[]), `join` (JoinKeyword[], table in `->expr`, condition in `->on`)
     * and `where` (Condition[]). A derived table appears in `from` or `join` with `subquery === 'SELECT'` and its
     * own SELECT is listed separately here. Inside a statement, the text of each nested subquery or parenthesized
     * UNION branch is a "(SELECT 1)" stub (it was parsed on its own): read it from this list. Identical SELECTs are
     * listed once. The `union` property of an entry must not be walked again: its branches are in this list.
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
