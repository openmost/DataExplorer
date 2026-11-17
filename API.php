<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer;

use Piwik\Access;
use Piwik\Cache;
use Piwik\Category\CategoryList;
use Piwik\Category\Subcategory;
use Piwik\Common;
use Piwik\Config;
use Piwik\Container\StaticContainer;
use Piwik\DataTable;
use Piwik\Db;
use Piwik\NoAccessException;
use Piwik\Piwik;
use Piwik\Plugin\Manager as PluginManager;
use Piwik\Plugins\DataExplorer\Access\ReportPermissions;
use Piwik\Plugins\DataExplorer\Catalog\DimensionCatalog;
use Piwik\Plugins\DataExplorer\Catalog\MetricCatalog;
use Piwik\Plugins\DataExplorer\Catalog\ScopeCompatibility;
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Plugins\DataExplorer\Dao\ReportsDao;
use Piwik\Plugins\DataExplorer\Model\Placement;
use Piwik\Plugins\DataExplorer\Model\ReportDefinition;
use Piwik\Plugins\DataExplorer\Query\SavedReportReader;
use Piwik\Plugins\DataExplorer\Query\VisualQueryBuilder;
use Piwik\Plugins\DataExplorer\Query\VisualQueryRunner;
use Piwik\Plugins\DataExplorer\Sql\ParsedQuery;
use Piwik\Plugins\DataExplorer\Sql\RawSqlRunner;
use Piwik\Plugins\DataExplorer\Sql\ReadOnlySetup;
use Piwik\Plugins\DataExplorer\Sql\RejectedQueryException;
use Piwik\Plugins\DataExplorer\Sql\SiteScopeProof;
use Piwik\Plugins\DataExplorer\Sql\SqlGuard;
use Piwik\Plugins\SegmentEditor\API as SegmentEditorAPI;
use Piwik\Plugins\SitesManager\API as SitesManagerAPI;
use Piwik\Segment;

/**
 * API of the DataExplorer plugin.
 *
 * @method static API getInstance()
 */
class API extends \Piwik\Plugin\API
{
    /**
     * Dashboards and the Marketplace teaser use the reporting menu but are not report pages.
     */
    private const NON_REPORTING_CATEGORIES = ['Dashboard_Dashboard', 'Marketplace_Marketplace'];

    private const MAX_SUBCATEGORY_LENGTH = 255;
    private const MAX_ORDER = 1000;

    /**
     * Dimensions and metrics grouped by category (for the "+" pickers), the stored segments of the site and the
     * limits of an exploration (row dimensions, metrics, column dimensions).
     *
     * @param int $idSite
     * @return array{dimensions: array, metrics: array, segments: array, limits: array}
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
            'limits' => [
                'maxDimensions' => Configuration::getMaxDimensions(),
                'maxMetrics' => ReportDefinition::MAX_METRICS,
                'maxColumns' => 1,
            ],
        ];
    }

    /**
     * Runs a visual exploration live (the explorer canvas). Nothing is saved. An evolution definition returns one
     * table per period: the days of a range, the requested periods, or the last periods ending at the selected one.
     *
     * @unsanitized
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param string $definition JSON of the exploration, same shape as a saved visual definition
     * @param string $segment combined (AND) with the segment stored in the definition
     * @return DataTable|DataTable\Map
     */
    public function runVisual(int $idSite, string $period, string $date, string $definition, string $segment = ''): DataTable\DataTableInterface
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

        $validated = ReportDefinition::fromArray($raw);
        if ($validated->getReportType() === ReportDefinition::REPORT_TYPE_EVOLUTION) {
            return (new VisualQueryRunner())->runEvolution($validated, $idSite, $period, $date, $segment);
        }

        return (new VisualQueryRunner())->run($validated, $idSite, $period, $date, $segment);
    }

    /**
     * Runs a raw SQL query live on the read-only connection (super user only). {idsite}, {date_start} and
     * {date_end} are bound from the site and period.
     *
     * @unsanitized
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
     * Columns of the tables raw SQL can read, keyed by their prefixed name as written in a query, for the
     * autocompletion of the SQL editor (super user only).
     *
     * @return array<string, string[]>
     */
    public function getSqlSchema(): array
    {
        (new ReportPermissions())->checkCanRunRawSql();

        $schema = [];
        foreach ((new ReadOnlySetup())->tables() as $table) {
            $schema[$table] = array_map(fn (array $column) => (string) $column['Field'], Db::fetchAll('SHOW COLUMNS FROM `' . str_replace('`', '``', $table) . '`'));
        }

        return $schema;
    }

    /**
     * State of the read-only connection raw SQL runs with (super user only), read when the SQL tab opens.
     *
     * @return array{state: string, message: string} state is ready when raw SQL can run
     */
    public function getSqlStatus(): array
    {
        (new ReportPermissions())->checkCanRunRawSql();

        return (new ReadOnlySetup())->status();
    }

    /**
     * Data of a saved report on a site where it is placed, for any user with view access on that site.
     * Visual reports are read from their archives (segment applied), raw SQL reports run live with a short cache
     * (segment ignored). Evolution and KPI card raw SQL reports run once per sub-period of a multiple period,
     * at most raw_sql_max_points times (the most recent ones).
     *
     * @param int $idSite
     * @param string $period
     * @param string $date
     * @param int $idReport
     * @param string $segment
     * @param bool $expanded
     * @param int $idSubtable
     * @return DataTable\DataTableInterface
     */
    public function getReport(int $idSite, string $period, string $date, int $idReport, string $segment = '', bool $expanded = false, int $idSubtable = 0)
    {
        $report = (new ReportsDao())->get($idReport);
        if ($report === null) {
            throw new NoAccessException(Piwik::translate('DataExplorer_ErrorReportNotAvailable'));
        }

        return (new SavedReportReader())->read($report, $idSite, $period, $date, $segment, $expanded, $idSubtable);
    }

    /**
     * Checks a raw SQL query with the guard used before each run, for the live validation of the editor
     * (super user only). Offsets count Unicode code points, the *Utf16 variants count UTF-16 code units like
     * JavaScript strings do. A null offset means the whole query.
     *
     * @unsanitized
     * @param string $sql
     * @return array{valid: bool, errors: array, warnings: array}
     */
    public function validateSql(string $sql): array
    {
        (new ReportPermissions())->checkCanRunRawSql();

        try {
            $parsed = $this->guardSql($sql);
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

    /**
     * Saves a new report. Visual reports need write access on every listed site, raw SQL reports and "all
     * websites" need super user access, and a raw SQL query must prove that every per-site table it reads is
     * filtered by idsite = {idsite}.
     *
     * @unsanitized
     * @param string $name
     * @param string $type visual or sql
     * @param string $reportType table, evolution or kpicard
     * @param string $definition JSON of the definition, {"sql": "..."} for a raw SQL report
     * @param string $placement JSON, {"all": true} or {"sites": [1, 2]}
     * @param string $category an id of getCategories(), empty for the Data Explorer category
     * @param string $subcategory required, an existing subcategory id of the category or the name of a new one
     * @param int $order 0 to 1000
     * @return int id of the report
     */
    public function addReport(string $name, string $type, string $reportType, string $definition, string $placement, string $category, string $subcategory = '', int $order = 100): int
    {
        $permissions = new ReportPermissions();
        $newPlacement = $this->parsePlacement($placement);
        $permissions->checkCanSave($newPlacement, $type);
        $this->checkSitesExist($newPlacement);

        $row = $this->buildRow($name, $type, $reportType, $definition, $newPlacement, $category, $subcategory, $order);
        $row['created_by'] = Piwik::getCurrentUserLogin();

        $dao = new ReportsDao();
        $idReport = $dao->insert($row, $newPlacement);
        $report = $dao->get($idReport);

        $this->afterWrite($report);

        /**
         * Triggered after a DataExplorer report is created or updated.
         *
         * @param int $idReport
         * @param array $report the stored report
         * @param array|null $previous the report before the update, null on creation
         */
        Piwik::postEvent('DataExplorer.reportSaved', [$idReport, $report, null]);

        return $idReport;
    }

    /**
     * Updates a report, with the access rules of addReport() for its new placement plus the right to edit it:
     * its author with write access on every site it is placed on, or an admin of all those sites. Raw SQL
     * reports, and turning a report into or out of raw SQL, need super user access. Only a change of what the
     * report computes rebuilds its history; a new placement only builds it on the sites added.
     *
     * @unsanitized
     * @param int $idReport
     * @param string $name
     * @param string $type
     * @param string $reportType
     * @param string $definition
     * @param string $placement
     * @param string $category
     * @param string $subcategory
     * @param int $order
     */
    public function updateReport(int $idReport, string $name, string $type, string $reportType, string $definition, string $placement, string $category, string $subcategory = '', int $order = 100): void
    {
        $dao = new ReportsDao();
        $previous = $this->getWritableReport($dao, $idReport);

        $permissions = new ReportPermissions();
        $newPlacement = $this->parsePlacement($placement);
        $permissions->checkCanSave($newPlacement, $type);
        $this->checkSitesExist($newPlacement);

        $row = $this->buildRow($name, $type, $reportType, $definition, $newPlacement, $category, $subcategory, $order);
        $computedChanged = $this->computesSomethingElse($previous, $row);
        $dao->update($idReport, $row, $newPlacement, $computedChanged);
        $report = $dao->get($idReport);

        if ($computedChanged) {
            $this->afterWrite($report);
        } else {
            $this->afterWrite($report, $this->addedSites($previous['placement'], $newPlacement));
        }

        Piwik::postEvent('DataExplorer.reportSaved', [$idReport, $report, $previous]);
    }

    /**
     * Soft deletes a report, with the rights of updateReport().
     *
     * @param int $idReport
     */
    public function deleteReport(int $idReport): void
    {
        $dao = new ReportsDao();
        $report = $this->getWritableReport($dao, $idReport);

        $dao->softDelete($idReport);

        $this->afterWrite(null);

        /**
         * Triggered after a DataExplorer report is deleted.
         *
         * @param int $idReport
         * @param array $report the report as it was before the deletion
         */
        Piwik::postEvent('DataExplorer.reportDeleted', [$idReport, $report]);
    }

    /**
     * Reports placed on the site, with an editable flag. The query of raw SQL reports is only returned to super
     * users, and the placement lists only the sites the user can view.
     *
     * @param int $idSite
     * @return array[]
     */
    public function getReports(int $idSite): array
    {
        Piwik::checkUserHasViewAccess($idSite);

        $permissions = new ReportPermissions();
        $isSuperUser = Piwik::hasUserSuperUserAccess();

        $result = [];
        foreach ((new ReportsDao())->getForSite($idSite) as $report) {
            try {
                $permissions->checkCanView($report, $idSite);
            } catch (NoAccessException $e) {
                continue;
            }

            $definition = $report['definition'];
            if (!$isSuperUser || !isset($definition['sql']) || !is_string($definition['sql'])) {
                $definition['sql'] = '';
            }

            /** @var Placement $placement */
            $placement = $report['placement'];
            $sites = $placement->getSiteIds();
            if (!$isSuperUser) {
                $sites = array_values(array_filter($sites, fn (int $id) => Piwik::isUserHasViewAccess($id)));
            }

            $result[] = [
                'idreport' => $report['idreport'],
                'name' => $report['name'],
                'type' => $report['type'],
                'report_type' => $report['report_type'],
                'definition' => $definition,
                'placement' => ['all' => $placement->isAllWebsites(), 'sites' => $sites],
                'category' => $report['category'],
                'subcategory' => $report['subcategory'],
                'order' => $report['order'],
                'revision' => $report['revision'],
                'created_by' => $report['created_by'],
                'created_date' => $report['created_date'],
                'updated_date' => $report['updated_date'],
                'editable' => $this->canWrite($report),
            ];
        }

        return $result;
    }

    /**
     * The reporting categories and their subcategories, where the save dialog can place a report. A new
     * subcategory may be created under any of these categories.
     *
     * @param int $idSite
     * @return array[] [{id, name, subcategories: [{id, name}]}]
     */
    public function getCategories(int $idSite): array
    {
        (new ReportPermissions())->checkCanExplore($idSite);

        return $this->reportingCategories();
    }

    /**
     * Categories of the reporting menu, with their subcategories for the site of the request.
     */
    private function reportingCategories(): array
    {
        $allowed = $this->reportingCategoryIds();

        $categories = [];
        foreach (CategoryList::get()->getCategories() as $category) {
            if (!isset($allowed[$category->getId()])) {
                continue;
            }

            $subcategories = $category->getSubcategories();
            usort($subcategories, fn ($a, $b) => $a->getOrder() <=> $b->getOrder());

            $categories[] = [
                'id' => (string) $category->getId(),
                'name' => $category->getDisplayName(),
                'order' => (int) $category->getOrder(),
                'subcategories' => array_map(fn ($subcategory) => [
                    'id' => (string) $subcategory->getId(),
                    'name' => Piwik::translate((string) $subcategory->getName()),
                ], $subcategories),
            ];
        }
        usort($categories, fn (array $a, array $b) => $a['order'] <=> $b['order']);

        return array_map(function (array $category) {
            unset($category['order']);
            return $category;
        }, $categories);
    }

    /**
     * Categories that hold report pages declared as Subcategory classes. Read from the classes only, not from
     * Category.addSubcategories: its listeners load per-site data of the request's idSite, which the saving user
     * may not be able to view.
     *
     * @return array<string, true>
     */
    private function reportingCategoryIds(): array
    {
        $ids = [];
        foreach (PluginManager::getInstance()->findMultipleComponents('Categories', Subcategory::class) as $class) {
            $subcategory = StaticContainer::getContainer()->make($class);
            $ids[(string) $subcategory->getCategoryId()] = true;
        }

        foreach (self::NON_REPORTING_CATEGORIES as $id) {
            unset($ids[$id]);
        }

        return $ids;
    }

    private function getWritableReport(ReportsDao $dao, int $idReport): array
    {
        $report = $dao->get($idReport);
        if ($report === null || !empty($report['deleted'])) {
            throw new \Exception(Piwik::translate('DataExplorer_ErrorReportNotFound'));
        }

        $this->checkCanWrite($report);

        return $report;
    }

    /**
     * checkCanEdit() plus the raw SQL rule: runSaved executes the stored query for every viewer, so only a super
     * user may change or remove it, whatever the placement.
     */
    private function checkCanWrite(array $report): void
    {
        (new ReportPermissions())->checkCanEdit($report);

        if ($report['type'] === ReportDefinition::TYPE_SQL) {
            Piwik::checkUserHasSuperUserAccess();
        }
    }

    private function canWrite(array $report): bool
    {
        try {
            $this->checkCanWrite($report);
        } catch (NoAccessException $e) {
            return false;
        }

        return true;
    }

    private function parsePlacement(string $placement): Placement
    {
        $raw = json_decode($placement, true);
        if (!is_array($raw)) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorPlacementInvalid'));
        }

        return Placement::fromArray($raw);
    }

    private function checkSitesExist(Placement $placement): void
    {
        if ($placement->isAllWebsites()) {
            return;
        }

        $existing = Access::doAsSuperUser(fn () => array_map('intval', SitesManagerAPI::getInstance()->getAllSitesId()));
        foreach ($placement->getSiteIds() as $idSite) {
            if (!in_array($idSite, $existing, true)) {
                throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorSiteUnknown', [$idSite]));
            }
        }
    }

    /**
     * Resolves the dimensions, metrics, filters and segment of a visual report on every listed site, so an id the
     * catalogue of a site lacks is refused at save rather than failing at archiving. An "all websites" report is
     * checked on the site of the request only: site-specific dimensions are missing elsewhere by design and the
     * read path skips them there.
     */
    private function checkVisualOnSites(ReportDefinition $definition, Placement $placement): void
    {
        if ($placement->isAllWebsites()) {
            $idSite = Common::getRequestVar('idSite', 0, 'int');
            if ($idSite < 1) {
                $ids = Access::doAsSuperUser(fn () => SitesManagerAPI::getInstance()->getAllSitesId());
                $idSite = $ids ? (int) reset($ids) : 0;
            }
            $siteIds = $idSite > 0 ? [$idSite] : [];
        } else {
            $siteIds = $placement->getSiteIds();
        }

        $builder = new VisualQueryBuilder();
        foreach ($siteIds as $idSite) {
            $builder->resolve($definition, $idSite);

            if ($definition->getSegment() !== '') {
                try {
                    new Segment($definition->getSegment(), [$idSite]);
                } catch (\Exception $e) {
                    throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorSegmentInvalid', [$e->getMessage()]));
                }
            }
        }
    }

    /**
     * @return array the columns of a ReportsDao row, validated
     */
    private function buildRow(string $name, string $type, string $reportType, string $definition, Placement $placement, string $category, string $subcategory, int $order): array
    {
        $raw = json_decode($definition, true);
        if (!is_array($raw)) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorDefinitionInvalid'));
        }

        $validated = ReportDefinition::fromArray(array_merge($raw, ['name' => $name, 'type' => $type, 'report_type' => $reportType]));

        if ($validated->getType() === ReportDefinition::TYPE_SQL) {
            $unproven = SiteScopeProof::check($this->guardSql($validated->getSql()));
            if ($unproven !== null) {
                throw new RejectedQueryException(Piwik::translate('DataExplorer_SqlWarningNotSiteScoped', [$unproven]), $unproven);
            }
        } else {
            $this->checkVisualOnSites($validated, $placement);
        }

        $category = trim($category);
        if ($category === '') {
            $category = ExploreSubcategory::CATEGORY;
        }
        if (!isset($this->reportingCategoryIds()[$category])) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorCategoryInvalid'));
        }

        $subcategory = trim($subcategory);
        if ($subcategory === '') {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorSubcategoryRequired'));
        }
        if (mb_strlen($subcategory) > self::MAX_SUBCATEGORY_LENGTH) {
            throw new \InvalidArgumentException(Piwik::translate('DataExplorer_ErrorSubcategoryInvalid', [self::MAX_SUBCATEGORY_LENGTH]));
        }

        $stored = $validated->toArray();
        unset($stored['name'], $stored['type'], $stored['report_type']);

        return [
            'name' => $validated->getName(),
            'type' => $validated->getType(),
            'report_type' => $validated->getReportType(),
            'definition' => $stored,
            'category' => $category,
            'subcategory' => $subcategory,
            'order' => max(0, min(self::MAX_ORDER, $order)),
        ];
    }

    /**
     * True when the update changes what the report computes, so its archived records no longer match it. A rename,
     * a new place in the menu or a new placement keeps the archived history.
     */
    private function computesSomethingElse(array $previous, array $row): bool
    {
        try {
            $before = ReportDefinition::fromReport($previous)->toArray();
            $after = ReportDefinition::fromReport($row)->toArray();
        } catch (\InvalidArgumentException $e) {
            return true;
        }
        // the chart and the table view are read when the report renders, never archived
        unset($before['name'], $after['name'], $before['chart'], $after['chart'], $before['table_view'], $after['table_view']);

        return $before !== $after;
    }

    /**
     * @return int[] sites of $new that $old did not include
     */
    private function addedSites(Placement $old, Placement $new): array
    {
        if ($old->isAllWebsites()) {
            return [];
        }

        $siteIds = $new->isAllWebsites()
            ? Access::doAsSuperUser(fn () => array_map('intval', SitesManagerAPI::getInstance()->getAllSitesId()))
            : $new->getSiteIds();

        return array_values(array_filter($siteIds, fn (int $idSite) => !$old->includes($idSite)));
    }

    /**
     * Drops what core cached for this request, so the same request already lists and archives the new
     * definitions, then rebuilds the history of a visual report: on every placed site, or on $onlySites.
     *
     * @param array|null $report hydrated ReportsDao row, null to only drop the caches
     * @param int[]|null $onlySites
     */
    private function afterWrite(?array $report, ?array $onlySites = null): void
    {
        ReportsDao::clearCache();
        Cache::getTransientCache()->flushAll();

        if ($report === null || $report['type'] !== ReportDefinition::TYPE_VISUAL || $onlySites === []) {
            return;
        }

        $placement = $onlySites === null ? $report['placement'] : Placement::fromArray(['sites' => $onlySites]);

        PluginManager::getInstance()->getLoadedPlugin('DataExplorer')->invalidateHistory([
            'type' => ReportDefinition::TYPE_VISUAL,
            'placement' => $placement,
        ]);
    }

    /**
     * @throws RejectedQueryException
     */
    private function guardSql(string $sql): ParsedQuery
    {
        $readOnly = Configuration::getReadOnlyDbConfig();
        $prefix = $readOnly !== null ? $readOnly['tables_prefix'] : Common::prefixTable('');
        $database = $readOnly !== null ? $readOnly['dbname'] : (string) Config::getInstance()->database['dbname'];

        return SqlGuard::validate($sql, $prefix, $database);
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
