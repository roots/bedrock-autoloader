<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use Roots\Bedrock\MUPluginRepos\AllMUPluginRepo;
use Roots\Bedrock\MUPluginRepos\DiffRepo;
use Roots\Bedrock\MUPluginRepos\NormalMUPluginRepo;

/**
 * Class Autoloader
 *
 * @package Roots\Bedrock
 * @author  Roots
 * @link    https://roots.io/
 */
class Autoloader
{
    public static function init()
    {
        $allMUPluginRepo = new AllMUPluginRepo('/../' . basename(WPMU_PLUGIN_DIR));
        $autoloadMUPluginRepo = new DiffRepo(
            $allMUPluginRepo,
            new NormalMUPluginRepo()
        );

        $loader = new Loader($autoloadMUPluginRepo);
        $loader->run();

        $cacheStore = new CacheStore();
        $activator = new Activator($autoloadMUPluginRepo, $cacheStore);
        $activator->run();

        $admin = new Admin($allMUPluginRepo, $autoloadMUPluginRepo);
        // REVIEW: Should we add_filter here?
        add_filter('show_advanced_plugins', [$admin, 'showInAdmin'], 0, 2);
    }
}
