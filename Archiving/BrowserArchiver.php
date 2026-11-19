<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Archiving;

use Piwik\Access;
use Piwik\Archive;
use Piwik\ArchiveProcessor\Rules;
use Piwik\Common;
use Piwik\Container\StaticContainer;
use Piwik\Context;
use Piwik\DataAccess\ArchiveSelector;
use Piwik\DataAccess\ArchiveTableCreator;
use Piwik\Date;
use Piwik\Db;
use Piwik\Log\LoggerInterface;
use Piwik\Period;
use Piwik\Period\Range;
use Piwik\Plugins\CoreAdminHome\API as CoreAdminHomeAPI;
use Piwik\Segment;
use Piwik\SettingsServer;

/**
 * Builds the records of a saved visual report on display when browser archiving is on.
 *
 * Without a segment (or with a pre-processed one) browser archiving archives every plugin at once and never runs
 * again over a valid archive, so the archives that predate a new report, or a new revision, would show it empty
 * until core:archive processes its invalidations. Each such period is archived for this plugin alone, its
 * sub-periods first, which is what core:archive does: readers select the newest archive among the archive of all
 * plugins and the archives of this plugin.
 *
 * A period is up to date when one of its archives holds a record of the report, when it had no visit, or when it
 * was archived after the report last changed (a metric totalling 0 is not stored). A period without any archive is
 * left to the archiving of the request, once its sub-periods are up to date.
 */
class BrowserArchiver
{
    private const PLUGIN = 'DataExplorer';

    /**
     * @param array $report a hydrated row of ReportsDao
     * @param string[] $recordNames the records of the report, any one of them proves it was archived
     */
    public function archiveMissingRecords(array $report, array $recordNames, int $idSite, string $period, string $date, string $segment): void
    {
        if (SettingsServer::isArchivePhpTriggered() || $recordNames === []) {
            return;
        }

        $params = Archive::build($idSite, $period, $date, $segment)->getParams();
        $periods = $params->getPeriods();
        $segmentObj = $params->getSegment();
        if ($periods === []) {
            return;
        }

        $label = reset($periods)->getLabel();
        if (!Rules::isArchivingEnabledFor([$idSite], $segmentObj, $label)) {
            return;
        }
        // other segments are archived per plugin in the browser, a new revision finds no archive and builds one
        if (!$segmentObj->isEmpty() && !Rules::isSegmentPreProcessed([$idSite], $segmentObj)) {
            return;
        }

        try {
            $this->archivePeriods(array_values($periods), $recordNames, (string) $report['updated_date'], $idSite, $segmentObj);
        } catch (\Exception $e) {
            // the report then shows what the archives hold, as before
            StaticContainer::get(LoggerInterface::class)->warning('DataExplorer: browser archiving of saved report {idreport} failed: {message}', [
                'idreport' => (int) $report['idreport'],
                'message' => $e->getMessage(),
            ]);
        }
    }

    /**
     * @param Period[] $periods
     * @param string[] $recordNames
     */
    private function archivePeriods(array $periods, array $recordNames, string $changedAt, int $idSite, Segment $segment): void
    {
        foreach ($this->periodsToArchive($periods, $recordNames, $changedAt, $idSite, $segment) as [$period, $hasArchive]) {
            if ($period->getLabel() !== 'day') {
                $this->archivePeriods(array_values($period->getSubperiods()), $recordNames, $changedAt, $idSite, $segment);
            }
            if ($hasArchive) {
                $this->archiveThisPluginAlone($idSite, $period, $segment);
            }
        }
    }

    /**
     * @param Period[] $periods
     * @param string[] $recordNames
     * @return array<int, array{0: Period, 1: bool}> the periods not up to date, and whether they have an archive
     */
    private function periodsToArchive(array $periods, array $recordNames, string $changedAt, int $idSite, Segment $segment): array
    {
        $archivable = Date::now()->addDay(2);
        $periods = array_values(array_filter($periods, fn (Period $period) => !$period->getDateStart()->isLater($archivable)));
        if ($periods === []) {
            return [];
        }

        // core selects archive ids of ranges one range at a time
        $batches = [];
        foreach ($periods as $period) {
            if ($period instanceof Range) {
                $batches[] = [$period];
            } else {
                $batches['periods'][] = $period;
            }
        }

        $result = [];
        foreach ($batches as $batch) {
            $idsByRange = [];
            foreach (ArchiveSelector::getArchiveIds([$idSite], $batch, $segment, [self::PLUGIN]) as $idsByDate) {
                foreach ($idsByDate as $dateRange => $ids) {
                    $idsByRange[$dateRange] = array_merge($idsByRange[$dateRange] ?? [], array_map('intval', $ids));
                }
            }

            foreach ($batch as $period) {
                $ids = array_values(array_unique($idsByRange[$period->getRangeString()] ?? []));
                if ($ids === []) {
                    $result[] = [$period, false];
                } elseif (!$this->isUpToDate($period, $ids, $recordNames, $changedAt)) {
                    $result[] = [$period, true];
                }
            }
        }

        return $result;
    }

    /**
     * @param int[] $ids
     * @param string[] $recordNames
     */
    private function isUpToDate(Period $period, array $ids, array $recordNames, string $changedAt): bool
    {
        $numericTable = ArchiveTableCreator::getNumericTable($period->getDateStart(), false);
        $blobTable = ArchiveTableCreator::getBlobTable($period->getDateStart(), false);
        $idList = implode(',', $ids);

        $names = array_merge($recordNames, ['nb_visits']);
        $rows = empty($numericTable) ? [] : Db::fetchAll(
            'SELECT `name`, `value`, `ts_archived` FROM `' . $numericTable . '`
              WHERE `idarchive` IN (' . $idList . ') AND (`name` IN (' . Common::getSqlStringFieldsArray($names) . ') OR `name` LIKE ?)',
            array_merge($names, ['done%'])
        );

        $visits = null;
        $latest = '';
        foreach ($rows as $row) {
            if (in_array($row['name'], $recordNames, true)) {
                return true;
            }
            if ($row['name'] === 'nb_visits') {
                $visits = max((float) $visits, (float) $row['value']);
            } elseif ((string) $row['ts_archived'] > $latest) {
                $latest = (string) $row['ts_archived'];
            }
        }

        if ($visits !== null && $visits <= 0) {
            return true;
        }
        if ($latest > $changedAt) {
            return true;
        }

        return !empty($blobTable) && Db::fetchOne(
            'SELECT 1 FROM `' . $blobTable . '` WHERE `idarchive` IN (' . $idList . ') AND `name` IN (' . Common::getSqlStringFieldsArray($recordNames) . ') LIMIT 1',
            $recordNames
        ) !== false;
    }

    /**
     * What core:archive runs for an invalidation of this plugin: its archive alone, sub-periods resolved to the
     * newest archive of each, as a super user since the viewer may not archive.
     */
    private function archiveThisPluginAlone(int $idSite, Period $period, Segment $segment): void
    {
        $date = $period instanceof Range ? $period->getRangeString() : $period->getDateStart()->toString();
        $segmentString = $segment->isEmpty() ? false : $segment->getOriginalString();

        Access::doAsSuperUser(function () use ($idSite, $period, $date, $segmentString) {
            Context::executeWithQueryParameters(['trigger' => 'archivephp', 'pluginOnly' => '1'], function () use ($idSite, $period, $date, $segmentString) {
                CoreAdminHomeAPI::getInstance()->archiveReports($idSite, $period->getLabel(), $date, $segmentString, self::PLUGIN);
            });
        });
    }
}
