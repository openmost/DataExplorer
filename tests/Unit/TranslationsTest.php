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

/**
 * @group DataExplorer
 * @group TranslationsTest
 * @group Plugins
 */
class TranslationsTest extends TestCase
{
    private const LOCALES = ['fr', 'de', 'es', 'it', 'nl', 'pl', 'pt', 'ja', 'ar', 'zh-cn', 'zh-tw'];

    private static function root(): string
    {
        return dirname(__DIR__, 2);
    }

    private static function load(string $locale): array
    {
        $decoded = json_decode((string) file_get_contents(self::root() . '/lang/' . $locale . '.json'), true);
        self::assertIsArray($decoded, $locale . ' is valid JSON');

        return $decoded;
    }

    private static function section(string $locale): array
    {
        $data = self::load($locale);
        self::assertArrayHasKey('DataExplorer', $data);

        return $data['DataExplorer'];
    }

    public static function localeProvider(): array
    {
        $cases = [];
        foreach (self::LOCALES as $locale) {
            $cases[$locale] = [$locale];
        }

        return $cases;
    }

    /**
     * @dataProvider localeProvider
     */
    public function test_localeHasTheSameKeysAsEnglish(string $locale): void
    {
        foreach (['DataExplorer', 'OpenmostCommunication'] as $section) {
            $en = self::load('en')[$section];
            $other = self::load($locale)[$section] ?? [];

            $this->assertSame([], array_values(array_diff(array_keys($en), array_keys($other))), "$locale misses keys of $section");
            $this->assertSame([], array_values(array_diff(array_keys($other), array_keys($en))), "$locale has unknown keys in $section");
        }
    }

    /**
     * @dataProvider localeProvider
     */
    public function test_valuesAreNonEmptyStringsWithoutLongDashes(string $locale): void
    {
        foreach (self::load($locale) as $section => $keys) {
            foreach ($keys as $key => $value) {
                $this->assertIsString($value, "$locale $section.$key");
                $this->assertNotSame('', trim($value), "$locale $section.$key is empty");
                $this->assertSame(0, preg_match('/\x{2014}|\x{2013}/u', $value), "$locale $section.$key holds a long dash");
            }
        }
    }

    /**
     * @dataProvider localeProvider
     */
    public function test_placeholdersMatchEnglish(string $locale): void
    {
        $en = self::section('en');
        foreach (self::section($locale) as $key => $value) {
            $this->assertSame(self::placeholders($en[$key]), self::placeholders($value), "$locale $key placeholders differ from en");
        }
    }

    public function test_everyKeyUsedInCodeExistsInEnglish(): void
    {
        $en = self::section('en');
        $missing = [];
        foreach ($this->usedKeys() as $key => $file) {
            if (strpos($key, 'DataExplorer_') === 0 && !isset($en[substr($key, strlen('DataExplorer_'))])) {
                $missing[] = $key . ' (' . $file . ')';
            }
        }

        $this->assertSame([], $missing);
    }

    public function test_dynamicKeyFamiliesAreComplete(): void
    {
        $en = self::section('en');
        $families = [
            'ReportType_' => ['table', 'evolution', 'kpicard'],
            'Scope_' => ['visit', 'action', 'conversion'],
            'FilterOperator_' => ['equals', 'not_equals', 'contains', 'starts_with', 'ends_with', 'regex', 'gt', 'lt'],
            'Visualization_' => ['table', 'bar', 'pie', 'treemap', 'line', 'kpicard'],
        ];
        foreach ($families as $prefix => $suffixes) {
            foreach ($suffixes as $suffix) {
                $this->assertArrayHasKey($prefix . $suffix, $en);
            }
        }
    }

    public function test_clientSideKeysExistInEnglish(): void
    {
        $source = (string) file_get_contents(self::root() . '/DataExplorer.php');
        $this->assertSame(1, preg_match('/CLIENT_SIDE_TRANSLATION_KEYS = \[(.*?)\];/s', $source, $block));
        preg_match_all("/'([A-Za-z]+_[A-Za-z0-9_]+)'/", $block[1], $matches);
        $this->assertNotEmpty($matches[1]);

        $en = self::section('en');
        foreach ($matches[1] as $key) {
            if (strpos($key, 'DataExplorer_') === 0) {
                $this->assertArrayHasKey(substr($key, strlen('DataExplorer_')), $en, $key);
            }
        }
    }

    public function test_everyKeyTheVueCodeTranslates_isSentToTheClient(): void
    {
        $source = (string) file_get_contents(self::root() . '/DataExplorer.php');
        $this->assertSame(1, preg_match('/CLIENT_SIDE_TRANSLATION_KEYS = \[(.*?)\];/s', $source, $block));
        preg_match_all("/'([A-Za-z]+_[A-Za-z0-9_]+)'/", $block[1], $listed);

        $files = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator(self::root() . '/vue/src'));
        $missing = [];
        foreach ($files as $file) {
            $name = $file->getFilename();
            if (!preg_match('/\.(vue|ts)$/', $name) || strpos($name, '.spec.') !== false) {
                continue;
            }
            preg_match_all("/translate\(\s*'([A-Za-z]+_[A-Za-z0-9_]+)'/", (string) file_get_contents($file->getPathname()), $matches);
            foreach ($matches[1] as $key) {
                if (!in_array($key, $listed[1], true)) {
                    $missing[$key] = $name;
                }
            }
        }

        $this->assertSame([], $missing, 'translated in Vue but missing from CLIENT_SIDE_TRANSLATION_KEYS');
    }

    /**
     * @return array<string, string> literal key => first file using it
     */
    private function usedKeys(): array
    {
        $found = [];
        $skip = ['vendor', 'node_modules', 'tests', 'lang', 'dist'];
        $iterator = new \RecursiveIteratorIterator(new \RecursiveCallbackFilterIterator(
            new \RecursiveDirectoryIterator(self::root(), \FilesystemIterator::SKIP_DOTS),
            function (\SplFileInfo $file) use ($skip): bool {
                return !in_array($file->getFilename(), $skip, true) && ($file->isDir() || preg_match('/\.(php|vue|ts|twig)$/', $file->getFilename()) === 1);
            }
        ));

        foreach ($iterator as $file) {
            if (!$file->isFile()) {
                continue;
            }
            if (preg_match_all("/['\"](DataExplorer_[A-Za-z0-9]+(?:_[A-Za-z0-9]+)*)['\"]/", (string) file_get_contents($file->getPathname()), $matches)) {
                foreach ($matches[1] as $key) {
                    $found[$key] ??= substr($file->getPathname(), strlen(self::root()) + 1);
                }
            }
        }

        return $found;
    }

    private static function placeholders(string $text): array
    {
        preg_match_all('/%(?:\d+\$)?[sd]/', $text, $matches);
        $list = $matches[0];
        sort($list);

        return $list;
    }
}
