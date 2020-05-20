<?php

declare(strict_types=1);

namespace Roots\Bedrock\MUPluginRepos;

interface PluginRepoInterface
{
    // TODO: Name a better name. This is actually the plugin file name.
    public function allNames(): array;

    public function allPlugins(): array;
}
