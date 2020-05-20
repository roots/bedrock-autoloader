<?php

declare(strict_types=1);

namespace Roots\Bedrock\PluginRepo;

use BadMethodCallException;

// REVIEW: Overkill?
// TODO: Name a better name.
class DiffRepo implements PluginRepoInterface
{
    /** @var PluginRepoInterface */
    protected $universalRepo;
    /** @var PluginRepoInterface[] */
    protected $rejectRepos;
    /** @var String[] */
    protected $files;

    public function __construct(PluginRepoInterface $universalRepo, PluginRepoInterface ...$rejectRepos)
    {
        $this->universalRepo = $universalRepo;
        $this->rejectRepos = $rejectRepos;
    }

    public function allFiles(): array
    {
        $this->files = $this->files ?? $this->filesDiff();
        return $this->files;
    }

    // TODO: Name a better name.
    public function filesDiff(): array
    {
        $rejects = array_map(function (PluginRepoInterface $pluginRepo): array {
            return $pluginRepo->allFiles();
        }, $this->rejectRepos);

        return array_diff(
            $this->universalRepo->allFiles(),
            ...$rejects
        );
    }

    public function all(): array
    {
        // TODO.
        throw new BadMethodCallException(__METHOD__ . ' not implemented.');
    }
}
