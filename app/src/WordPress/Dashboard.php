<?php

namespace NoBg\WordPress;


use Averta\WordPress\Utility\JSON;
use Averta\WordPress\Utility\Sanitize;
use NoBg\Security\CSRF;

class Dashboard {

	protected $attachment_id;

	protected $page_slug = 'nobg_remove_background';

	protected $capability = '';

	protected $hook = '';


	public function __construct() {
		$this->page_slug = NOBG_PLUGIN_SLUG;
		$this->capability = 'upload_files';
	}

	public function init() {
		add_action( 'admin_menu', [ $this, 'registerAdminPages' ] );
		add_action( 'plugins_loaded', [ $this, 'wpPluginsBootstrapped' ] );
		add_action( 'admin_enqueue_scripts', [ $this, 'enqueueAssets' ] );
	}


	/**
	 * Enqueues assets
	 *
	 * @return $this
	 */
	public function enqueueAssets( $hook ) {
		if ( ! current_user_can( $this->capability ) ) {
			return $this;
		}

		wp_enqueue_script( 'no-bg-admin',
			\NoBg::core()->assets()->getUrl() . '/resources/scripts/admin/admin.js',
			[ 'jquery'],
			NOBG_VERSION,
			true
		);

		if ( $this->hook !== $hook ) {
			return $this;
		}

		wp_enqueue_script( 'vanilla-picker',
			\NoBg::core()->assets()->getUrl() . '/resources/scripts/admin/vanilla-picker.min.js',
			[], NOBG_VERSION,
			true
		);
		wp_enqueue_script( 'no-bg-dashboard',
			\NoBg::core()->assets()->getUrl() . '/resources/scripts/admin/dashboard.js',
			[ 'jquery', 'vanilla-picker' ],
			NOBG_VERSION,
			true
		);

		// Add Environment variables
		wp_add_inline_script( 'no-bg-dashboard', 'window.noBgEnv = '. JSON::encode([
			'wpHomepage' => esc_url_raw( home_url() ),
			'wpAjaxAPI' => esc_url_raw( admin_url( 'admin-ajax.php' ) ),
			'ckey' => esc_js( \NoBg::auth()->getClientKey() ),
			'csrfToken' => esc_js( \NoBg::csrf()->getToken( CSRF::DASHBOARD_ACTION ) ),
		]), 'before' );

		wp_enqueue_style( 'no-bg-dashboard', \NoBg::core()->assets()->getUrl() . '/resources/styles/admin/dashboard.css', [], NOBG_VERSION );

		return $this;
	}

	/**
	 * Retrieves the dashboard url
	 *
	 * @param $id
	 *
	 * @return mixed|void
	 */
	public function getPageUrl( $id ) {
		$url = add_query_arg( [
			'page' => $this->page_slug,
			'id' => $id,
		],
			self_admin_url( 'tools.php' )
		);

		return apply_filters( 'no/bg/dashboard/edit', $url, $this );
	}

	/**
	 * Register admin pages.
	 *
	 * @return void
	 */
	public function registerAdminPages() {
		$this->hook = add_submenu_page(
			'tools.php',
			__( 'Remove Background', 'remove-background' ),
			__( 'Remove Background', 'remove-background' ),
			$this->capability,
			$this->page_slug,
			[ $this, 'renderAdminDashboard' ]
		);
	}

	/**
	 * On plugins loaded
	 *
	 * @return void
	 */
	public function wpPluginsBootstrapped() {
		add_filter( 'attachment_fields_to_edit', [ $this, 'editMediaModal' ], 9, 2 );
	}

	/**
	 * Adds remove background button to attachment modal
	 *
	 * @param $fields
	 * @param $post
	 *
	 * @return mixed
	 */
	public function editMediaModal( $fields, $post ) {

		if ( ! $this->isSupportedImageAttachment( $post ) ) {
			return $fields;
		}

		$link = $this->getPageUrl( $post->ID );
		$fields["no-bg-remove-background"] = array(
			"label" => esc_html__( "Remove Background (AI)", "remove-background" ),
			"input" => "html",
			"html" => "<a class='button-secondary no-bg-remove-bg-button' href='" . esc_url( $link ) . "'><i class='eicon-ai'></i>" . esc_html__( "Remove background", "remove-background" ) . "</a>",
			"helps" => esc_html__( "Remove or replace the background of this image using AI.", "remove-background" )
		);

		return $fields;
	}

	/**
	 * Whether the attachment is supported image format or not
	 *
	 * @param $post
	 *
	 * @return bool
	 */
	private function isSupportedImageAttachment( $post ) {
		// Check if the post is an attachment
		if ( ! $post || $post->post_type !== 'attachment' ) {
			return false;
		}

		$mime_type = get_post_mime_type( $post->ID );

		// supported MIME types for background removal
		$supported_mime_types = [
			'image/jpeg',
			'image/png',
			'image/gif',
			'image/webp',
		];

		// Check if the MIME type is in the list of supported types
		if ( in_array( $mime_type, $supported_mime_types, true ) ) {
			return true;
		}

		return false;
	}


	/**
	 * Renders the admin dashboard page
	 *
	 * @return void
	 */
	public function renderAdminDashboard() {

		// authorize user
		if ( ! current_user_can( $this->capability ) ) {
			wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'remove-background' ) );
		}

		// authorized user here
		$errorNotice = '';
		$infoNotice = '';
		$imageUrl = '';

		if ( ! empty( $_GET['id'] ) ) {
			if ( $id = Sanitize::int( wp_unslash( $_GET['id'] ) ) ) {
				$this->attachment_id = $id;
				$post = get_post( $this->attachment_id );

				if ( ! $post || $post->post_type !== 'attachment' ) {
					$errorNotice = __( 'Not a valid attachment.', 'remove-background' );
				}
				if ( ! $post || ! $this->isSupportedImageAttachment( $post ) ) {
					$errorNotice = __( 'The attachment image type is not supported for this service.', 'remove-background' );
				}
				$imageUrl = wp_get_attachment_url( $this->attachment_id );
			}

			\NoBg::client()->authorize();
		} else {
			$infoNotice = esc_html__( 'To start using image background removal service, navigate to Media Library, select an image, and click on "Remove Background" button in sidebar.', 'remove-background' );
			$infoNotice = "<p class='no-bg-info-notice'>{$infoNotice}</p>";
			$infoNotice .= '<div class="no-bg-buttons-container">';

			$lastAttachmentId = \NoBg::imageClient()->getLastAttachmentImageId();
			$lastAttachmentModalUrl = \NoBg::imageClient()->getAttachmentModalPageUrl( $lastAttachmentId );

			$infoNotice .= '<a class="button button-primary" href="' . esc_url( $lastAttachmentModalUrl ) . '">' . esc_html__( 'Go to Media library', 'remove-background' ) . '</a>';

			// display only if there is at lead one image in media library
			if( $lastAttachmentId ){
				$lastAttachmentDashboardUrl = $this->getPageUrl( $lastAttachmentId );
				$infoNotice .= '<a class="button button-secondary" href="' . esc_url( $lastAttachmentDashboardUrl ) . '">' . esc_html__( 'Start with an image', 'remove-background' ) . '</a>';
			}

			$infoNotice .= '</div>';
		}
		?>
		<div id="no-bg-dash-wrap" class="wrap">
			<h1 class="no-bg-page-title"><?php esc_html_e( 'Welcome to community-driven image processing service', 'remove-background' ); ?></h1>
			<div class="no-bg-main">
				<div class="no-bg-secondary">
					<div class="card no-bg-card no-bg-intro-request">
						<h2 class="title"><?php esc_html_e( 'Help keep this free service running', 'remove-background' ); ?></h2>
						<p><?php esc_html_e( 'To ensure our service remains accessible to everyone, we need your patience and
							cooperation.
							Based on volume of requests, there might be a wait time. Your understanding helps us keep
							this service running smoothly for the entire community.', 'remove-background' ); ?></p>
					</div>
				</div>
				<div class="no-bg-primary">
					<?php if ( $this->attachment_id ) { ?>
						<div class="no-bg-dash-container">
							<div class="no-bg-color-option">
								<div class="no-bg-color-picker-container">
									<div class="no-bg-color-picker"></div>
								</div>
								<strong><?php esc_html_e( 'Custom background color [default: transparent]', 'remove-background' ); ?></strong>
							</div>
							<div class="no-bg-preview-container"
								 data-attachment-id="<?php echo esc_attr( $this->attachment_id ); ?>">
								<div class="no-bg-frames-container">
									<div class="no-bg-before-frame no-bg-preview-frame scanning">
										<img class="no-bg-before-image no-bg-preview-image"
											 src="<?php echo esc_url( $imageUrl ); ?>" alt=""/>
										<div class="no-bg-overlay"></div>
									</div>
									<div class="no-bg-after-frame no-bg-preview-frame no-bg-transparent">
										<img class="no-bg-after-image no-bg-preview-image"
											 src="<?php echo esc_url( $imageUrl ); ?>" alt=""/>
										<p class="no-bg-preview-message"><?php esc_html_e( 'Preparing preview ..', 'remove-background' ); ?></p>
									</div>
								</div>
							</div>
							<div class="card no-bg-card no-bg-status-box">
								<ul>
									<li><strong>Estimated Wait Time: </strong><span
											class="no-bg-status-waiting">calculating ...</span>
										<span class="no-bg-status-queue"></span>
									</li>
								</ul>
								<hr>
								<h2 class="title">Please keep this page open</h2>
								<p>Your image is in the queue. Please stay on this page. Navigating away will cancel
									your
									spot.
									Thanks for your patience.</p>
							</div>
							<div class="card no-bg-card no-bg-error hidden">
								<h2 class="title"><?php esc_html_e( 'An Error Occurred!', 'remove-background' ); ?></h2>
								<p></p>
							</div>
						</div>
						<div class="no-bg-buttons-container">
							<button
								class="no-bg-preview-btn button button-primary"><?php esc_html_e( 'Generate a preview', 'remove-background' ); ?></button>
							<button
								class="no-bg-download-btn button button-primary"><?php esc_html_e( 'Download to Media Library', 'remove-background' ); ?></button>
							<a href="#" target="_blank" class="no-bg-view-in-library-btn"><?php esc_html_e( 'Successfully downloaded. View the image in Media Library', 'remove-background' ); ?><span
									class="dashicons dashicons-external"></span></a>
						</div>
					<?php } elseif ( $errorNotice ) {
						echo '<div class="card no-bg-card no-bg-error"><p>' . esc_html( $errorNotice ) . '</p></div>';
					}
					if ( $infoNotice ) {
						echo wp_kses_post( $infoNotice );
					} ?>
				</div>
			</div>
		</div>
		<?php
	}

}

