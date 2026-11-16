<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Dao;

use Piwik\Common;
use Piwik\Date;
use Piwik\Db;
use Piwik\DbHelper;
use Piwik\Plugins\DataExplorer\Model\Placement;

class ReportsDao
{
    private const REPORT_TABLE = 'dataexplorer_report';
    private const SITE_TABLE = 'dataexplorer_report_site';

    /** @var array<int, array|null> per request, see getCached() */
    private static $cachedReports = [];
    /** @var array<int, array[]> per request, see getForSiteCached() */
    private static $cachedForSite = [];

    public static function install(): void
    {
        DbHelper::createTable(self::REPORT_TABLE, "
            `idreport` INT UNSIGNED NOT NULL AUTO_INCREMENT,
            `all_websites` TINYINT(1) NOT NULL DEFAULT 0,
            `name` VARCHAR(255) NOT NULL,
            `type` VARCHAR(10) NOT NULL,
            `report_type` VARCHAR(10) NOT NULL,
            `definition` MEDIUMTEXT NOT NULL,
            `category` VARCHAR(100) NOT NULL DEFAULT '',
            `subcategory` VARCHAR(255) NOT NULL DEFAULT '',
            `order` INT NOT NULL DEFAULT 0,
            `revision` INT NOT NULL DEFAULT 1,
            `created_by` VARCHAR(100) NOT NULL DEFAULT '',
            `created_date` DATETIME NOT NULL,
            `updated_date` DATETIME NOT NULL,
            `deleted` TINYINT(1) NOT NULL DEFAULT 0,
            PRIMARY KEY (`idreport`)
        ");

        DbHelper::createTable(self::SITE_TABLE, "
            `idreport` INT UNSIGNED NOT NULL,
            `idsite` INT UNSIGNED NOT NULL,
            PRIMARY KEY (`idreport`, `idsite`)
        ");
    }

    public static function uninstall(): void
    {
        Db::dropTables([self::tableName(self::REPORT_TABLE), self::tableName(self::SITE_TABLE)]);
    }

    /**
     * @return string[] prefixed table names, for Db.getTablesInstalled
     */
    public static function getTableNames(): array
    {
        return [self::tableName(self::REPORT_TABLE), self::tableName(self::SITE_TABLE)];
    }

    /**
     * @param array $row name, type, report_type, definition (array), category, subcategory, order, created_by
     */
    public function insert(array $row, Placement $placement): int
    {
        $now = Date::now()->getDatetime();

        Db::query(
            'INSERT INTO ' . self::reportTable() . '
                (`all_websites`, `name`, `type`, `report_type`, `definition`, `category`, `subcategory`, `order`,
                 `revision`, `created_by`, `created_date`, `updated_date`, `deleted`)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1, ?, ?, ?, 0)',
            [
                $placement->isAllWebsites() ? 1 : 0,
                (string) $row['name'],
                (string) $row['type'],
                (string) $row['report_type'],
                self::encode($row['definition'] ?? []),
                (string) ($row['category'] ?? ''),
                (string) ($row['subcategory'] ?? ''),
                (int) ($row['order'] ?? 0),
                (string) ($row['created_by'] ?? ''),
                $now,
                $now,
            ]
        );

        $id = (int) Db::get()->lastInsertId();
        $this->writePlacement($id, $placement);
        self::clearCache();

        return $id;
    }

    public function update(int $id, array $row, Placement $placement): void
    {
        Db::query(
            'UPDATE ' . self::reportTable() . '
                SET `all_websites` = ?, `name` = ?, `type` = ?, `report_type` = ?, `definition` = ?, `category` = ?,
                    `subcategory` = ?, `order` = ?, `revision` = `revision` + 1, `updated_date` = ?
              WHERE `idreport` = ?',
            [
                $placement->isAllWebsites() ? 1 : 0,
                (string) $row['name'],
                (string) $row['type'],
                (string) $row['report_type'],
                self::encode($row['definition'] ?? []),
                (string) ($row['category'] ?? ''),
                (string) ($row['subcategory'] ?? ''),
                (int) ($row['order'] ?? 0),
                Date::now()->getDatetime(),
                $id,
            ]
        );

        $this->writePlacement($id, $placement);
        self::clearCache();
    }

    public function softDelete(int $id): void
    {
        Db::query(
            'UPDATE ' . self::reportTable() . ' SET `deleted` = 1, `updated_date` = ? WHERE `idreport` = ?',
            [Date::now()->getDatetime(), $id]
        );
        self::clearCache();
    }

    /**
     * get() memoized for the request: core builds the report list, the subcategories and the report of a widget
     * several times per request. Every write of this class clears the memo.
     */
    public function getCached(int $id): ?array
    {
        if (!array_key_exists($id, self::$cachedReports)) {
            self::$cachedReports[$id] = $this->get($id);
        }

        return self::$cachedReports[$id];
    }

    /**
     * getForSite() memoized for the request, see getCached().
     *
     * @return array[]
     */
    public function getForSiteCached(int $idSite): array
    {
        if (!array_key_exists($idSite, self::$cachedForSite)) {
            self::$cachedForSite[$idSite] = $this->getForSite($idSite);
        }

        return self::$cachedForSite[$idSite];
    }

    public static function clearCache(): void
    {
        self::$cachedReports = [];
        self::$cachedForSite = [];
    }

    /**
     * Deleted reports are returned too, callers check the `deleted` flag.
     */
    public function get(int $id): ?array
    {
        $row = Db::fetchRow('SELECT * FROM ' . self::reportTable() . ' WHERE `idreport` = ?', [$id]);

        return $row ? $this->hydrate($row) : null;
    }

    /**
     * @return array[] non deleted reports placed on the site or on all websites, by `order` then id
     */
    public function getForSite(int $idSite): array
    {
        $rows = Db::fetchAll(
            'SELECT r.* FROM ' . self::reportTable() . ' r
              WHERE r.`deleted` = 0
                AND (r.`all_websites` = 1
                     OR EXISTS (SELECT 1 FROM ' . self::siteTable() . ' s WHERE s.`idreport` = r.`idreport` AND s.`idsite` = ?))
              ORDER BY r.`order` ASC, r.`idreport` ASC',
            [$idSite]
        );

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * @return array[] every non deleted report, by `order` then id
     */
    public function getAll(): array
    {
        $rows = Db::fetchAll('SELECT * FROM ' . self::reportTable() . ' WHERE `deleted` = 0 ORDER BY `order` ASC, `idreport` ASC');

        return array_map([$this, 'hydrate'], $rows);
    }

    /**
     * A report left with no site stays stored but unplaced.
     */
    public function removeSite(int $idSite): void
    {
        Db::query('DELETE FROM ' . self::siteTable() . ' WHERE `idsite` = ?', [$idSite]);
        self::clearCache();
    }

    private function writePlacement(int $id, Placement $placement): void
    {
        Db::query('DELETE FROM ' . self::siteTable() . ' WHERE `idreport` = ?', [$id]);

        foreach ($placement->getSiteIds() as $idSite) {
            Db::query('INSERT INTO ' . self::siteTable() . ' (`idreport`, `idsite`) VALUES (?, ?)', [$id, $idSite]);
        }
    }

    private function hydrate(array $row): array
    {
        $id = (int) $row['idreport'];

        $row['idreport'] = $id;
        $row['all_websites'] = (int) $row['all_websites'];
        $row['order'] = (int) $row['order'];
        $row['revision'] = (int) $row['revision'];
        $row['deleted'] = (int) $row['deleted'];

        $definition = json_decode((string) $row['definition'], true);
        $row['definition'] = is_array($definition) ? $definition : [];

        if ($row['all_websites'] === 1) {
            $row['placement'] = Placement::fromArray(['all' => true]);
        } else {
            $ids = Db::fetchAll('SELECT `idsite` FROM ' . self::siteTable() . ' WHERE `idreport` = ? ORDER BY `idsite`', [$id]);
            $ids = array_map(static function ($r) {
                return (int) $r['idsite'];
            }, $ids);
            $row['placement'] = $ids ? Placement::fromArray(['sites' => $ids]) : Placement::none();
        }

        return $row;
    }

    private static function encode($definition): string
    {
        return (string) json_encode($definition, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    private static function tableName(string $name): string
    {
        return Common::prefixTable($name);
    }

    private static function reportTable(): string
    {
        return self::tableName(self::REPORT_TABLE);
    }

    private static function siteTable(): string
    {
        return self::tableName(self::SITE_TABLE);
    }
}
