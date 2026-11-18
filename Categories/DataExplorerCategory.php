<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Categories;

use Piwik\Category\Category;

class DataExplorerCategory extends Category
{
    protected $id = ExploreSubcategory::CATEGORY;
    // right before Custom Reports (65), the other place reports are built
    protected $order = 64;
    protected $icon = 'icon-table';
}
