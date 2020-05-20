<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use Roots\Bedrock\MUPluginRepos\PluginRepoInterface;

class Admin
{
    /** @var PluginRepoInterface */
    protected $allMUPluginRepo;

    /** @var PluginRepoInterface */
    protected $autoloadMUPluginRepo;

    /**
     * Admin constructor.
     *
     * @param PluginRepoInterface $allMUPluginRepo
     * @param PluginRepoInterface $autoloadMUPluginRepo
     */
    public function __construct(PluginRepoInterface $allMUPluginRepo, PluginRepoInterface $autoloadMUPluginRepo)
    {
        $this->allMUPluginRepo = $allMUPluginRepo;
        $this->autoloadMUPluginRepo = $autoloadMUPluginRepo;
    }

    /**
     * Filter show_advanced_plugins to display the autoloaded plugins.
     *
     * @param $show bool Whether to show the advanced plugins for the specified plugin type.
     * @param $type string The plugin type, i.e., `mustuse` or `dropins`
     *
     * @return bool We return `false` to prevent WordPress from overriding our work
     * {@internal We add the plugin details ourselves, so we return false to disable the filter.}
     */
    public function showInAdmin($show, $type)
    {
        $screen = get_current_screen();
        $current = is_multisite() ? 'plugins-network' : 'plugins';

        if ($screen->base !== $current || $type !== 'mustuse' || ! current_user_can('activate_plugins')) {
            return $show;
        }

        $autoloadMUPluginNames = $this->autoloadMUPluginRepo->allNames();
        $allPlugins = $this->allMUPluginRepo->allPlugins();
        foreach ($allPlugins as $pluginName => $plugin) {
            if (in_array($pluginName, $autoloadMUPluginNames, true)) {
                $allPlugins[$pluginName]['Name'] .= ' *';
            }
        }

        $GLOBALS['plugins']['mustuse'] = $allPlugins;

        return false;
    }
}
