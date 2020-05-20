<?php

declare(strict_types=1);

namespace Roots\Bedrock\PluginRepo;

use Roots\Bedrock\WordPress;

class MUPluginRepo implements PluginRepoInterface
{
    /** @var WordPress */
    protected $wordPress;
    /** @var array[] */
    protected $plugins;

    /**
     * NormalMUPluginRepo constructor.
     *
     * @param WordPress $wordPress
     */
    public function __construct(WordPress $wordPress)
    {
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
        $this->plugins = $this->plugins ?? $this->wordPress->getMUPlugins();

        return $this->plugins;
    }
}
