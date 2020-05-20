<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use Roots\Bedrock\PluginRepo\PluginRepoInterface;

class Admin
{
    /** @var WordPress */
    protected $wordPress;
    /** @var PluginRepoInterface */
    protected $allMUPluginRepo;
    /** @var PluginRepoInterface */
    protected $autoloadMUPluginRepo;


    /**
     * Admin constructor.
     *
     * @param WordPress $wordPress
     * @param PluginRepoInterface $allMUPluginRepo
     * @param PluginRepoInterface $autoloadMUPluginRepo
     */
    public function __construct(
        WordPress $wordPress,
        PluginRepoInterface $allMUPluginRepo,
        PluginRepoInterface $autoloadMUPluginRepo
    ) {
        $this->wordPress = $wordPress;
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
        if (! $this->shouldShow((string) $type)) {
            return $show;
        }

        $autoloadMUPluginNames = $this->autoloadMUPluginRepo->allFiles();
        $allPlugins = $this->allMUPluginRepo->all();
        foreach ($allPlugins as $pluginName => $plugin) {
            if (in_array($pluginName, $autoloadMUPluginNames, true)) {
                $allPlugins[$pluginName]['Name'] .= ' *';
            }
        }

        $GLOBALS['plugins']['mustuse'] = $allPlugins;

        return false;
    }

    protected function shouldShow(string $type): bool
    {
        if ($type !== 'mustuse') {
            return false;
        }

        $currentScreenBase = $this->wordPress->getCurrentScreenBase();
        $pluginScreenBase = $this->wordPress->getPluginScreenBase();
        if ($currentScreenBase !== $pluginScreenBase) {
            return false;
        }

        return $this->wordPress->currentUserCan('activate_plugins');
    }
}
