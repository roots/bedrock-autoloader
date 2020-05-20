<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use Roots\Bedrock\PluginRepo\PluginRepoInterface;

class Activator
{
    /** @var WordPress */
    protected $wordPress;
    /** @var PluginRepoInterface */
    protected $pluginRepo;
    /** @var CacheStore */
    protected $cacheStore;

    /**
     * Activator constructor.
     *
     * @param WordPress           $wordPress
     * @param PluginRepoInterface $pluginRepo
     * @param CacheStore          $cacheStore
     */
    public function __construct(WordPress $wordPress, PluginRepoInterface $pluginRepo, CacheStore $cacheStore)
    {
        $this->wordPress = $wordPress;
        $this->pluginRepo = $pluginRepo;
        $this->cacheStore = $cacheStore;
    }

    public function run(): void
    {
        $pluginNames = $this->pluginRepo->allFiles();

        array_map(function (string $pluginName): void {
            if (! $this->cacheStore->isActivated($pluginName)) {
                $this->wordPress->doAction('activate_' . $pluginName);
            }
        }, $pluginNames);

        $this->cacheStore->resetActivated(...$pluginNames);
    }
}
