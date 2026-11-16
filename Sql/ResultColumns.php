<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Sql;

use Piwik\Common;
use Piwik\Config;
use Piwik\Plugins\DataExplorer\Configuration;

/**
 * The column names of a raw SQL result, read from its select list without running it: report metadata (scheduled
 * reports, exports) must declare them before the data is fetched.
 */
final class ResultColumns
{
    /**
     * @return string[]|null in select order, null when a name only exists once the query ran: "*", an expression
     *                       without alias, a parenthesized first branch, a duplicate name, or a query the guard refuses
     */
    public static function of(string $sql): ?array
    {
        if (strpos(ltrim($sql), '(') === 0) {
            return null;
        }

        $readOnly = Configuration::getReadOnlyDbConfig();
        try {
            $parsed = SqlGuard::validate(
                $sql,
                $readOnly !== null ? $readOnly['tables_prefix'] : Common::prefixTable(''),
                $readOnly !== null ? $readOnly['dbname'] : (string) Config::getInstance()->database['dbname']
            );
        } catch (RejectedQueryException $e) {
            return null;
        }

        $names = [];
        foreach ($parsed->getStatement()->expr ?? [] as $expression) {
            $name = self::name($expression);
            if ($name === null || isset($names[$name])) {
                return null;
            }
            $names[$name] = $name;
        }

        return $names ? array_values($names) : null;
    }

    private static function name($expression): ?string
    {
        if (is_string($expression->alias ?? null) && $expression->alias !== '') {
            return $expression->alias;
        }

        $column = $expression->column ?? null;
        $isPlainColumn = is_string($column) && $column !== '' && $column !== '*'
            && empty($expression->function) && empty($expression->subquery)
            && preg_match('/^[`\w.]+$/u', (string) ($expression->expr ?? '')) === 1;

        return $isPlainColumn ? $column : null;
    }
}
