<?php

declare(strict_types=1);

namespace Roots\Bedrock\MUPluginRepos;

use function get_mu_plugins;

class NormalMUPluginRepo implements PluginRepoInterface
{
    public function allNames(): array
    {
        return array_keys(
            $this->allPlugins()
        );
    }

    public function allPlugins(): array
    {
        if (! function_exists('get_mu_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        // REVIEW: Do we need memoization here?
        return get_mu_plugins();
    }
}
