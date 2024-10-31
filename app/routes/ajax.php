<?php
/**
 * WordPress AJAX Routes.
 * WARNING: Do not use \NoBg::route()->all() here, otherwise you will override
 * ALL AJAX requests which you most likely do not want to do.
 *
 * @link https://docs.wpemerge.com/#/framework/routing/methods
 *
 * @package NoBg
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Upload multiple document posters
NoBg::route()->methods( [ 'POST' ] )
    ->where( 'ajax', 'no-bg-remove-background', true, true )
    ->middleware( 'csrf-api:no-bg-dashboard' )
    ->handle( 'ImageProcessController@submit' );

NoBg::route()->methods( [ 'GET' ] )
    ->where( 'ajax', 'no-bg-process-status', true, true )
    ->middleware( 'csrf-api:no-bg-dashboard' )
    ->handle( 'ImageProcessController@processStatus' );

NoBg::route()->methods( [ 'POST' ] )
    ->where( 'ajax', 'no-bg-download-image', true, true )
    ->middleware( 'csrf-api:no-bg-dashboard' )
    ->handle( 'ImageProcessController@downloadImage' );
