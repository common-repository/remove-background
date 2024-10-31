<?php

namespace NoBg\Services;


use Averta\Core\Utility\Arr;
use NoBg\GuzzleHttp\Client;
use NoBg\GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

/**
 * Performs an HTTP request and returns its response.
 *
 * @package NoBg\Services
 */
class RemoteAPIService {
	/**
	 * List of endpoints
	 *
	 * @var array
	 */
	protected $endpoints = [
		"1" => "https://no-background.com/"
	];


	/**
	 * Retrieves an endpoint by number
	 *
	 * @param int $endpointNumber
	 *
	 * @param string $branch The relative path to be appended to endpoint url
	 *
	 * @return mixed|string
	 */
	public function endpoint( $endpointNumber = 1, $branch = '' ) {
		if ( ! empty( $this->endpoints[ $endpointNumber ] ) ) {
			return $this->endpoints[ $endpointNumber ] . $branch;
		}

		return '';
	}


	private function isAbsoluteUrl( $url ) {
		return strpos( $url, '://' ) !== false;
	}

	/**
	 * Get default options for requests
	 *
	 * @return array
	 * @throws GuzzleException
	 */
	private function getDefaultOptions() {
		global $wp_version;

		return [
			'headers' => [
				'user-agent' => 'WordPress/' . $wp_version . '; ' . get_home_url(),
				'timeout' => 30,
				'X-NOBG-CKEY' => \NoBg::auth()->getClientKey(),
				'X-NOBG-VER' => NOBG_VERSION,
			]
		];
	}

	/**
	 * Create and send an HTTP GET request to specified API endpoints.
	 *
	 * @param string $endpoint URI object or string.
	 * @param array $options Request options to apply.
	 * @param int $endpointNumber Endpoint number
	 *
	 * @return \NoBg\Psr\Http\Message\ResponseInterface
	 * @throws GuzzleException
	 */
	public function get( $endpoint, $options = [], $endpointNumber = 1 ) {
		// Maybe convert branch to absolute endpoint url
		if ( ! $this->isAbsoluteUrl( $endpoint ) ) {
			$endpoint = $this->endpoint( $endpointNumber, $endpoint );
		}

		$client = new Client( [
			'verify' => $this->isLocalEnvironment() ? false : ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			'proxy' => $this->getProxy()
		] );

		$optionsWithAuth = Arr::merge( $options, $this->getDefaultOptions() );

		return $client->get( $endpoint, $optionsWithAuth );
	}

	/**
	 * Create and send an HTTP POST request to specified API endpoints.
	 *
	 * @param string $endpoint URI object or string.
	 * @param array $options Request options to apply.
	 * @param int $endpointNumber Endpoint number
	 *
	 * @return ResponseInterface
	 * @throws GuzzleException
	 */
	public function post( $endpoint, $options = [], $endpointNumber = 1 ) {
		// Maybe convert branch to absolute endpoint url
		if ( ! $this->isAbsoluteUrl( $endpoint ) ) {
			$endpoint = $this->endpoint( $endpointNumber, $endpoint );
		}

		$client = new Client( [
			'verify' => $this->isLocalEnvironment() ? false : ABSPATH . WPINC . '/certificates/ca-bundle.crt',
			'proxy' => $this->getProxy()
		] );

		$optionsWithAuth = Arr::merge( $options, $this->getDefaultOptions() );

		return $client->post( $endpoint, $optionsWithAuth );
	}

	/**
	 * Get proxy config
	 *
	 * @return string|null
	 */
	public function getProxy() {

		// Check if proxy constants are defined
		$proxy = null;
		if ( defined( 'WP_PROXY_HOST' ) && defined( 'WP_PROXY_PORT' ) ) {
			$proxy = WP_PROXY_HOST . ':' . WP_PROXY_PORT;

			// Add credentials if necessary
			if ( defined( 'WP_PROXY_USERNAME' ) && defined( 'WP_PROXY_PASSWORD' ) ) {
				$proxy = WP_PROXY_USERNAME . ':' . WP_PROXY_PASSWORD . '@' . $proxy;
			}
		}

		return $proxy;

	}

	protected function isLocalEnvironment() {
		// check if its a local environment domain
		$siteUrl = get_site_url();
		$localPatterns = [
			'/localhost/',
			'/127.0.0.1/',
			'/.local/',
			'/.dev/',
			'/.test/',
		];

		foreach ( $localPatterns as $pattern ) {
			if ( preg_match( $pattern, $siteUrl ) ) {
				return true;
			}
		}

		if ( defined( 'WP_LOCAL_DEVELOPMENT' ) && WP_LOCAL_DEVELOPMENT ) {
			return true;
		}

		return false;
	}


}
