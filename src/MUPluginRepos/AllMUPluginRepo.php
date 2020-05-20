<?php

declare(strict_types=1);

namespace Roots\Bedrock\MUPluginRepos;

use function get_plugins;

class AllMUPluginRepo implements PluginRepoInterface
{
    /** @var string Relative path to the mu-plugins folder. Relative to WP_PLUGIN_DIR. */
    protected $relativePath;

    /**
     * AutoloadPluginRepo constructor.
     *
     * @param string $relativePath Relative path to the mu-plugins folder. Relative to WP_PLUGIN_DIR.
     */
    public function __construct(string $relativePath)
    {
        $this->relativePath = $relativePath;
    }

    public function allNames(): array
    {
        return array_keys(
            $this->allPlugins()
        );
    }

    public function allPlugins(): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // REVIEW: Do we need memoization here?
        return get_plugins($this->relativePath);
    }
}
