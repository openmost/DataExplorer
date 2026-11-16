<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer;

use Piwik\Common;
use Piwik\Config;
use Piwik\DataTable;
use Piwik\Piwik;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\ScopeCompatibility;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\VisualQueryRunner;
use Piwik\Plugins\DataExplorer\Sql\RawSqlRunner;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Plugins\DataExplorer\Sql\SiteScopeProof;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Plugins\SegmentEditor\API as SegmentEditorAPI;

/**
 * API of the DataExplorer plugin.
 *
 * @method static API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Dimensions and metrics grouped by category (for the "+" pickers) and the stored segments of the site.
     *
     * @param int $idSite
     * @return array{dimensions: array, metrics: array, segments: array}
     */
    public function getCatalog(int $idSite): array
    {
        (new ReportPermissions())->checkCanExplore($idSite);

        $compatibility = new ScopeCompatibility();

        $dimensions = [];
        foreach ((new DimensionCatalog())->all($idSite) as $entry) {
            $dimensions[] = $entry->toArray();
        }

        $metrics = [];
        foreach ((new MetricCatalog())->all($idSite) as $entry) {
            $row = $entry->toArray();
            $row['allowedScopes'] = $compatibility->allowedScopes($entry);
            $metrics[] = $row;
        }

        $segments = [];
        foreach (SegmentEditorAPI::getInstance()->getAll($idSite) as $segment) {
            $segments[] = ['definition' => (string) $segment['definition'], 'name' => (string) $segment['name']];
        }

        return [
            'dimensions' => $this->groupByCategory($dimensions),
            'metrics' => $this->groupByCategory($metrics),
            'segments' => $segments,
        ];
    }

    /**
     * Runs a visual exploration live (the explorer canvas). Nothing is saved.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string $definition JSON of the exploration, same shape as a saved visual definition
     * @param string $segment combined (AND) with the segment stored in the definition
     * @return DataTable
     */
    public function runVisual(int $idSite, string $period, string $date, string $definition, string $segment = ''): DataTable
    {
        (new ReportPermissions())->checkCanExplore($idSite);

        $raw = json_decode($definition, true);
        if (!is_array($raw)) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorDefinitionInvalid'));
        }
        $raw['type'] = ReportDefinition::TYPE_VISUAL;
        if (!isset($raw['name']) || !is_string($raw['name']) || trim($raw['name']) === '') {
            $raw['name'] = 'preview';
        }

        return (new VisualQueryRunner())->run(ReportDefinition::fromArray($raw), $idSite, $period, $date, $segment);
    }

    /**
     * Runs a raw SQL query live on the read-only connection (super user only). {idsite}, {date_start} and
     * {date_end} are bound from the site and period.
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string $sql
     * @return DataTable
     */
    public function runSql(int $idSite, string $period, string $date, string $sql): DataTable
    {
        (new ReportPermissions())->checkCanRunRawSql();

        return (new RawSqlRunner())->run($sql, $idSite, $period, $date);
    }

    /**
     * Checks a raw SQL query with the guard used before each run, for the live validation of the editor
     * (super user only). Offsets count Unicode code points, the *Utf16 variants count UTF-16 code units like
     * JavaScript strings do. A null offset means the whole query.
     *
     * @param string $sql
     * @param int $idSite the site the editor is open on
     * @return array{valid: bool, errors: array, warnings: array}
     */
    public function validateSql(string $sql, int $idSite = 0): array
    {
        (new ReportPermissions())->checkCanRunRawSql();

        $readOnly = Configuration::getReadOnlyDbConfig();
        $prefix = $readOnly !== null ? $readOnly['tables_prefix'] : Common::prefixTable('');
        $database = $readOnly !== null ? $readOnly['dbname'] : (string) Config::getInstance()->database['dbname'];

        try {
            $parsed = SqlGuard::validate($sql, $prefix, $database);
        } catch (RejectedQueryException $e) {
            return [
                'valid' => false,
                'errors' => [$this->problem($sql, $e->getOffset(), $this->tokenLength($e->getOffendingToken()), $e->getMessage())],
                'warnings' => [],
            ];
        }

        $warnings = [];
        $unproven = SiteScopeProof::check($parsed);
        if ($unproven !== null) {
            $offset = null;
            $length = 0;
            foreach ($parsed->getTableInstances() as $instance) {
                if ($instance['alias'] === $unproven || ($instance['alias'] === null && $instance['table'] === $unproven)) {
                    $offset = $instance['position'];
                    $length = mb_strlen($instance['name']);
                    break;
                }
            }
            $warnings[] = $this->problem($sql, $offset, $length, Piwik::translate('DataExplorer_SqlWarningNotSiteScoped', [$unproven]));
        }

        return ['valid' => true, 'errors' => [], 'warnings' => $warnings];
    }

    private function problem(string $sql, ?int $offset, int $length, string $message): array
    {
        if ($offset === null) {
            return ['offset' => null, 'offsetUtf16' => null, 'length' => 0, 'lengthUtf16' => 0, 'message' => $message];
        }

        return [
            'offset' => $offset,
            'offsetUtf16' => $this->utf16Length(mb_substr($sql, 0, $offset)),
            'length' => $length,
            'lengthUtf16' => $this->utf16Length(mb_substr($sql, $offset, $length)),
            'message' => $message,
        ];
    }

    private function tokenLength(string $token): int
    {
        // The guard shortens long tokens with "...", the underline covers the shown part.
        if (mb_strlen($token) > 60 && substr($token, -3) === '...') {
            return mb_strlen($token) - 3;
        }

        return mb_strlen($token);
    }

    private function utf16Length(string $text): int
    {
        return intdiv(strlen((string) mb_convert_encoding($text, 'UTF-16LE', 'UTF-8')), 2);
    }

    private function groupByCategory(array $items): array
    {
        $groups = [];
        foreach ($items as $item) {
            $groups[$item['category']][] = $item;
        }

        $result = [];
        foreach ($groups as $category => $groupItems) {
            usort($groupItems, fn (array $a, array $b) => strcmp($a['name'], $b['name']));
            $result[] = ['category' => Piwik::translate($category), 'items' => $groupItems];
        }
        usort($result, fn (array $a, array $b) => strcmp($a['category'], $b['category']));

        return $result;
    }
}
