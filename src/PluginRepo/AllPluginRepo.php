<?php

declare(strict_types=1);

namespace Roots\Bedrock\PluginRepo;

use Roots\Bedrock\WordPress;

class AllPluginRepo implements PluginRepoInterface
{
    /** @var string Relative path to the mu-plugins folder. Relative to WP_PLUGIN_DIR. */
    protected $relativePath;
    /** @var WordPress */
    protected $wordPress;
    /** @var array[] */
    protected $plugins;

    /**
     * AutoloadPluginRepo constructor.
     *
     * @param string    $relativePath Relative path to the mu-plugins folder. Relative to WP_PLUGIN_DIR.
     * @param WordPress $wordPress
     */
    public function __construct(string $relativePath, WordPress $wordPress)
    {
        $this->relativePath = $relativePath;
        $this->wordPress = $wordPress;
    }

    public function allFiles(): array
    {
        return array_keys(
            $this->all()
        );
    }

    public function all(): array
    {
        $this->plugins = $this->plugins ?? $this->wordPress->getPlugins($this->relativePath);
        return $this->plugins;
    }
}
