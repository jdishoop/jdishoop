<?php
/**
 * WartaRadar Manajemen Iklan
 * Core Functions
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * 1. HAK AKSES
 * ========================================================= */

function wrmi_can_manage_ads() {

	return current_user_can(
		'manage_options'
	);
}


/* =========================================================
 * 2. REQUIRE ADMIN ACCESS
 * ========================================================= */

function wrmi_require_admin_access() {

	if ( ! wrmi_can_manage_ads() ) {

		wp_die(
			esc_html__(
				'Anda tidak memiliki izin untuk mengakses Manajemen Iklan.',
				'wartaradar-manajemen-iklan'
			)
		);
	}
}


/* =========================================================
 * 3. SANITASI TEXT
 * ========================================================= */

function wrmi_sanitize_text( $value ) {

	return sanitize_text_field(
		wp_unslash( $value )
	);
}


/* =========================================================
 * 4. SANITASI URL
 * ========================================================= */

function wrmi_sanitize_url( $value ) {

	return esc_url_raw(
		wp_unslash( $value )
	);
}


/* =========================================================
 * 5. STATUS IKLAN
 * ========================================================= */

function wrmi_get_statuses() {

	return array(
		'active'   => 'Aktif',
		'inactive' => 'Nonaktif',
		'pending'  => 'Menunggu',
		'paused'   => 'Dijeda',
		'finished' => 'Selesai',
	);
}


/* =========================================================
 * 6. LABEL STATUS
 * ========================================================= */

function wrmi_get_status_label( $status ) {

	$statuses = wrmi_get_statuses();

	return isset( $statuses[ $status ] )
		? $statuses[ $status ]
		: $status;
}


/* =========================================================
 * 7. FORMAT DATETIME
 * ========================================================= */

function wrmi_format_datetime( $timestamp ) {

	if ( empty( $timestamp ) ) {
		return '-';
	}

	return wp_date(
		'd F Y H:i',
		(int) $timestamp
	);
}


/* =========================================================
 * 8. NAMA USER AKTIF
 * ========================================================= */

function wrmi_current_user_name() {

	$user = wp_get_current_user();

	if (
		! $user
		|| empty( $user->ID )
	) {
		return '';
	}

	return $user->display_name;
}


/* =========================================================
 * 9. CATAT AKTIVITAS
 * ========================================================= */

function wrmi_log_activity(
	$action,
	$object_type = '',
	$object_id = 0,
	$description = ''
) {

	$activity = array(
		'action'      => sanitize_text_field( $action ),
		'object_type' => sanitize_text_field( $object_type ),
		'object_id'   => absint( $object_id ),
		'user_id'     => get_current_user_id(),
		'user_name'   => wrmi_current_user_name(),
		'description' => sanitize_text_field( $description ),
		'timestamp'   => current_time( 'timestamp' ),
	);

	do_action(
		'wrmi_activity_logged',
		$activity
	);

	return $activity;
}


/* =========================================================
 * 10. POST VALUE
 * ========================================================= */

function wrmi_post_value(
	$key,
	$default = ''
) {

	if (
		! isset( $_POST[ $key ] )
	) {
		return $default;
	}

	return wrmi_sanitize_text(
		$_POST[ $key ]
	);
}


/* =========================================================
 * 11. POST URL
 * ========================================================= */

function wrmi_post_url(
	$key,
	$default = ''
) {

	if (
		! isset( $_POST[ $key ] )
	) {
		return $default;
	}

	return wrmi_sanitize_url(
		$_POST[ $key ]
	);
}


/* =========================================================
 * 12. VERIFY NONCE
 * ========================================================= */

function wrmi_verify_nonce(
	$action,
	$field = 'wrmi_nonce'
) {

	if (
		empty( $_POST[ $field ] )
	) {
		return false;
	}

	return (bool) wp_verify_nonce(
		sanitize_text_field(
			wp_unslash(
				$_POST[ $field ]
			)
		),
		$action
	);
}


/* =========================================================
 * 13. MODUL ACTION
 * ========================================================= */

function wrmi_module_action(
	$module,
	$action,
	$object_id = 0
) {

	do_action(
		'wrmi_module_action',
		array(
			'module'    => sanitize_text_field( $module ),
			'action'    => sanitize_text_field( $action ),
			'object_id' => absint( $object_id ),
			'user_id'   => get_current_user_id(),
			'timestamp' => current_time( 'timestamp' ),
		)
	);
}