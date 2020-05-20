<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use Roots\Bedrock\MUPluginRepos\PluginRepoInterface;

class Activator
{
    /** @var PluginRepoInterface */
    protected $pluginRepo;

    /** @var CacheStore */
    protected $cacheStore;

    /**
     * Activator constructor.
     *
     * @param PluginRepoInterface $pluginRepo
     * @param CacheStore          $cacheStore
     */
    public function __construct(PluginRepoInterface $pluginRepo, CacheStore $cacheStore)
    {
        $this->pluginRepo = $pluginRepo;
        $this->cacheStore = $cacheStore;
    }

    public function run(): void
    {
        $pluginNames = $this->pluginRepo->allNames();

        array_map(function (string $pluginName): void {
            if (! $this->cacheStore->isActivated($pluginName)) {
                do_action('activate_' . $pluginName);
            }
        }, $pluginNames);

        $this->cacheStore->resetActivated(...$pluginNames);
    }
}
