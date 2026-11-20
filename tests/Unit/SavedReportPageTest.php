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
use Piwik\Plugins\DataExplorer\Categories\ExploreSubcategory;
use Piwik\Plugins\DataExplorer\Categories\ManageSubcategory;
use Piwik\Plugins\DataExplorer\Reports\GetSavedReport;

/**
 * @group DataExplorer
 * @group SavedReportPageTest
 * @group Plugins
 */
class SavedReportPageTest extends TestCase
{
    public function test_aReportOnAPageOfThePluginItself_getsItsOwnPage(): void
    {
        foreach ([ExploreSubcategory::ID, ManageSubcategory::ID] as $reserved) {
            $page = GetSavedReport::pageId(['idreport' => 19, 'category' => ExploreSubcategory::CATEGORY, 'subcategory' => $reserved]);
            $this->assertSame('19', $page, $reserved);
        }
    }

    public function test_aReportOnAnotherPage_staysThere(): void
    {
        $this->assertSame('General_Overview', GetSavedReport::pageId(['idreport' => 3, 'category' => 'General_Visitors', 'subcategory' => 'General_Overview']));
        $this->assertStringEndsWith('3', GetSavedReport::pageId(['idreport' => 3, 'category' => 'General_Visitors', 'subcategory' => '']));
    }
}
