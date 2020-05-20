<?php

declare(strict_types=1);

namespace Roots\Bedrock\Test;

use Codeception\Test\Unit;
use Mockery;
use Roots\Bedrock\Activator;
use Roots\Bedrock\CacheStore;
use Roots\Bedrock\PluginRepo\PluginRepoInterface;
use Roots\Bedrock\WordPress;

class ActivatorTest extends Unit
{
    public function testRun()
    {
        $allFiles = ['aaa', 'bbb', 'ccc', 'ddd'];

        $pluginRepo = Mockery::mock(PluginRepoInterface::class);
        $pluginRepo->expects()
                   ->allFiles()
                   ->andReturn($allFiles);

        $cacheStore = Mockery::mock(CacheStore::class);
        $cacheStore->expects()
                   ->isActivated('aaa')
                   ->andReturn(true);
        $cacheStore->expects()
                   ->isActivated('bbb')
                   ->andReturn(true);
        $cacheStore->expects()
                   ->isActivated('ccc')
                   ->andReturn(false);
        $cacheStore->expects()
                   ->isActivated('ddd')
                   ->andReturn(false);
        $cacheStore->expects()
                   ->resetActivated(...$allFiles);

        $wordPress = Mockery::mock(WordPress::class);
        $wordPress->expects()
                  ->doAction('activate_ccc');
        $wordPress->expects()
                  ->doAction('activate_ddd');

        $subject = new Activator($wordPress, $pluginRepo, $cacheStore);
        $subject->run();
    }
}
