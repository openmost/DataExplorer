<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

namespace Piwik\Plugins\DataExplorer;

/**
 * Makes core archive the plugin: its only RecordBuilder (one SavedReport per saved visual report) is added through
 * the Archiver.addRecordBuilders event, so core cannot discover it from the RecordBuilders folder.
 */
class Archiver extends \Piwik\Plugin\Archiver
{
}
