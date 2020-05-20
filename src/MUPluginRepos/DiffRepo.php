<?php

declare(strict_types=1);

namespace Roots\Bedrock\MUPluginRepos;

// REVIEW: Overkill?
// TODO: Name a better name.
class DiffRepo implements PluginRepoInterface
{
    /** @var PluginRepoInterface */
    protected $universalRepo;

    /** @var PluginRepoInterface[] */
    protected $rejectRepos;

    public function __construct(PluginRepoInterface $universalRepo, PluginRepoInterface ...$rejectRepos)
    {
        $this->universalRepo = $universalRepo;
        $this->rejectRepos = $rejectRepos;
    }

    // REVIEW: Do we need memoization here?
    // TODO: Name a better name.
    public function allNames(): array
    {
        $rejects = array_map(function (PluginRepoInterface $pluginRepo): array {
            return $pluginRepo->allNames();
        }, $this->rejectRepos);

        return array_diff(
            $this->universalRepo->allNames(),
            ...$rejects
        );
    }

    public function allPlugins(): array
    {
        // TODO.
        return [];
    }
}
