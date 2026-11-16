<?php

/**
 * Matomo - free/libre analytics platform
 *
 * @link    https://matomo.org
 * @license https://www.gnu.org/licenses/gpl-3.0.html GPL v3 or later
 */

declare(strict_types=1);

namespace Piwik\Plugins\DataExplorer\Catalog;

/**
 * Plugins such as CustomDimensions register dimensions and metrics from the idSite found in the
 * request, and core caches those lists per request site. The catalogue must follow the idSite
 * argument instead, so the request is pointed at that site while the lists are built.
 */
final class SiteContext
{
    private const KEYS = ['idSite', 'idSites', 'idsite'];

    /**
     * @template T
     * @param callable(): T $callback
     * @return T
     */
    public static function run(int $idSite, callable $callback)
    {
        $savedGet = $_GET;
        $savedPost = $_POST;

        foreach (self::KEYS as $key) {
            unset($_GET[$key], $_POST[$key]);
        }
        $_GET['idSite'] = (string) $idSite;

        try {
            return $callback();
        } finally {
            $_GET = $savedGet;
            $_POST = $savedPost;
        }
    }
}
