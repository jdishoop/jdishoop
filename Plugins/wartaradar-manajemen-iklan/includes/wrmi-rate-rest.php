<?php
/**
 * REST API baca tarif WRMI.
 * Sumber data: tabel wrmi_rates.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', 'wrmi_rate_rest_register_routes' );

function wrmi_rate_rest_register_routes() {
    register_rest_route(
        'wrmi/v1',
        '/rates',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'wrmi_rate_rest_get_rates',
            'permission_callback' => '__return_true',
        )
    );
}

function wrmi_rate_rest_get_rates( WP_REST_Request $request ) {
    global $wpdb;

    if ( ! function_exists( 'wrmi_get_rate_table_name' ) ) {
        return new WP_Error(
            'wrmi_rate_unavailable',
            'Modul tarif WRMI tidak tersedia.',
            array( 'status' => 500 )
        );
    }

    $table = wrmi_get_rate_table_name();

    $table_exists = $wpdb->get_var(
        $wpdb->prepare(
            'SHOW TABLES LIKE %s',
            $wpdb->esc_like( $table )
        )
    );

    if ( $table_exists !== $table ) {
        return new WP_Error(
            'wrmi_rate_table_missing',
            'Tabel tarif WRMI tidak ditemukan.',
            array( 'status' => 500 )
        );
    }

    $today = current_time( 'Y-m-d' );

    $sql = $wpdb->prepare(
        "SELECT id, placement, name, billing_unit, price, status,
                valid_from, valid_until
         FROM {$table}
         WHERE status = %s
           AND valid_from <= %s
           AND (valid_until IS NULL OR valid_until >= %s)
         ORDER BY sort_order ASC, id ASC",
        'active',
        $today,
        $today
    );

    $rows = $wpdb->get_results( $sql );

    if ( null === $rows && ! empty( $wpdb->last_error ) ) {
        return new WP_Error(
            'wrmi_rate_query_failed',
            'Tarif WRMI gagal dibaca.',
            array( 'status' => 500 )
        );
    }

    $rates = array();

    foreach ( (array) $rows as $row ) {
        $rates[] = array(
            'id'         => (int) $row->id,
            'placement'  => (string) $row->placement,
            'name'       => (string) $row->name,
            'billingUnit'=> (string) $row->billing_unit,
            'price'      => (float) $row->price,
            'status'     => (string) $row->status,
            'validFrom'  => (string) $row->valid_from,
            'validUntil' => $row->valid_until
                ? (string) $row->valid_until
                : null,
        );
    }

    $response = rest_ensure_response(
        array(
            'success' => true,
            'date'    => $today,
            'rates'   => $rates,
        )
    );

    $response->header(
        'Cache-Control',
        'no-store, no-cache, must-revalidate, max-age=0'
    );

    return $response;
}