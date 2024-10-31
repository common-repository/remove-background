<?php
/**
 * WP Emerge configuration.
 *
 * @link https://docs.wpemerge.com/#/framework/configuration
 *
 * @package NoBg
 */

if ( ! defined( 'ABSPATH' ) ) exit;

return [
	/**
	 * Default class namespace prefix.
	 */
	'namespace' => 'NoBg\\',

	/**
	 * Array of service providers you wish to enable.
	 */
	'providers' => [
		\WPEmergeAppCore\AppCore\AppCoreServiceProvider::class,
		\WPEmergeAppCore\Assets\AssetsServiceProvider::class,
		\WPEmergeAppCore\Avatar\AvatarServiceProvider::class,
		\WPEmergeAppCore\Config\ConfigServiceProvider::class,
		\WPEmergeAppCore\Image\ImageServiceProvider::class,
		\WPEmergeAppCore\Sidebar\SidebarServiceProvider::class,
		\NoBg\WordPress\AdminServiceProvider::class,
		\NoBg\Middleware\MiddlewareServiceProvider::class,
		\NoBg\Security\SecurityServiceProvider::class,
		\NoBg\WordPress\AssetsServiceProvider::class,
		\NoBg\WordPress\PluginServiceProvider::class,
		\NoBg\Services\ServiceProvider::class,
	],

	/**
	 * Array of route group definitions and default attributes.
	 * All of these are optional so if we are not using
	 * a certain group of routes we can skip it.
	 * If we are not using routing at all we can skip
	 * the entire 'routes' option.
	 */
	'routes' => [
		'web' => [
			'definitions' => __DIR__ . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web.php',
		],
		'admin' => [
			'definitions' => __DIR__ . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'admin.php',
		],
		'ajax' => [
			'definitions' => __DIR__ . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'ajax.php',
		],
	],

	/**
	 * Register middleware class aliases.
	 * Use fully qualified middleware class names.
	 *
	 * Internal aliases that you should avoid overriding:
	 * - 'flash'
	 * - 'old_input'
	 * - 'csrf'
	 * - 'user.logged_in'
	 * - 'user.logged_out'
	 * - 'user.can'
	 */
	'middleware' => [
		'cache' => \NoBg\Middleware\CacheMiddleware::class,
		'cors' => \NoBg\Middleware\CORSMiddleware::class,
		'csrf-api' => \NoBg\Middleware\CsrfAPIMiddleware::class,
		'nonce' => \NoBg\Middleware\NonceFieldMiddleware::class,
		'userCan' => \NoBg\Middleware\CapabilityMiddleware::class
	],

	/**
	 * Register middleware groups.
	 * Use fully qualified middleware class names or registered aliases.
	 * There are a couple built-in groups that you may override:
	 * - 'web'      - Automatically applied to web routes.
	 * - 'admin'    - Automatically applied to admin routes.
	 * - 'ajax'     - Automatically applied to ajax routes.
	 * - 'global'   - Automatically applied to all of the above.
	 * - 'wpemerge' - Internal group applied the same way 'global' is.
	 *
	 * Warning: The 'wpemerge' group contains some internal WP Emerge
	 * middleware which you should avoid overriding.
	 */
	'middleware_groups' => [
		'global' => [],
		'web' => [],
		'ajax' => [],
		'admin' => [],
	],

	/**
	 * Optionally specify middleware execution order.
	 * Use fully qualified middleware class names.
	 */
	'middleware_priority' => [],

	/**
	 * Custom directories to search for views.
	 * Use absolute paths or leave blank to disable.
	 * Applies only to the default PhpViewEngine.
	 */
	'views' => [ dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'views' ],

	/**
	 * App Core configuration.
	 */
	'app_core' => [
		'path' => dirname( __DIR__ ),
		'url' => plugin_dir_url( NOBG_PLUGIN_FILE ),
	]
];
