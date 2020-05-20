<?php

declare(strict_types=1);

namespace Roots\Bedrock\PluginRepo;

interface PluginRepoInterface
{
    public function allFiles(): array;

    public function all(): array;
}
