<?php

declare(strict_types=1);

namespace Roots\Bedrock\Test\PluginRepo;

use Codeception\Test\Unit;
use Mockery;
use Roots\Bedrock\PluginRepo\AllPluginRepo;
use Roots\Bedrock\WordPress;

class AllPluginRepoTest extends Unit
{
    public function testAll()
    {
        $expectedPath = '/../plugins';
        $expected = [
            'a.php' => [
                'Name' => 'a',
            ],
            'b/c.php' => [
                'Name' => 'bc',
            ],
        ];

        $wordPress = Mockery::mock(WordPress::class);
        $wordPress->expects()
                  ->getPlugins($expectedPath)
                  ->andReturn($expected);

        $subject = new AllPluginRepo($expectedPath, $wordPress);
        $actual = $subject->all();
        $this->assertSame($expected, $actual);
    }

    public function testAllFiles()
    {
        $expectedPath = '/../plugins';
        $plugins = [
            'a.php' => [
                'Name' => 'a',
            ],
            'b/c.php' => [
                'Name' => 'bc',
            ],
        ];
        $expected = ['a.php', 'b/c.php'];

        $wordPress = Mockery::mock(WordPress::class);
        $wordPress->expects()
                  ->getPlugins($expectedPath)
                  ->andReturn($plugins);

        $subject = new AllPluginRepo($expectedPath, $wordPress);
        $actual = $subject->allFiles();
        $this->assertSame($expected, $actual);
    }
}
