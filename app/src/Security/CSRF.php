<?php

namespace NoBg\Security;

use WPEmerge\Csrf\Csrf as CsrfBase;


class CSRF extends CsrfBase {
	const DASHBOARD_ACTION = 'no-bg-dashboard';

	/**
	 * Convenience header to check for the token.
	 *
	 * @var string
	 */
	protected $header = 'X-NOBG-CSRF';

	/**
	 * Constructor.
	 *
	 * @codeCoverageIgnore
	 *
	 * @param string $key
	 * @param integer $maximum_lifetime
	 */
	public function __construct( $key = 'no-bg-csrf', $maximum_lifetime = 2 ) {
		$this->key = $key;
		$this->maximum_lifetime = $maximum_lifetime;
	}
}
