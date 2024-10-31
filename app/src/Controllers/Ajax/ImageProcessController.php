<?php

namespace NoBg\Controllers\Ajax;

use Averta\WordPress\Utility\Sanitize;
use WPEmerge\Requests\RequestInterface;

class ImageProcessController {
	/**
	 * Retrieves Lists of all entries. (GET)
	 *
	 * @param RequestInterface $request
	 * @param string $view
	 *
	 * @return string
	 */
	public function submit( RequestInterface $request ) {
		$args = [];
		if ( ! $id = Sanitize::int( $request->body( 'id' ) ) ) {
			return \NoBg::json( [ 'error' => 'Attachment id is required.' ] )->withStatus( 200 );
		}
		if ( $bgColor = Sanitize::textfield( $request->body( 'c' ) ) ) {
			$args['c'] = $bgColor;
		}

		$result = \NoBg::imageClient()->sendAttachment( $id, $args );

		return \NoBg::json( $result );
	}

	public function processStatus( RequestInterface $request ) {
		if ( $processId = Sanitize::textfield( $request->query( 'process' ) ) ) {
			$result = \NoBg::imageClient()->getProcessStatus( $processId );
		} else {
			$result = [ 'error' => 'Not a valid process' ];
		}

		return \NoBg::json( $result );
	}

	public function downloadImage( RequestInterface $request ) {
		$originalAttachmentId = Sanitize::int( $request->body( 'id' ) );

		if ( $url = Sanitize::url( $request->body( 'url' ) ) ) {
			$result = \NoBg::imageClient()->downloadAndAttachImage( $url, $originalAttachmentId );
		} else {
			$result = [ 'error' => 'Image url not provided.' ];
		}

		return \NoBg::json( $result );
	}
}
