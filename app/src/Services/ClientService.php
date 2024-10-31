<?php

namespace NoBg\Services;

use Averta\WordPress\Utility\JSON;
use NoBg\GuzzleHttp\Exception\GuzzleException;

class ClientService {
	/**
	 * ClientService constructor.
	 */
	public function __construct() {
		if ( empty( \NoBg::options()->get( 'version_initial' ) ) ) {
			\NoBg::options()->set( 'version_initial', NOBG_VERSION );
			\NoBg::options()->set( 'date_joined', time() );
		}
		if ( empty( \NoBg::options()->get( 'date_joined' ) ) ) {
			\NoBg::options()->set( 'date_joined', time() );
		}
	}

	/**
	 * Register client info
	 *
	 * @return void
	 */
	public function authorize() {

		if (
			\NoBg::cache( 'base' )->get( 'is_client_registered' ) &&
			\NoBg::auth()->getClientKey()
		) {
			return;
		}

		$params = [
			'form_params' => [
				'version_initial' => \NoBg::options()->get( 'version_initial' )
			]
		];

		try {
			$response = \NoBg::remote()->post( 'api/w1/client/register', $params );

			$payload = JSON::decode( $response->getBody(), true );

			if ( ! empty( $payload['ckey'] ) ) {
				\NoBg::options()->set( 'client_key', $payload['ckey'] );
			} elseif ( ! empty( $payload['errors'] ) ) {
				\NoBg::options()->set( 'register_error_message', $payload['errors'] );
			}

			\NoBg::cache( 'base' )->set( 'is_client_registered', true, DAY_IN_SECONDS );

		} catch ( GuzzleException|\Exception $e ) {
			\NoBg::options()->set( 'register_error_message', $e->getMessage() );
		}
	}

}
