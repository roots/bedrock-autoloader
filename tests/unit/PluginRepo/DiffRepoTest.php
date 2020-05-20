<?php

declare(strict_types=1);

namespace Roots\Bedrock\Test\PluginRepo;

use Codeception\Test\Unit;
use Mockery;
use Roots\Bedrock\PluginRepo\DiffRepo;
use Roots\Bedrock\PluginRepo\PluginRepoInterface;

class DiffRepoTest extends Unit
{
    public function testAllFiles()
    {
        $universalRepo = Mockery::mock(PluginRepoInterface::class);
        $universalRepo->expects()
                      ->allFiles()
                      ->andReturn(['a.php', 'b/c.php', 'd/e.php', 'f.php', 'g.php', 'h/i.php', 'j/k.php']);

        $rejectRepo = Mockery::mock(PluginRepoInterface::class);
        $rejectRepo->expects()
                   ->allFiles()
                   ->andReturn(['a.php', 'd/e.php', 'z.php']);

        $subject = new DiffRepo($universalRepo, $rejectRepo);
        $actual = $subject->allFiles();
        $this->assertSame(
            ['b/c.php', 'f.php', 'g.php', 'h/i.php', 'j/k.php'],
            array_values($actual)
        );
    }

    public function testAllFilesMultipleRejectRepos()
    {
        $universalRepo = Mockery::mock(PluginRepoInterface::class);
        $universalRepo->expects()
                      ->allFiles()
                      ->andReturn(['a.php', 'b/c.php', 'd/e.php', 'f.php', 'g.php', 'h/i.php', 'j/k.php']);

        $rejectRepo = Mockery::mock(PluginRepoInterface::class);
        $rejectRepo->expects()
                   ->allFiles()
                   ->andReturn(['a.php', 'd/e.php', 'z.php']);

        $rejectRepo2 = Mockery::mock(PluginRepoInterface::class);
        $rejectRepo2->expects()
                    ->allFiles()
                    ->andReturn(['d/e.php', 'g.php', 'y.php']);

        $subject = new DiffRepo($universalRepo, $rejectRepo, $rejectRepo2);
        $actual = $subject->allFiles();
        $this->assertSame(
            ['b/c.php', 'f.php', 'h/i.php', 'j/k.php'],
            array_values($actual)
        );
    }
}
