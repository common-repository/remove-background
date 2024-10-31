<?php

namespace NoBg\Services;

class AuthenticationService {
	/**
	 * Get client key
	 *
	 * @return string
	 */
	public function getClientKey(){
		return \NoBg::options()->get( 'client_key', '' );
	}
}
