<?php
/**
 * REST API publik Affiliate untuk footer WartaRadar.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

add_action( 'rest_api_init', 'wrmi_affiliate_rest_register_routes' );

function wrmi_affiliate_rest_register_routes() {
    register_rest_route(
        'wrmi/v1',
        '/affiliates',
        array(
            'methods'             => WP_REST_Server::READABLE,
            'callback'            => 'wrmi_affiliate_rest_get_affiliates',
            'permission_callback' => '__return_true',
        )
    );
}

function wrmi_affiliate_rest_get_affiliates( WP_REST_Request $request ) {
    if ( ! function_exists( 'wrmi_get_footer_affiliates' ) ) {
        return new WP_Error(
            'wrmi_affiliate_unavailable',
            'Fungsi data Affiliate belum tersedia.',
            array( 'status' => 500 )
        );
    }

    $query = wrmi_get_footer_affiliates( 12 );
    $items = array();

    if ( $query instanceof WP_Query ) {
        foreach ( $query->posts as $affiliate ) {
            $id = (int) $affiliate->ID;
            $website = get_post_meta(
                $id,
                '_wrmi_affiliate_website',
                true
            );
            $logo = get_the_post_thumbnail_url( $id, 'medium' );

            $items[] = array(
                'id'          => $id,
                'name'        => get_the_title( $id ),
                'description' => wp_trim_words(
                    wp_strip_all_tags(
                        get_post_field( 'post_content', $id )
                    ),
                    24,
                    '…'
                ),
                'website'     => esc_url_raw( $website ),
                'logoUrl'     => $logo ? esc_url_raw( $logo ) : '',
            );
        }

        wp_reset_postdata();
    }

    return rest_ensure_response(
        array(
            'success'    => true,
            'affiliates' => $items,
        )
    );
}