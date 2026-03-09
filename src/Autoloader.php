<?php

namespace Roots\Bedrock;

/**
 * Class Autoloader
 * @package Roots\Bedrock
 * @author Roots
 * @link https://roots.io/
 */
class Autoloader
{
    /** @var static Singleton instance */
    private static $instance;

    /** @var array Store Autoloader cache and site option */
    private $cache;

    /** @var array Autoloaded plugins */
    private $autoPlugins;

    /** @var array Autoloaded mu-plugins */
    private $muPlugins;

    /** @var int Number of plugins */
    private $count;

    /** @var string Relative path to the mu-plugins dir */
    private $relativePath;

    /** @var array Entrypoints of all loaded plugins */
    private $loadedPluginEntryPoints;

    /**
     * Create singleton, populate vars, and set WordPress hooks
     */
    public function __construct()
    {
        if (isset(self::$instance)) {
            return;
        }

        self::$instance = $this;

        $this->relativePath = '/../' . basename(WPMU_PLUGIN_DIR);

        if (is_admin()) {
            add_filter('show_advanced_plugins', [$this, 'showInAdmin'], 0, 2);
        }

        $this->loadPlugins();
    }

   /**
    * Run some checks then autoload our plugins.
    */
    public function loadPlugins()
    {
        $this->checkCache();
        $this->validatePlugins();
        $this->countPlugins();

        $filtered = apply_filters('bedrock_autoloader_load_plugins', array_keys($this->cache['plugins']), $this->cache['plugins']);
        $this->loadedPluginEntryPoints = array_values(array_intersect((array) $filtered, array_keys($this->cache['plugins'])));
        array_map(static function ($plugin) {
            include_once WPMU_PLUGIN_DIR . '/' . $plugin;
        }, $this->loadedPluginEntryPoints);

        add_action('init', [$this, 'pluginHooks'], 0);
    }

    /**
     * Filter show_advanced_plugins to display the autoloaded plugins.
     * @param $show bool Whether to show the advanced plugins for the specified plugin type.
     * @param $type string The plugin type, i.e., `mustuse` or `dropins`
     * @return bool We return `false` to prevent WordPress from overriding our work
     * {@internal We add the plugin details ourselves, so we return false to disable the filter.}
     */
    public function showInAdmin($show, $type)
    {
        $screen = get_current_screen();
        $current = is_multisite() ? 'plugins-network' : 'plugins';

        if ($screen->base !== $current || $type !== 'mustuse' || !current_user_can('activate_plugins')) {
            return $show;
        }

        $this->updateCache();

        $this->autoPlugins = array_map(function ($auto_plugin) {
            $auto_plugin['Name'] .= ' *';
            return $auto_plugin;
        }, $this->autoPlugins);

        $GLOBALS['plugins']['mustuse'] = array_unique(array_merge($this->autoPlugins, $this->muPlugins), SORT_REGULAR);

        return false;
    }

    /**
     * This sets the cache or calls for an update
     */
    private function checkCache()
    {
        $cache = get_site_option('bedrock_autoloader');

        if ($cache === false || (isset($cache['plugins'], $cache['count']) && $this->countPluginDirs($cache['plugins']) !== $cache['count'])) {
            $this->updateCache();
            return;
        }

        $this->cache = $cache;
    }

    /**
     * Discover autoloadable plugins in the mu-plugins directory.
     *
     * Uses get_plugins() when WP_PLUGIN_DIR exists, otherwise falls back
     * to scanning WPMU_PLUGIN_DIR subdirectories for valid plugin headers.
     *
     * @return array Plugin data keyed by relative path (e.g. 'plugin-dir/plugin-file.php')
     */
    private function discoverPlugins()
    {
        require_once ABSPATH . 'wp-admin/includes/plugin.php';

        if (is_dir(WP_PLUGIN_DIR)) {
            $plugins = get_plugins($this->relativePath);
            if (!empty($plugins)) {
                return $plugins;
            }
        }

        $plugins = [];

        foreach ((array) glob(WPMU_PLUGIN_DIR . '/*/*.php', GLOB_NOSORT) as $file) {
            $data = get_plugin_data($file, false, false);

            if (empty($data['Name'])) {
                continue;
            }

            $relativePath = basename(dirname($file)) . '/' . basename($file);
            $plugins[$relativePath] = $data;
        }

        ksort($plugins);

        return $plugins;
    }

    /**
     * Get the plugins and mu-plugins from the mu-plugin path and remove duplicates.
     * Check cache against current plugins for newly activated plugins.
     * After that, we can update the cache.
     */
    private function updateCache()
    {
        $this->autoPlugins = $this->discoverPlugins();
        $this->muPlugins   = get_mu_plugins();
        $plugins           = array_diff_key($this->autoPlugins, $this->muPlugins);
        $rebuild           = !isset($this->cache['plugins']);
        $newPlugins        = array_intersect_key(
            array_merge(
                (array) get_site_option('bedrock_autoloader_new_plugins', []),
                $rebuild ? $plugins : array_diff_key($plugins, $this->cache['plugins'])
            ),
            $plugins
        );
        $this->count       = $this->countPluginDirs($plugins);
        $this->cache       = ['plugins' => $plugins, 'count' => $this->count];

        update_site_option('bedrock_autoloader', $this->cache);
        update_site_option('bedrock_autoloader_new_plugins', $newPlugins);
    }

    /**
     * This accounts for the plugin hooks that would run if the plugins were
     * loaded as usual. Plugins are removed by deletion, so there's no way
     * to deactivate or uninstall.
     */
    public function pluginHooks()
    {
        $newPlugins = (array) get_site_option('bedrock_autoloader_new_plugins', []);
        $newPluginsKeys = array_keys($newPlugins);
        foreach ($newPluginsKeys as $plugin_file) {
            if (!in_array($plugin_file, $this->loadedPluginEntryPoints)) {
                continue;
            }
            do_action('activate_' . $plugin_file);
            unset($newPlugins[$plugin_file]);
        }
        update_site_option('bedrock_autoloader_new_plugins', $newPlugins);
    }

    /**
     * Check that the plugin file exists, if it doesn't update the cache.
     */
    private function validatePlugins()
    {
        foreach ($this->cache['plugins'] as $plugin_file => $plugin_info) {
            if (!file_exists(WPMU_PLUGIN_DIR . '/' . $plugin_file)) {
                $this->updateCache();
                break;
            }
        }
    }

    /**
     * Count unique top-level plugin directories from a plugin map.
     *
     * @param array $plugins Plugin data keyed by relative path
     * @return int Number of unique plugin directories
     */
    private function countPluginDirs(array $plugins)
    {
        $dirs = [];
        foreach (array_keys($plugins) as $entryPoint) {
            $dirs[dirname($entryPoint)] = true;
        }
        return count($dirs);
    }

    /**
     * Count autoloaded plugins on the filesystem and trigger a cache
     * update if the count has changed since last check.
     */
    private function countPlugins()
    {
        if (isset($this->count)) {
            return $this->count;
        }

        $discovered = $this->discoverPlugins();
        $muPlugins = get_mu_plugins();
        $count = $this->countPluginDirs(array_diff_key($discovered, $muPlugins));

        if (!isset($this->cache['count']) || $count !== $this->cache['count']) {
            $this->count = $count;
            $this->updateCache();
        }

        return $this->count;
    }
}
