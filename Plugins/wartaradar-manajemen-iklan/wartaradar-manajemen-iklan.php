<?php
/**
 * Plugin Name: WartaRadar Manajemen Iklan
 * Plugin URI: https://wartaradar.com
 * Description: Mesin dan manajemen iklan WartaRadar untuk Affiliate, Advertiser, Campaign, Iklan, Placement, Statistik, Laporan, dan Log Aktivitas.
 * Version: 1.0.0
 * Author: WartaRadar
 * Author URI: https://wartaradar.com
 * Requires at least: 6.0
 * Requires PHP: 8.0
 * Text Domain: wartaradar-manajemen-iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * CONSTANTS
 * ========================================================= */

define(
	'WRMI_VERSION',
	'1.0.0'
);

define(
	'WRMI_PATH',
	untrailingslashit(
		plugin_dir_path( __FILE__ )
	)
);

define(
	'WRMI_URL',
	untrailingslashit(
		plugin_dir_url( __FILE__ )
	)
);


/* =========================================================
 * MODULES
 * ========================================================= */

$wrmi_modules = array(
	'includes/wrmi-functions.php',
	'includes/wrmi-dashboard.php',
	'includes/wrmi-afiliate.php',
	'includes/wrmi-affiliate-rest.php',
	'includes/wrmi-advertiser.php',
	'includes/wrmi-campaign.php',
	'includes/wrmi-ad.php',
	'includes/wrmi-placement.php',
	'includes/wrmi-rate.php',
	'includes/wrmi-rate-rest.php',
	'includes/wrmi-booking.php',
	'includes/wrmi-invoice.php',
	'includes/wrmi-payment.php',
	'includes/wrmi-statistics.php',
	'includes/wrmi-report.php',
	'includes/wrmi-log.php',
	'includes/wrmi-frontend.php',
);

foreach ( $wrmi_modules as $wrmi_module ) {

	$wrmi_file = WRMI_PATH . '/' . $wrmi_module;

	if ( file_exists( $wrmi_file ) ) {
		require_once $wrmi_file;
	}
}


/* =========================================================
 * CLICK TRACKING
 * ========================================================= */

/**
 * Proses klik iklan frontend sebelum redirect ke target URL.
 */
function wrmi_handle_ad_click() {

	if ( ! isset( $_GET['warmi_click'] ) ) {
		return;
	}

	$ad_id = absint(
		wp_unslash(
			$_GET['warmi_click']
		)
	);

	if ( ! $ad_id ) {
		return;
	}

	if ( 'wrmi_ad' !== get_post_type( $ad_id ) ) {
		return;
	}

	/*
	 * AD STATUS
	 */
	if ( ! function_exists( 'wrmi_get_ad_status' ) ) {
		return;
	}

	if ( 'active' !== wrmi_get_ad_status( $ad_id ) ) {
		return;
	}

	/*
	 * AD POST
	 */
	$ad = get_post( $ad_id );

	if ( ! $ad instanceof WP_Post ) {
		return;
	}

	if ( 'publish' !== $ad->post_status ) {
		return;
	}

	/*
	 * TARGET URL
	 */
	if ( ! function_exists( 'wrmi_get_ad_target_url' ) ) {
		return;
	}

	$target_url = wrmi_get_ad_target_url(
		$ad_id
	);

	if ( empty( $target_url ) ) {
		return;
	}

	$target_url = esc_url_raw( $target_url );

	if ( empty( $target_url ) ) {
		return;
	}

	/*
	 * HANYA HTTP / HTTPS.
	 */
	$target_scheme = wp_parse_url(
		$target_url,
		PHP_URL_SCHEME
	);

	if ( ! in_array( $target_scheme, array( 'http', 'https' ), true ) ) {
		return;
	}

	/*
	 * WAKTU WORDPRESS / TIMEZONE SITUS.
	 */
	$now   = current_time( 'timestamp' );
	$today = wp_date( 'Y-m-d', $now );

	/*
	 * AD START DATE.
	 */
	if ( function_exists( 'wrmi_get_ad_start_date' ) ) {

		$ad_start_date = wrmi_get_ad_start_date(
			$ad_id
		);

		if (
			! empty( $ad_start_date ) &&
			$ad_start_date > $today
		) {
			return;
		}
	}

	/*
	 * AD END DATE.
	 */
	if ( function_exists( 'wrmi_get_ad_end_date' ) ) {

		$ad_end_date = wrmi_get_ad_end_date(
			$ad_id
		);

		if (
			! empty( $ad_end_date ) &&
			$ad_end_date < $today
		) {
			return;
		}
	}

	/*
	 * CAMPAIGN RELATION.
	 */
	if ( ! function_exists( 'wrmi_get_ad_campaign_id' ) ) {
		return;
	}

	$campaign_id = absint(
		wrmi_get_ad_campaign_id(
			$ad_id
		)
	);

	if ( ! $campaign_id ) {
		return;
	}

	/*
	 * CAMPAIGN POST.
	 */
	$campaign = get_post( $campaign_id );

	if ( ! $campaign instanceof WP_Post ) {
		return;
	}

	if ( 'publish' !== $campaign->post_status ) {
		return;
	}

	/*
	 * CAMPAIGN STATUS.
	 */
	if ( ! function_exists( 'wrmi_get_campaign_status' ) ) {
		return;
	}

	if ( 'active' !== wrmi_get_campaign_status( $campaign_id ) ) {
		return;
	}

	/*
	 * CAMPAIGN START DATE.
	 */
	if ( function_exists( 'wrmi_get_campaign_start_date' ) ) {

		$campaign_start_date = wrmi_get_campaign_start_date(
			$campaign_id
		);

		if (
			! empty( $campaign_start_date ) &&
			$campaign_start_date > $today
		) {
			return;
		}
	}

	/*
	 * CAMPAIGN END DATE.
	 */
	if ( function_exists( 'wrmi_get_campaign_end_date' ) ) {

		$campaign_end_date = wrmi_get_campaign_end_date(
			$campaign_id
		);

		if (
			! empty( $campaign_end_date ) &&
			$campaign_end_date < $today
		) {
			return;
		}
	}

	/*
	 * CATAT KLIK SEBELUM REDIRECT.
	 */
	if ( function_exists( 'wrmi_record_ad_click' ) ) {

		wrmi_record_ad_click(
			$ad_id
		);
	}

	/*
	 * Redirect ke website advertiser.
	 *
	 * wp_redirect() digunakan karena target URL iklan
	 * memang dapat berada di domain eksternal.
	 */
	wp_redirect(
		$target_url,
		302,
		'WartaRadar Manajemen Iklan'
	);

	exit;
}

add_action(
	'template_redirect',
	'wrmi_handle_ad_click',
	1
);


/* =========================================================
 * ADMIN MENU
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_register_admin_menu',
	10
);

function wrmi_register_admin_menu() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_menu_page(
		'MANAJEMEN IKLAN',
		'MANAJEMEN IKLAN',
		'manage_options',
		'wrmi-dashboard',
		'wrmi_dashboard_page',
		'dashicons-megaphone',
		26
	);

	add_submenu_page(
		'wrmi-dashboard',
		'Dashboard Iklan',
		'Dashboard Iklan',
		'manage_options',
		'wrmi-dashboard',
		'wrmi_dashboard_page'
	);
}