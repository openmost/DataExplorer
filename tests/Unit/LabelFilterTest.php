<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\tests\Unit;

use PHPUnit\Framework\TestCase;
use Piwik\Plugins\DataExplorer\Query\LabelFilter;

/**
 * @group DataExplorer
 * @group LabelFilterTest
 * @group Plugins
 */
class LabelFilterTest extends TestCase
{
    /**
     * @dataProvider provideMatches
     */
    public function test_matches(string $operator, string $value, string $label, string $raw, bool $expected): void
    {
        $this->assertSame($expected, LabelFilter::matches($operator, $value, $label, $raw));
    }

    public function test_matches_aLabelInEnglish_whateverTheLanguageOfTheUser(): void
    {
        $this->assertTrue(LabelFilter::matches('equals', 'Desktop', 'Bureau', 'Desktop', '0'));
        $this->assertTrue(LabelFilter::matches('equals', 'Bureau', 'Bureau', 'Desktop', '0'));
        $this->assertFalse(LabelFilter::matches('equals', 'Smartphone', 'Bureau', 'Desktop', '0'));
    }

    public function provideMatches(): array
    {
        return [
            'equals the label' => ['equals', 'France', 'France', 'fr', true],
            'equals ignores the case' => ['equals', 'smartphone', 'Smartphone', '1', true],
            'equals the stored code' => ['equals', 'fr', 'France', 'fr', true],
            'equals is not a prefix' => ['equals', 'Fran', 'France', 'fr', false],
            'contains in the label' => ['contains', 'ERMAN', 'Germany', 'de', true],
            'contains nowhere' => ['contains', 'xyz', 'Germany', 'de', false],
            'starts with the label' => ['starts_with', 'uni', 'United States', 'us', true],
            'starts with, not in the middle' => ['starts_with', 'states', 'United States', 'us', false],
            'ends with the label' => ['ends_with', 'STATES', 'United States', 'us', true],
            'ends with, not in the middle' => ['ends_with', 'united', 'United States', 'us', false],
            'regex on the label' => ['regex', '^(France|Germany)$', 'Germany', 'de', true],
            'regex is case insensitive' => ['regex', '^chrome$', 'Chrome', 'CH', true],
            'regex with a slash' => ['regex', 'a/b', 'a/b', 'a/b', true],
            'invalid regex matches nothing' => ['regex', '(', 'France', 'fr', false],
            'sanitized input' => ['equals', 'C&#039;te', "C'te", 'ct', true],
            'numeric operators are not label operators' => ['gt', '1', '2', '2', false],
        ];
    }
}
