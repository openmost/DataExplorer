<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Categories;

use Piwik\Category\Subcategory;

/**
 * "Data Explorer > Manage explorations", the list of the reports saved on the website, last in the menu.
 */
class ManageSubcategory extends Subcategory
{
    public const ID = 'DataExplorer_ManageReports';

    protected $categoryId = ExploreSubcategory::CATEGORY;
    protected $id = self::ID;
    protected $order = 9999;
}
