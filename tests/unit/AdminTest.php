<?php

declare(strict_types=1);

namespace Roots\Bedrock\Test;

use Codeception\Test\Unit;
use Mockery;
use Roots\Bedrock\Admin;
use Roots\Bedrock\PluginRepo\PluginRepoInterface;
use Roots\Bedrock\WordPress;
use stdClass;

class AdminTest extends Unit
{
    public function testSkipIfTypeIsNotMustUse()
    {
        $wordPress = Mockery::mock(WordPress::class);
        $allPluginRepo = Mockery::mock(PluginRepoInterface::class);
        $autoloadPluginRepo = Mockery::mock(PluginRepoInterface::class);

        $expected = new stdClass();

        $subject = new Admin($wordPress, $allPluginRepo, $autoloadPluginRepo);

        $actual = $subject->showInAdmin($expected, 'xxx');

        $this->assertSame($expected, $actual);
    }

    public function testSkipIfCurrentScreenIsPluginsScreen()
    {
        $wordPress = Mockery::mock(WordPress::class);
        $wordPress->expects()
                  ->getCurrentScreenBase()
                  ->andReturn('xxx');
        $wordPress->expects()
                  ->getPluginScreenBase()
                  ->andReturn('yyy');
        $allPluginRepo = Mockery::mock(PluginRepoInterface::class);
        $autoloadPluginRepo = Mockery::mock(PluginRepoInterface::class);

        $expected = new stdClass();

        $subject = new Admin($wordPress, $allPluginRepo, $autoloadPluginRepo);

        $actual = $subject->showInAdmin($expected, 'mustuse');

        $this->assertSame($expected, $actual);
    }

    public function testSkipIfCurrentUserCannotActivatePlugins()
    {
        $wordPress = Mockery::mock(WordPress::class);
        $wordPress->expects()
                  ->getCurrentScreenBase()
                  ->andReturn('xxx');
        $wordPress->expects()
                  ->getPluginScreenBase()
                  ->andReturn('xxx');
        $wordPress->expects()
                  ->currentUserCan('activate_plugins')
                  ->andReturn(false);

        $allPluginRepo = Mockery::mock(PluginRepoInterface::class);
        $autoloadPluginRepo = Mockery::mock(PluginRepoInterface::class);

        $expected = new stdClass();

        $subject = new Admin($wordPress, $allPluginRepo, $autoloadPluginRepo);

        $actual = $subject->showInAdmin($expected, 'mustuse');

        $this->assertSame($expected, $actual);
    }

    public function testShowInAdmin()
    {
        $wordPress = Mockery::mock(WordPress::class);
        $wordPress->expects()
                  ->getCurrentScreenBase()
                  ->andReturn('xxx');
        $wordPress->expects()
                  ->getPluginScreenBase()
                  ->andReturn('xxx');
        $wordPress->expects()
                  ->currentUserCan('activate_plugins')
                  ->andReturn(true);

        $allPluginRepo = Mockery::mock(PluginRepoInterface::class)
                                ->allFiles()
                                ->andReturn(['aaa/aaa.php', 'bbb/bbb.php', 'zzz/zzz.php']);

        $autoloadPluginRepo = Mockery::mock(PluginRepoInterface::class)
                                ->allFiles()
                                ->andReturn(['aaa/aaa.php', 'bbb/bbb.php', 'ccc/ccc.php', 'ddd/ddd.php']);

        $subject = new Admin($wordPress, $allPluginRepo, $autoloadPluginRepo);

        $actual = $subject->showInAdmin(new stdClass(), 'mustuse');

        $this->assertFalse($actual);
        $this->assertStringEndsWith( 'ccc/ccc.php', ' *');
        $this->assertStringEndsWith( 'ddd/ddd.php', ' *');
    }
}
