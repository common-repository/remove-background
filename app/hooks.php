<?php
/**
 * Declare any actions and filters here.
 * In most cases you should use a service provider, but in cases where you
 * just need to add an action/filter and forget about it you can add it here.
 *
 * @package NoBg
 */

use Averta\WordPress\Utility\Sanitize;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function after_no_bg_row_meta( $plugin_meta, $plugin_file, $plugin_data, $status ){
	if( NOBG_PLUGIN_BASENAME == $plugin_file ) {
		$plugin_meta[] = '<a href="https://wordpress.org/support/view/plugin-reviews/remove-background/?rating=5#postform" target="_blank" title="' . esc_attr__( 'Rate this plugin', 'remove-background' ) . '">' . __( 'Rate this plugin', 'remove-background' ) . '</a>';
	}
	return $plugin_meta;
}
add_filter( "plugin_row_meta", 'after_no_bg_row_meta', 10, 4 );


/**
 * Stores ajax request for admin rate notice
 *
 * @return void
 */
function no_bg_on_remind_rate_notice() {
	if (isset($_POST['remind_rate_time'])) {
		\NoBg::options()->set( 'remind_rate_time', Sanitize::int( $_POST['remind_rate_time'] ) );
	}
	wp_die();
}
add_action('wp_ajax_no_bg_remind_rate_notice', 'no_bg_on_remind_rate_notice');


/**
 * Displays a rate notice
 *
 * @return void
 */
function no_bg_rate_reminder_admin_notice() {
	if( ! $remindRateTime = \NoBg::options()->get( 'remind_rate_time', false ) ){
		if( $joinDate = \NoBg::options()->get( 'date_joined' ) ){
			$date = new DateTime();
			$date->setTimestamp($joinDate);
			$date->modify('+2 days');
			$remindRateTime = $date->getTimestamp();
		}
	}

	if( ! ( $remindRateTime && ( time() >= $remindRateTime ) ) ){
		return;
	}
	$noBgUrl = add_query_arg( ['page' => NOBG_PLUGIN_SLUG ], self_admin_url( 'tools.php' ) );
	?>
	<div class="notice no-bg-rate no-bg-notice notice-info is-dismissible">
		<div class="no-bg-notice-image">
			<img width="105" src="<?php echo NOBG_PLUGIN_URL . '/resources/images/rating.svg';?>">
		</div>
		<div>
			<h3><?php echo sprintf( esc_html__( 'Hi! Thank you so much for using %s AI Remove Background %s', 'remove-background' ), '<a href="' . esc_url( $noBgUrl ) . '">','</a>' );?></h3>
			<p class="no-bg-notice-message"><?php echo esc_html__( 'Could you please do us a HUGE favor? If you could take 2 min of your time, we would be really thankful if you give "Remove Background" a 5-star rating on WordPress. By spreading the love, we can push this community-driven tool forward and create even greater free stuff in the future!', 'remove-background' ); ?></p>

			<a class="rate-btn no-bg-rate-action" href="https://wordpress.org/support/plugin/remove-background/reviews/?filter=5#new-post" target="_blank"><span class="no-bg-overlay"></span><button ><?php echo esc_html__( 'Sure, I like "Remove Background" ', 'remove-background' );?></button></a>
			<a class="rate-btn skip-btn delay no-bg-rate-remind-later" href="#"><span class="no-bg-overlay"></span><button><?php echo esc_html__( 'Maybe Later', 'remove-background' );?></button></a>
			<a class="rate-btn skip-btn no-bg-rate-dismiss-notice" href="#"><span class="no-bg-overlay"></span><button><?php echo esc_html__( 'I Already Did :)', 'remove-background' );?></button></a>
		</div>
	</div>
	<?php
}

add_action( 'admin_notices', 'no_bg_rate_reminder_admin_notice' );
