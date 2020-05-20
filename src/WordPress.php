<?php

declare(strict_types=1);

namespace Roots\Bedrock;

use WP_Screen;

class WordPress
{
    public function getPlugins(string $pluginFolder): array
    {
        if (! function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return get_plugins($pluginFolder);
    }

    public function getMUPlugins(): array
    {
        if (! function_exists('get_mu_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }

        return get_mu_plugins();
    }

    public function doAction(string $tag): void
    {
        do_action($tag);
    }

    public function getCurrentScreenBase(): ?string
    {
        $screen = get_current_screen();

        return $screen instanceof WP_Screen
            ? ($screen->base ?? null)
            : null;
    }

    public function currentUserCan(string $capability): bool
    {
        return current_user_can($capability);
    }

    public function getPluginScreenBase(): string
    {
        return is_multisite() ? 'plugins-network' : 'plugins';
    }
}
