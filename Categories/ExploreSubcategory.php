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
 * "Data Explorer > Explore", the page of the explorer widget.
 */
class ExploreSubcategory extends Subcategory
{
    public const CATEGORY = 'DataExplorer_DataExplorer';
    public const ID = 'DataExplorer_Explore';

    protected $categoryId = self::CATEGORY;
    protected $id = self::ID;
    protected $order = 1;
}
