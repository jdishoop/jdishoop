<?php
/**
 * WartaRadar Manajemen Jurnal
 * Fungsi Pendukung UI
 *
 * 19.3T-11C
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * CEK ROLE
 * ========================================================= */

function wrmj_current_user_is_jurnalis() {

    if ( function_exists( 'wartaradar_core_is_jurnalis' ) ) {
        return wartaradar_core_is_jurnalis();
    }

    return false;
}


function wrmj_current_user_is_editor() {

    if ( function_exists( 'wartaradar_core_is_editor' ) ) {
        return wartaradar_core_is_editor();
    }

    return false;
}


function wrmj_current_user_is_admin() {

    return current_user_can( 'manage_options' );
}


/* =========================================================
 * WORKFLOW STATUS
 *
 * SEMUA STATUS DIAMBIL DARI CORE
 * ========================================================= */

function wrmj_get_post_workflow_status( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    if ( function_exists( 'wartaradar_core_get_workflow_status' ) ) {
        return wartaradar_core_get_workflow_status( $post_id );
    }

    return '';
}


/* =========================================================
 * LABEL STATUS
 * ========================================================= */

function wrmj_get_status_label( $status ) {

    $labels = array(
        'draft'          => 'Draft',
        'menunggu_review' => 'Menunggu Review',
        'perlu_revisi'   => 'Perlu Revisi',
        'published'      => 'Jurnal Terbit',
    );

    return isset( $labels[ $status ] )
        ? $labels[ $status ]
        : 'Belum Ditentukan';
}


/* =========================================================
 * CLASS STATUS
 * ========================================================= */

function wrmj_get_status_class( $status ) {

    $classes = array(
        'draft'           => 'wrmj-status-draft',
        'menunggu_review' => 'wrmj-status-review',
        'perlu_revisi'    => 'wrmj-status-revisi',
        'published'       => 'wrmj-status-published',
    );

    return isset( $classes[ $status ] )
        ? $classes[ $status ]
        : 'wrmj-status-unknown';
}


/* =========================================================
 * URL DASHBOARD
 * ========================================================= */

function wrmj_dashboard_url( $area = 'jurnalis' ) {

    switch ( $area ) {

        case 'editor':
            return admin_url(
                'admin.php?page=wrmj-editor-dashboard'
            );

        case 'admin':
            return admin_url(
                'admin.php?page=wrmj-admin-dashboard'
            );

        case 'jurnalis':
        default:
            return admin_url(
                'admin.php?page=wrmj-jurnalis-dashboard'
            );
    }
}


/* =========================================================
 * URL FILTER JURNALIS
 * ========================================================= */

function wrmj_jurnalis_url( $page = 'wrmj-jurnalis-dashboard' ) {

    $allowed_pages = array(
        'wrmj-jurnalis-dashboard',
        'wrmj-jurnalis-draft',
        'wrmj-jurnalis-review',
        'wrmj-jurnalis-revision',
        'wrmj-jurnalis-published',
    );

    if ( ! in_array( $page, $allowed_pages, true ) ) {
        $page = 'wrmj-jurnalis-dashboard';
    }

    return admin_url(
        'admin.php?page=' . $page
    );
}


/* =========================================================
 * URL FILTER EDITOR
 * ========================================================= */

function wrmj_editor_url( $filter = 'semua' ) {

    $base = wrmj_dashboard_url( 'editor' );

    $filters = array(
        'semua'   => $base,
        'review'  => admin_url(
            'admin.php?page=wrmj-editor-review'
        ),
        'revisi'  => admin_url(
            'admin.php?page=wrmj-editor-revision'
        ),
        'terbit'  => admin_url(
            'admin.php?page=wrmj-editor-published'
        ),
    );

    return isset( $filters[ $filter ] )
        ? $filters[ $filter ]
        : $base;
}


/* =========================================================
 * URL FILTER ADMINISTRATOR
 * ========================================================= */

function wrmj_admin_url( $section = 'semua' ) {

    $sections = array(

        'semua' => 'wrmj-admin-dashboard',

        'jurnalis' => 'wrmj-admin-jurnalis',

        'editor' => 'wrmj-admin-editor',

        'kategori' => 'wrmj-admin-kategori',

        'statistik' => 'wrmj-admin-statistik',

        'pengaturan' => 'wrmj-admin-pengaturan',

        'wrmj-admin-dashboard' => 'wrmj-admin-dashboard',

        'wrmj-admin-jurnalis' => 'wrmj-admin-jurnalis',

        'wrmj-admin-editor' => 'wrmj-admin-editor',

        'wrmj-admin-kategori' => 'wrmj-admin-kategori',

        'wrmj-admin-statistik' => 'wrmj-admin-statistik',

        'wrmj-admin-pengaturan' => 'wrmj-admin-pengaturan',

    );


    $page = isset( $sections[ $section ] )
        ? $sections[ $section ]
        : 'wrmj-admin-dashboard';


    return admin_url(
        'admin.php?page=' . $page
    );
}


/* =========================================================
 * QUERY JURNAL
 *
 * Query membaca metadata workflow dari CORE.
 * ========================================================= */

function wrmj_get_journal_query_args( $status = '', $author_id = 0 ) {

    $args = array(
        'post_type'           => 'post',
        'post_status'         => array(
            'publish',
            'draft',
            'pending',
            'future',
            'private',
        ),
        'posts_per_page'      => 20,
        'orderby'             => 'date',
        'order'               => 'DESC',
        'ignore_sticky_posts' => true,
    );


    if ( $author_id ) {

        $args['author'] = absint( $author_id );
    }


    if ( $status ) {

        $args['meta_query'] = array(
            array(
                'key'     => '_wartaradar_workflow_status',
                'value'   => sanitize_key( $status ),
                'compare' => '=',
            ),
        );
    }

    return $args;
}


/* =========================================================
 * FORMAT TANGGAL
 * ========================================================= */

function wrmj_format_date( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    return wp_date(
        'd M Y H:i',
        get_post_timestamp( $post_id ),
        wp_timezone()
    );
}


/* =========================================================
 * NAMA PENULIS
 * ========================================================= */

function wrmj_get_author_name( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '-';
    }

    $author_id = (int) get_post_field(
        'post_author',
        $post_id
    );

    if ( ! $author_id ) {
        return '-';
    }

    $name = get_the_author_meta(
        'display_name',
        $author_id
    );

    return $name ? $name : '-';
}


/* =========================================================
 * EDITOR YANG DITUGASKAN
 * DATA DARI CORE
 * ========================================================= */

function wrmj_get_editor_name( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '-';
    }

    if ( ! function_exists( 'wartaradar_core_get_editor_id' ) ) {
        return '-';
    }

    $editor_id = (int) wartaradar_core_get_editor_id(
        $post_id
    );

    if ( ! $editor_id ) {
        return '-';
    }

    $name = get_the_author_meta(
        'display_name',
        $editor_id
    );

    return $name ? $name : '-';
}


/* =========================================================
 * EDITOR YANG MENERBITKAN
 * DATA DARI CORE
 * ========================================================= */

function wrmj_get_published_by_name( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '-';
    }

    if ( ! function_exists( 'wartaradar_core_get_published_by' ) ) {
        return '-';
    }

    $user_id = (int) wartaradar_core_get_published_by(
        $post_id
    );

    if ( ! $user_id ) {
        return '-';
    }

    $name = get_the_author_meta(
        'display_name',
        $user_id
    );

    return $name ? $name : '-';
}


/* =========================================================
 * CATATAN REVIEW
 * DATA DARI CORE
 * ========================================================= */

function wrmj_get_review_note( $post_id ) {

    $post_id = absint( $post_id );

    if ( ! $post_id ) {
        return '';
    }

    if ( ! function_exists( 'wartaradar_core_get_review_note' ) ) {
        return '';
    }

    return (string) wartaradar_core_get_review_note(
        $post_id
    );
}


/* =========================================================
 * JUMLAH JURNAL BERDASARKAN STATUS
 * ========================================================= */

function wrmj_count_journals( $status = '', $author_id = 0 ) {

    $args = wrmj_get_journal_query_args(
        $status,
        $author_id
    );

    $args['posts_per_page'] = 1;
    $args['fields']         = 'ids';

    $query = new WP_Query( $args );

    $count = (int) $query->found_posts;

    wp_reset_postdata();

    return $count;
}


/* =========================================================
 * ESCAPE JUDUL JURNAL
 * ========================================================= */

function wrmj_journal_title( $post_id ) {

    $title = get_the_title( $post_id );

    return $title
        ? wp_strip_all_tags( $title )
        : '(Tanpa Judul)';
}


/* =========================================================
 * END
 * ========================================================= */




