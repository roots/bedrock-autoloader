<?php

namespace unit;

use Roots\Bedrock\Autoloader;

class AutoloaderTest extends \WP_Mock\Tools\TestCase
{
    private static $plugins = [
        '10-fake/10-fake.php' => ['Name' => 'UwU', 'Version' => '1.0.0'],
        '20-fake/20-fake.php' => ['Name' => '0w0', 'Version' => '1.0.0'],
    ];

    public function setUp(): void
    {
        \WP_Mock::setUp();

        $reflect = new \ReflectionClass(Autoloader::class);
        $instance = $reflect->getProperty('instance');
        $instance->setAccessible(true);
        $instance->setValue(null, null);
    }

    public function tearDown(): void
    {
        \WP_Mock::tearDown();
    }

    private function getProperty(Autoloader $a, string $name)
    {
        $reflect = new \ReflectionClass(Autoloader::class);
        $prop = $reflect->getProperty($name);
        $prop->setAccessible(true);
        return $prop->getValue($a);
    }

    private function setProperty(Autoloader $a, string $name, $value): void
    {
        $reflect = new \ReflectionClass(Autoloader::class);
        $prop = $reflect->getProperty($name);
        $prop->setAccessible(true);
        $prop->setValue($a, $value);
    }

    private function mockBaseWpFunctions(): void
    {
        \WP_Mock::userFunction('is_admin', ['return' => false]);
        \WP_Mock::userFunction('get_plugins', [
            'args' => '/../mu-plugins',
            'return' => self::$plugins,
        ]);
        \WP_Mock::userFunction('get_mu_plugins', ['return' => []]);
        \WP_Mock::userFunction('get_site_option', [
            'args' => ['bedrock_autoloader'],
            'return' => false,
        ]);
        \WP_Mock::userFunction('get_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', []],
            'return' => [],
        ]);
        \WP_Mock::userFunction('update_site_option', ['return' => true]);
        \WP_Mock::userFunction('add_action', ['return' => true]);
        \WP_Mock::userFunction('add_filter', ['return' => true]);
    }

    public function testLoadPlugins()
    {
        $this->mockBaseWpFunctions();

        $a = new Autoloader();

        $cache = $this->getProperty($a, 'cache');
        $this->assertCount(2, $cache['plugins']);
        $this->assertEquals(2, $cache['count']);

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertCount(2, $loaded);
        $this->assertContains('10-fake/10-fake.php', $loaded);
        $this->assertContains('20-fake/20-fake.php', $loaded);
    }

    public function testFilteredPluginDoesNotLoad()
    {
        $this->mockBaseWpFunctions();

        \WP_Mock::onFilter('bedrock_autoloader_load_plugins')
            ->with(array_keys(self::$plugins), self::$plugins)
            ->reply(['20-fake/20-fake.php']);

        $a = new Autoloader();

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertCount(1, $loaded);
        $this->assertContains('20-fake/20-fake.php', $loaded);
        $this->assertNotContains('10-fake/10-fake.php', $loaded);
    }

    public function testFilteredPluginActivationIsDeferred()
    {
        $reflect = new \ReflectionClass(Autoloader::class);
        $a = $reflect->newInstanceWithoutConstructor();

        // 10-fake was filtered out on this request
        $this->setProperty($a, 'loadedPluginEntryPoints', ['20-fake/20-fake.php']);

        $newPlugins = [
            '10-fake/10-fake.php' => self::$plugins['10-fake/10-fake.php'],
        ];

        \WP_Mock::userFunction('get_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', []],
            'return' => $newPlugins,
        ]);

        $activatedPlugins = [];
        \WP_Mock::userFunction('do_action', [
            'return' => function () use (&$activatedPlugins) {
                $activatedPlugins[] = func_get_args()[0];
            },
        ]);

        \WP_Mock::userFunction('update_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', $newPlugins],
            'return' => true,
        ]);

        $a->pluginHooks();

        // activate_ hook should NOT have fired for the filtered plugin
        $this->assertNotContains('activate_10-fake/10-fake.php', $activatedPlugins);
        $this->assertConditionsMet();
    }

    public function testUnfilteredPluginGetsActivateHook()
    {
        $reflect = new \ReflectionClass(Autoloader::class);
        $a = $reflect->newInstanceWithoutConstructor();

        // 10-fake is now loaded (was previously filtered out)
        $this->setProperty($a, 'loadedPluginEntryPoints', [
            '10-fake/10-fake.php',
            '20-fake/20-fake.php',
        ]);

        $newPlugins = [
            '10-fake/10-fake.php' => self::$plugins['10-fake/10-fake.php'],
        ];

        \WP_Mock::userFunction('get_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', []],
            'return' => $newPlugins,
        ]);

        \WP_Mock::expectAction('activate_10-fake/10-fake.php');

        \WP_Mock::userFunction('update_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', []],
            'return' => true,
        ]);

        $a->pluginHooks();

        $this->assertConditionsMet();
    }

    public function testRemovedPluginIsPrunedFromPending()
    {
        \WP_Mock::userFunction('is_admin', ['return' => false]);
        \WP_Mock::userFunction('get_plugins', [
            'args' => '/../mu-plugins',
            'return' => [
                '10-fake/10-fake.php' => self::$plugins['10-fake/10-fake.php'],
            ],
        ]);
        \WP_Mock::userFunction('get_mu_plugins', ['return' => []]);
        \WP_Mock::userFunction('get_site_option', [
            'args' => ['bedrock_autoloader'],
            'return' => false,
        ]);

        // Stale plugin that was removed from disk
        \WP_Mock::userFunction('get_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', []],
            'return' => [
                'removed-plugin/removed-plugin.php' => ['Name' => 'Gone', 'Version' => '1.0.0'],
            ],
        ]);

        // Pruned new_plugins should only contain 10-fake
        $expectedNewPlugins = [
            '10-fake/10-fake.php' => self::$plugins['10-fake/10-fake.php'],
        ];
        \WP_Mock::userFunction('update_site_option', [
            'args' => ['bedrock_autoloader_new_plugins', $expectedNewPlugins],
            'return' => true,
        ]);
        \WP_Mock::userFunction('update_site_option', [
            'args' => ['bedrock_autoloader', \WP_Mock\Functions::type('array')],
            'return' => true,
        ]);
        \WP_Mock::userFunction('add_action', ['return' => true]);
        \WP_Mock::userFunction('add_filter', ['return' => true]);

        $a = new Autoloader();

        $cache = $this->getProperty($a, 'cache');
        $this->assertArrayNotHasKey('removed-plugin/removed-plugin.php', $cache['plugins']);
    }

    public function testMalformedFilterReturnIsSanitized()
    {
        $this->mockBaseWpFunctions();

        \WP_Mock::onFilter('bedrock_autoloader_load_plugins')
            ->with(array_keys(self::$plugins), self::$plugins)
            ->reply('not-an-array');

        $a = new Autoloader();

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertIsArray($loaded);
        $this->assertEmpty($loaded);
    }

    public function testFilterCannotInjectArbitraryPaths()
    {
        $this->mockBaseWpFunctions();

        \WP_Mock::onFilter('bedrock_autoloader_load_plugins')
            ->with(array_keys(self::$plugins), self::$plugins)
            ->reply(['../../etc/passwd', '20-fake/20-fake.php']);

        $a = new Autoloader();

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertCount(1, $loaded);
        $this->assertContains('20-fake/20-fake.php', $loaded);
        $this->assertNotContains('../../etc/passwd', $loaded);
    }

    /**
     * Run a callback with WP_PLUGIN_DIR temporarily removed,
     * exercising the fallback discovery path.
     */
    private function withFallbackDiscovery(callable $callback): void
    {
        $pluginsDir = WP_PLUGIN_DIR;
        $tempDir = $pluginsDir . '_disabled';
        rename($pluginsDir, $tempDir);

        try {
            \WP_Mock::userFunction('get_plugin_data', [
                'return' => function ($file) {
                    $relativePath = basename(dirname($file)) . '/' . basename($file);
                    $map = [
                        '10-fake/10-fake.php' => ['Name' => 'UwU', 'Version' => '1.0.0'],
                        '20-fake/20-fake.php' => ['Name' => '0w0', 'Version' => '1.0.0'],
                    ];
                    return $map[$relativePath] ?? ['Name' => ''];
                },
            ]);

            $reflect = new \ReflectionClass(Autoloader::class);
            $a = $reflect->newInstanceWithoutConstructor();
            $this->setProperty($a, 'relativePath', '/../' . basename(WPMU_PLUGIN_DIR));

            $callback($a, $reflect);
        } finally {
            rename($tempDir, $pluginsDir);
        }
    }

    public function testFallbackDiscoveryWhenPluginsDirMissing()
    {
        $this->withFallbackDiscovery(function (Autoloader $a, \ReflectionClass $reflect) {
            $method = $reflect->getMethod('discoverPlugins');
            $method->setAccessible(true);
            $plugins = $method->invoke($a);

            $this->assertCount(2, $plugins);
            $this->assertArrayHasKey('10-fake/10-fake.php', $plugins);
            $this->assertArrayHasKey('20-fake/20-fake.php', $plugins);

            $keys = array_keys($plugins);
            $this->assertEquals('10-fake/10-fake.php', $keys[0]);
            $this->assertEquals('20-fake/20-fake.php', $keys[1]);
        });
    }

    public function testCountExcludesNonPluginDirectories()
    {
        $this->withFallbackDiscovery(function (Autoloader $a, \ReflectionClass $reflect) {
            $discover = $reflect->getMethod('discoverPlugins');
            $discover->setAccessible(true);
            $plugins = $discover->invoke($a);

            $countDirs = $reflect->getMethod('countPluginDirs');
            $countDirs->setAccessible(true);
            $count = $countDirs->invoke($a, $plugins);

            // fixtures/mu-plugins has 3 subdirs (10-fake, 20-fake, not-a-plugin)
            // but only 2 are valid plugins — count should be 2
            $this->assertEquals(2, $count);
        });
    }
}
