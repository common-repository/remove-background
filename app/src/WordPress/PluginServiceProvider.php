<?php

namespace NoBg\WordPress;

use Averta\WordPress\Models\WPOptions;
use WPEmerge\ServiceProviders\ServiceProviderInterface;

/**
 * Register plugin options.
 */
class PluginServiceProvider implements ServiceProviderInterface {
	/**
	 * {@inheritDoc}
	 */
	public function register( $container ) {
		$app = $container[ WPEMERGE_APPLICATION_KEY ];

		// register no name options
		$container['no.bg.options'] = function () {
			return new WPOptions( 'nobg__' );
		};
		$app->alias( 'options', 'no.bg.options' );
	}

	/**
	 * {@inheritDoc}
	 */
	public function bootstrap( $container ) {
		register_activation_hook( NOBG_PLUGIN_FILE, [ $this, 'activate' ] );
		register_deactivation_hook( NOBG_PLUGIN_FILE, [ $this, 'deactivate' ] );

		add_action( 'plugins_loaded', [ $this, 'loadTextDomain' ] );
		add_action( 'admin_init', [ $this, 'check_plugin_upgrade_via_upload' ] );
		// add_action( 'init', [$this, 'startSession'] );
	}

	/**
	 * Check if plugin updated via upload or not
	 */
	public function check_plugin_upgrade_via_upload() {
		$previousVersion = \NoBg::options()->get( 'version', 0 );
		if ( version_compare( NOBG_VERSION, $previousVersion, '>' ) ) {
			\NoBg::options()->set( 'version_previous', $previousVersion );
			\NoBg::options()->set( 'version', NOBG_VERSION );
			do_action( 'no/bg/plugin/updated' );
		}
	}

	/**
	 * Plugin activation.
	 *
	 * @return void
	 */
	public function activate() {
		// Nothing to do right now.
	}

	/**
	 * Plugin deactivation.
	 *
	 * @return void
	 */
	public function deactivate() {
		// Nothing to do right now.
	}

	/**
	 * Load textdomain.
	 *
	 * @return void
	 */
	public function loadTextDomain() {
		load_plugin_textdomain( 'remove-background', false, basename( dirname( NOBG_PLUGIN_FILE ) ) . DIRECTORY_SEPARATOR . 'languages' );
	}

	/**
	 * Start a new session.
	 *
	 * @return void
	 */
	public function startSession() {
		if ( session_status() === PHP_SESSION_NONE ) {
			session_start();
		}
	}
}
