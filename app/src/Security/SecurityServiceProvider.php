<?php

namespace NoBg\Security;

use WPEmerge\ServiceProviders\ServiceProviderInterface;

class SecurityServiceProvider implements ServiceProviderInterface {

	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {

		$container[ CSRF::class ] = function ( $c ) {
			return new CSRF( 'no-bg-csrf', 2 );
		};
	}

	/**
	 * {@inheritDoc}
	 */
	public function bootstrap( $container ) {
		// Nothing to bootstrap.
	}

}
