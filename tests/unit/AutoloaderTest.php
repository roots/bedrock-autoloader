<?php

namespace unit;

use Roots\Bedrock\Autoloader\Autoloader;

class AutoloaderTest extends \WP_Mock\Tools\TestCase
{
    private static $plugins = [
        '10-fake/10-fake.php' => ['Name' => 'UwU', 'Version' => '1.0.0'],
        '20-fake/20-fake.php' => ['Name' => '0w0', 'Version' => '1.0.0'],
    ];

    public function setUp(): void
    {
        \WP_Mock::setUp();
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

    private function makeAutoloader(string $muPluginDir = WPMU_PLUGIN_DIR): Autoloader
    {
        return new Autoloader($muPluginDir);
    }

    private function mockPluginData(): void
    {
        \WP_Mock::userFunction('get_plugin_data', [
            'return' => function ($file) {
                $relativePath = basename(dirname($file)).'/'.basename($file);

                return self::$plugins[$relativePath] ?? ['Name' => ''];
            },
        ]);
    }

    private function mockBaseWpFunctions(): void
    {
        \WP_Mock::userFunction('is_admin', ['return' => false]);
        $this->mockPluginData();
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

    public function test_constructor_has_no_side_effects()
    {
        $a = $this->makeAutoloader();

        $this->assertNull($this->getProperty($a, 'cache'));
        $this->assertNull($this->getProperty($a, 'autoPlugins'));
        $this->assertNull($this->getProperty($a, 'loadedPluginEntryPoints'));
        $this->assertFalse($this->getProperty($a, 'booted'));
    }

    public function test_boot_is_idempotent()
    {
        $this->mockBaseWpFunctions();

        $a = $this->makeAutoloader();
        $a->boot();

        $cacheAfterFirst = $this->getProperty($a, 'cache');

        // Second boot should be a no-op — loadPlugins won't run again
        $a->boot();

        $cacheAfterSecond = $this->getProperty($a, 'cache');
        $this->assertSame($cacheAfterFirst, $cacheAfterSecond);
        $this->assertTrue($this->getProperty($a, 'booted'));
    }

    public function test_load_plugins()
    {
        $this->mockBaseWpFunctions();

        $a = $this->makeAutoloader();
        $a->boot();

        $cache = $this->getProperty($a, 'cache');
        $this->assertCount(2, $cache['plugins']);
        $this->assertEquals(2, $cache['count']);

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertCount(2, $loaded);
        $this->assertContains('10-fake/10-fake.php', $loaded);
        $this->assertContains('20-fake/20-fake.php', $loaded);
    }

    public function test_filtered_plugin_does_not_load()
    {
        $this->mockBaseWpFunctions();

        \WP_Mock::onFilter('bedrock_autoloader_load_plugins')
            ->with(array_keys(self::$plugins), self::$plugins)
            ->reply(['20-fake/20-fake.php']);

        $a = $this->makeAutoloader();
        $a->boot();

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertCount(1, $loaded);
        $this->assertContains('20-fake/20-fake.php', $loaded);
        $this->assertNotContains('10-fake/10-fake.php', $loaded);
    }

    public function test_filtered_plugin_activation_is_deferred()
    {
        $a = $this->makeAutoloader();

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

    public function test_unfiltered_plugin_gets_activate_hook()
    {
        $a = $this->makeAutoloader();

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

    public function test_removed_plugin_is_pruned_from_pending()
    {
        \WP_Mock::userFunction('is_admin', ['return' => false]);
        $this->mockPluginData();
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

        // Pruned new_plugins should contain both discovered plugins (stale one removed)
        $expectedNewPlugins = self::$plugins;
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

        $a = $this->makeAutoloader();
        $a->boot();

        $cache = $this->getProperty($a, 'cache');
        $this->assertArrayNotHasKey('removed-plugin/removed-plugin.php', $cache['plugins']);
    }

    public function test_malformed_filter_return_is_sanitized()
    {
        $this->mockBaseWpFunctions();

        \WP_Mock::onFilter('bedrock_autoloader_load_plugins')
            ->with(array_keys(self::$plugins), self::$plugins)
            ->reply('not-an-array');

        $a = $this->makeAutoloader();
        $a->boot();

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertIsArray($loaded);
        $this->assertEmpty($loaded);
    }

    public function test_filter_cannot_inject_arbitrary_paths()
    {
        $this->mockBaseWpFunctions();

        \WP_Mock::onFilter('bedrock_autoloader_load_plugins')
            ->with(array_keys(self::$plugins), self::$plugins)
            ->reply(['../../etc/passwd', '20-fake/20-fake.php']);

        $a = $this->makeAutoloader();
        $a->boot();

        $loaded = $this->getProperty($a, 'loadedPluginEntryPoints');
        $this->assertCount(1, $loaded);
        $this->assertContains('20-fake/20-fake.php', $loaded);
        $this->assertNotContains('../../etc/passwd', $loaded);
    }

    public function test_discovery_scans_mu_plugin_dir()
    {
        $this->mockPluginData();

        $a = $this->makeAutoloader();

        $reflect = new \ReflectionClass(Autoloader::class);
        $method = $reflect->getMethod('discoverPlugins');
        $method->setAccessible(true);
        $plugins = $method->invoke($a);

        $this->assertCount(2, $plugins);
        $this->assertArrayHasKey('10-fake/10-fake.php', $plugins);
        $this->assertArrayHasKey('20-fake/20-fake.php', $plugins);

        $keys = array_keys($plugins);
        $this->assertEquals('10-fake/10-fake.php', $keys[0]);
        $this->assertEquals('20-fake/20-fake.php', $keys[1]);
    }

    public function test_discovery_uses_injected_path()
    {
        $this->mockPluginData();

        $a = $this->makeAutoloader('/nonexistent/empty-dir');

        $reflect = new \ReflectionClass(Autoloader::class);
        $method = $reflect->getMethod('discoverPlugins');
        $method->setAccessible(true);
        $plugins = $method->invoke($a);

        $this->assertEmpty($plugins);
    }

    public function test_count_excludes_non_plugin_directories()
    {
        $this->mockPluginData();

        $a = $this->makeAutoloader();

        $reflect = new \ReflectionClass(Autoloader::class);
        $discover = $reflect->getMethod('discoverPlugins');
        $discover->setAccessible(true);
        $plugins = $discover->invoke($a);

        $countDirs = $reflect->getMethod('countPluginDirs');
        $countDirs->setAccessible(true);
        $count = $countDirs->invoke($a, $plugins);

        // fixtures/mu-plugins has 3 subdirs (10-fake, 20-fake, not-a-plugin)
        // but only 2 are valid plugins — count should be 2
        $this->assertEquals(2, $count);
    }
}
