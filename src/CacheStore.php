<?php

declare(strict_types=1);

namespace Roots\Bedrock;

// TODO: Name a better name.
class CacheStore
{
    protected const CACHE_KEY = 'bedrock_autoloader_activated_plugins';

    public function isActivated(string $pluginName): bool
    {
        return in_array(
            $pluginName,
            $this->activated(),
            true
        );
    }

    protected function activated(): array
    {
        $value = get_site_option(static::CACHE_KEY, []);
        return is_array($value) ? $value : [];
    }

    // TODO: Name a better name.
    public function resetActivated(string ...$pluginNames): void
    {
        update_site_option(static::CACHE_KEY, $pluginNames);
    }
}
