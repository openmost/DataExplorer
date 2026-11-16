<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use PhpMyAdmin\SqlParser\Components\Expression;
use PhpMyAdmin\SqlParser\Context;
use PhpMyAdmin\SqlParser\Contexts\ContextMySql80000;
use PhpMyAdmin\SqlParser\Lexer;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\SelectStatement;
use PhpMyAdmin\SqlParser\Token;
use Piwik\Config;
use Piwik\Piwik;

/**
 * Allowlist validator of raw SQL: a query passes only when every token is of a known harmless kind and the
 * parsed tree is a single SELECT (or UNION of SELECTs) reading allowed Matomo tables. Anything else is refused.
 * It never rewrites the query, so it also refuses the constructs MySQL could read differently from the lexer
 * (double quotes under ANSI_QUOTES, backslashes under NO_BACKSLASH_ESCAPES, control characters ending comments).
 */
final class SqlGuard
{
    /** Unprefixed Matomo tables holding credentials, sessions or settings, never readable. */
    public const EXCLUDED_TABLES = [
        'user',
        'user_token_auth',
        'session',
        'option',
        'access',
        'logger_message',
        'user_dashboard',
        'brute_force_log',
        'twofactor_recovery_code',
        'user_language',
        'changes',
        'tracking_failure',
    ];

    private const FORBIDDEN_SCHEMAS = ['information_schema', 'mysql', 'performance_schema', 'sys'];

    /**
     * Functions with side effects, waits or server information, and keywords of statements or clauses other than
     * a plain read. Refused wherever they appear unquoted, even where MySQL would read them as a column name.
     */
    private const DENIED_WORDS = [
        'LOAD_FILE', 'SLEEP', 'BENCHMARK', 'GET_LOCK', 'RELEASE_LOCK', 'RELEASE_ALL_LOCKS', 'IS_FREE_LOCK',
        'IS_USED_LOCK', 'MASTER_POS_WAIT', 'SOURCE_POS_WAIT', 'WAIT_FOR_EXECUTED_GTID_SET',
        'WAIT_UNTIL_SQL_THREAD_AFTER_GTIDS', 'SYS_EXEC', 'SYS_EVAL', 'USER', 'CURRENT_USER', 'CURRENT_ROLE',
        'SYSTEM_USER', 'SESSION_USER', 'CONNECTION_ID', 'LAST_INSERT_ID', 'ROW_COUNT', 'FOUND_ROWS', 'UUID_SHORT',
        'DATABASE', 'SCHEMA', 'VERSION', 'ICU_VERSION', 'PS_CURRENT_THREAD_ID', 'PS_THREAD_ID', 'ROLES_GRAPHML',
        'INTO', 'OUTFILE', 'DUMPFILE', 'LOCK', 'UNLOCK', 'SHARE', 'NOWAIT', 'LOCKED', 'UPDATE', 'FOR', 'PROCEDURE',
        'ANALYSE', 'HANDLER', 'CALL', 'DO', 'SET', 'WITH', 'TABLE', 'VALUES', 'LATERAL', 'MAX_STATEMENT_TIME',
        'DELIMITER', 'LOAD', 'INSERT', 'DELETE',
    ];

    /** Reserved words a read-only SELECT needs, every other reserved word is refused. */
    private const ALLOWED_RESERVED = [
        'SELECT', 'FROM', 'WHERE', 'GROUP', 'BY', 'HAVING', 'ORDER', 'ASC', 'DESC', 'LIMIT', 'AS', 'ON', 'USING',
        'JOIN', 'INNER', 'CROSS', 'LEFT', 'RIGHT', 'OUTER', 'NATURAL', 'STRAIGHT_JOIN', 'UNION', 'ALL', 'DISTINCT',
        'DISTINCTROW', 'AND', 'OR', 'NOT', 'XOR', 'IS', 'NULL', 'TRUE', 'FALSE', 'IN', 'EXISTS', 'BETWEEN', 'LIKE',
        'REGEXP', 'RLIKE', 'CASE', 'WHEN', 'THEN', 'ELSE', 'IF', 'DIV', 'MOD', 'INTERVAL', 'COLLATE', 'BINARY',
        'CHAR', 'CONVERT', 'CURRENT_DATE', 'CURRENT_TIME', 'CURRENT_TIMESTAMP', 'LOCALTIME', 'LOCALTIMESTAMP',
        'UTC_DATE', 'UTC_TIME', 'UTC_TIMESTAMP', 'DAY_HOUR', 'DAY_MICROSECOND', 'DAY_MINUTE', 'DAY_SECOND',
        'HOUR_MICROSECOND', 'HOUR_MINUTE', 'HOUR_SECOND', 'MINUTE_MICROSECOND', 'MINUTE_SECOND',
        'SECOND_MICROSECOND', 'YEAR_MONTH', 'SEPARATOR', 'OVER', 'PARTITION', 'RANGE', 'DUAL', 'UNSIGNED',
        'DECIMAL', 'DOUBLE', 'FLOAT', 'REPLACE', 'REPEAT', 'USE', 'FORCE', 'IGNORE', 'INDEX', 'KEY',
        'HIGH_PRIORITY', 'SQL_BIG_RESULT', 'SQL_SMALL_RESULT', 'SQL_CALC_FOUND_ROWS', 'LEADING', 'TRAILING', 'BOTH',
    ];

    /** Multi-word keywords the lexer merges into one token, only these are accepted. */
    private const ALLOWED_COMPOSED = [
        'GROUP BY', 'ORDER BY', 'PARTITION BY', 'UNION ALL', 'UNION DISTINCT', 'WITH ROLLUP', 'CROSS JOIN',
        'INNER JOIN', 'LEFT JOIN', 'LEFT OUTER JOIN', 'RIGHT JOIN', 'RIGHT OUTER JOIN', 'NATURAL JOIN',
        'NATURAL LEFT JOIN', 'NATURAL LEFT OUTER JOIN', 'NATURAL RIGHT JOIN', 'NATURAL RIGHT OUTER JOIN', 'NOT NULL',
        'NOT IN', 'MEMBER OF',
    ];

    /** Non-reserved, non-function keywords that may precede a parenthesis without naming a stored function. */
    private const KEYWORDS_BEFORE_PARENTHESIS = ['ANY', 'SOME'];

    /** Placeholders a saved query uses for the current site and period, bound by the runner. */
    public const PLACEHOLDERS = ['{idsite}', '{date_start}', '{date_end}'];

    private const MAX_TOKEN_LENGTH_IN_MESSAGE = 60;

    /** @var Token[] */
    private $tokens;

    /** @var array<int, true> indexes of the tokens forming a placeholder */
    private $placeholderTokens = [];

    /** @var string */
    private $prefix;

    /** @var string */
    private $databaseName;

    /**
     * @param string $tablePrefix Matomo table prefix, e.g. "matomo_"
     * @param string|null $databaseName the only schema a table may be qualified with, defaults to the Matomo database
     * @throws RejectedQueryException with a translated message
     */
    public static function validate(string $sql, string $tablePrefix, ?string $databaseName = null): ParsedQuery
    {
        if (!class_exists(Parser::class)) {
            require_once __DIR__ . '/../vendor/autoload.php';
        }

        $databaseName = $databaseName ?? (string) Config::getInstance()->database['dbname'];

        $previousContext = Context::$loadedContext;
        $previousMode = Context::$MODE;
        Context::load(ContextMySql80000::class);
        Context::setMode(Context::SQL_MODE_NONE);
        try {
            return (new self(strtolower($tablePrefix), $databaseName))->run($sql);
        } finally {
            Context::load($previousContext);
            Context::setMode($previousMode);
        }
    }

    private function __construct(string $prefix, string $databaseName)
    {
        $this->prefix = $prefix;
        $this->databaseName = $databaseName;
    }

    private function run(string $sql): ParsedQuery
    {
        $this->checkCharacters($sql);

        $lexer = new Lexer($sql, false);
        $this->tokens = array_values(array_filter($lexer->list->tokens, function ($token) {
            return $token instanceof Token;
        }));
        $bracePositions = $this->findPlaceholders();

        foreach ($lexer->errors as $error) {
            // The lexer does not know the placeholder braces, they are its only accepted errors.
            if (!isset($bracePositions[(int) $error->pos]) || (string) $error->ch !== $bracePositions[(int) $error->pos]) {
                throw self::syntax($this->tokenAtPosition($lexer->list->tokens, (int) $error->pos, (string) $error->ch));
            }
        }

        $this->checkTokens();
        $closing = $this->matchParentheses();

        $statements = [];
        $tables = [];
        $main = $this->parseUnit($sql, $statements, $tables);
        $produced = [-1 => 1 + count($main->union)];

        foreach ($closing as $open => $close) {
            $first = $this->nextMeaningful($open);
            if ($first === null || !$this->isKeyword($first, 'SELECT')) {
                continue;
            }
            $inner = '';
            for ($i = $open + 1; $i < $close; $i++) {
                $inner .= $this->tokens[$i]->token;
            }
            $sub = $this->parseUnit($inner, $statements, $tables);
            $produced[$open] = 1 + count($sub->union);
        }

        $this->checkEverySelectWasParsed($closing, $produced);

        return new ParsedQuery($main, array_values($statements), array_values(array_unique($tables)));
    }

    /**
     * NUL ends a MySQL comment but not a lexer one, other control characters count as whitespace for MySQL only.
     */
    private function checkCharacters(string $sql): void
    {
        if (!mb_check_encoding($sql, 'UTF-8')) {
            throw self::syntax('');
        }
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($sql[$i]);
            if (($byte < 32 && $byte !== 9 && $byte !== 10 && $byte !== 13) || $byte === 127) {
                throw self::syntax($sql[$i]);
            }
        }
    }

    /**
     * @return array<int, string> character position => brace of each placeholder brace token
     */
    private function findPlaceholders(): array
    {
        $positions = [];
        $count = count($this->tokens);
        for ($i = 0; $i + 2 < $count; $i++) {
            $text = $this->tokens[$i]->token . $this->tokens[$i + 1]->token . $this->tokens[$i + 2]->token;
            if ($this->tokens[$i]->token === '{' && in_array($text, self::PLACEHOLDERS, true)) {
                $this->placeholderTokens += [$i => true, $i + 1 => true, $i + 2 => true];
                $positions[(int) $this->tokens[$i]->position] = '{';
                $positions[(int) $this->tokens[$i + 2]->position] = '}';
            }
        }

        return $positions;
    }

    private function checkTokens(): void
    {
        $delimiterSeen = false;
        foreach ($this->tokens as $i => $token) {
            if (isset($this->placeholderTokens[$i])) {
                continue;
            }
            if ($delimiterSeen && $token->type !== Token::TYPE_WHITESPACE && !$this->isEnd($token)) {
                throw self::notSelect($token->token);
            }

            switch ($token->type) {
                case Token::TYPE_WHITESPACE:
                case Token::TYPE_NUMBER:
                case Token::TYPE_BOOL:
                    break;
                case Token::TYPE_COMMENT:
                    $this->checkComment($token);
                    break;
                case Token::TYPE_KEYWORD:
                    if ($this->isQualified($i)) {
                        $this->checkIdentifier($i, (string) $token->value, true, false);
                    } else {
                        $this->checkKeyword($i);
                    }
                    break;
                case Token::TYPE_NONE:
                    $this->checkIdentifier($i, (string) $token->value, $this->isQualified($i), false);
                    break;
                case Token::TYPE_SYMBOL:
                    if ($token->flags !== Token::FLAG_SYMBOL_BACKTICK) {
                        throw self::token($token->token);
                    }
                    $this->checkIdentifier($i, (string) $token->value, true, true);
                    break;
                case Token::TYPE_STRING:
                    if ($token->flags !== Token::FLAG_STRING_SINGLE_QUOTES || strpos((string) $token->token, '\\') !== false) {
                        throw self::token($token->token);
                    }
                    break;
                case Token::TYPE_OPERATOR:
                    if ($token->token === ':=') {
                        throw self::token($token->token);
                    }
                    break;
                case Token::TYPE_DELIMITER:
                    if ($this->isEnd($token)) {
                        break;
                    }
                    if ($token->token !== ';') {
                        throw self::token($token->token);
                    }
                    $delimiterSeen = true;
                    break;
                default:
                    throw self::token((string) $token->token);
            }
        }

        if ($this->nextMeaningful(-1) === null) {
            throw self::notSelect('');
        }
    }

    private function checkComment(Token $token): void
    {
        $text = (string) $token->token;
        if ($token->flags === Token::FLAG_COMMENT_BASH || $token->flags === Token::FLAG_COMMENT_SQL) {
            return;
        }

        // "/*/" is a whole comment for the lexer but only an opening one for MySQL; "/*!", "/*M!" and "/*+" run code.
        $isPlainComment = $token->flags === Token::FLAG_COMMENT_C
            && strlen($text) >= 4
            && substr($text, 0, 2) === '/*'
            && substr($text, -2) === '*/'
            && !in_array($text[2], ['!', '+'], true)
            && strtoupper(substr($text, 2, 2)) !== 'M!';
        if (!$isPlainComment) {
            throw self::token($text);
        }
    }

    private function checkKeyword(int $i): void
    {
        $token = $this->tokens[$i];
        $keyword = strtoupper((string) $token->keyword);

        if ($token->flags & Token::FLAG_KEYWORD_COMPOSED) {
            $keyword = (string) preg_replace('/\s+/', ' ', $keyword);
            if (!in_array($keyword, self::ALLOWED_COMPOSED, true)) {
                throw self::token($token->token);
            }

            return;
        }

        if (in_array($keyword, self::DENIED_WORDS, true)) {
            throw self::token($token->token);
        }
        if (($token->flags & Token::FLAG_KEYWORD_RESERVED) && !in_array($keyword, self::ALLOWED_RESERVED, true)) {
            throw self::token($token->token);
        }

        $isCallable = ($token->flags & (Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_FUNCTION | Token::FLAG_KEYWORD_DATA_TYPE))
            || in_array($keyword, self::KEYWORDS_BEFORE_PARENTHESIS, true);
        if (!$isCallable && $this->isFollowedByParenthesis($i)) {
            throw self::token($token->token);
        }
    }

    /**
     * @param bool $qualified preceded by a dot, or quoted: never a built-in function for MySQL
     */
    private function checkIdentifier(int $i, string $name, bool $qualified, bool $quoted): void
    {
        $raw = (string) $this->tokens[$i]->token;
        $lower = strtolower($name);

        if (!$quoted && !preg_match('/^[A-Za-z0-9_$\x{80}-\x{10FFFF}]+$/u', $name)) {
            throw self::syntax($raw);
        }

        if (in_array($lower, self::FORBIDDEN_SCHEMAS, true)) {
            throw self::table($raw);
        }
        if (strpos($lower, $this->prefix) === 0 && in_array(substr($lower, strlen($this->prefix)), self::EXCLUDED_TABLES, true)) {
            throw self::table($name);
        }
        if (!$quoted && !$qualified && in_array(strtoupper($name), self::DENIED_WORDS, true)) {
            throw self::token($raw);
        }
        // Built-in functions are keywords: an identifier before "(" names a stored or loadable function.
        if ($this->isFollowedByParenthesis($i)) {
            throw self::token($raw);
        }
    }

    /**
     * @return array<int, int> index of each "(" token => index of its ")"
     */
    private function matchParentheses(): array
    {
        $stack = [];
        $closing = [];
        foreach ($this->tokens as $i => $token) {
            if ($token->type !== Token::TYPE_OPERATOR) {
                continue;
            }
            if ($token->token === '(') {
                $stack[] = $i;
            } elseif ($token->token === ')') {
                if (empty($stack)) {
                    throw self::syntax(')');
                }
                $closing[array_pop($stack)] = $i;
            }
        }
        if (!empty($stack)) {
            throw self::syntax('(');
        }
        ksort($closing);

        return $closing;
    }

    /**
     * Parses one SELECT, with its UNION branches, and validates its clauses. A subquery is a separate unit:
     * the parser keeps it as opaque text inside the outer statement.
     *
     * @param SelectStatement[] $statements collected statements, keyed to drop a UNION branch seen twice
     * @param string[] $tables collected unprefixed table names
     */
    private function parseUnit(string $sql, array &$statements, array &$tables): SelectStatement
    {
        $parser = new Parser($sql, false);
        if (!empty($parser->errors)) {
            $error = $parser->errors[0];
            $token = $error->token ?? null;
            throw self::syntax($token instanceof Token ? (string) $token->token : '');
        }
        if (count($parser->statements) !== 1) {
            throw self::notSelect(isset($parser->statements[1]) ? $this->firstTokenOf($parser->statements[1]) : '');
        }

        $main = $parser->statements[0];
        if (!($main instanceof SelectStatement) || get_class($main) !== SelectStatement::class) {
            throw self::notSelect($this->firstTokenOf($main));
        }

        $branches = [$main];
        foreach ($main->union as $union) {
            $branch = $union[1] ?? null;
            if (!($branch instanceof SelectStatement) || get_class($branch) !== SelectStatement::class || !empty($branch->union)) {
                throw self::notSelect('UNION');
            }
            $branches[] = $branch;
        }

        foreach ($branches as $select) {
            $this->checkSelect($select, $tables);
            $statements[$this->keyOf($select)] = $statements[$this->keyOf($select)] ?? $select;
        }

        return $main;
    }

    /**
     * @param string[] $tables
     */
    private function checkSelect(SelectStatement $select, array &$tables): void
    {
        if ($select->into !== null) {
            throw self::token('INTO');
        }
        if ($select->procedure !== null) {
            throw self::token('PROCEDURE');
        }
        if ($select->end_options !== null && !$select->end_options->isEmpty()) {
            throw self::token($select->end_options->build($select->end_options));
        }
        if ($select->options !== null && $select->options->has('MAX_STATEMENT_TIME')) {
            throw self::token('MAX_STATEMENT_TIME');
        }

        foreach ($select->from ?: [] as $expression) {
            $this->checkTableExpression($expression, $tables);
        }
        foreach ($select->join ?: [] as $join) {
            if (!($join->expr instanceof Expression)) {
                throw self::syntax('JOIN');
            }
            $this->checkTableExpression($join->expr, $tables);
        }
    }

    /**
     * @param string[] $tables
     */
    private function checkTableExpression(Expression $expression, array &$tables): void
    {
        if ($expression->subquery !== null) {
            if (strtoupper((string) $expression->subquery) !== 'SELECT') {
                throw self::token((string) $expression->expr);
            }

            return;
        }

        $table = (string) $expression->table;
        $database = (string) $expression->database;
        if ($table === '') {
            if (strtoupper(trim((string) $expression->expr)) === 'DUAL') {
                return;
            }
            throw self::token((string) $expression->expr);
        }

        if ($database !== '' && $database !== $this->databaseName) {
            throw self::table($database . '.' . $table);
        }

        $lower = strtolower($table);
        $name = strpos($lower, $this->prefix) === 0 ? substr($lower, strlen($this->prefix)) : '';
        if ($name === '' || !preg_match('/^[a-z0-9_]+$/', $name) || in_array($name, self::EXCLUDED_TABLES, true)) {
            throw self::table($table);
        }

        $tables[] = $name;
    }

    /**
     * Refuses the query if the parser silently dropped a SELECT whose tables were then never checked: every SELECT keyword
     * belongs to the top-level query or to a parenthesized subquery, and each of them parsed at least as many
     * statements as it holds SELECT keywords.
     *
     * @param array<int, int> $closing
     * @param array<int, int> $produced statements parsed per unit, -1 for the top-level query
     */
    private function checkEverySelectWasParsed(array $closing, array $produced): void
    {
        $owned = [];
        foreach ($this->tokens as $i => $token) {
            if (!$this->isKeyword($token, 'SELECT')) {
                continue;
            }
            $owner = -1;
            foreach ($closing as $open => $close) {
                if ($open < $i && $i < $close && isset($produced[$open]) && $open > $owner) {
                    $owner = $open;
                }
            }
            $owned[$owner] = ($owned[$owner] ?? 0) + 1;
        }

        foreach ($owned as $owner => $count) {
            if (($produced[$owner] ?? 0) < $count) {
                throw self::syntax('SELECT');
            }
        }
    }

    private function keyOf(SelectStatement $select): string
    {
        $copy = clone $select;
        $copy->union = [];

        return $copy->build();
    }

    private function firstTokenOf($statement): string
    {
        $built = method_exists($statement, 'build') ? (string) $statement->build() : '';

        return (string) strtok($built, " \n\t");
    }

    private function isQualified(int $i): bool
    {
        $previous = $this->previousMeaningful($i);

        return $previous !== null && $previous->type === Token::TYPE_OPERATOR && $previous->token === '.';
    }

    private function isFollowedByParenthesis(int $i): bool
    {
        $next = $this->nextMeaningful($i);

        return $next !== null && $next->type === Token::TYPE_OPERATOR && $next->token === '(';
    }

    private function nextMeaningful(int $i): ?Token
    {
        $count = count($this->tokens);
        for ($j = $i + 1; $j < $count; $j++) {
            $token = $this->tokens[$j];
            if ($this->isEnd($token)) {
                return null;
            }
            if ($token->type !== Token::TYPE_WHITESPACE && $token->type !== Token::TYPE_COMMENT) {
                return $token;
            }
        }

        return null;
    }

    private function previousMeaningful(int $i): ?Token
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            $token = $this->tokens[$j];
            if ($token->type !== Token::TYPE_WHITESPACE && $token->type !== Token::TYPE_COMMENT) {
                return $token;
            }
        }

        return null;
    }

    private function isKeyword(Token $token, string $keyword): bool
    {
        return $token->type === Token::TYPE_KEYWORD && strtoupper((string) $token->keyword) === $keyword;
    }

    private function isEnd(Token $token): bool
    {
        return $token->type === Token::TYPE_DELIMITER && $token->token === null;
    }

    /**
     * @param Token[] $tokens
     */
    private function tokenAtPosition(array $tokens, int $position, string $fallback): string
    {
        $found = null;
        foreach ($tokens as $token) {
            if ($token instanceof Token && $token->token !== null && $token->position !== null && $token->position <= $position) {
                $found = $token;
            }
        }

        return $found !== null ? (string) $found->token : $fallback;
    }

    private static function token(string $offending): RejectedQueryException
    {
        return self::reject('DataExplorer_SqlRejectedToken', $offending);
    }

    private static function table(string $offending): RejectedQueryException
    {
        return self::reject('DataExplorer_SqlRejectedTable', $offending);
    }

    private static function syntax(string $offending): RejectedQueryException
    {
        return self::reject('DataExplorer_SqlRejectedSyntax', $offending);
    }

    private static function notSelect(string $offending): RejectedQueryException
    {
        return self::reject('DataExplorer_SqlRejectedStatement', $offending);
    }

    private static function reject(string $key, string $offending): RejectedQueryException
    {
        if (mb_strlen($offending) > self::MAX_TOKEN_LENGTH_IN_MESSAGE) {
            $offending = mb_substr($offending, 0, self::MAX_TOKEN_LENGTH_IN_MESSAGE) . '...';
        }

        return new RejectedQueryException(Piwik::translate($key, [$offending]), $offending);
    }
}
