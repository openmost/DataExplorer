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
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SqlParser\Token;
use Piwik\Config;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Configuration;

/**
 * Allowlist validator of raw SQL: a query passes only when every token is of a known harmless kind and the
 * parsed tree is a single SELECT (or UNION of SELECTs) reading allowed Matomo tables. Anything else is refused.
 * It never rewrites the query, so it also refuses the constructs MySQL could read differently from the lexer
 * (double quotes under ANSI_QUOTES, backslashes under NO_BACKSLASH_ESCAPES, control characters ending comments).
 */
final class SqlGuard
{
    /** Unprefixed Matomo tables a raw query may read, every other table is refused. */
    private const ALLOWED_TABLES = [
        'log_visit', 'log_link_visit_action', 'log_conversion', 'log_conversion_item', 'log_action',
        'log_page_view_time', 'site', 'site_url', 'goal', 'custom_dimensions', 'segment',
    ];

    /** Monthly archive tables, e.g. archive_numeric_2024_01. */
    private const ALLOWED_ARCHIVE_KINDS = ['numeric', 'blob'];

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

    /** First words of the keywords that join two table references. */
    private const JOIN_WORDS = ['JOIN', 'INNER', 'CROSS', 'LEFT', 'RIGHT', 'NATURAL', 'STRAIGHT_JOIN', 'OUTER'];

    /** First words of the clauses that end the table references of a SELECT. */
    private const REGION_END_WORDS = ['WHERE', 'GROUP', 'HAVING', 'WINDOW', 'ORDER', 'LIMIT', 'UNION'];

    /** Non-reserved, non-function keywords that may precede a parenthesis without naming a stored function. */
    private const KEYWORDS_BEFORE_PARENTHESIS = ['ANY', 'SOME'];

    /**
     * Unprefixed names of the readable tables, the monthly archive tables as "archive_numeric_*" and
     * "archive_blob_*". Use isAllowedTable() to filter the tables that exist.
     *
     * @return string[]
     */
    public static function allowedTables(): array
    {
        $archives = array_map(function ($kind) {
            return 'archive_' . $kind . '_*';
        }, self::ALLOWED_ARCHIVE_KINDS);

        return array_merge(array_slice(self::ALLOWED_TABLES, 0, 6), $archives, array_slice(self::ALLOWED_TABLES, 6));
    }

    /**
     * @param string $name unprefixed table name, any case
     */
    public static function isAllowedTable(string $name): bool
    {
        $name = strtolower($name);

        return in_array($name, self::ALLOWED_TABLES, true)
            || (bool) preg_match('/^archive_(' . implode('|', self::ALLOWED_ARCHIVE_KINDS) . ')_\d{4}_\d{2}$/', $name);
    }

    /** Placeholders a saved query uses for the current site and period, bound by the runner. */
    public const PLACEHOLDERS = ['{idsite}', '{date_start}', '{date_end}'];

    private const MAX_TOKEN_LENGTH_IN_MESSAGE = 60;

    /** @var Token[] */
    private $tokens;

    /** @var array<int, true> indexes of the tokens forming a placeholder */
    private $placeholderTokens = [];

    /** @var array<int, true> indexes of the tokens that are names, so a "." may follow them */
    private $nameTokens = [];

    /** @var array<int, true> indexes of the names right after a ".", never keywords for MySQL */
    private $qualifiedNames = [];

    /** @var array<int, int> token index => "(" index of its innermost enclosing group, -1 at top level */
    private $enclosing = [];

    /** @var array<int, int> "(" index => "(" index of the enclosing group, -1 at top level */
    private $groupParent = [];

    /** @var Token[] tokens of the unit being parsed, to locate what its statements refer to */
    private $unitTokens = [];

    /** @var array<int, int> "(" index => ")" index of every parenthesis */
    private $closing = [];

    /** @var array<int, int> "(" index => ")" index of every parenthesized SELECT */
    private $selectGroups = [];

    /** @var array<int, array<string, true>> unit => lowercase tables and aliases its table references declare */
    private $declared = [];

    /** @var int[] indexes of the alias tokens of every table reference */
    private $aliases = [];

    /**
     * @var array<int, array{unit: int, branch: int, table: ?string, name: string, alias: ?string, position: int, on: ?int[], preserved: bool, where: ?int[]}>
     * every table reference, see ParsedQuery::getTableInstances()
     */
    private $instances = [];

    /** @var array<string, true> lowercase names used as a schema in front of a table */
    private $schemaQualifiers = [];

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
        // Before any lexing: validation runs again at each display of a saved report and parsing cost grows with nesting.
        $maxLength = Configuration::getSqlMaxLength();
        if (strlen($sql) > $maxLength) {
            throw self::reject('DataExplorer_SqlRejectedTooLong', (string) $maxLength, '');
        }
        $this->checkCharacters($sql);

        $lexer = new Lexer($sql, false);
        $this->tokens = array_values(array_filter($lexer->list->tokens, function ($token) {
            return $token instanceof Token;
        }));

        $maxSubqueries = Configuration::getSqlMaxSubqueries();
        $selects = 0;
        foreach ($this->tokens as $token) {
            if ($this->isKeyword($token, 'SELECT') && ++$selects > $maxSubqueries + 1) {
                throw self::reject('DataExplorer_SqlRejectedTooManySubqueries', (string) $maxSubqueries, 'SELECT');
            }
        }

        $bracePositions = $this->findPlaceholders();

        foreach ($lexer->errors as $error) {
            // The lexer does not know the placeholder braces, they are its only accepted errors.
            if (!isset($bracePositions[(int) $error->pos]) || (string) $error->ch !== $bracePositions[(int) $error->pos]) {
                $token = $this->tokenAtPosition($lexer->list->tokens, (int) $error->pos);
                throw self::parserError(
                    $error,
                    $token !== null ? (string) $token->token : (string) $error->ch,
                    $token !== null ? $token->position : (int) $error->pos
                );
            }
        }

        $this->checkTokens();
        $closing = $this->matchParentheses();

        $selectGroups = [];
        foreach ($closing as $open => $close) {
            $first = $this->nextMeaningful($open);
            if ($first !== null && $this->isKeyword($first, 'SELECT')) {
                $selectGroups[$open] = $close;
            }
        }

        $segments = $this->checkStructure($selectGroups, $closing);

        $tables = [];
        $units = [-1 => $this->parseUnit($this->unitTokens(0, count($this->tokens), $selectGroups), $tables)];
        foreach ($selectGroups as $open => $close) {
            $tokens = $this->unitTokens($open + 1, $close, $selectGroups);
            $tokens[] = new Token(null, Token::TYPE_DELIMITER);
            $units[$open] = $this->parseUnit($tokens, $tables);
        }

        $this->closing = $closing;
        $this->selectGroups = $selectGroups;
        foreach ($units as $unit => $branches) {
            $this->checkBranchesMatchSegments($branches, $segments[$unit]);
            $this->checkTableGrammar($unit, $segments[$unit]);
            $this->checkTableRegions($unit, $branches, $segments[$unit]);
        }
        $this->checkQualifiers();

        $statements = [];
        foreach ($units as $unit => $branches) {
            foreach ($branches as $k => $branch) {
                if (!$segments[$unit][$k]['parenthesized']) {
                    $statements[$this->keyOf($branch)] = $statements[$this->keyOf($branch)] ?? $branch;
                }
            }
        }

        return new ParsedQuery(
            $units[-1][0],
            array_values($statements),
            array_values(array_unique($tables)),
            $this->buildExecutableSql(),
            $this->tokens,
            $this->instances
        );
    }

    /**
     * The tokens of one unit, each nested parenthesized SELECT replaced by "(SELECT 1)": it is parsed as its own
     * unit, so every token is parsed once whatever the nesting. A parenthesized UNION branch is stubbed the same way:
     * the parser still sees a branch there and the branch itself is validated as its own unit.
     *
     * @param array<int, int> $selectGroups "(" index => ")" index of each parenthesized SELECT
     * @return Token[]
     */
    private function unitTokens(int $from, int $to, array $selectGroups): array
    {
        $tokens = [];
        for ($i = $from; $i < $to; $i++) {
            if (isset($selectGroups[$i])) {
                foreach (self::stubTokens() as $stub) {
                    $stub = clone $stub;
                    $stub->position = $this->tokens[$i]->position;
                    $tokens[] = $stub;
                }
                $i = $selectGroups[$i];
                continue;
            }
            $tokens[] = $this->tokens[$i];
        }

        return $tokens;
    }

    /**
     * Index of the first meaningful token after a UNION keyword, past its ALL or DISTINCT.
     */
    private function branchStart(int $union): ?int
    {
        $next = $this->nextMeaningfulIndex($union);
        if ($next !== null && $this->tokens[$next]->type === Token::TYPE_KEYWORD
            && in_array(strtoupper((string) $this->tokens[$next]->token), ['ALL', 'DISTINCT'], true)) {
            $next = $this->nextMeaningfulIndex($next);
        }

        return $next;
    }

    /**
     * @return Token[]
     */
    private static function stubTokens(): array
    {
        static $tokens = null;
        if ($tokens === null) {
            $tokens = (new Lexer('(SELECT 1)', false))->list->tokens;
            $tokens = array_values(array_filter($tokens, function ($token) {
                return $token instanceof Token && $token->token !== null;
            }));
        }

        return $tokens;
    }

    private function buildExecutableSql(): string
    {
        $sql = '';
        foreach ($this->tokens as $token) {
            if ($token->type === Token::TYPE_DELIMITER) {
                continue;
            }
            // A space keeps the tokens on both sides of a removed comment apart.
            $sql .= $token->type === Token::TYPE_COMMENT ? ' ' : $token->token;
        }

        return trim($sql) . "\n";
    }

    /**
     * NUL ends a MySQL comment but not a lexer one, other control characters count as whitespace for MySQL only.
     */
    private function checkCharacters(string $sql): void
    {
        if (!mb_check_encoding($sql, 'UTF-8')) {
            throw self::syntax('', null);
        }
        $length = strlen($sql);
        for ($i = 0; $i < $length; $i++) {
            $byte = ord($sql[$i]);
            if (($byte < 32 && $byte !== 9 && $byte !== 10 && $byte !== 13) || $byte === 127) {
                throw self::syntax($sql[$i], mb_strlen(substr($sql, 0, $i)));
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
                throw self::notSelect($token->token, $token->position);
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
                        if (!($token->flags & (Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_COMPOSED))) {
                            $this->nameTokens[$i] = true;
                        }
                    }
                    break;
                case Token::TYPE_NONE:
                    $this->checkIdentifier($i, (string) $token->value, $this->isQualified($i), false);
                    break;
                case Token::TYPE_SYMBOL:
                    if ($token->flags !== Token::FLAG_SYMBOL_BACKTICK) {
                        throw self::token($token->token, $token->position);
                    }
                    $this->checkIdentifier($i, (string) $token->value, $this->isQualified($i), true);
                    break;
                case Token::TYPE_STRING:
                    if ($token->flags !== Token::FLAG_STRING_SINGLE_QUOTES || strpos((string) $token->token, '\\') !== false) {
                        throw self::token($token->token, $token->position);
                    }
                    break;
                case Token::TYPE_OPERATOR:
                    if ($token->token === ':=') {
                        throw self::token($token->token, $token->position);
                    }
                    if ($token->token === '.') {
                        $this->checkDot($i);
                    }
                    break;
                case Token::TYPE_DELIMITER:
                    if ($this->isEnd($token)) {
                        break;
                    }
                    if ($token->token !== ';') {
                        throw self::token($token->token, $token->position);
                    }
                    $delimiterSeen = true;
                    break;
                default:
                    throw self::token((string) $token->token, $token->position);
            }
        }

        if ($this->nextMeaningful(-1) === null) {
            throw self::notSelect('', null);
        }
    }

    /**
     * MySQL reads "1.FROM t" as the number "1." then a FROM clause, the lexer as the name "FROM" qualified by "1":
     * a dot is accepted only between two names.
     */
    private function checkDot(int $i): void
    {
        $previous = $this->previousMeaningfulIndex($i);
        if ($previous === null || !isset($this->nameTokens[$previous])) {
            throw self::token('.', $this->tokens[$i]->position);
        }

        $next = $this->nextMeaningful($i);
        $isName = $next !== null && (
            $next->type === Token::TYPE_NONE
            || $next->type === Token::TYPE_KEYWORD
            || ($next->type === Token::TYPE_SYMBOL && $next->flags === Token::FLAG_SYMBOL_BACKTICK)
            || ($next->type === Token::TYPE_OPERATOR && $next->token === '*')
        );
        if (!$isName) {
            throw self::token('.', $this->tokens[$i]->position);
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
            throw self::token($text, $token->position);
        }
    }

    private function checkKeyword(int $i): void
    {
        $token = $this->tokens[$i];
        $keyword = strtoupper((string) $token->keyword);

        if ($token->flags & Token::FLAG_KEYWORD_COMPOSED) {
            $keyword = (string) preg_replace('/\s+/', ' ', $keyword);
            if (!in_array($keyword, self::ALLOWED_COMPOSED, true)) {
                throw self::token($token->token, $token->position);
            }

            return;
        }

        if (in_array($keyword, self::DENIED_WORDS, true)) {
            throw self::token($token->token, $token->position);
        }
        if (($token->flags & Token::FLAG_KEYWORD_RESERVED) && !in_array($keyword, self::ALLOWED_RESERVED, true)) {
            throw self::token($token->token, $token->position);
        }

        $isCallable = ($token->flags & (Token::FLAG_KEYWORD_RESERVED | Token::FLAG_KEYWORD_FUNCTION | Token::FLAG_KEYWORD_DATA_TYPE))
            || in_array($keyword, self::KEYWORDS_BEFORE_PARENTHESIS, true);
        if (!$isCallable && $this->isFollowedByParenthesis($i)) {
            throw self::token($token->token, $token->position);
        }
    }

    /**
     * @param bool $qualified preceded by a dot, or quoted: never a built-in function for MySQL
     */
    private function checkIdentifier(int $i, string $name, bool $qualified, bool $quoted): void
    {
        $raw = (string) $this->tokens[$i]->token;
        $lower = strtolower($name);

        // MySQL reads a leading digit as a number when it can ("1.", "1e1"), so such names are never accepted.
        if (!$quoted && !preg_match('/^[A-Za-z_$][A-Za-z0-9_$]*$/', $name)) {
            throw self::syntax($raw, $this->tokens[$i]->position);
        }
        $this->nameTokens[$i] = true;
        if ($this->isQualified($i)) {
            $this->qualifiedNames[$i] = true;
        }

        if (in_array($lower, self::FORBIDDEN_SCHEMAS, true)) {
            throw self::table($raw, $this->tokens[$i]->position);
        }
        // Net under the parser: a prefixed name anywhere, even where no table was reported, must be an allowed table.
        if ($this->prefix !== '' && $name !== $this->databaseName && strpos($lower, $this->prefix) === 0 && !self::isAllowedTable(substr($lower, strlen($this->prefix)))) {
            throw self::table($name, $this->tokens[$i]->position);
        }
        if (!$quoted && !$qualified && in_array(strtoupper($name), self::DENIED_WORDS, true)) {
            throw self::token($raw, $this->tokens[$i]->position);
        }
        // Built-in functions are keywords: an identifier before "(" names a stored or loadable function.
        if ($this->isFollowedByParenthesis($i)) {
            throw self::token($raw, $this->tokens[$i]->position);
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
            $this->enclosing[$i] = empty($stack) ? -1 : end($stack);
            if ($token->type !== Token::TYPE_OPERATOR) {
                continue;
            }
            if ($token->token === '(') {
                $this->groupParent[$i] = $this->enclosing[$i];
                $stack[] = $i;
            } elseif ($token->token === ')') {
                if (empty($stack)) {
                    throw self::syntax(')', $token->position);
                }
                $closing[array_pop($stack)] = $i;
            }
        }
        if (!empty($stack)) {
            throw self::syntax('(', $this->tokens[end($stack)]->position);
        }
        ksort($closing);

        return $closing;
    }

    /**
     * Parses one SELECT, with its UNION branches, and validates its clauses. A subquery is a separate unit:
     * the parser keeps it as opaque text inside the outer statement.
     *
     * @param Token[] $tokens the unit, ending with the end-of-list token
     * @param string[] $tables collected unprefixed table names
     * @return SelectStatement[] the SELECT and its UNION branches
     */
    private function parseUnit(array $tokens, array &$tables): array
    {
        $this->unitTokens = $tokens;
        $parser = new Parser(new TokensList($tokens, count($tokens)), false);
        if (!empty($parser->errors)) {
            $error = $parser->errors[0];
            $token = $error->token ?? null;
            throw self::parserError($error, $token instanceof Token ? (string) $token->token : '', $token instanceof Token ? $token->position : null);
        }
        if (count($parser->statements) !== 1) {
            throw self::notSelect('', $this->statementOffset($parser->statements[1] ?? null));
        }

        $main = $parser->statements[0];
        if (!($main instanceof SelectStatement) || get_class($main) !== SelectStatement::class) {
            throw self::notSelect($this->firstTokenOf($main), $this->statementOffset($main));
        }

        $branches = [$main];
        foreach ($main->union as $union) {
            $branch = $union[1] ?? null;
            if (!($branch instanceof SelectStatement) || get_class($branch) !== SelectStatement::class || !empty($branch->union)) {
                throw self::notSelect('UNION', $this->findInUnit('UNION'));
            }
            $branches[] = $branch;
        }

        foreach ($branches as $select) {
            $this->checkSelect($select, $tables);
        }

        return $branches;
    }

    /**
     * @param string[] $tables
     */
    private function checkSelect(SelectStatement $select, array &$tables): void
    {
        if ($select->into !== null) {
            throw self::token('INTO', $this->findInUnit('INTO'));
        }
        if ($select->procedure !== null) {
            throw self::token('PROCEDURE', $this->findInUnit('PROCEDURE'));
        }
        if ($select->end_options !== null && !$select->end_options->isEmpty()) {
            $options = $select->end_options->build($select->end_options);
            throw self::token($options, $this->findInUnit((string) strtok($options, ' ')));
        }
        if ($select->options !== null && $select->options->has('MAX_STATEMENT_TIME')) {
            throw self::token('MAX_STATEMENT_TIME', $this->findInUnit('MAX_STATEMENT_TIME'));
        }

        foreach ($select->from ?: [] as $expression) {
            $this->checkTableExpression($expression, $tables);
        }
        foreach ($select->join ?: [] as $join) {
            if (!($join->expr instanceof Expression)) {
                throw self::syntax('JOIN', $this->findInUnit('JOIN'));
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
                throw self::token((string) $expression->expr, $this->findInUnit($this->firstWord((string) $expression->expr)));
            }

            return;
        }

        $table = (string) $expression->table;
        $database = (string) $expression->database;
        if ($table === '') {
            if (strtoupper(trim((string) $expression->expr)) === 'DUAL') {
                return;
            }
            throw self::token((string) $expression->expr, $this->findInUnit($this->firstWord((string) $expression->expr)));
        }

        if ($database !== '' && $database !== $this->databaseName) {
            throw self::table($database . '.' . $table, $this->findInUnit($database));
        }

        $lower = strtolower($table);
        if (!$this->isAllowedPrefixedTable($lower)) {
            throw self::table($table, $this->findInUnit($table));
        }

        $tables[] = substr($lower, strlen($this->prefix));
    }

    /**
     * Structural proof, on tokens only, that the parser cannot leave a SELECT, FROM, JOIN or UNION unchecked:
     * - a UNION sits directly in the top level or in a parenthesized SELECT (a unit), never in another parenthesis;
     * - a SELECT starts the query, follows a UNION [ALL|DISTINCT] of a unit, or opens its own parenthesis;
     * - a FROM or JOIN sits directly in a unit, or a FROM is the keyword of TRIM / SUBSTRING / EXTRACT holding no SELECT.
     * Each unit is cut into branch segments at its UNIONs; checkBranchesMatchSegments() then requires the parser to
     * have produced exactly one branch per segment with exactly the FROM and JOINs of that segment.
     *
     * @param array<int, int> $selectGroups "(" index => ")" index of each parenthesized SELECT
     * @param array<int, int> $closing "(" index => ")" index of every parenthesis
     * @return array<int, array<int, array{parenthesized: bool, FROM: int[], JOIN: int[]}>> per unit, per segment
     */
    private function checkStructure(array $selectGroups, array $closing): array
    {
        $selectsBefore = [];
        $selects = 0;
        foreach ($this->tokens as $i => $token) {
            $selectsBefore[$i] = $selects;
            if ($this->clauseOf($i) === 'SELECT') {
                $selects++;
            }
        }

        $first = $this->nextMeaningfulIndex(-1);
        $segments = [-1 => [$this->segment($first !== null && isset($selectGroups[$first]))]];
        foreach ($selectGroups as $open => $close) {
            $segments[$open] = [$this->segment(false)];
        }

        foreach ($this->tokens as $i => $token) {
            $clause = $this->clauseOf($i);
            if ($clause === null) {
                continue;
            }
            $group = $this->enclosing[$i];
            $inUnit = $group === -1 || isset($selectGroups[$group]);

            if ($clause === 'UNION') {
                if (!$inUnit) {
                    throw self::syntax((string) $token->token, $token->position);
                }
                $start = $this->branchStart($i);
                $segments[$group][] = $this->segment($start !== null && isset($selectGroups[$start]));
                continue;
            }

            if ($clause === 'SELECT') {
                $previous = $this->previousMeaningfulIndex($i);
                $startsQuery = $previous === null;
                $startsBranch = $inUnit && $previous !== null && $this->clauseOf($previous) === 'UNION'
                    || ($inUnit && $previous !== null && in_array(strtoupper((string) $this->tokens[$previous]->token), ['ALL', 'DISTINCT'], true)
                        && ($before = $this->previousMeaningfulIndex($previous)) !== null && $this->clauseOf($before) === 'UNION');
                $opensGroup = $group !== -1 && isset($selectGroups[$group]) && $this->nextMeaningfulIndex($group) === $i;
                if (!$startsQuery && !$startsBranch && !$opensGroup) {
                    throw self::syntax((string) $token->token, $token->position);
                }
                continue;
            }

            if ($inUnit) {
                $segments[$group][count($segments[$group]) - 1][$clause][] = $i;
                continue;
            }
            $isFunctionKeyword = $clause === 'FROM' && $this->isFromFunction($group)
                && $selectsBefore[$closing[$group]] === $selectsBefore[$group];
            if (!$isFunctionKeyword) {
                throw self::syntax((string) $token->token, $token->position);
            }
        }

        return $segments;
    }

    /**
     * @return array{parenthesized: bool, FROM: int[], JOIN: int[]}
     */
    private function segment(bool $parenthesized): array
    {
        return ['parenthesized' => $parenthesized, 'FROM' => [], 'JOIN' => []];
    }

    /**
     * @param SelectStatement[] $branches
     * @param array<int, array{parenthesized: bool, FROM: int[], JOIN: int[]}> $segments
     */
    private function checkBranchesMatchSegments(array $branches, array $segments): void
    {
        if (count($branches) !== count($segments)) {
            throw self::syntax('UNION', $this->findInUnit('UNION'));
        }

        foreach ($segments as $k => $segment) {
            $branch = $branches[$k];
            // A parenthesized branch is a "(SELECT 1)" stub here, its clauses are matched in its own unit.
            $expectedFrom = $segment['parenthesized'] || empty($branch->from) ? 0 : 1;
            $expectedJoins = $segment['parenthesized'] ? 0 : count($branch->join ?: [])
                + ($branch->options !== null && $branch->options->has('STRAIGHT_JOIN') ? 1 : 0);

            foreach (['FROM' => $expectedFrom, 'JOIN' => $expectedJoins] as $clause => $expected) {
                if (count($segment[$clause]) !== $expected) {
                    $at = $segment[$clause][$expected] ?? ($segment[$clause][0] ?? null);
                    throw self::syntax($clause, $at === null ? $this->findInUnit($clause) : $this->tokens[$at]->position);
                }
            }
        }
    }

    /**
     * The parser reads ", t" after a JOIN condition as part of the condition, MySQL as one more table. So in the table
     * region of each branch (its FROM up to WHERE, GROUP, HAVING, WINDOW, ORDER, LIMIT, UNION or the end of the unit),
     * the commas at the unit's own depth must separate exactly the parsed FROM entries, all before the first join.
     *
     * @param SelectStatement[] $branches
     * @param array<int, array{parenthesized: bool, FROM: int[], JOIN: int[]}> $segments
     */
    private function checkTableRegions(int $unit, array $branches, array $segments): void
    {
        $end = $unit === -1 ? count($this->tokens) : $this->closing[$unit];
        foreach ($segments as $k => $segment) {
            if ($segment['parenthesized'] || empty($segment['FROM'])) {
                continue;
            }

            $commas = [];
            $joinSeen = false;
            for ($i = $segment['FROM'][0] + 1; $i < $end; $i++) {
                if ($this->enclosing[$i] !== $unit || isset($this->qualifiedNames[$i])) {
                    continue;
                }
                $token = $this->tokens[$i];
                if ($token->type === Token::TYPE_DELIMITER) {
                    break;
                }
                if ($token->type === Token::TYPE_OPERATOR && $token->token === ',') {
                    if ($joinSeen) {
                        throw self::syntax(',', $token->position);
                    }
                    $commas[] = $i;
                    continue;
                }
                if ($token->type !== Token::TYPE_KEYWORD) {
                    continue;
                }
                $word = (string) preg_replace('/\s+/', ' ', strtoupper((string) $token->token));
                $first = (string) strtok($word, ' ');
                if (in_array($first, ['WHERE', 'GROUP', 'HAVING', 'WINDOW', 'ORDER', 'LIMIT', 'UNION'], true)) {
                    break;
                }
                if (in_array($first, ['JOIN', 'INNER', 'CROSS', 'LEFT', 'RIGHT', 'NATURAL', 'STRAIGHT_JOIN', 'OUTER'], true)) {
                    $joinSeen = true;
                }
            }

            $expected = count($branches[$k]->from ?: []) - 1;
            if (count($commas) !== $expected) {
                $at = $commas[max(0, $expected)] ?? $segment['FROM'][0];
                throw self::syntax((string) $this->tokens[$at]->token, $this->tokens[$at]->position);
            }
        }
    }

    /**
     * Token-level grammar of the table references, independent of the parser, which reads some forms differently
     * from MySQL. From FROM to the end of the region, at the unit's own depth, only these are accepted:
     * - a table: an allowlisted prefixed name, optionally "<Matomo db>.", then [PARTITION (names)] [[AS] alias]
     *   [USE|FORCE|IGNORE INDEX|KEY (names)]...;
     * - a derived table: exactly one parenthesized SELECT (its own unit, walked on its own) then [[AS] alias];
     * - between references: a comma (before any join) or JOIN-family keywords, a joined reference taking ON <condition>
     *   or USING (names).
     * Every other token, a parenthesis included, is refused where it stands. The tables and aliases seen are
     * recorded as the unit's declarations for checkQualifiers().
     *
     * @param array<int, array{parenthesized: bool, FROM: int[], JOIN: int[]}> $segments
     */
    private function checkTableGrammar(int $unit, array $segments): void
    {
        $this->declared[$unit] = $this->declared[$unit] ?? [];
        $end = $unit === -1 ? count($this->tokens) : $this->closing[$unit];
        foreach ($segments as $k => $segment) {
            if (!$segment['parenthesized'] && !empty($segment['FROM'])) {
                $this->walkTableRegion($unit, $k, $segment['FROM'][0], $end);
            }
        }
    }

    private function walkTableRegion(int $unit, int $branch, int $from, int $end): void
    {
        $cursor = $from;
        $joined = false;
        $joinTarget = false;
        $rightJoin = false;
        $naturalJoin = false;
        $outerJoin = false;
        $firstInstance = count($this->instances);
        while (true) {
            $start = $this->regionToken($cursor, $end);
            if ($start === null) {
                throw $this->rejectAtRegionEnd($cursor, $end);
            }
            $instancesBefore = count($this->instances);
            $cursor = $this->tableFactor($unit, $branch, $start, $end);
            $instance = count($this->instances) > $instancesBefore ? $instancesBefore : null;
            if ($instance !== null) {
                $this->instances[$instance]['preserved'] = $rightJoin;
            }

            $next = $this->regionToken($cursor, $end);
            $hasSpecification = $next !== null && ($this->isWord($next, 'ON') || $this->isWord($next, 'USING'));
            // MySQL reads "x RIGHT JOIN a NATURAL JOIN c ON X" as "x RIGHT JOIN (a NATURAL JOIN c) ON X": the condition
            // would look like c's own while c's rows are all kept. A NATURAL join never takes a condition of its own.
            if ($joinTarget && $naturalJoin && $hasSpecification) {
                throw self::syntax((string) $this->tokens[$next]->token, $this->tokens[$next]->position);
            }
            // MySQL requires ON or USING right after an outer join target, so a condition is never left to a later join.
            if ($joinTarget && $outerJoin && !$naturalJoin && !$hasSpecification) {
                $at = $next ?? $cursor;
                throw self::syntax((string) $this->tokens[$at]->token, $this->tokens[$at]->position);
            }
            if ($joinTarget && $next !== null && $this->isWord($next, 'ON')) {
                $cursor = $this->skipCondition($next, $end);
                if ($instance !== null && !$naturalJoin) {
                    $this->instances[$instance]['on'] = [$next + 1, $cursor];
                }
            } elseif ($joinTarget && $next !== null && $this->isWord($next, 'USING')) {
                $cursor = $this->nameList($this->nextMeaningfulIndex($next), $next);
            }

            $next = $this->regionToken($cursor, $end);
            if ($next === null) {
                $where = $this->whereCondition($unit, $cursor, $end);
                for ($i = $firstInstance; $i < count($this->instances); $i++) {
                    $this->instances[$i]['where'] = $where;
                }

                return;
            }
            $token = $this->tokens[$next];
            if ($token->type === Token::TYPE_OPERATOR && $token->token === ',' && !$joined) {
                $cursor = $next;
                $joinTarget = false;
                $rightJoin = false;
                $naturalJoin = false;
                $outerJoin = false;
                continue;
            }
            if (!$this->isJoinWord($next)) {
                throw $this->rejectTokenAt($next);
            }
            $rightJoin = $this->joinWordHas($next, 'RIGHT');
            $naturalJoin = $this->joinWordHas($next, 'NATURAL');
            $outerJoin = $rightJoin || $this->joinWordHas($next, 'LEFT');
            while ($this->clauseOf($next) !== 'JOIN') {
                $previous = $next;
                $next = $this->regionToken($next, $end);
                if ($next === null || !$this->isJoinWord($next)) {
                    throw $next === null ? $this->rejectTokenAt($previous) : $this->rejectTokenAt($next);
                }
                $rightJoin = $rightJoin || $this->joinWordHas($next, 'RIGHT');
                $naturalJoin = $naturalJoin || $this->joinWordHas($next, 'NATURAL');
                $outerJoin = $outerJoin || $rightJoin || $this->joinWordHas($next, 'LEFT');
            }
            $cursor = $next;
            $joined = true;
            $joinTarget = true;
        }
    }

    /**
     * Token range of the WHERE condition following the table region that ends at $cursor, null without WHERE.
     *
     * @return int[]|null first and last token index of the condition
     */
    private function whereCondition(int $unit, int $cursor, int $end): ?array
    {
        $where = $this->nextMeaningfulIndex($cursor);
        if ($where === null || $where >= $end || !$this->isWord($where, 'WHERE')) {
            return null;
        }
        $last = $where;
        for ($i = $where + 1; $i < $end; $i++) {
            $token = $this->tokens[$i];
            if ($this->isEnd($token) || ($this->enclosing[$i] === $unit && $token->type === Token::TYPE_DELIMITER)) {
                break;
            }
            if ($this->enclosing[$i] === $unit && in_array($this->firstWordOf($i), self::REGION_END_WORDS, true)) {
                break;
            }
            $last = $i;
        }

        return [$where + 1, $last];
    }

    /**
     * @param array{unit: int, branch: int, table: ?string, name: string, alias: ?string, position: int} $instance
     */
    private function recordInstance(array $instance): void
    {
        $this->instances[] = $instance + ['on' => null, 'preserved' => false, 'where' => null];
    }

    /**
     * @return int index of the last token of the table reference starting at $start
     */
    private function tableFactor(int $unit, int $branch, int $start, int $end): int
    {
        $token = $this->tokens[$start];
        if ($token->type === Token::TYPE_OPERATOR && $token->token === '(') {
            if (!isset($this->selectGroups[$start])) {
                throw self::syntax('(', $token->position);
            }

            $aliasesBefore = count($this->aliases);
            $cursor = $this->alias($unit, $this->selectGroups[$start], $end);
            $this->recordInstance([
                'unit' => $unit, 'branch' => $branch, 'table' => null, 'name' => '',
                'alias' => count($this->aliases) > $aliasesBefore ? self::rawName($this->tokens[$cursor]) : null,
                'position' => (int) $token->position,
            ]);

            return $cursor;
        }
        if ($token->type === Token::TYPE_KEYWORD && strtoupper((string) $token->value) === 'DUAL') {
            return $start;
        }
        if (!isset($this->nameTokens[$start]) || isset($this->qualifiedNames[$start])) {
            throw $this->rejectTokenAt($start);
        }

        $table = $start;
        $dot = $this->nextMeaningfulIndex($start);
        if ($dot !== null && $this->tokens[$dot]->type === Token::TYPE_OPERATOR && $this->tokens[$dot]->token === '.') {
            $schema = strtolower((string) $token->value);
            if ($schema !== strtolower($this->databaseName)) {
                throw self::table((string) $token->value, $token->position);
            }
            $this->schemaQualifiers[$schema] = true;
            $table = (int) $this->nextMeaningfulIndex($dot);
            $after = $this->nextMeaningfulIndex($table);
            if ($after !== null && $this->tokens[$after]->type === Token::TYPE_OPERATOR && $this->tokens[$after]->token === '.') {
                throw self::syntax('.', $this->tokens[$after]->position);
            }
        }
        $name = strtolower((string) $this->tokens[$table]->value);
        if (!$this->isAllowedPrefixedTable($name)) {
            throw self::table((string) $this->tokens[$table]->value, $this->tokens[$table]->position);
        }
        $this->declared[$unit][$name] = true;
        $cursor = $table;

        $next = $this->regionToken($cursor, $end);
        if ($next !== null && $this->isWord($next, 'PARTITION')) {
            $cursor = $this->nameList($this->nextMeaningfulIndex($next), $next);
        }
        $aliasesBefore = count($this->aliases);
        $cursor = $this->alias($unit, $cursor, $end);
        $this->recordInstance([
            'unit' => $unit, 'branch' => $branch, 'table' => substr($name, strlen($this->prefix)),
            'name' => self::rawName($this->tokens[$table]),
            'alias' => count($this->aliases) > $aliasesBefore ? self::rawName($this->tokens[$cursor]) : null,
            'position' => (int) $this->tokens[$table]->position,
        ]);
        while (($next = $this->regionToken($cursor, $end)) !== null
            && ($this->isWord($next, 'USE') || $this->isWord($next, 'FORCE') || $this->isWord($next, 'IGNORE'))) {
            $kind = $this->nextMeaningfulIndex($next);
            if ($kind === null || !($this->isWord($kind, 'INDEX') || $this->isWord($kind, 'KEY'))) {
                throw $this->rejectTokenAt($kind ?? $next);
            }
            $cursor = $this->nameList($this->nextMeaningfulIndex($kind), $kind);
        }

        return $cursor;
    }

    /**
     * An optional "[AS] alias" after the token at $cursor.
     *
     * @return int index of the last token consumed
     */
    private function alias(int $unit, int $cursor, int $end): int
    {
        $next = $this->regionToken($cursor, $end);
        if ($next === null) {
            return $cursor;
        }
        $hasAs = $this->isWord($next, 'AS');
        if ($hasAs) {
            $cursor = $next;
            $next = $this->regionToken($next, $end);
        }
        if ($next === null || !isset($this->nameTokens[$next]) || isset($this->qualifiedNames[$next])) {
            if ($hasAs) {
                throw $next === null ? $this->rejectTokenAt($cursor) : $this->rejectTokenAt($next);
            }

            return $cursor;
        }
        $this->aliases[] = $next;
        $this->declared[$unit][strtolower((string) $this->tokens[$next]->value)] = true;

        return $next;
    }

    /**
     * "( name [, name]... )" or "()" at $open, as after USING, PARTITION or an index hint.
     *
     * @return int index of the ")"
     */
    private function nameList(?int $open, int $after): int
    {
        if ($open === null || $this->tokens[$open]->type !== Token::TYPE_OPERATOR || $this->tokens[$open]->token !== '(') {
            throw $this->rejectTokenAt($open ?? $after);
        }
        $close = $this->closing[$open];
        $expectName = true;
        for ($i = $this->nextMeaningfulIndex($open); $i !== null && $i < $close; $i = $this->nextMeaningfulIndex($i)) {
            $token = $this->tokens[$i];
            $isComma = $token->type === Token::TYPE_OPERATOR && $token->token === ',';
            $isName = isset($this->nameTokens[$i]) && !isset($this->qualifiedNames[$i]);
            if ($expectName ? !$isName : !$isComma) {
                throw $this->rejectTokenAt($i);
            }
            $expectName = !$expectName;
        }
        if ($expectName && $this->nextMeaningfulIndex($open) !== $close) {
            throw self::syntax(')', $this->tokens[$close]->position);
        }

        return $close;
    }

    /**
     * The condition after ON runs to the next JOIN-family keyword, comma or end of the region at the unit's depth.
     *
     * @return int index of the last token of the condition
     */
    private function skipCondition(int $on, int $end): int
    {
        $cursor = $on;
        while (($next = $this->regionToken($cursor, $end)) !== null) {
            $token = $this->tokens[$next];
            if ($token->type === Token::TYPE_OPERATOR && $token->token === ',' || $this->isJoinWord($next)) {
                break;
            }
            if ($this->isWord($next, 'ON') || $this->isWord($next, 'USING')) {
                throw $this->rejectTokenAt($next);
            }
            $cursor = $token->type === Token::TYPE_OPERATOR && $token->token === '(' ? $this->closing[$next] : $next;
        }
        if ($cursor === $on) {
            throw $this->rejectTokenAt($on);
        }

        return $cursor;
    }

    /**
     * Index of the next meaningful token of the table region, null at its end: the end of the unit, a delimiter or a
     * clause keyword at the unit's own depth.
     */
    private function regionToken(int $i, int $end): ?int
    {
        $next = $this->nextMeaningfulIndex($i);
        if ($next === null || $next >= $end || $this->tokens[$next]->type === Token::TYPE_DELIMITER) {
            return null;
        }

        return in_array($this->firstWordOf($next), self::REGION_END_WORDS, true) ? null : $next;
    }

    private function rejectAtRegionEnd(int $cursor, int $end): RejectedQueryException
    {
        $next = $this->nextMeaningfulIndex($cursor);

        return $this->rejectTokenAt($next !== null && $next <= $end && $next < count($this->tokens) ? $next : $cursor);
    }

    private function rejectTokenAt(int $i): RejectedQueryException
    {
        $token = $this->tokens[$i];
        if (isset($this->nameTokens[$i]) && $token->type !== Token::TYPE_KEYWORD) {
            return self::table((string) $token->value, $token->position);
        }

        return self::syntax((string) $token->token, $token->position);
    }

    private function isJoinWord(int $i): bool
    {
        $word = $this->firstWordOf($i);
        if (!in_array($word, self::JOIN_WORDS, true)) {
            return false;
        }

        // LEFT( and RIGHT( are the string functions.
        return !(($word === 'LEFT' || $word === 'RIGHT') && $this->isFollowedByParenthesis($i));
    }

    private function joinWordHas(int $i, string $word): bool
    {
        return (bool) preg_match('/(^|\s)' . $word . '(\s|$)/', strtoupper((string) $this->tokens[$i]->token));
    }

    private function isWord(int $i, string $word): bool
    {
        $token = $this->tokens[$i];

        return $token->type === Token::TYPE_KEYWORD && !isset($this->qualifiedNames[$i])
            && strtoupper((string) $token->token) === $word;
    }

    /**
     * @return string first word of an unqualified keyword token, upper case, '' for any other token
     */
    private function firstWordOf(int $i): string
    {
        $token = $this->tokens[$i];
        if ($token->type !== Token::TYPE_KEYWORD || isset($this->qualifiedNames[$i])) {
            return '';
        }

        return (string) strtok(strtoupper(trim((string) $token->token)), " \t\r\n");
    }

    /**
     * A name as written, unquoted, in its own case: the lexer upper-cases the value of a keyword token.
     */
    public static function rawName(Token $token): string
    {
        if ($token->type === Token::TYPE_SYMBOL) {
            return (string) $token->value;
        }

        return (string) $token->token;
    }

    private function isAllowedPrefixedTable(string $name): bool
    {
        // substr() rather than strpos(): PHP 7 refuses an empty needle, and the prefix may be empty.
        return substr($name, 0, strlen($this->prefix)) === $this->prefix && self::isAllowedTable(substr($name, strlen($this->prefix)));
    }

    /**
     * Second net under the parser: a qualified name "q.x" resolves only against the tables and aliases declared in its
     * own unit or an enclosing one (correlated subqueries), a prefixed allowlisted table, or the Matomo database
     * followed by an allowlisted table; "q.x.y" only as "<Matomo db>.<allowlisted table>.<column>". An alias may not
     * be named like the Matomo database or like any name the query uses as a schema, so it cannot pass for one.
     */
    private function checkQualifiers(): void
    {
        $chains = [];
        foreach ($this->tokens as $i => $token) {
            if (!isset($this->nameTokens[$i]) || isset($this->qualifiedNames[$i])) {
                continue;
            }
            $chain = [$i];
            $j = $i;
            while (($dot = $this->nextMeaningfulIndex($j)) !== null && $this->tokens[$dot]->type === Token::TYPE_OPERATOR
                && $this->tokens[$dot]->token === '.' && ($j = $this->nextMeaningfulIndex($dot)) !== null) {
                $chain[] = $j;
            }
            if (count($chain) > 1) {
                $chains[] = $chain;
                if (count($chain) > 2) {
                    $this->schemaQualifiers[strtolower((string) $token->value)] = true;
                }
            }
        }

        $database = strtolower($this->databaseName);
        foreach ($this->aliases as $alias) {
            $name = strtolower((string) $this->tokens[$alias]->value);
            if ($name === $database || isset($this->schemaQualifiers[$name])) {
                throw self::table((string) $this->tokens[$alias]->value, $this->tokens[$alias]->position);
            }
        }

        foreach ($chains as $chain) {
            $names = array_map(function ($index) {
                return strtolower((string) $this->tokens[$index]->value);
            }, $chain);
            $isDatabase = $names[0] === $database;

            if (count($chain) === 2) {
                $accepted = $this->isDeclaredInScope($names[0], $this->unitOf($chain[0]))
                    || $this->isAllowedPrefixedTable($names[0]) || ($isDatabase && $this->isAllowedPrefixedTable($names[1]));
            } else {
                $accepted = count($chain) === 3 && $isDatabase && $this->isAllowedPrefixedTable($names[1]);
            }
            if (!$accepted) {
                throw self::table((string) $this->tokens[$chain[0]]->value, $this->tokens[$chain[0]]->position);
            }
        }
    }

    /**
     * "(" index of the innermost parenthesized SELECT holding the token, -1 for the top level.
     */
    private function unitOf(int $i): int
    {
        $group = $this->enclosing[$i];
        while ($group !== -1 && !isset($this->selectGroups[$group])) {
            $group = $this->groupParent[$group];
        }

        return $group;
    }

    private function isDeclaredInScope(string $name, int $unit): bool
    {
        while (true) {
            if (isset($this->declared[$unit][$name])) {
                return true;
            }
            if ($unit === -1) {
                return false;
            }
            $unit = $this->unitOf($unit);
        }
    }

    /**
     * @return string|null "SELECT", "FROM", "JOIN" or "UNION" when the token is such a keyword for MySQL
     */
    private function clauseOf(int $i): ?string
    {
        $token = $this->tokens[$i];
        if (isset($this->qualifiedNames[$i]) || isset($this->placeholderTokens[$i])
            || ($token->type !== Token::TYPE_KEYWORD && $token->type !== Token::TYPE_NONE)) {
            return null;
        }

        $word = (string) preg_replace('/\s+/', ' ', strtoupper((string) $token->token));
        if ($word === 'SELECT' || $word === 'FROM') {
            return $word;
        }
        if ($word === 'UNION' || $word === 'UNION ALL' || $word === 'UNION DISTINCT') {
            return 'UNION';
        }
        if ($word === 'JOIN' || $word === 'STRAIGHT_JOIN' || substr($word, -5) === ' JOIN') {
            return 'JOIN';
        }

        return null;
    }

    /**
     * Functions whose own syntax has a FROM keyword: TRIM(x FROM y), SUBSTRING(x FROM n), EXTRACT(unit FROM d).
     */
    private function isFromFunction(int $open): bool
    {
        $previous = $this->previousMeaningfulIndex($open);
        if ($previous === null || isset($this->qualifiedNames[$previous])) {
            return false;
        }
        $token = $this->tokens[$previous];

        return $token->type === Token::TYPE_KEYWORD
            && in_array(strtoupper((string) $token->token), ['TRIM', 'SUBSTRING', 'SUBSTR', 'MID', 'EXTRACT'], true);
    }

    private function keyOf(SelectStatement $select): string
    {
        $copy = clone $select;
        $copy->union = [];

        return $copy->build();
    }

    /**
     * Position of the first token of the current unit spelling $word (keyword, name or quoted name), any case.
     */
    private function findInUnit(string $word): ?int
    {
        $word = strtolower(trim($word, '`'));
        foreach ($this->unitTokens as $token) {
            if ($token->token !== null && $token->type !== Token::TYPE_STRING && $token->type !== Token::TYPE_COMMENT
                && (strtolower((string) $token->token) === $word || strtolower((string) $token->value) === $word)) {
                return $token->position;
            }
        }

        return null;
    }

    private function firstWord(string $expression): string
    {
        return preg_match('/[A-Za-z0-9_$]+/', $expression, $match) ? $match[0] : $expression;
    }

    private function statementOffset($statement): ?int
    {
        if (!is_object($statement) || !isset($statement->first) || !isset($this->unitTokens[$statement->first])) {
            return null;
        }
        $first = $this->unitTokens[$statement->first];
        if ($first->type === Token::TYPE_WHITESPACE || $first->type === Token::TYPE_DELIMITER) {
            foreach (array_slice($this->unitTokens, $statement->first) as $token) {
                if ($token->type !== Token::TYPE_WHITESPACE && $token->type !== Token::TYPE_DELIMITER && $token->type !== Token::TYPE_COMMENT) {
                    return $token->position;
                }
            }
        }

        return $first->position;
    }

    private function firstTokenOf($statement): string
    {
        $built = method_exists($statement, 'build') ? (string) $statement->build() : '';

        return (string) strtok($built, " \n\t");
    }

    private function isQualified(int $i): bool
    {
        $previous = $this->previousMeaningfulIndex($i);

        return $previous !== null && $this->tokens[$previous]->type === Token::TYPE_OPERATOR && $this->tokens[$previous]->token === '.';
    }

    private function isFollowedByParenthesis(int $i): bool
    {
        $next = $this->nextMeaningful($i);

        return $next !== null && $next->type === Token::TYPE_OPERATOR && $next->token === '(';
    }

    private function nextMeaningfulIndex(int $i): ?int
    {
        $count = count($this->tokens);
        for ($j = $i + 1; $j < $count; $j++) {
            $token = $this->tokens[$j];
            if ($this->isEnd($token)) {
                return null;
            }
            if ($token->type !== Token::TYPE_WHITESPACE && $token->type !== Token::TYPE_COMMENT) {
                return $j;
            }
        }

        return null;
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

    private function previousMeaningfulIndex(int $i): ?int
    {
        for ($j = $i - 1; $j >= 0; $j--) {
            $type = $this->tokens[$j]->type;
            if ($type !== Token::TYPE_WHITESPACE && $type !== Token::TYPE_COMMENT) {
                return $j;
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
     * @return Token|null the token holding the error position: the lexer reports an unterminated string at its end
     */
    private function tokenAtPosition(array $tokens, int $position): ?Token
    {
        $found = null;
        foreach ($tokens as $token) {
            if ($token instanceof Token && $token->token !== null && $token->position !== null && $token->position <= $position) {
                $found = $token;
            }
        }

        return $found;
    }

    private static function token(string $offending, ?int $offset): RejectedQueryException
    {
        return self::rejectToken('DataExplorer_SqlRejectedToken', $offending, $offset);
    }

    private static function table(string $offending, ?int $offset): RejectedQueryException
    {
        return self::rejectToken('DataExplorer_SqlRejectedTable', $offending, $offset);
    }

    private static function syntax(string $offending, ?int $offset): RejectedQueryException
    {
        return self::rejectToken('DataExplorer_SqlRejectedSyntax', $offending, $offset);
    }

    private static function notSelect(string $offending, ?int $offset): RejectedQueryException
    {
        return self::rejectToken('DataExplorer_SqlRejectedStatement', $offending, $offset);
    }

    /**
     * A lexer or parser error, with the parser's own (English) explanation.
     */
    private static function parserError(\Exception $error, string $offending, ?int $offset): RejectedQueryException
    {
        $offending = self::shorten($offending);

        return new RejectedQueryException(
            Piwik::translate('DataExplorer_SqlRejectedParser', [$offending, $error->getMessage()]),
            $offending,
            $offset
        );
    }

    private static function rejectToken(string $key, string $offending, ?int $offset): RejectedQueryException
    {
        $offending = self::shorten($offending);

        return self::reject($key, $offending, $offending, $offset);
    }

    private static function shorten(string $offending): string
    {
        if (mb_strlen($offending) > self::MAX_TOKEN_LENGTH_IN_MESSAGE) {
            return mb_substr($offending, 0, self::MAX_TOKEN_LENGTH_IN_MESSAGE) . '...';
        }

        return $offending;
    }

    private static function reject(string $key, string $parameter, string $offending, ?int $offset = null): RejectedQueryException
    {
        return new RejectedQueryException(Piwik::translate($key, [$parameter]), $offending, $offset);
    }
}
