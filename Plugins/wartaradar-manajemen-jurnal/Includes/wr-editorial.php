<?php
/**
 * WartaRadar Manajemen Jurnal
 * Pengelolaan Headline Utama dan Sorotan Redaksi.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Hanya Administrator dan Editor Berita yang boleh mengelola pilihan editorial.
 */
function wrmj_editorial_user_can_manage() {
    return is_user_logged_in()
        && ( wrmj_user_is_admin() || wrmj_user_is_editor() );
}

/**
 * Podcast tidak boleh dipilih sebagai berita editorial.
 */
function wrmj_editorial_is_podcast( $post_id ) {
    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    if ( function_exists( 'wartaradar_core_is_podcast' ) ) {
        return (bool) wartaradar_core_is_podcast( $post_id );
    }

    $value = get_post_meta( $post_id, '_wartaradar_is_podcast', true );

    return in_array( $value, array( '1', 1, true, 'true' ), true );
}

/**
 * Memastikan ID adalah artikel berita biasa yang masih berstatus publish.
 */
function wrmj_editorial_is_valid_published_post( $post_id ) {
    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    $post = get_post( $post_id );

    return $post instanceof WP_Post
        && 'post' === $post->post_type
        && 'publish' === $post->post_status
        && ! wrmj_editorial_is_podcast( $post_id );
}

/**
 * Membaca dan memvalidasi pilihan editorial yang masih layak ditampilkan.
 *
 * @return array{headline_id:int,editor_pick_ids:array}
 */
function wrmj_get_homepage_editorial_data() {
    $saved = get_option( 'wrmj_homepage_editorial', array() );

    if ( ! is_array( $saved ) ) {
        $saved = array();
    }

    $headline_id = isset( $saved['headline_id'] )
        ? absint( $saved['headline_id'] )
        : 0;

    if ( ! wrmj_editorial_is_valid_published_post( $headline_id ) ) {
        $headline_id = 0;
    }

    $raw_ids = isset( $saved['editor_pick_ids'] ) && is_array( $saved['editor_pick_ids'] )
        ? $saved['editor_pick_ids']
        : array();

    $editor_pick_ids = array();

    foreach ( $raw_ids as $raw_id ) {
        $post_id = absint( $raw_id );

        if (
            $post_id
            && $post_id !== $headline_id
            && wrmj_editorial_is_valid_published_post( $post_id )
            && ! in_array( $post_id, $editor_pick_ids, true )
        ) {
            $editor_pick_ids[] = $post_id;
        }
    }

    return array(
        'headline_id'     => $headline_id,
        'editor_pick_ids' => $editor_pick_ids,
    );
}

/**
 * Merender satu artikel pilihan dengan markup yang tidak bergantung pada tema.
 */
function wrmj_render_editorial_card( $post_id, $class = '' ) {
    $post = get_post( absint( $post_id ) );

    if ( ! $post instanceof WP_Post || 'publish' !== $post->post_status ) {
        return '';
    }

    $classes = trim( 'wrmj-editorial-card ' . sanitize_html_class( $class ) );
    $title   = get_the_title( $post );
    $url     = get_permalink( $post );
    $excerpt = wp_trim_words(
        wp_strip_all_tags( get_the_excerpt( $post ) ),
        32
    );
    $image   = get_the_post_thumbnail( $post, 'large' );

    $html = '<article class="' . esc_attr( $classes ) . '">';

    if ( $image ) {
        $html .= '<a class="wrmj-editorial-card__image" href="' . esc_url( $url ) . '">';
        $html .= $image;
        $html .= '</a>';
    }

    $html .= '<div class="wrmj-editorial-card__content">';
    $html .= '<h3><a href="' . esc_url( $url ) . '">' . esc_html( $title ) . '</a></h3>';

    if ( $excerpt ) {
        $html .= '<p>' . esc_html( $excerpt ) . '</p>';
    }

    $html .= '</div></article>';

    return $html;
}

/**
 * Shortcode portabel untuk menampilkan headline dan sorotan redaksi.
 *
 * Contoh: [wrmj_homepage_editorial pick_count="4"]
 */
function wrmj_homepage_editorial_shortcode( $attributes ) {
    $attributes = shortcode_atts(
        array(
            'headline'   => 'yes',
            'picks'      => 'yes',
            'pick_count' => 3,
        ),
        $attributes,
        'wrmj_homepage_editorial'
    );

    $editorial = wrmj_get_homepage_editorial_data();
    $show_headline = ! in_array(
        strtolower( (string) $attributes['headline'] ),
        array( '0', 'no', 'false', 'off' ),
        true
    );
    $show_picks = ! in_array(
        strtolower( (string) $attributes['picks'] ),
        array( '0', 'no', 'false', 'off' ),
        true
    );
    $pick_count = min( 12, absint( $attributes['pick_count'] ) );
    $html       = '';

    if ( $show_headline && $editorial['headline_id'] ) {
        $card = wrmj_render_editorial_card(
            $editorial['headline_id'],
            'wrmj-editorial-card--headline'
        );

        if ( $card ) {
            $html .= '<section class="wrmj-homepage-editorial__headline">';
            $html .= '<h2>' . esc_html__( 'Headline Utama', 'wartaradar-manajemen-jurnal' ) . '</h2>';
            $html .= $card . '</section>';
        }
    }

    $pick_ids = $show_picks && $pick_count
        ? array_slice( $editorial['editor_pick_ids'], 0, $pick_count )
        : array();

    if ( $pick_ids ) {
        $html .= '<section class="wrmj-homepage-editorial__picks">';
        $html .= '<h2>' . esc_html__( 'Sorotan Redaksi', 'wartaradar-manajemen-jurnal' ) . '</h2>';

        foreach ( $pick_ids as $post_id ) {
            $html .= wrmj_render_editorial_card(
                $post_id,
                'wrmj-editorial-card--pick'
            );
        }

        $html .= '</section>';
    }

    return $html;
}
add_shortcode( 'wrmj_homepage_editorial', 'wrmj_homepage_editorial_shortcode' );

function wrmj_enqueue_editorial_styles() {
    wp_enqueue_style(
        'wrmj-editorial',
        WRMJ_URL . 'assets/wrmj-editorial.css',
        array(),
        WRMJ_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'wrmj_enqueue_editorial_styles' );

/**
 * Daftar artikel terbit untuk halaman pengaturan.
 */
function wrmj_editorial_get_published_posts() {
    $posts = get_posts(
        array(
            'post_type'              => 'post',
            'post_status'            => 'publish',
            'posts_per_page'         => -1,
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'ignore_sticky_posts'    => true,
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
        )
    );

    return array_values(
        array_filter(
            $posts,
            function ( $post ) {
                return $post instanceof WP_Post
                    && ! wrmj_editorial_is_podcast( $post->ID );
            }
        )
    );
}

/**
 * Menyimpan pilihan editorial dari halaman Pengaturan Beranda.
 */
function wrmj_handle_save_homepage_editorial() {
    if ( ! wrmj_editorial_user_can_manage() ) {
        wp_die( 'Anda tidak memiliki izin untuk mengelola pilihan editorial.' );
    }

    check_admin_referer(
        'wrmj_save_homepage_editorial',
        'wrmj_editorial_nonce'
    );

    $headline_id = isset( $_POST['headline_id'] )
        ? absint( wp_unslash( $_POST['headline_id'] ) )
        : 0;

    if ( $headline_id && ! wrmj_editorial_is_valid_published_post( $headline_id ) ) {
        wp_die( 'Headline Utama harus berupa artikel berita yang sudah terbit.' );
    }

    $raw_pick_ids = isset( $_POST['editor_pick_ids'] )
        ? wp_unslash( $_POST['editor_pick_ids'] )
        : array();

    if ( ! is_array( $raw_pick_ids ) ) {
        $raw_pick_ids = array();
    }

    $editor_pick_ids = array();

    foreach ( $raw_pick_ids as $raw_id ) {
        $post_id = absint( $raw_id );

        if (
            $post_id
            && $post_id !== $headline_id
            && wrmj_editorial_is_valid_published_post( $post_id )
            && ! in_array( $post_id, $editor_pick_ids, true )
        ) {
            $editor_pick_ids[] = $post_id;
        }
    }

    update_option(
        'wrmj_homepage_editorial',
        array(
            'headline_id'     => $headline_id,
            'editor_pick_ids' => $editor_pick_ids,
        ),
        false
    );

    wp_safe_redirect(
        add_query_arg(
            'updated',
            '1',
            admin_url( 'admin.php?page=wrmj-editorial-settings' )
        )
    );
    exit;
}
add_action(
    'admin_post_wrmj_save_homepage_editorial',
    'wrmj_handle_save_homepage_editorial'
);

/**
 * Hapus pilihan saat artikel berubah dari publish ke status lain.
 * Artikel yang kelak diterbitkan ulang tidak otomatis dipilih kembali.
 */
function wrmj_editorial_clear_unpublished_post( $new_status, $old_status, $post ) {
    if (
        ! ( $post instanceof WP_Post )
        || 'post' !== $post->post_type
        || 'publish' !== $old_status
        || 'publish' === $new_status
    ) {
        return;
    }

    wrmj_editorial_remove_post_from_selections( $post->ID );
}
add_action(
    'transition_post_status',
    'wrmj_editorial_clear_unpublished_post',
    10,
    3
);

/**
 * Hapus artikel yang dihapus permanen dari pilihan editorial.
 */
function wrmj_editorial_clear_deleted_post( $post_id ) {
    wrmj_editorial_remove_post_from_selections( $post_id );
}
add_action(
    'before_delete_post',
    'wrmj_editorial_clear_deleted_post'
);

/**
 * Helper internal untuk membersihkan satu ID dari seluruh slot editorial.
 */
function wrmj_editorial_remove_post_from_selections( $post_id ) {
    $post_id = absint( $post_id );
    $saved   = get_option( 'wrmj_homepage_editorial', array() );

    if ( ! $post_id || ! is_array( $saved ) ) {
        return;
    }

    $changed = false;

    if ( isset( $saved['headline_id'] ) && absint( $saved['headline_id'] ) === $post_id ) {
        $saved['headline_id'] = 0;
        $changed = true;
    }

    if ( isset( $saved['editor_pick_ids'] ) && is_array( $saved['editor_pick_ids'] ) ) {
        $filtered = array_values(
            array_filter(
                array_map( 'absint', $saved['editor_pick_ids'] ),
                function ( $id ) use ( $post_id ) {
                    return $id && $id !== $post_id;
                }
            )
        );

        if ( $filtered !== array_values( array_map( 'absint', $saved['editor_pick_ids'] ) ) ) {
            $saved['editor_pick_ids'] = $filtered;
            $changed = true;
        }
    }

    if ( $changed ) {
        update_option( 'wrmj_homepage_editorial', $saved, false );
    }
}

/**
 * Callback halaman pengaturan yang digunakan oleh menu Admin dan Editor.
 */
function wrmj_render_editorial_settings_page() {
    if ( ! wrmj_editorial_user_can_manage() ) {
        wp_die( 'Anda tidak memiliki izin untuk mengakses halaman ini.' );
    }

    require WRMJ_PATH . 'admin/wr-editorial-settings.php';
}
