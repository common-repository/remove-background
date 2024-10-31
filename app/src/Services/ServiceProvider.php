<?php

namespace NoBg\Services;

use Averta\WordPress\Cache\DatabaseCache;
use Averta\WordPress\Cache\WPCache;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

/**
 * initialize common services
 */
class ServiceProvider implements ServiceProviderInterface {

	public function register( $container ) {
		$app = $container[ WPEMERGE_APPLICATION_KEY ];

		// register Cache modules
		$container['no.bg.services.cache.base'] = function () {
			return new WPCache( 'nobg__' );
		};

		$container['no.bg.services.cache.api'] = function () {
			return new WPCache( 'nobg_api_' );
		};

		// persistent database cache module
		$container['no.bg.services.cache.database'] = function () {
			return new DatabaseCache( 'nobg_d_' );
		};

		// register cache alias for retrieving a cache module
		$app->alias( 'cache', function () use ( $app ) {
			$module = ! empty( func_get_args()['0'] ) ? strtolower( func_get_args()['0'] ) : 'api';

			return $app->resolve( 'no.bg.services.cache.' . $module );
		} );

		$container['no.bg.services.remote.api'] = function () {
			return new RemoteAPIService();
		};
		$app->alias( 'remote', 'no.bg.services.remote.api' );

		$container['no.bg.security.authentication'] = function () {
			return new AuthenticationService();
		};
		$app->alias( 'auth', 'no.bg.security.authentication' );

		$container['no.bg.services.storage'] = function () {
			return new StorageService();
		};
		$app->alias( 'storage', 'no.bg.services.storage' );

		$container['no.bg.services.image.client'] = function () {
			return new ImageHttpClient();
		};
		$app->alias( 'imageClient', 'no.bg.services.image.client' );
	}

	public function bootstrap( $container ) {}
}
