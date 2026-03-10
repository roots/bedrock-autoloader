#!/usr/bin/env bash
set -euo pipefail

cd /roots

# Create Bedrock project
composer create-project roots/bedrock bedrock --no-interaction --no-progress --prefer-dist

cd bedrock

# Use the local autoloader source as a path repository
composer config repositories.autoloader path /roots/autoloader
composer require roots/bedrock-autoloader:@dev --no-interaction --no-progress

# Add turn-comments-off to the mu-plugins installer path
php -r '
    $json = json_decode(file_get_contents("composer.json"), true);
    $key = "web/app/mu-plugins/{\$name}/";
    $json["extra"]["installer-paths"][$key][] = "wpackagist-plugin/turn-comments-off";
    file_put_contents("composer.json", json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) . "\n");
'
composer require wpackagist-plugin/turn-comments-off --no-interaction --no-progress

# Update the bootstrap for v2 API
cat > web/app/mu-plugins/bedrock-autoloader.php << 'BOOTSTRAP'
<?php

/**
 * Plugin Name:  Bedrock Autoloader
 * Plugin URI:   https://github.com/roots/bedrock-autoloader
 * Description:  An autoloader that enables standard plugins to be required just like must-use plugins. The autoloaded plugins are included during mu-plugin loading. An asterisk (*) next to the name of the plugin designates the plugins that have been autoloaded.
 * Version:      2.0.0
 * Author:       Roots
 * Author URI:   https://roots.io/
 * License:      MIT License
 */

if (is_blog_installed() && class_exists(\Roots\Bedrock\Autoloader\Autoloader::class)) {
    $autoloader = new \Roots\Bedrock\Autoloader\Autoloader(WPMU_PLUGIN_DIR);

    foreach ($autoloader->boot() as $file) {
        include_once $file;
    }

    $autoloader->markLoaded();

    unset($autoloader, $file);
}
BOOTSTRAP

# Create .env
cat > .env << 'ENV'
DB_NAME=wordpress
DB_USER=wordpress
DB_PASSWORD=password
DB_HOST=database
DB_PREFIX=wp_

WP_ENV=development
WP_HOME=http://web
WP_SITEURL=${WP_HOME}/wp

AUTH_KEY='generateme'
SECURE_AUTH_KEY='generateme'
LOGGED_IN_KEY='generateme'
NONCE_KEY='generateme'
AUTH_SALT='generateme'
SECURE_AUTH_SALT='generateme'
LOGGED_IN_SALT='generateme'
NONCE_SALT='generateme'
ENV

# Install WordPress
wp core install \
  --url=http://web \
  --title=Bedrock \
  --admin_user=admin \
  --admin_password=admin \
  --admin_email=admin@example.com \
  --skip-email \
  --allow-root

# Install Playwright
cd /roots/autoloader
npm install --no-save @playwright/test
npx playwright install chromium
