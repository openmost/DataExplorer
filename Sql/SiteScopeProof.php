<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use PhpMyAdmin\SqlParser\Token;

/**
 * Proof, before a raw SQL report is published on sites, that it only reads rows of the displayed site: every
 * instance of a per-site table, in every SELECT (UNION branches, subqueries and derived tables each on their own),
 * must be filtered on its site column by the {idsite} placeholder.
 *
 * An instance is proven by one of these forms standing as a top-level AND conjunct of the WHERE of its own SELECT,
 * or of its own JOIN ... ON condition (not when it is the target of a RIGHT JOIN, whose rows are all kept):
 *   q.col = {idsite}, {idsite} = q.col, q.col IN ({idsite})
 * where q is the instance's alias, or its table name when it has none, spelled in the same case, and col its site
 * column. The unqualified column proves it only when it is the only table reference of its SELECT. A conjunct counts
 * inside "(a AND b)" groups, never under OR, XOR, ||, NOT, a function, a CASE, a BETWEEN or a subquery.
 * Everything is read from the tokens the guard validated, not from the parser's view of the expressions.
 * Anything not matching these forms (<=>, a database-qualified column, an IN list of two) is not proven.
 */
final class SiteScopeProof
{
    /**
     * Site column of each per-site table. log_action is shared by all sites and has no site column.
     * log_page_view_time only exists from Matomo 6, with an idsite column; listed so the port needs no change.
     * segment rows of "all websites" segments (enable_only_idsite = 0) are therefore never readable by a published
     * report: only the displayed site's own segments are.
     */
    private const SITE_COLUMNS = [
        'log_visit' => 'idsite',
        'log_link_visit_action' => 'idsite',
        'log_conversion' => 'idsite',
        'log_conversion_item' => 'idsite',
        'log_page_view_time' => 'idsite',
        'site' => 'idsite',
        'site_url' => 'idsite',
        'goal' => 'idsite',
        'custom_dimensions' => 'idsite',
        'segment' => 'enable_only_idsite',
    ];

    private const PLACEHOLDER = '{idsite}';

    /** @var Token[] */
    private $tokens;

    /**
     * @return string|null null when proven or when no per-site table is read, else the alias (or unprefixed table
     *                     name) of the first table reference in source order that is not proven
     */
    public static function check(ParsedQuery $query): ?string
    {
        return (new self($query->getTokens()))->firstUnproven($query->getTableInstances());
    }

    /**
     * @param string $table unprefixed table name, lowercase
     */
    public static function siteColumn(string $table): ?string
    {
        if (preg_match('/^archive_(numeric|blob)_\d{4}_\d{2}$/', $table)) {
            return 'idsite';
        }

        return self::SITE_COLUMNS[$table] ?? null;
    }

    /**
     * @param Token[] $tokens
     */
    private function __construct(array $tokens)
    {
        $this->tokens = $tokens;
    }

    /**
     * @param array<int, array<string, mixed>> $instances
     */
    private function firstUnproven(array $instances): ?string
    {
        $perScope = [];
        foreach ($instances as $instance) {
            $scope = $instance['unit'] . ':' . $instance['branch'];
            $perScope[$scope] = ($perScope[$scope] ?? 0) + 1;
        }

        usort($instances, function ($a, $b) {
            return $a['position'] <=> $b['position'];
        });

        foreach ($instances as $instance) {
            $column = $instance['table'] === null ? null : self::siteColumn($instance['table']);
            if ($column === null) {
                continue;
            }
            $qualifier = $instance['alias'] ?? $instance['name'];
            $alone = $perScope[$instance['unit'] . ':' . $instance['branch']] === 1;

            $proven = $this->conditionProves($instance['where'], $qualifier, $column, $alone)
                || (!$instance['preserved'] && $this->conditionProves($instance['on'], $qualifier, $column, false));
            if (!$proven) {
                return $instance['alias'] ?? $instance['table'];
            }
        }

        return null;
    }

    /**
     * @param int[]|null $range first and last token index of the condition
     */
    private function conditionProves(?array $range, string $qualifier, string $column, bool $unqualifiedAllowed): bool
    {
        if ($range === null) {
            return false;
        }
        $indexes = [];
        for ($i = $range[0]; $i <= $range[1]; $i++) {
            $type = $this->tokens[$i]->type;
            if ($type !== Token::TYPE_WHITESPACE && $type !== Token::TYPE_COMMENT) {
                $indexes[] = $i;
            }
        }

        return $this->conjunctionProves($indexes, $qualifier, $column, $unqualifiedAllowed);
    }

    /**
     * @param int[] $indexes meaningful token indexes of a condition
     */
    private function conjunctionProves(array $indexes, string $qualifier, string $column, bool $unqualifiedAllowed): bool
    {
        $conjuncts = $this->conjuncts($indexes);
        if ($conjuncts === null) {
            return false;
        }

        foreach ($conjuncts as $conjunct) {
            if ($this->matchesFilter($conjunct, $qualifier, $column, $unqualifiedAllowed)) {
                return true;
            }
            $inner = $this->groupContent($conjunct);
            if ($inner !== null && $this->conjunctionProves($inner, $qualifier, $column, $unqualifiedAllowed)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Splits a condition at its top-level AND keywords. Null when it is not a plain conjunction at its top level:
     * an OR, XOR or || there binds looser than AND, so none of its parts filters the rows on its own. The AND of a
     * BETWEEN and anything between CASE and END are not top-level.
     *
     * @param int[] $indexes
     * @return int[][]|null
     */
    private function conjuncts(array $indexes): ?array
    {
        $conjuncts = [];
        $current = [];
        $depth = 0;
        $caseDepth = 0;
        $pendingBetween = 0;
        foreach ($indexes as $position => $i) {
            $token = $this->tokens[$i];
            $qualified = $position > 0 && $this->isOperator($indexes[$position - 1], '.');
            if ($this->isOperator($i, '(')) {
                $depth++;
            } elseif ($this->isOperator($i, ')')) {
                $depth--;
                if ($depth < 0) {
                    return null;
                }
            } elseif ($depth === 0 && !$qualified && $token->type === Token::TYPE_KEYWORD) {
                $word = strtoupper((string) $token->token);
                if ($word === 'CASE') {
                    $caseDepth++;
                } elseif ($word === 'END') {
                    if (--$caseDepth < 0) {
                        return null;
                    }
                } elseif ($caseDepth === 0 && ($word === 'OR' || $word === 'XOR')) {
                    return null;
                } elseif ($caseDepth === 0 && $word === 'BETWEEN') {
                    $pendingBetween++;
                } elseif ($caseDepth === 0 && $word === 'AND') {
                    if ($pendingBetween > 0) {
                        $pendingBetween--;
                    } else {
                        $conjuncts[] = $current;
                        $current = [];
                        continue;
                    }
                }
            } elseif ($depth === 0 && $caseDepth === 0 && $this->isOperator($i, '||')) {
                return null;
            }
            $current[] = $i;
        }
        if ($depth !== 0 || $caseDepth !== 0 || $pendingBetween !== 0) {
            return null;
        }
        $conjuncts[] = $current;

        return $conjuncts;
    }

    /**
     * The content of a conjunct that is one parenthesized group, null otherwise or when the group is a subquery,
     * whose condition filters its own SELECT only.
     *
     * @param int[] $conjunct
     * @return int[]|null
     */
    private function groupContent(array $conjunct): ?array
    {
        $count = count($conjunct);
        if ($count < 3 || !$this->isOperator($conjunct[0], '(') || !$this->isOperator($conjunct[$count - 1], ')')) {
            return null;
        }
        $depth = 0;
        foreach ($conjunct as $position => $i) {
            if ($this->isOperator($i, '(')) {
                $depth++;
            } elseif ($this->isOperator($i, ')')) {
                $depth--;
                if ($depth === 0 && $position !== $count - 1) {
                    return null;
                }
            }
        }
        $first = $this->tokens[$conjunct[1]];
        if ($first->type === Token::TYPE_KEYWORD && strtoupper((string) $first->token) === 'SELECT') {
            return null;
        }

        return array_slice($conjunct, 1, $count - 2);
    }

    /**
     * @param int[] $conjunct
     */
    private function matchesFilter(array $conjunct, string $qualifier, string $column, bool $unqualifiedAllowed): bool
    {
        $count = count($conjunct);
        $columnLength = 0;
        if ($count >= 3 && $this->isOperator($conjunct[1], '.')) {
            if ($this->isOperator($conjunct[0], '.') || !$this->isName($conjunct[0], $qualifier) || !$this->isColumn($conjunct[2], $column)) {
                return false;
            }
            $columnLength = 3;
        } elseif ($unqualifiedAllowed && $count >= 1 && $this->isColumn($conjunct[0], $column)) {
            $columnLength = 1;
        }

        if ($columnLength > 0) {
            $rest = array_slice($conjunct, $columnLength);
            if (count($rest) === 4 && $this->isOperator($rest[0], '=') && $this->isPlaceholder($rest, 1)) {
                return true;
            }

            return count($rest) === 6 && $this->isKeyword($rest[0], 'IN') && $this->isOperator($rest[1], '(')
                && $this->isPlaceholder($rest, 2) && $this->isOperator($rest[5], ')');
        }

        // {idsite} = column
        if ($count >= 5 && $this->isPlaceholder($conjunct, 0) && $this->isOperator($conjunct[3], '=')) {
            $rest = array_slice($conjunct, 4);
            if (count($rest) === 3) {
                return $this->isName($rest[0], $qualifier) && $this->isOperator($rest[1], '.') && $this->isColumn($rest[2], $column);
            }

            return $unqualifiedAllowed && count($rest) === 1 && $this->isColumn($rest[0], $column);
        }

        return false;
    }

    /**
     * The guard only accepts a placeholder written as three adjacent tokens.
     *
     * @param int[] $indexes
     */
    private function isPlaceholder(array $indexes, int $at): bool
    {
        if (!isset($indexes[$at + 2]) || $indexes[$at + 1] !== $indexes[$at] + 1 || $indexes[$at + 2] !== $indexes[$at] + 2) {
            return false;
        }

        return $this->tokens[$indexes[$at]]->token . $this->tokens[$indexes[$at] + 1]->token
            . $this->tokens[$indexes[$at] + 2]->token === self::PLACEHOLDER;
    }

    /**
     * Same spelling, case included: an alias differing only by case may name another table reference.
     */
    private function isName(int $i, string $name): bool
    {
        return $this->isNameToken($i) && SqlGuard::rawName($this->tokens[$i]) === $name;
    }

    private function isColumn(int $i, string $column): bool
    {
        return $this->isNameToken($i) && strtolower(SqlGuard::rawName($this->tokens[$i])) === $column;
    }

    private function isNameToken(int $i): bool
    {
        $token = $this->tokens[$i];

        return $token->type === Token::TYPE_NONE || $token->type === Token::TYPE_KEYWORD
            || ($token->type === Token::TYPE_SYMBOL && $token->flags === Token::FLAG_SYMBOL_BACKTICK);
    }

    private function isOperator(int $i, string $operator): bool
    {
        return $this->tokens[$i]->type === Token::TYPE_OPERATOR && $this->tokens[$i]->token === $operator;
    }

    private function isKeyword(int $i, string $keyword): bool
    {
        return $this->tokens[$i]->type === Token::TYPE_KEYWORD && strtoupper((string) $this->tokens[$i]->token) === $keyword;
    }
}
