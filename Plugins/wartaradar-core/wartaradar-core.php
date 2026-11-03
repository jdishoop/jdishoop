<?php
/**
 * Plugin Name: WartaRadar Core
 * Plugin URI: https://wartaradar.com
 * Description: Mesin inti internal WartaRadar untuk role, workflow, metadata jurnal, dan Podcast.
 * Version: 1.4.3
 * Author: WartaRadar
 * Author URI: https://wartaradar.com
 * Text Domain: wartaradar-core
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/* =========================================================
 * CORE CONSTANTS
 * ========================================================= */

define( 'WARTARADAR_CORE_VERSION', '1.4.3' );
define( 'WARTARADAR_CORE_FILE', __FILE__ );
define( 'WARTARADAR_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'WARTARADAR_CORE_URL', plugin_dir_url( __FILE__ ) );

/* =========================================================
 * WORKFLOW STATUS
 * ========================================================= */

define( 'WARTARADAR_STATUS_DRAFT', 'draft' );
define( 'WARTARADAR_STATUS_REVIEW', 'menunggu_review' );
define( 'WARTARADAR_STATUS_REVISION', 'perlu_revisi' );
define( 'WARTARADAR_STATUS_PUBLISHED', 'published' );

/* =========================================================
 * PODCAST
 * ========================================================= */

define( 'WARTARADAR_META_IS_PODCAST', '_wartaradar_is_podcast' );
define( 'WARTARADAR_META_PODCAST_SERIES_ID', '_wartaradar_podcast_series_id' );
define( 'WARTARADAR_META_PODCAST_SERIES_TITLE', '_wartaradar_podcast_series_title' );
define( 'WARTARADAR_META_PODCAST_EPISODE', '_wartaradar_podcast_episode' );
define( 'WARTARADAR_META_PODCAST_AUDIO_URL', '_wartaradar_podcast_audio_url' );

/* =========================================================
 * ROLE DEFINITIONS
 * ========================================================= */

/**
 * Capability Jurnalis.
 */
function wartaradar_core_get_jurnalis_capabilities() {

    return array(
        'read'                 => true,
        'edit_posts'           => true,
        'edit_published_posts' => true,
        'upload_files'         => true,
    );
}

/**
 * Capability Editor Berita.
 *
 * Editor dapat mengelola, memeriksa, merevisi,
 * dan menerbitkan berita, tetapi tidak dapat menghapus berita.
 */
function wartaradar_core_get_editor_capabilities() {

    return array(
        'read'                       => true,
        'edit_posts'                 => true,
        'edit_others_posts'          => true,
        'edit_published_posts'       => true,
        'publish_posts'              => true,
        'read_private_posts'         => true,
        'upload_files'               => true,
        'wartaradar_manage_podcasts' => true,
        'wartaradar_publish_podcasts' => true,
    );
}

/**
 * Capability delete yang harus dicabut dari Editor.
 */
function wartaradar_core_get_editor_forbidden_delete_capabilities() {

    return array(
        'delete_posts',
        'delete_others_posts',
        'delete_published_posts',
        'delete_private_posts',
    );
}

/**
 * Sinkronisasi role Jurnalis.
 */
function wartaradar_core_sync_jurnalis_role() {

    $role = get_role( 'jurnalis' );

    if ( ! $role ) {
        add_role(
            'jurnalis',
            'Jurnalis',
            wartaradar_core_get_jurnalis_capabilities()
        );

        return;
    }

    foreach ( wartaradar_core_get_jurnalis_capabilities() as $capability => $grant ) {

        if ( $grant ) {
            $role->add_cap( $capability );
        } else {
            $role->remove_cap( $capability );
        }
    }
}

/**
 * Sinkronisasi role Editor Berita.
 */
function wartaradar_core_sync_editor_role() {

    $role = get_role( 'editor_berita' );

    if ( ! $role ) {
        add_role(
            'editor_berita',
            'Editor Berita',
            wartaradar_core_get_editor_capabilities()
        );

        $role = get_role( 'editor_berita' );
    }

    if ( ! $role ) {
        return;
    }

    foreach ( wartaradar_core_get_editor_capabilities() as $capability => $grant ) {

        if ( $grant ) {
            $role->add_cap( $capability );
        } else {
            $role->remove_cap( $capability );
        }
    }

    foreach ( wartaradar_core_get_editor_forbidden_delete_capabilities() as $capability ) {
        $role->remove_cap( $capability );
    }
}

/**
 * Sinkronisasi seluruh role WartaRadar.
 */
/**
 * Capability Podcaster.
 *
 * Podcaster dapat mengelola Podcast tetapi tidak menerbitkan.
 */
function wartaradar_core_get_podcaster_capabilities() {

    return array(
        'read'                       => true,
        'edit_posts'                 => true,
        'upload_files'               => true,
        'wartaradar_manage_podcasts' => true,
        'wartaradar_publish_podcasts' => false,
    );
}

/**
 * Sinkronisasi role Podcaster.
 */
function wartaradar_core_sync_podcaster_role() {

    $role = get_role( 'podcaster' );

    if ( ! $role ) {
        add_role(
            'podcaster',
            'Podcaster',
            wartaradar_core_get_podcaster_capabilities()
        );

        $role = get_role( 'podcaster' );
    }

    if ( ! $role ) {
        return;
    }

    foreach ( wartaradar_core_get_podcaster_capabilities() as $capability => $grant ) {

        if ( $grant ) {
            $role->add_cap( $capability );
        } else {
            $role->remove_cap( $capability );
        }
    }

    $role->remove_cap( 'publish_posts' );
    $role->remove_cap( 'publish_pages' );
}

/**
 * Apakah user dapat mengelola Podcast?
 */
function wartaradar_core_can_manage_podcast( $user = null ) {

    if ( null === $user ) {
        $user = wp_get_current_user();
    }

    if ( ! $user || ! $user->exists() ) {
        return false;
    }

    if ( in_array( 'administrator', (array) $user->roles, true ) ) {
        return true;
    }

    return user_can(
        $user,
        'wartaradar_manage_podcasts'
    );
}

/**
 * Apakah user dapat menerbitkan Podcast?
 */
function wartaradar_core_can_publish_podcast( $user = null ) {

    if ( null === $user ) {
        $user = wp_get_current_user();
    }

    if ( ! $user || ! $user->exists() ) {
        return false;
    }

    if ( in_array( 'administrator', (array) $user->roles, true ) ) {
        return true;
    }

    return user_can(
        $user,
        'wartaradar_publish_podcasts'
    );
}
/**
 * Mencegah user tanpa capability publish Podcast menerbitkan Podcast.
 *
 * Gate ini bekerja sebelum WordPress menyimpan post.
 * Artikel biasa tidak disentuh.
 */
function wartaradar_core_enforce_podcast_publish_capability(
    $data,
    $postarr,
    $unsanitized_postarr,
    $update
) {

    if ( empty( $data['post_type'] ) || 'post' !== $data['post_type'] ) {
        return $data;
    }

    if ( empty( $data['post_status'] ) || 'publish' !== $data['post_status'] ) {
        return $data;
    }

    /*
     * Jika form Podcast sedang digunakan, checkbox adalah sumber
     * kebenaran untuk status Podcast pada request tersebut.
     */
    $is_podcast = false;

    if (
        isset( $_POST['wartaradar_core_podcast_nonce'] )
        && wp_verify_nonce(
            sanitize_text_field(
                wp_unslash( $_POST['wartaradar_core_podcast_nonce'] )
            ),
            'wartaradar_core_save_podcast'
        )
    ) {
        $is_podcast = isset( $_POST['wartaradar_is_podcast'] );
    } elseif ( ! empty( $postarr['ID'] ) ) {

        /*
         * Untuk update di luar form Podcast, pertahankan
         * perlindungan terhadap Podcast yang sudah ada.
         */
        $is_podcast = wartaradar_core_is_podcast(
            absint( $postarr['ID'] )
        );
    }

    if ( ! $is_podcast ) {
        return $data;
    }

    if ( ! wartaradar_core_can_publish_podcast() ) {
        $data['post_status'] = 'draft';
    }

    return $data;
}
/**
 * Sinkronisasi capability Podcast Administrator.
 */
function wartaradar_core_sync_administrator_podcast_capabilities() {

    $role = get_role( 'administrator' );

    if ( ! $role ) {
        return;
    }

    $role->add_cap( 'wartaradar_manage_podcasts' );
    $role->add_cap( 'wartaradar_publish_podcasts' );
}

/**
 * Sinkronisasi seluruh role WartaRadar.
 */
add_filter(
    'wp_insert_post_data',
    'wartaradar_core_enforce_podcast_publish_capability',
    10,
    4
);
/**
 * Kapabilitas Pengendali Keuangan WRMI.
 */
function wartaradar_core_get_pengendali_keuangan_capabilities() {

    return array(
        'read'                => true,
        'wrmi_manage_payment' => true,
    );
}

/**
 * Sinkronisasi role Pengendali Keuangan.
 */
function wartaradar_core_sync_pengendali_keuangan_role() {

    $role = get_role( 'pengendali_keuangan' );

    if ( ! $role ) {
        add_role(
            'pengendali_keuangan',
            'Pengendali Keuangan',
            wartaradar_core_get_pengendali_keuangan_capabilities()
        );

        $role = get_role( 'pengendali_keuangan' );
    }

    if ( ! $role ) {
        return;
    }

    foreach ( wartaradar_core_get_pengendali_keuangan_capabilities() as $capability => $grant ) {

        if ( $grant ) {
            $role->add_cap( $capability );
        } else {
            $role->remove_cap( $capability );
        }
    }
}
function wartaradar_core_initialize_roles() {

    wartaradar_core_sync_jurnalis_role();
    wartaradar_core_sync_editor_role();
    wartaradar_core_sync_podcaster_role();
    wartaradar_core_sync_pengendali_keuangan_role();
    wartaradar_core_sync_administrator_podcast_capabilities();
}

/* =========================================================
 * ACTIVATION / DEACTIVATION
 * ========================================================= */

function wartaradar_core_activate() {

    wartaradar_core_initialize_roles();

    update_option(
        'wartaradar_core_version',
        WARTARADAR_CORE_VERSION
    );
}

register_activation_hook(
    __FILE__,
    'wartaradar_core_activate'
);

function wartaradar_core_deactivate() {

    /*
     * Role dan data jurnal tidak dihapus ketika plugin dinonaktifkan.
     * Core hanya berhenti menjalankan mesinnya.
     */
}

register_deactivation_hook(
    __FILE__,
    'wartaradar_core_deactivate'
);

/**
 * Sinkronisasi role setelah pembaruan versi Core.
 */
function wartaradar_core_maybe_upgrade() {

    $installed_version = get_option(
        'wartaradar_core_version',
        ''
    );

    if ( $installed_version !== WARTARADAR_CORE_VERSION ) {

        wartaradar_core_initialize_roles();

        update_option(
            'wartaradar_core_version',
            WARTARADAR_CORE_VERSION
        );
    }
}

add_action(
    'init',
    'wartaradar_core_maybe_upgrade',
    5
);

/* =========================================================
 * ROLE HELPERS
 * ========================================================= */

/**
 * Apakah user adalah Jurnalis?
 */
function wartaradar_core_is_jurnalis( $user = null ) {

    if ( null === $user ) {
        $user = wp_get_current_user();
    }

    if ( ! $user instanceof WP_User ) {
        return false;
    }

    return in_array(
        'jurnalis',
        (array) $user->roles,
        true
    );
}

/**
 * Apakah user adalah Editor Berita?
 */
function wartaradar_core_is_editor( $user = null ) {

    if ( null === $user ) {
        $user = wp_get_current_user();
    }

    if ( ! $user instanceof WP_User ) {
        return false;
    }

    return in_array(
        'editor_berita',
        (array) $user->roles,
        true
    );
}

/**
 * Apakah user adalah Administrator?
 */
function wartaradar_core_is_administrator( $user = null ) {

    if ( null === $user ) {
        $user = wp_get_current_user();
    }

    if ( ! $user instanceof WP_User ) {
        return false;
    }

    return in_array(
        'administrator',
        (array) $user->roles,
        true
    );
}

/**
 * Apakah user merupakan bagian dari newsroom?
 */
function wartaradar_core_is_newsroom_user( $user = null ) {

    return (
        wartaradar_core_is_jurnalis( $user )
        || wartaradar_core_is_editor( $user )
        || wartaradar_core_is_administrator( $user )
    );
}

/* =========================================================
 * WORKFLOW HELPERS
 * ========================================================= */

/**
 * Memeriksa apakah status workflow valid.
 */
function wartaradar_core_is_valid_workflow_status( $status ) {

    return in_array(
        $status,
        array(
            WARTARADAR_STATUS_DRAFT,
            WARTARADAR_STATUS_REVIEW,
            WARTARADAR_STATUS_REVISION,
            WARTARADAR_STATUS_PUBLISHED,
        ),
        true
    );
}

/**
 * Mengambil status workflow jurnal.
 */
function wartaradar_core_get_workflow_status( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return WARTARADAR_STATUS_DRAFT;
    }

    $status = get_post_meta(
        $post_id,
        '_wartaradar_workflow_status',
        true
    );

    if ( ! wartaradar_core_is_valid_workflow_status( $status ) ) {
        return WARTARADAR_STATUS_DRAFT;
    }

    return $status;
}

/**
 * Menetapkan status workflow jurnal.
 */
function wartaradar_core_set_workflow_status( $post_id, $status ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    if ( 'post' !== get_post_type( $post_id ) ) {
        return false;
    }

    if ( ! wartaradar_core_is_valid_workflow_status( $status ) ) {
        return false;
    }

    return update_post_meta(
        $post_id,
        '_wartaradar_workflow_status',
        $status
    );
}

/* =========================================================
 * EDITOR METADATA
 * ========================================================= */

/**
 * Mengambil ID Editor yang menangani jurnal.
 */
function wartaradar_core_get_editor_id( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return 0;
    }

    return absint(
        get_post_meta(
            $post_id,
            '_wartaradar_editor_id',
            true
        )
    );
}

/**
 * Menetapkan ID Editor.
 */
function wartaradar_core_set_editor_id( $post_id, $user_id ) {

    $post_id = absint( $post_id );
    $user_id = absint( $user_id );

    if ( ! $post_id || ! $user_id ) {
        return false;
    }

    if ( ! get_userdata( $user_id ) ) {
        return false;
    }

    return update_post_meta(
        $post_id,
        '_wartaradar_editor_id',
        $user_id
    );
}

/* =========================================================
 * REVIEW NOTE
 * ========================================================= */

/**
 * Mengambil catatan review.
 */
function wartaradar_core_get_review_note( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    return (string) get_post_meta(
        $post_id,
        '_wartaradar_review_note',
        true
    );
}

/**
 * Menyimpan catatan review.
 */
function wartaradar_core_set_review_note( $post_id, $note ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    $note = sanitize_textarea_field( $note );

    return update_post_meta(
        $post_id,
        '_wartaradar_review_note',
        $note
    );
}

/* =========================================================
 * REVIEW TIMESTAMP
 * ========================================================= */

/**
 * Menyimpan waktu review.
 */
function wartaradar_core_set_reviewed_at( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    return update_post_meta(
        $post_id,
        '_wartaradar_reviewed_at',
        current_time( 'mysql' )
    );
}

/**
 * Mengambil waktu review.
 */
function wartaradar_core_get_reviewed_at( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    return (string) get_post_meta(
        $post_id,
        '_wartaradar_reviewed_at',
        true
    );
}

/* =========================================================
 * PUBLISHED BY
 * ========================================================= */

/**
 * Menyimpan user yang menerbitkan jurnal.
 */
function wartaradar_core_set_published_by( $post_id, $user_id ) {

    $post_id = absint( $post_id );
    $user_id = absint( $user_id );

    if ( ! $post_id || ! $user_id ) {
        return false;
    }

    if ( ! get_userdata( $user_id ) ) {
        return false;
    }

    return update_post_meta(
        $post_id,
        '_wartaradar_published_by',
        $user_id
    );
}

/**
 * Mengambil user yang menerbitkan jurnal.
 */
function wartaradar_core_get_published_by( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return 0;
    }

    return absint(
        get_post_meta(
            $post_id,
            '_wartaradar_published_by',
            true
        )
    );
}

/* =========================================================
 * PODCAST ENGINE
 * ========================================================= */

/**
 * Memeriksa apakah jurnal ditandai sebagai Podcast.
 */
function wartaradar_core_is_podcast( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
        return false;
    }

    return '1' === get_post_meta(
        $post_id,
        WARTARADAR_META_IS_PODCAST,
        true
    );
}

/**
 * Mengambil ID serial Podcast.
 */
function wartaradar_core_get_podcast_series_id( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    return sanitize_text_field(
        get_post_meta(
            $post_id,
            WARTARADAR_META_PODCAST_SERIES_ID,
            true
        )
    );
}

/**
 * Mengambil nama serial Podcast.
 */
function wartaradar_core_get_podcast_series_title( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    return (string) get_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_SERIES_TITLE,
        true
    );
}

/**
 * Mengambil nomor episode Podcast.
 */
function wartaradar_core_get_podcast_episode( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return 0;
    }

    return absint(
        get_post_meta(
            $post_id,
            WARTARADAR_META_PODCAST_EPISODE,
            true
        )
    );
}

/**
 * Menampilkan episode Podcast untuk konten atau template tema.
 *
 * Contoh: [wartaradar_podcast_list count="8" series="series-id"]
 */
function wartaradar_core_podcast_list_shortcode( $attributes ) {

    $attributes = shortcode_atts(
        array(
            'count'  => 10,
            'series' => '',
        ),
        $attributes,
        'wartaradar_podcast_list'
    );

    $meta_query = array(
        array(
            'key'     => WARTARADAR_META_IS_PODCAST,
            'value'   => '1',
            'compare' => '=',
        ),
    );
    $series_id = sanitize_text_field( $attributes['series'] );

    if ( '' !== $series_id ) {
        $meta_query[] = array(
            'key'     => WARTARADAR_META_PODCAST_SERIES_ID,
            'value'   => $series_id,
            'compare' => '=',
        );
    }

    $podcast_query = new WP_Query(
        array(
            'post_type'           => 'post',
            'post_status'         => 'publish',
            'posts_per_page'      => min( 50, max( 1, absint( $attributes['count'] ) ) ),
            'ignore_sticky_posts' => true,
            'meta_query'          => $meta_query,
        )
    );

    if ( ! $podcast_query->have_posts() ) {
        return '<p class="wrc-podcast-empty">' . esc_html__( 'Belum ada episode Podcast yang diterbitkan.', 'wartaradar-core' ) . '</p>';
    }

    ob_start();
    echo '<div class="wrc-podcast-list">';

    while ( $podcast_query->have_posts() ) {
        $podcast_query->the_post();
        $post_id     = get_the_ID();
        $series_title = wartaradar_core_get_podcast_series_title( $post_id );
        $episode      = wartaradar_core_get_podcast_episode( $post_id );
        $audio_url    = esc_url_raw(
            get_post_meta(
                $post_id,
                WARTARADAR_META_PODCAST_AUDIO_URL,
                true
            )
        );

        echo '<article class="wrc-podcast-episode">';
        echo '<div class="wrc-podcast-episode__meta">';
        echo esc_html( $series_title ? $series_title : __( 'Podcast', 'wartaradar-core' ) );

        if ( $episode ) {
            echo ' · ' . esc_html( sprintf( __( 'Episode %d', 'wartaradar-core' ), $episode ) );
        }

        echo ' · ' . esc_html( get_the_date() ) . '</div>';
        echo '<h2 class="wrc-podcast-episode__title"><a href="' . esc_url( get_permalink() ) . '">' . esc_html( get_the_title() ) . '</a></h2>';

        $excerpt = wp_trim_words( wp_strip_all_tags( get_the_excerpt() ), 28 );

        if ( $excerpt ) {
            echo '<p class="wrc-podcast-episode__excerpt">' . esc_html( $excerpt ) . '</p>';
        }

        if ( $audio_url ) {
            echo wp_audio_shortcode( array( 'src' => $audio_url ) );
        }

        echo '</article>';
    }

    echo '</div>';
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode( 'wartaradar_podcast_list', 'wartaradar_core_podcast_list_shortcode' );

function wartaradar_core_enqueue_podcast_styles() {
    wp_enqueue_style(
        'wartaradar-core-podcast',
        WARTARADAR_CORE_URL . 'assets/wartaradar-podcast.css',
        array(),
        WARTARADAR_CORE_VERSION
    );
}
add_action( 'wp_enqueue_scripts', 'wartaradar_core_enqueue_podcast_styles' );

/**
 * Menetapkan metadata serial Podcast.
 */
function wartaradar_core_set_podcast_series(
    $post_id,
    $series_id,
    $series_title
) {

    $post_id = absint( $post_id );
    $series_id = sanitize_text_field( $series_id );
    $series_title = sanitize_text_field( $series_title );

    if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
        return false;
    }

    if ( '' === $series_id || '' === $series_title ) {
        return false;
    }

    update_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_SERIES_ID,
        $series_id
    );

    return update_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_SERIES_TITLE,
        $series_title
    );
}

/**
 * Menetapkan nomor episode Podcast.
 */
function wartaradar_core_set_podcast_episode( $post_id, $episode ) {

    $post_id = absint( $post_id );
    $episode = absint( $episode );

    if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
        return false;
    }

    if ( $episode < 1 ) {
        return false;
    }

    return update_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_EPISODE,
        $episode
    );
}

/**
 * Menghapus metadata serial dan episode Podcast.
 */
function wartaradar_core_clear_podcast_series_data( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return false;
    }

    delete_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_SERIES_ID
    );

    delete_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_SERIES_TITLE
    );

    delete_post_meta(
        $post_id,
        WARTARADAR_META_PODCAST_EPISODE
    );

    return true;
}

/**
 * Menetapkan status Podcast.
 *
 * Nilai true  = Podcast.
 * Nilai false = berita biasa.
 */
function wartaradar_core_set_podcast( $post_id, $is_podcast = false ) {

    $post_id = absint( $post_id );

    if ( ! $post_id || 'post' !== get_post_type( $post_id ) ) {
        return false;
    }

    if ( $is_podcast ) {
        return update_post_meta(
            $post_id,
            WARTARADAR_META_IS_PODCAST,
            '1'
        );
    }

    delete_post_meta(
        $post_id,
        WARTARADAR_META_IS_PODCAST
    );

    wartaradar_core_clear_podcast_series_data( $post_id );

    return true;
}

/**
 * Mengambil seluruh serial Podcast yang sudah memiliki metadata serial.
 *
 * Setiap item menggunakan series_id sebagai key.
 */
function wartaradar_core_get_podcast_series_list() {

    $query = new WP_Query(
        array(
            'post_type'              => 'post',
            'post_status'            => array(
                'publish',
                'draft',
                'pending',
                'future',
                'private',
            ),
            'posts_per_page'         => -1,
            'fields'                 => 'ids',
            'meta_query'             => array(
                'relation' => 'AND',
                array(
                    'key'     => WARTARADAR_META_IS_PODCAST,
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_SERIES_ID,
                    'compare' => 'EXISTS',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_SERIES_TITLE,
                    'compare' => 'EXISTS',
                ),
            ),
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
            'update_post_meta_cache' => true,
            'update_post_term_cache' => false,
        )
    );

    $series = array();

    foreach ( $query->posts as $post_id ) {

        $series_id = wartaradar_core_get_podcast_series_id( $post_id );
        $title = wartaradar_core_get_podcast_series_title( $post_id );

        if ( '' === $series_id || '' === $title ) {
            continue;
        }

        if ( isset( $series[ $series_id ] ) ) {
            continue;
        }

        $series[ $series_id ] = array(
            'id'    => $series_id,
            'title' => $title,
        );
    }

    wp_reset_postdata();

    return array_values( $series );
}

/**
 * Mengambil nomor episode tertinggi dalam satu serial.
 */
/**
 * Mengambil ID episode sebelumnya dalam satu serial Podcast.
 */
function wartaradar_core_get_podcast_previous_episode( $series_id, $current_episode ) {

    $series_id       = sanitize_text_field( $series_id );
    $current_episode = absint( $current_episode );

    if ( '' === $series_id || $current_episode < 1 ) {
        return 0;
    }

    $query = new WP_Query(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => WARTARADAR_META_IS_PODCAST,
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_SERIES_ID,
                    'value'   => $series_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_EPISODE,
                    'value'   => $current_episode,
                    'type'    => 'NUMERIC',
                    'compare' => '<',
                ),
            ),
            'meta_key'      => WARTARADAR_META_PODCAST_EPISODE,
            'orderby'       => 'meta_value_num',
            'order'         => 'DESC',
            'no_found_rows' => true,
        )
    );

    $post_id = ! empty( $query->posts ) ? absint( $query->posts[0] ) : 0;

    wp_reset_postdata();

    return $post_id;
}

/**
 * Mengambil ID episode berikutnya dalam satu serial Podcast.
 */
function wartaradar_core_get_podcast_next_episode( $series_id, $current_episode ) {

    $series_id       = sanitize_text_field( $series_id );
    $current_episode = absint( $current_episode );

    if ( '' === $series_id || $current_episode < 1 ) {
        return 0;
    }

    $query = new WP_Query(
        array(
            'post_type'      => 'post',
            'post_status'    => 'publish',
            'posts_per_page' => 1,
            'fields'         => 'ids',
            'meta_query'     => array(
                'relation' => 'AND',
                array(
                    'key'     => WARTARADAR_META_IS_PODCAST,
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_SERIES_ID,
                    'value'   => $series_id,
                    'compare' => '=',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_EPISODE,
                    'value'   => $current_episode,
                    'type'    => 'NUMERIC',
                    'compare' => '>',
                ),
            ),
            'meta_key'      => WARTARADAR_META_PODCAST_EPISODE,
            'orderby'       => 'meta_value_num',
            'order'         => 'ASC',
            'no_found_rows' => true,
        )
    );

    $post_id = ! empty( $query->posts ) ? absint( $query->posts[0] ) : 0;

    wp_reset_postdata();

    return $post_id;
}
function wartaradar_core_get_podcast_max_episode( $series_id, $exclude_post_id = 0 ) {

    $series_id = sanitize_text_field( $series_id );
    $exclude_post_id = absint( $exclude_post_id );

    if ( '' === $series_id ) {
        return 0;
    }

    $query_args = array(
        'post_type'              => 'post',
        'post_status'            => array(
            'publish',
            'draft',
            'pending',
            'future',
            'private',
        ),
        'posts_per_page'         => -1,
        'fields'                 => 'ids',
        'meta_query'             => array(
            'relation' => 'AND',
            array(
                'key'     => WARTARADAR_META_IS_PODCAST,
                'value'   => '1',
                'compare' => '=',
            ),
            array(
                'key'     => WARTARADAR_META_PODCAST_SERIES_ID,
                'value'   => $series_id,
                'compare' => '=',
            ),
        ),
        'no_found_rows'          => true,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    );

    if ( $exclude_post_id ) {
        $query_args['post__not_in'] = array( $exclude_post_id );
    }

    $query = new WP_Query( $query_args );

    $max_episode = 0;

    foreach ( $query->posts as $post_id ) {

        $episode = wartaradar_core_get_podcast_episode( $post_id );

        if ( $episode > $max_episode ) {
            $max_episode = $episode;
        }
    }

    wp_reset_postdata();

    return $max_episode;
}

/**
 * Mengambil nomor episode berikutnya dalam satu serial.
 *
 * Jika post saat ini sudah merupakan episode dari serial tersebut,
 * nomor episode yang sedang dipakai tetap dipertahankan.
 */
function wartaradar_core_get_next_podcast_episode(
    $series_id,
    $current_post_id = 0
) {

    $series_id = sanitize_text_field( $series_id );
    $current_post_id = absint( $current_post_id );

    if ( '' === $series_id ) {
        return 1;
    }

    if ( $current_post_id ) {

        $current_series_id = wartaradar_core_get_podcast_series_id(
            $current_post_id
        );

        if ( $current_series_id === $series_id ) {

            $current_episode = wartaradar_core_get_podcast_episode(
                $current_post_id
            );

            if ( $current_episode > 0 ) {
                return $current_episode;
            }
        }
    }

    return wartaradar_core_get_podcast_max_episode(
        $series_id,
        $current_post_id
    ) + 1;
}

/**
 * Mengambil metadata serial berdasarkan ID serial.
 */
function wartaradar_core_get_podcast_series( $series_id ) {

    $series_id = sanitize_text_field( $series_id );

    if ( '' === $series_id ) {
        return false;
    }

    $query = new WP_Query(
        array(
            'post_type'              => 'post',
            'post_status'            => array(
                'publish',
                'draft',
                'pending',
                'future',
                'private',
            ),
            'posts_per_page'         => 1,
            'fields'                 => 'ids',
            'meta_query'             => array(
                'relation' => 'AND',
                array(
                    'key'     => WARTARADAR_META_IS_PODCAST,
                    'value'   => '1',
                    'compare' => '=',
                ),
                array(
                    'key'     => WARTARADAR_META_PODCAST_SERIES_ID,
                    'value'   => $series_id,
                    'compare' => '=',
                ),
            ),
            'orderby'                => 'date',
            'order'                  => 'DESC',
            'no_found_rows'          => true,
        )
    );

    if ( empty( $query->posts ) ) {
        wp_reset_postdata();

        return false;
    }

    $post_id = absint( $query->posts[0] );

    wp_reset_postdata();

    $title = wartaradar_core_get_podcast_series_title( $post_id );

    if ( '' === $title ) {
        return false;
    }

    return array(
        'id'    => $series_id,
        'title' => $title,
    );
}

/* =========================================================
 * PODCAST META BOX
 * ========================================================= */

/**
 * Menambahkan Podcast Engine pada editor berita.
 */
function wartaradar_core_add_podcast_meta_box() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        return;
    }

    add_meta_box(
        'wartaradar_core_podcast',
        'Format Berita',
        'wartaradar_core_render_podcast_meta_box',
        'post',
        'side',
        'high'
    );
}

add_action(
    'add_meta_boxes',
    'wartaradar_core_add_podcast_meta_box'
);

/**
 * Tampilan Podcast Engine.
 */
function wartaradar_core_render_podcast_meta_box( $post ) {

    wp_nonce_field(
        'wartaradar_core_save_podcast',
        'wartaradar_core_podcast_nonce'
    );

    $is_podcast = wartaradar_core_is_podcast( $post->ID );

    $series_id = wartaradar_core_get_podcast_series_id(
        $post->ID
    );

    $series_title = wartaradar_core_get_podcast_series_title(
        $post->ID
    );

    $episode = wartaradar_core_get_podcast_episode(
        $post->ID
    );

    $series_list = wartaradar_core_get_podcast_series_list();

    $audio_url = get_post_meta( $post->ID, WARTARADAR_META_PODCAST_AUDIO_URL, true );

    $has_series = (
        '' !== $series_id
        && '' !== $series_title
        && $episode > 0
    );

    $default_mode = $has_series
        ? 'existing'
        : 'new';
    ?>

    <div
        id="wartaradar-core-podcast-box"
        style="margin-top:0;"
    >

        <p style="margin-top:0;">
            <label>
                <input
                    type="checkbox"
                    name="wartaradar_is_podcast"
                    value="1"
                    id="wartaradar-is-podcast"
                    <?php checked( $is_podcast, true ); ?>
                >
                <strong>Tandai sebagai Podcast</strong>
            </label>
        </p>

        <div
            id="wartaradar-podcast-settings"
            style="<?php echo $is_podcast ? '' : 'display:none;'; ?>"
        >

            <hr>

            <p style="margin-bottom:4px;">
                <label for="wartaradar-podcast-audio-url"><strong>URL Audio Podcast</strong></label>
            </p>

            <input
                type="url"
                class="widefat"
                id="wartaradar-podcast-audio-url"
                name="wartaradar_podcast_audio_url"
                value="<?php echo esc_attr( $audio_url ); ?>"
                placeholder="https://contoh.com/audio.mp3"
            >

            <p style="margin:5px 0 12px;color:#646970;font-size:12px;">
                Masukkan URL langsung ke berkas audio, misalnya MP3. Tautan halaman YouTube atau Google Drive tidak otomatis dapat diputar.
            </p>

            <hr>

            <p>
                <strong>Serial Podcast</strong>
            </p>

            <p style="margin-bottom:8px;">
                <label>
                    <input
                        type="radio"
                        name="wartaradar_podcast_mode"
                        value="new"
                        <?php checked( $default_mode, 'new' ); ?>
                    >
                    Serial Baru
                </label>
            </p>

            <p style="margin-top:0;">
                <label>
                    <input
                        type="radio"
                        name="wartaradar_podcast_mode"
                        value="existing"
                        <?php checked( $default_mode, 'existing' ); ?>
                    >
                    Lanjutkan Serial
                </label>
            </p>

            <div
                id="wartaradar-podcast-new-series"
                style="<?php echo 'new' === $default_mode ? '' : 'display:none;'; ?>"
            >

                <p style="margin-bottom:4px;">
                    <label for="wartaradar-podcast-series-title">
                        <strong>Nama Serial</strong>
                    </label>
                </p>

                <input
                    type="text"
                    class="widefat"
                    id="wartaradar-podcast-series-title"
                    name="wartaradar_podcast_series_title"
                    value="<?php echo esc_attr( 'new' === $default_mode ? $series_title : '' ); ?>"
                    placeholder="Contoh: WartaRadar Talk"
                >

                <p
                    style="
                        margin:5px 0 0;
                        color:#646970;
                        font-size:12px;
                    "
                >
                    Digunakan untuk membuat serial Podcast baru.
                </p>

            </div>

            <div
                id="wartaradar-podcast-existing-series"
                style="<?php echo 'existing' === $default_mode ? '' : 'display:none;'; ?>"
            >

                <p style="margin-bottom:4px;">
                    <label for="wartaradar-podcast-series-id">
                        <strong>Pilih Serial</strong>
                    </label>
                </p>

                <select
                    class="widefat"
                    id="wartaradar-podcast-series-id"
                    name="wartaradar_podcast_series_id"
                >
                    <option value="">ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â Pilih Serial ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â</option>

                    <?php foreach ( $series_list as $series ) : ?>

                        <option
                            value="<?php echo esc_attr( $series['id'] ); ?>"
                            <?php selected( $series_id, $series['id'] ); ?>
                        >
                            <?php echo esc_html( $series['title'] ); ?>
                        </option>

                    <?php endforeach; ?>

                </select>

                <?php if ( empty( $series_list ) ) : ?>

                    <p
                        style="
                            margin:5px 0 0;
                            color:#b32d2e;
                            font-size:12px;
                        "
                    >
                        Belum ada serial Podcast yang tersedia.
                    </p>

                <?php endif; ?>

            </div>

            <div
                id="wartaradar-podcast-episode-preview"
                style="
                    margin-top:12px;
                    padding:8px 10px;
                    background:#f6f7f7;
                    border:1px solid #dcdcde;
                    border-radius:4px;
                "
            >

                <span
                    style="
                        display:block;
                        color:#646970;
                        font-size:12px;
                    "
                >
                    Episode
                </span>

                <strong
                    id="wartaradar-podcast-episode-number"
                    style="font-size:16px;"
                >
                    <?php
                    if ( $has_series ) {
                        echo esc_html(
                            sprintf(
                                'Episode %02d',
                                $episode
                            )
                        );
                    } else {
                        echo esc_html( 'Episode 01' );
                    }
                    ?>
                </strong>

            </div>

            <p
                style="
                    margin:8px 0 0;
                    color:#646970;
                    font-size:12px;
                "
            >
                Nomor episode ditentukan otomatis oleh WartaRadar.
            </p>

        </div>

        <p
            style="
                margin-bottom:0;
                color:#646970;
                font-size:12px;
            "
        >
            Podcast dikelola sebagai serial dan episode.
        </p>

    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        var podcastCheckbox = document.getElementById(
            'wartaradar-is-podcast'
        );

        var settings = document.getElementById(
            'wartaradar-podcast-settings'
        );

        var newSeries = document.getElementById(
            'wartaradar-podcast-new-series'
        );

        var existingSeries = document.getElementById(
            'wartaradar-podcast-existing-series'
        );

        var radios = document.querySelectorAll(
            'input[name="wartaradar_podcast_mode"]'
        );

        function updatePodcastVisibility() {

            if ( ! podcastCheckbox || ! settings ) {
                return;
            }

            settings.style.display = podcastCheckbox.checked
                ? ''
                : 'none';
        }

        function updateSeriesMode() {

            var selected = document.querySelector(
                'input[name="wartaradar_podcast_mode"]:checked'
            );

            if ( ! selected ) {
                return;
            }

            if ( 'new' === selected.value ) {

                newSeries.style.display = '';
                existingSeries.style.display = 'none';

            } else {

                newSeries.style.display = 'none';
                existingSeries.style.display = '';
            }
        }

        if ( podcastCheckbox ) {
            podcastCheckbox.addEventListener(
                'change',
                updatePodcastVisibility
            );
        }

        radios.forEach(function (radio) {

            radio.addEventListener(
                'change',
                updateSeriesMode
            );

        });

        updatePodcastVisibility();
        updateSeriesMode();

    });
    </script>

    <?php
}

/**
 * Menyimpan Podcast Engine.
 */
function wartaradar_core_save_podcast_meta( $post_id ) {

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( 'post' !== get_post_type( $post_id ) ) {
        return;
    }

    if ( ! isset( $_POST['wartaradar_core_podcast_nonce'] ) ) {
        return;
    }

    if ( ! wp_verify_nonce(
        sanitize_text_field(
            wp_unslash( $_POST['wartaradar_core_podcast_nonce'] )
        ),
        'wartaradar_core_save_podcast'
    ) ) {
        return;
    }

    if ( ! wartaradar_core_can_manage_podcast() ) {
        return;
    }

    if ( ! current_user_can( 'edit_post', $post_id ) ) {
        return;
    }

    $is_podcast = isset( $_POST['wartaradar_is_podcast'] )
        && '1' === sanitize_text_field(
            wp_unslash( $_POST['wartaradar_is_podcast'] )
        );

    /*
     * BUKAN PODCAST
     */
    if ( ! $is_podcast ) {

        wartaradar_core_set_podcast(
            $post_id,
            false
        );

        delete_post_meta( $post_id, WARTARADAR_META_PODCAST_AUDIO_URL );

        return;
    }

    /*
     * AKTIFKAN STATUS PODCAST.
     */
    wartaradar_core_set_podcast(
        $post_id,
        true
    );

    
    /*
     * Simpan URL audio langsung untuk episode Podcast.
     * Hanya URL HTTP/HTTPS yang diterima.
     */
    $audio_url = '';

    if ( isset( $_POST['wartaradar_podcast_audio_url'] ) ) {
        $audio_url = esc_url_raw(
            trim(
                wp_unslash( $_POST['wartaradar_podcast_audio_url'] )
            ),
            array( 'http', 'https' )
        );
    }

    $audio_scheme = wp_parse_url( $audio_url, PHP_URL_SCHEME );

    if (
        '' !== $audio_url
        && in_array( strtolower( (string) $audio_scheme ), array( 'http', 'https' ), true )
    ) {
        update_post_meta(
            $post_id,
            WARTARADAR_META_PODCAST_AUDIO_URL,
            $audio_url
        );
    } else {
        delete_post_meta(
            $post_id,
            WARTARADAR_META_PODCAST_AUDIO_URL
        );
    }
$mode = isset( $_POST['wartaradar_podcast_mode'] )
        ? sanitize_key(
            wp_unslash( $_POST['wartaradar_podcast_mode'] )
        )
        : 'new';

    if ( ! in_array(
        $mode,
        array( 'new', 'existing' ),
        true
    ) ) {
        $mode = 'new';
    }

    /*
     * =========================================================
     * SERIAL BARU
     * =========================================================
     *
     * Setiap kali mode = new, selalu membuat series_id baru.
     */
    if ( 'new' === $mode ) {

        $series_title = isset(
            $_POST['wartaradar_podcast_series_title']
        )
            ? sanitize_text_field(
                wp_unslash(
                    $_POST['wartaradar_podcast_series_title']
                )
            )
            : '';

        if ( '' === $series_title ) {

            wartaradar_core_clear_podcast_series_data(
                $post_id
            );

            return;
        }

        /*
         * Serial Baru selalu mendapatkan ID serial baru.
         */
        $series_id = 'wr-series-' . wp_generate_uuid4();

        wartaradar_core_set_podcast_series(
            $post_id,
            $series_id,
            $series_title
        );

        /*
         * Serial baru selalu dimulai dari Episode 01.
         */
        wartaradar_core_set_podcast_episode(
            $post_id,
            1
        );

        return;
    }

    /*
     * =========================================================
     * LANJUTKAN SERIAL
     * =========================================================
     */

    $series_id = isset(
        $_POST['wartaradar_podcast_series_id']
    )
        ? sanitize_text_field(
            wp_unslash(
                $_POST['wartaradar_podcast_series_id']
            )
        )
        : '';

    if ( '' === $series_id ) {
        return;
    }

    $series = wartaradar_core_get_podcast_series(
        $series_id
    );

    if ( ! $series ) {
        return;
    }

    /*
     * Jika post sudah merupakan episode dari serial yang sama,
     * nomor episode saat ini dipertahankan.
     *
     * Jika post berasal dari serial lain / belum memiliki serial,
     * otomatis mendapatkan episode berikutnya.
     */
    $episode = wartaradar_core_get_next_podcast_episode(
        $series_id,
        $post_id
    );

    wartaradar_core_set_podcast_series(
        $post_id,
        $series_id,
        $series['title']
    );

    wartaradar_core_set_podcast_episode(
        $post_id,
        $episode
    );
}add_action(
    'save_post',
    'wartaradar_core_save_podcast_meta',
    30
);


/* =========================================================
 * REST API PODCAST
 * ========================================================= */
add_action(
    'rest_api_init',
    function () {
        register_rest_field(
            'post',
            'is_podcast',
            array(
                'get_callback' => function ( $object ) {
                    $post_id = isset( $object['id'] ) ? absint( $object['id'] ) : 0;
                    return $post_id > 0 && wartaradar_core_is_podcast( $post_id );
                },
                'schema' => array(
                    'description' => 'Status Podcast berdasarkan checkbox WartaRadar.',
                    'type'        => 'boolean',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
            )
        );

        register_rest_field(
            'post',
            'podcast_audio_url',
            array(
                'get_callback' => function ( $object ) {
                    $post_id = isset( $object['id'] ) ? absint( $object['id'] ) : 0;

                    if ( ! $post_id || ! wartaradar_core_is_podcast( $post_id ) ) {
                        return '';
                    }

                    return (string) get_post_meta(
                        $post_id,
                        WARTARADAR_META_PODCAST_AUDIO_URL,
                        true
                    );
                },
                'schema' => array(
                    'description' => 'URL audio langsung episode Podcast WartaRadar.',
                    'type'        => 'string',
                    'context'     => array( 'view', 'edit' ),
                    'readonly'    => true,
                ),
            )
        );
    }
);

/* =========================================================
/* =========================================================
 * ADMIN PODCAST
 * ========================================================= */

/**
 * Label status workflow Podcast untuk Admin.
 */
function wartaradar_core_get_podcast_workflow_status_label( $status ) {

    $labels = array(
        WARTARADAR_STATUS_DRAFT    => 'Draft',
        WARTARADAR_STATUS_REVIEW   => 'Menunggu Review',
        WARTARADAR_STATUS_REVISION => 'Perlu Revisi',
        WARTARADAR_STATUS_PUBLISHED => 'Terbit',
    );

    return isset( $labels[ $status ] )
        ? $labels[ $status ]
        : 'Draft';
}

/**
 * Menambahkan menu Admin Podcast.
 */
function wartaradar_core_admin_podcast_menu() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        return;
    }

    add_menu_page(
        'WartaRadar Podcast',
        'WartaRadar',
        'wartaradar_manage_podcasts',
        'wartaradar',
        'wartaradar_core_admin_podcast_page',
        'dashicons-microphone',
        4
    );

    add_submenu_page(
        'wartaradar',
        'Semua Podcast',
        'Podcast',
        'wartaradar_manage_podcasts',
        'wr-podcast',
        'wartaradar_core_admin_podcast_page'
    );

    add_submenu_page(
        'wartaradar',
        'Tambah Podcast',
        'Tambah Podcast',
        'wartaradar_manage_podcasts',
        'post-new.php?post_type=post&podcast=1'
    );

    add_submenu_page(
        'wartaradar',
        'Serial Podcast',
        'Serial Podcast',
        'wartaradar_manage_podcasts',
        'wr-podcast-series',
        'wartaradar_core_admin_podcast_series_page'
    );
}

add_action(
    'admin_menu',
    'wartaradar_core_admin_podcast_menu',
    20
);

/**
 * Memastikan menu parent WartaRadar tidak mengganggu
 * halaman Podcast ketika submenu dibuka.
 */
function wartaradar_core_admin_podcast_parent_redirect() {

    if ( ! is_admin() ) {
        return;
    }

    if ( ! isset( $_GET['page'] ) ) {
        return;
    }

    if ( 'wartaradar' !== sanitize_key( wp_unslash( $_GET['page'] ) ) ) {
        return;
    }

    if ( ! wartaradar_core_can_manage_podcast() ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk mengelola Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }
}


/**
 * Halaman Semua Podcast.
 */
function wartaradar_core_admin_podcast_page() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk mengelola Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }

    $paged = isset( $_GET['paged'] )
        ? max(
            1,
            absint(
                $_GET['paged']
            )
        )
        : 1;

    $search = isset( $_GET['s'] )
        ? sanitize_text_field(
            wp_unslash( $_GET['s'] )
        )
        : '';

    $series_search = isset( $_GET['podcast_series'] )
        ? sanitize_text_field(
            wp_unslash( $_GET['podcast_series'] )
        )
        : '';

    $workflow_status = isset( $_GET['podcast_status'] )
        ? sanitize_key(
            wp_unslash( $_GET['podcast_status'] )
        )
        : '';

    $meta_query = array(
        array(
            'key'     => WARTARADAR_META_IS_PODCAST,
            'value'   => '1',
            'compare' => '=',
        ),
    );

    if ( '' !== $series_search ) {
        $meta_query[] = array(
            'key'     => WARTARADAR_META_PODCAST_SERIES_TITLE,
            'value'   => $series_search,
            'compare' => 'LIKE',
        );
    }

    if (
        '' !== $workflow_status
        && wartaradar_core_is_valid_workflow_status( $workflow_status )
    ) {
        $meta_query[] = array(
            'key'     => '_wartaradar_workflow_status',
            'value'   => $workflow_status,
            'compare' => '=',
        );
    }

    $query_args = array(
        'post_type'              => 'post',
        'post_status'            => array(
            'publish',
            'draft',
            'pending',
            'future',
            'private',
        ),
        'posts_per_page'         => 20,
        'paged'                  => $paged,
        'orderby'                => 'date',
        'order'                  => 'DESC',
        's'                      => $search,
        'meta_query'             => $meta_query,
        'ignore_sticky_posts'    => true,
        'no_found_rows'          => false,
        'update_post_meta_cache' => true,
        'update_post_term_cache' => false,
    );

    $query = new WP_Query( $query_args );

    $base_url = admin_url( 'admin.php?page=wr-podcast' );
    ?>

    <div class="wrap">

        <h1 class="wp-heading-inline">
            Podcast
        </h1>

        <a
            href="<?php echo esc_url( admin_url( 'post-new.php?post_type=post&podcast=1' ) ); ?>"
            class="page-title-action"
        >
            Tambah Podcast
        </a>

        <hr class="wp-header-end">

        <form
            method="get"
            action="<?php echo esc_url( admin_url( 'admin.php' ) ); ?>"
            style="margin:16px 0;"
        >

            <input
                type="hidden"
                name="page"
                value="wr-podcast"
            >

            <p class="search-box" style="float:none;">

                <label
                    class="screen-reader-text"
                    for="wr-podcast-search"
                >
                    Cari Podcast
                </label>

                <input
                    type="search"
                    id="wr-podcast-search"
                    name="s"
                    value="<?php echo esc_attr( $search ); ?>"
                    placeholder="Cari judul Podcast"
                >

                <input
                    type="search"
                    name="podcast_series"
                    value="<?php echo esc_attr( $series_search ); ?>"
                    placeholder="Cari serial"
                    style="margin-left:4px;"
                >

                <select
                    name="podcast_status"
                    style="margin-left:4px;"
                >
                    <option value="">
                        Semua Status
                    </option>

                    <option
                        value="<?php echo esc_attr( WARTARADAR_STATUS_DRAFT ); ?>"
                        <?php selected( $workflow_status, WARTARADAR_STATUS_DRAFT ); ?>
                    >
                        Draft
                    </option>

                    <option
                        value="<?php echo esc_attr( WARTARADAR_STATUS_REVIEW ); ?>"
                        <?php selected( $workflow_status, WARTARADAR_STATUS_REVIEW ); ?>
                    >
                        Menunggu Review
                    </option>

                    <option
                        value="<?php echo esc_attr( WARTARADAR_STATUS_REVISION ); ?>"
                        <?php selected( $workflow_status, WARTARADAR_STATUS_REVISION ); ?>
                    >
                        Perlu Revisi
                    </option>

                    <option
                        value="<?php echo esc_attr( WARTARADAR_STATUS_PUBLISHED ); ?>"
                        <?php selected( $workflow_status, WARTARADAR_STATUS_PUBLISHED ); ?>
                    >
                        Terbit
                    </option>

                </select>

                <button
                    type="submit"
                    class="button"
                    style="margin-left:4px;"
                >
                    Filter
                </button>

            </p>

        </form>

        <div class="tablenav top">

            <div class="tablenav-pages">

                <span class="displaying-num">
                    <?php
                    echo esc_html(
                        sprintf(
                            '%s Podcast',
                            number_format_i18n(
                                $query->found_posts
                            )
                        )
                    );
                    ?>
                </span>

            </div>

            <br class="clear">

        </div>

        <table class="wp-list-table widefat fixed striped">

            <thead>
                <tr>
                    <th scope="col" style="width:90px;">
                        Episode
                    </th>

                    <th scope="col">
                        Judul
                    </th>

                    <th scope="col" style="width:220px;">
                        Serial
                    </th>

                    <th scope="col" style="width:150px;">
                        Status
                    </th>

                    <th scope="col" style="width:160px;">
                        Tanggal
                    </th>

                    <th scope="col" style="width:190px;">
                        Aksi
                    </th>
                </tr>
            </thead>

            <tbody>

                <?php if ( $query->have_posts() ) : ?>

                    <?php while ( $query->have_posts() ) : ?>

                        <?php
                        $query->the_post();

                        $post_id = get_the_ID();

                        $episode = wartaradar_core_get_podcast_episode(
                            $post_id
                        );

                        $series_title = wartaradar_core_get_podcast_series_title(
                            $post_id
                        );

                        $status = wartaradar_core_get_workflow_status(
                            $post_id
                        );

                        $status_label =
                            wartaradar_core_get_podcast_workflow_status_label(
                                $status
                            );

                        $edit_link = get_edit_post_link(
                            $post_id
                        );

                        $view_link = get_permalink(
                            $post_id
                        );
                        ?>

                        <tr>

                            <td>
                                <strong>
                                    <?php
                                    echo esc_html(
                                        $episode > 0
                                            ? sprintf(
                                                'Episode %02d',
                                                $episode
                                            )
                                            : 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â'
                                    );
                                    ?>
                                </strong>
                            </td>

                            <td>

                                <strong>
                                    <a href="<?php echo esc_url( $edit_link ); ?>">
                                        <?php
                                        echo esc_html(
                                            get_the_title()
                                                ? get_the_title()
                                                : '(Tanpa Judul)'
                                        );
                                        ?>
                                    </a>
                                </strong>

                                <div class="row-actions">

                                    <span class="edit">
                                        <a href="<?php echo esc_url( $edit_link ); ?>">
                                            Edit
                                        </a>
                                    </span>

                                    <?php if ( $view_link ) : ?>

                                        <span class="view">
                                            |
                                            <a
                                                href="<?php echo esc_url( $view_link ); ?>"
                                                target="_blank"
                                                rel="noopener"
                                            >
                                                Lihat
                                            </a>
                                        </span>

                                    <?php endif; ?>

                                </div>

                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    '' !== $series_title
                                        ? $series_title
                                        : 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â'
                                );
                                ?>
                            </td>

                            <td>
                                <strong>
                                    <?php echo esc_html( $status_label ); ?>
                                </strong>
                            </td>

                            <td>
                                <?php
                                echo esc_html(
                                    get_the_date(
                                        'd M Y H:i',
                                        $post_id
                                    )
                                );
                                ?>
                            </td>

                            <td>

                                <?php if (
                                    wartaradar_core_can_publish_podcast()
                                    && current_user_can(
                                        'publish_post',
                                        $post_id
                                    )
                                    && 'published' !== $status
                                ) : ?>

                                    <?php
                                    $publish_url = wp_nonce_url(
                                        admin_url(
                                            'admin-post.php?action=wartaradar_publish_podcast&post_id='
                                            . $post_id
                                        ),
                                        'wartaradar_publish_podcast_' . $post_id
                                    );
                                    ?>

                                    <a
                                        href="<?php echo esc_url( $publish_url ); ?>"
                                        class="button button-small"
                                    >
                                        Terbitkan
                                    </a>

                                <?php endif; ?>

                                <?php if (
                                    current_user_can(
                                        'delete_post',
                                        $post_id
                                    )
                                ) : ?>

                                    <?php
                                    $delete_url = wp_nonce_url(
                                        admin_url(
                                            'admin-post.php?action=wartaradar_delete_podcast&post_id='
                                            . $post_id
                                        ),
                                        'wartaradar_delete_podcast_' . $post_id
                                    );
                                    ?>

                                    <a
                                        href="<?php echo esc_url( $delete_url ); ?>"
                                        class="button button-small"
                                        style="color:#b32d2e;"
                                        onclick="return confirm('Hapus Podcast ini? Tindakan ini tidak dapat dibatalkan.');"
                                    >
                                        Hapus
                                    </a>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endwhile; ?>

                <?php else : ?>

                    <tr>

                        <td
                            colspan="6"
                            style="padding:30px;text-align:center;"
                        >
                            Belum ada Podcast.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

        <?php wp_reset_postdata(); ?>

        <?php

        $pagination = paginate_links(
            array(
                'base'      => add_query_arg(
                    'paged',
                    '%#%',
                    $base_url
                ),
                'format'    => '',
                'current'   => $paged,
                'total'     => max(
                    1,
                    (int) $query->max_num_pages
                ),
                'prev_text' => '&laquo;',
                'next_text' => '&raquo;',
                'type'      => 'plain',
                'add_args'  => array_filter(
                    array(
                        's'              => $search,
                        'podcast_series' => $series_search,
                        'podcast_status' => $workflow_status,
                    )
                ),
            )
        );

        if ( $pagination ) :
            ?>

            <div class="tablenav bottom">

                <div class="tablenav-pages">
                    <?php echo wp_kses_post( $pagination ); ?>
                </div>

                <br class="clear">

            </div>

        <?php endif; ?>

    </div>

    <?php
}

/**
 * Halaman Serial Podcast.
 */
function wartaradar_core_admin_podcast_series_page() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk mengelola Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }

    $series_list =
        wartaradar_core_get_podcast_series_list();
    ?>

    <div class="wrap">

        <h1>
            Serial Podcast
        </h1>

        <p>
            Daftar serial Podcast yang dikelola oleh WartaRadar Core.
        </p>

        <table class="wp-list-table widefat fixed striped">

            <thead>

                <tr>

                    <th style="width:90px;">
                        No.
                    </th>

                    <th>
                        Nama Serial
                    </th>

                    <th style="width:180px;">
                        Episode Terakhir
                    </th>

                    <th style="width:120px;">
                        Aksi
                    </th>

                </tr>

            </thead>

            <tbody>

                <?php if ( ! empty( $series_list ) ) : ?>

                    <?php foreach ( $series_list as $index => $series ) : ?>

                        <?php
                        $max_episode =
                            wartaradar_core_get_podcast_max_episode(
                                $series['id']
                            );

                        $series_url = add_query_arg(
                            array(
                                'page'           => 'wr-podcast',
                                'podcast_series' => $series['title'],
                            ),
                            admin_url( 'admin.php' )
                        );
                        ?>

                        <tr>

                            <td>
                                <?php
                                echo esc_html(
                                    $index + 1
                                );
                                ?>
                            </td>

                            <td>

                                <strong>
                                    <?php
                                    echo esc_html(
                                        $series['title']
                                    );
                                    ?>
                                </strong>

                                <div
                                    style="
                                        margin-top:4px;
                                        color:#646970;
                                        font-size:12px;
                                    "
                                >
                                    <?php
                                    echo esc_html(
                                        $series['id']
                                    );
                                    ?>
                                </div>

                            </td>

                            <td>

                                <?php
                                echo esc_html(
                                    $max_episode > 0
                                        ? sprintf(
                                            'Episode %02d',
                                            $max_episode
                                        )
                                        : 'ÃƒÂ¢Ã¢â€šÂ¬Ã¢â‚¬Â'
                                );
                                ?>

                            </td>

                            <td>

                                <a
                                    href="<?php echo esc_url( $series_url ); ?>"
                                    class="button button-small"
                                >
                                    Lihat Episode
                                </a>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else : ?>

                    <tr>

                        <td
                            colspan="4"
                            style="padding:30px;text-align:center;"
                        >
                            Belum ada serial Podcast.
                        </td>

                    </tr>

                <?php endif; ?>

            </tbody>

        </table>

    </div>

    <?php
}

/**
 * Handler menerbitkan Podcast.
 */
function wartaradar_core_admin_publish_podcast() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk mengelola Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }

    $post_id = isset( $_GET['post_id'] )
        ? absint( $_GET['post_id'] )
        : 0;

    if ( ! $post_id ) {
        wp_die(
            esc_html__( 'Podcast tidak valid.', 'wartaradar-core' ),
            esc_html__( 'Permintaan Tidak Valid', 'wartaradar-core' ),
            array(
                'response' => 400,
            )
        );
    }

    check_admin_referer(
        'wartaradar_publish_podcast_' . $post_id
    );

    if ( ! wartaradar_core_is_podcast( $post_id ) ) {
        wp_die(
            esc_html__( 'Jurnal tersebut bukan Podcast.', 'wartaradar-core' ),
            esc_html__( 'Permintaan Tidak Valid', 'wartaradar-core' ),
            array(
                'response' => 400,
            )
        );
    }

    if (
        ! wartaradar_core_can_publish_podcast()
        || ! current_user_can(
            'publish_post',
            $post_id
        )
    ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk menerbitkan Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }

    wp_update_post(
        array(
            'ID'          => $post_id,
            'post_status' => 'publish',
        )
    );

    wartaradar_core_set_workflow_status(
        $post_id,
        WARTARADAR_STATUS_PUBLISHED
    );

    wartaradar_core_set_published_by(
        $post_id,
        get_current_user_id()
    );

    wp_safe_redirect(
        admin_url(
            'admin.php?page=wr-podcast'
        )
    );

    exit;
}

add_action(
    'admin_post_wartaradar_publish_podcast',
    'wartaradar_core_admin_publish_podcast'
);

/**
 * Handler menghapus Podcast.
 *
 * Delete tetap mengikuti capability native WordPress.
 * Dengan demikian Editor dan Podcaster tidak memperoleh
 * hak hapus hanya karena memiliki akses Podcast.
 */
function wartaradar_core_admin_delete_podcast() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk mengelola Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }

    $post_id = isset( $_GET['post_id'] )
        ? absint( $_GET['post_id'] )
        : 0;

    if ( ! $post_id ) {
        wp_die(
            esc_html__( 'Podcast tidak valid.', 'wartaradar-core' ),
            esc_html__( 'Permintaan Tidak Valid', 'wartaradar-core' ),
            array(
                'response' => 400,
            )
        );
    }

    check_admin_referer(
        'wartaradar_delete_podcast_' . $post_id
    );

    if ( ! wartaradar_core_is_podcast( $post_id ) ) {
        wp_die(
            esc_html__( 'Jurnal tersebut bukan Podcast.', 'wartaradar-core' ),
            esc_html__( 'Permintaan Tidak Valid', 'wartaradar-core' ),
            array(
                'response' => 400,
            )
        );
    }

    if ( ! current_user_can( 'delete_post', $post_id ) ) {
        wp_die(
            esc_html__( 'Anda tidak memiliki izin untuk menghapus Podcast.', 'wartaradar-core' ),
            esc_html__( 'Akses Ditolak', 'wartaradar-core' ),
            array(
                'response' => 403,
            )
        );
    }

    wp_delete_post(
        $post_id,
        false
    );

    wp_safe_redirect(
        admin_url(
            'admin.php?page=wr-podcast'
        )
    );

    exit;
}

add_action(
    'admin_post_wartaradar_delete_podcast',
    'wartaradar_core_admin_delete_podcast'
);

/**
 * Menandai checkbox Podcast otomatis ketika pengguna
 * masuk melalui menu "Tambah Podcast".
 */
function wartaradar_core_admin_podcast_new_post_script() {

    if ( ! wartaradar_core_can_manage_podcast() ) {
        return;
    }

    if ( ! isset( $_GET['podcast'] ) ) {
        return;
    }

    if (
        '1' !== sanitize_text_field(
            wp_unslash( $_GET['podcast'] )
        )
    ) {
        return;
    }
    ?>

    <script>
    document.addEventListener('DOMContentLoaded', function () {

        var checkbox = document.getElementById(
            'wartaradar-is-podcast'
        );

        if ( checkbox ) {
            checkbox.checked = true;
            checkbox.dispatchEvent(
                new Event('change')
            );
        }

    });
    </script>

    <?php
}

add_action(
    'admin_head-post-new.php',
    'wartaradar_core_admin_podcast_new_post_script'
);

/* =========================================================
 * INITIAL WORKFLOW
 * ========================================================= */

/**
 * Membuat status workflow awal untuk artikel.
 *
 * Hanya bekerja pada post utama,
 * bukan revision atau autosave.
 */
function wartaradar_core_initialize_post_workflow( $post_id ) {

    if ( wp_is_post_revision( $post_id ) ) {
        return;
    }

    if ( wp_is_post_autosave( $post_id ) ) {
        return;
    }

    if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
    }

    if ( 'post' !== get_post_type( $post_id ) ) {
        return;
    }

    $existing_status = get_post_meta(
        $post_id,
        '_wartaradar_workflow_status',
        true
    );

    if ( '' !== $existing_status ) {
        return;
    }

    add_post_meta(
        $post_id,
        '_wartaradar_workflow_status',
        WARTARADAR_STATUS_DRAFT,
        true
    );
}

add_action(
    'save_post',
    'wartaradar_core_initialize_post_workflow',
    20
);

/* =========================================================
 * END WARTARADAR CORE
 * ========================================================= */







