<?php

declare(strict_types=1);

namespace Roots\Bedrock\Test\PluginRepo;

use Codeception\Test\Unit;
use Mockery;
use Roots\Bedrock\PluginRepo\MUPluginRepo;
use Roots\Bedrock\WordPress;

class MUPluginRepoTest extends Unit
{
    public function testAll()
    {
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
                  ->getMUPlugins()
                  ->andReturn($expected);

        $subject = new MUPluginRepo($wordPress);
        $actual = $subject->all();
        $this->assertSame($expected, $actual);
    }

    public function testAllFiles()
    {
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
                  ->getMUPlugins()
                  ->andReturn($plugins);

        $subject = new MUPluginRepo($wordPress);
        $actual = $subject->allFiles();
        $this->assertSame($expected, $actual);
    }
}
