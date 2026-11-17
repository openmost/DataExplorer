<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Query;

use Piwik\Common;

/**
 * The filter operators applied in PHP to a displayed label and its stored value, case insensitive like the
 * default collation of the log tables.
 */
final class LabelFilter
{
    public static function matches(string $operator, string $value, string $label, string $raw): bool
    {
        $value = Common::unsanitizeInputValue($value);
        foreach (array_unique([$label, $raw]) as $candidate) {
            if (self::matchesOne($operator, $value, $candidate)) {
                return true;
            }
        }

        return false;
    }

    private static function matchesOne(string $operator, string $value, string $candidate): bool
    {
        $needle = mb_strtolower($value);
        $haystack = mb_strtolower($candidate);

        switch ($operator) {
            case 'equals':
                return $haystack === $needle;
            case 'contains':
                return $needle === '' || mb_strpos($haystack, $needle) !== false;
            case 'starts_with':
                return $needle === '' || mb_strpos($haystack, $needle) === 0;
            case 'ends_with':
                return $needle === '' || mb_substr($haystack, -mb_strlen($needle)) === $needle;
            case 'regex':
                return @preg_match('/' . str_replace('/', '\/', $value) . '/iu', $candidate) === 1;
        }

        return false;
    }
}
