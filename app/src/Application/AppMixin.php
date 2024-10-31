<?php

namespace NoBg\Application;


use Averta\WordPress\Cache\WPCache;
use Averta\WordPress\Models\WPOptions;
use NoBg\Services\AuthenticationService;
use NoBg\Services\ClientService;
use NoBg\Services\ImageHttpClient;
use NoBg\Services\RemoteAPIService;
use NoBg\Services\StorageService;

/**
 * "@mixin" annotation for better IDE support.
 * This class is not meant to be used in any other capacity.
 *
 * @codeCoverageIgnore
 */
final class AppMixin {

	/**
	 * @return WPOptions
	 */
	public static function options(): WPOptions {}

	/**
	 * Retrieves the cache module
	 *
	 * @param string $module
	 *
	 * @return WPCache
	 */
	public static function cache( $module = 'api' ): WPCache {}

	/**
	 * @return RemoteAPIService
	 */
	public static function remote(): RemoteAPIService {}

	public static function storage(): StorageService {}

	/**
	 * @return AuthenticationService
	 */
	public static function auth(): AuthenticationService {
	}

	/**
	 * @return ClientService
	 */
	public static function client(): ClientService {
	}

	/**
	 * @return ImageHttpClient
	 */
	public static function imageClient(): ImageHttpClient {
	}
}
