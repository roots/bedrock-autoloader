<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use Roots\Bedrock\MUPluginRepos\PluginRepoInterface;

class Loader
{
    /** @var PluginRepoInterface */
    protected $pluginRepo;

    /**
     * Loader constructor.
     *
     * @param PluginRepoInterface $pluginRepo
     */
    public function __construct(PluginRepoInterface $pluginRepo)
    {
        $this->pluginRepo = $pluginRepo;
    }

    public function run(): void
    {
        $pluginNames = $this->pluginRepo->allFiles();

        array_map(function (string $pluginName) {
            require_once WPMU_PLUGIN_DIR . '/' . $pluginName;
        }, $pluginNames);
    }
}
