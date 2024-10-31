<?php

namespace NoBg\WordPress;

use NoBg\Services\ClientService;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

/**
 * Register admin-related entities, like admin menu pages.
 */
class AdminServiceProvider implements ServiceProviderInterface {
	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$app = $container[ WPEMERGE_APPLICATION_KEY ];

		// register client service
		$container['no.bg.services.client.api'] = function () {
			return new ClientService();
		};
		$app->alias( 'client', 'no.bg.services.client.api' );

		$container['no.bg.services.dashboard'] = function () {
			return new Dashboard();
		};
		$app->alias( 'dashboard', 'no.bg.services.dashboard' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function bootstrap( $container ) {
		if ( is_admin() ) {

			// Only executes in admin pages
			if ( ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
				\NoBg::client()->authorize();
			}
		}

		\NoBg::dashboard()->init();
	}


}
