<?php
/**
 * Plugin Name: Remove Background
 * Plugin URI: https://wordpress.org/plugins/remove-background/
 * Description: Pixel perfect AI-powered Background Removal tool.
 * Version: 0.9.7
 * Requires at least: 5.6
 * Requires PHP: 7.4
 * Author: Remove Background
 * Author URI: https://profiles.wordpress.org/removebackground/
 * License: GPL-2.0-only
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: remove-background
 * Domain Path: /languages
 *
 * @package NoBg
 */

if ( ! defined( 'ABSPATH' ) ) exit;

const NOBG_VERSION = '0.9.7';

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Make sure we can load a compatible version of WP Emerge.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'version.php';

$name = trim( get_file_data( __FILE__, [ 'Plugin Name' ] )[0] );
$load = nobg_should_load_wpemerge( $name, '0.17.0', '2.0.0' );

if ( ! $load ) {
	// An incompatible WP Emerge version is already loaded - stop further execution.
	return;
}

// CONSTANTS -------

const NOBG_PLUGIN_ID = 'nobg';
const NOBG_PLUGIN_SLUG = 'remove-background';
const NOBG_PLUGIN_FILE = __FILE__;
const NOBG_PLUGIN_PATH = __DIR__;
define( 'NOBG_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'NOBG_PLUGIN_BASENAME', plugin_basename( __FILE__ ) );

// Load composer dependencies.
$autoload = __DIR__ . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'autoload.php';
if ( file_exists( $autoload ) ) {
	require_once $autoload;
}

nobg_declare_loaded_wpemerge( $name, 'theme', __FILE__ );

// Load helpers.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'NoBg.php';
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'helpers.php';

// Bootstrap plugin after all dependencies and helpers are loaded.
\NoBg::make()->bootstrap( require __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'config.php' );

// Register hooks.
require_once __DIR__ . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'hooks.php';
