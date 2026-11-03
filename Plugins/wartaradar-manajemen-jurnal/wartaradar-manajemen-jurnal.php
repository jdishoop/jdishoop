<?php
/**
 * Plugin Name: WartaRadar Manajemen Jurnal
 * Plugin URI: https://wartaradar.com
 * Description: Dashboard manajemen jurnal untuk Jurnalis, Editor Berita, dan Administrator WartaRadar.
 * Version: 1.0.0
 * Author: WartaRadar
 * Text Domain: wartaradar-manajemen-jurnal
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * KONSTANTA
 * ========================================================= */

define( 'WRMJ_VERSION', '1.0.0' );
define( 'WRMJ_PATH', plugin_dir_path( __FILE__ ) );
define( 'WRMJ_URL', plugin_dir_url( __FILE__ ) );


/* =========================================================
 * LOAD FILE
 * ========================================================= */

require_once WRMJ_PATH . 'Includes/wr-functions.php';
require_once WRMJ_PATH . 'Includes/wr-editorial.php';


/* =========================================================
 * ROLE HELPER
 * ========================================================= */
function wrmj_user_is_jurnalis() {

    $user = wp_get_current_user();

    return (
        $user instanceof WP_User
        && in_array( 'jurnalis', (array) $user->roles, true )
    );
}


function wrmj_user_is_editor() {

    $user = wp_get_current_user();

    return (
        $user instanceof WP_User
        && in_array( 'editor_berita', (array) $user->roles, true )
    );
}


function wrmj_user_is_admin() {

    return current_user_can( 'manage_options' );
}
/* =========================================================
 * ACTION JURNALIS
 * ========================================================= */
function wrmj_handle_jurnalis_action() {

    if ( ! is_user_logged_in() ) {
        wp_die(
            'Anda harus login untuk melakukan tindakan ini.'
        );
    }

    if (
        ! wrmj_user_is_jurnalis()
        && ! wrmj_user_is_admin()
    ) {
        wp_die(
            'Anda tidak memiliki izin.'
        );
    }

    $post_id = isset( $_GET['post_id'] )
        ? absint( $_GET['post_id'] )
        : 0;

    $action = isset( $_GET['wrmj_action'] )
        ? sanitize_key( $_GET['wrmj_action'] )
        : '';

    if ( ! $post_id ) {
        wp_safe_redirect(
            wrmj_jurnalis_url()
        );
        exit;
    }

    if ( $action !== 'kirim_review' ) {
        wp_safe_redirect(
            wrmj_jurnalis_url()
        );
        exit;
    }

    check_admin_referer(
        'wrmj_kirim_review_' . $post_id,
        'wrmj_nonce'
    );

    $post = get_post( $post_id );

    if ( ! $post || $post->post_type !== 'post' ) {
        wp_safe_redirect(
            wrmj_jurnalis_url()
        );
        exit;
    }

    if (
        wrmj_user_is_jurnalis()
        && absint( $post->post_author ) !== get_current_user_id()
    ) {
        wp_die(
            'Jurnal tersebut bukan milik Anda.'
        );
    }

    $workflow_status = wrmj_get_post_workflow_status(
        $post_id
    );

    if (
        $workflow_status !== 'draft'
        && $workflow_status !== 'perlu_revisi'
    ) {
        wp_safe_redirect(
            wrmj_jurnalis_url()
        );
        exit;
    }

    $updated = false;

    if ( function_exists( 'wartaradar_core_set_workflow_status' ) ) {

        $updated = wartaradar_core_set_workflow_status(
            $post_id,
            'menunggu_review'
        );

    } else {

        $updated = update_post_meta(
            $post_id,
            '_wartaradar_workflow_status',
            'menunggu_review'
        );

    }

    if ( ! $updated ) {
        wp_die(
            'Status jurnal gagal diubah menjadi Menunggu Review.'
        );
    }

    wp_safe_redirect(
        add_query_arg(
            'wrmj_sent',
            '1',
            wrmj_jurnalis_url()
        )
    );

    exit;
}

add_action(
    'admin_post_wrmj_jurnalis_action',
    'wrmj_handle_jurnalis_action'
);

add_action(
    'admin_post_nopriv_wrmj_jurnalis_action',
    'wrmj_handle_jurnalis_action'
);


/* =========================================================
 * ACTION EDITOR
 * ========================================================= */

function wrmj_handle_editor_action() {

    if ( ! is_user_logged_in() ) {
        wp_die(
            'Anda harus login untuk melakukan tindakan ini.'
        );
    }

    if (
        ! wrmj_user_is_editor()
        && ! wrmj_user_is_admin()
    ) {
        wp_die(
            'Anda tidak memiliki izin.'
        );
    }

    $post_id = isset( $_POST['post_id'] )
        ? absint( $_POST['post_id'] )
        : 0;

    $action = isset( $_POST['wrmj_action'] )
        ? sanitize_key( $_POST['wrmj_action'] )
        : '';

    if ( ! $post_id ) {
        wp_safe_redirect(
            wrmj_editor_url()
        );
        exit;
    }

    check_admin_referer(
        'wrmj_editor_action_' . $post_id
    );

    $post = get_post( $post_id );

    if ( ! $post || $post->post_type !== 'post' ) {
        wp_safe_redirect(
            wrmj_editor_url()
        );
        exit;
    }

    $workflow_status =
        wrmj_get_post_workflow_status(
            $post_id
        );


    /* -----------------------------------------------------
     * PERLU REVISI
     * ----------------------------------------------------- */

    if (
        $action === 'revisi'
        && $workflow_status === 'menunggu_review'
    ) {

        $note = isset( $_POST['review_note'] )
            ? sanitize_textarea_field(
                wp_unslash(
                    $_POST['review_note']
                )
            )
            : '';

        if ( function_exists( 'wartaradar_core_set_review_note' ) ) {

            wartaradar_core_set_review_note(
                $post_id,
                $note
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_review_note',
                $note
            );

        }


        if ( function_exists( 'wartaradar_core_set_workflow_status' ) ) {

            wartaradar_core_set_workflow_status(
                $post_id,
                'perlu_revisi'
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_workflow_status',
                'perlu_revisi'
            );

        }


        if ( function_exists( 'wartaradar_core_set_editor_id' ) ) {

            wartaradar_core_set_editor_id(
                $post_id,
                get_current_user_id()
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_editor_id',
                get_current_user_id()
            );

        }


        if ( function_exists( 'wartaradar_core_set_reviewed_at' ) ) {

            wartaradar_core_set_reviewed_at(
                $post_id,
                current_time( 'mysql' )
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_reviewed_at',
                current_time( 'mysql' )
            );

        }


        wp_safe_redirect(
            add_query_arg(
                'wrmj_revision',
                '1',
                wrmj_editor_url(
                    'wrmj-editor-revision'
                )
            )
        );

        exit;
    }


    /* -----------------------------------------------------
     * SETUJUI & TERBITKAN
     * ----------------------------------------------------- */

    if (
        $action === 'publish'
        && $workflow_status === 'menunggu_review'
    ) {

        $updated = wp_update_post(
            array(
                'ID'          => $post_id,
                'post_status' => 'publish',
            ),
            true
        );

        if ( is_wp_error( $updated ) ) {

            wp_safe_redirect(
                add_query_arg(
                    'wrmj_error',
                    '1',
                    wrmj_editor_url(
                        'wrmj-editor-review'
                    )
                )
            );

            exit;
        }


        if ( function_exists( 'wartaradar_core_set_workflow_status' ) ) {

            wartaradar_core_set_workflow_status(
                $post_id,
                'published'
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_workflow_status',
                'published'
            );

        }


        if ( function_exists( 'wartaradar_core_set_editor_id' ) ) {

            wartaradar_core_set_editor_id(
                $post_id,
                get_current_user_id()
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_editor_id',
                get_current_user_id()
            );

        }


        if ( function_exists( 'wartaradar_core_set_published_by' ) ) {

            wartaradar_core_set_published_by(
                $post_id,
                get_current_user_id()
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_published_by',
                get_current_user_id()
            );

        }


        if ( function_exists( 'wartaradar_core_set_reviewed_at' ) ) {

            wartaradar_core_set_reviewed_at(
                $post_id,
                current_time( 'mysql' )
            );

        } else {

            update_post_meta(
                $post_id,
                '_wartaradar_reviewed_at',
                current_time( 'mysql' )
            );

        }


        wp_safe_redirect(
            add_query_arg(
                'wrmj_published',
                '1',
                wrmj_editor_url(
                    'wrmj-editor-published'
                )
            )
        );

        exit;
    }


    wp_safe_redirect(
        wrmj_editor_url(
            'wrmj-editor-review'
        )
    );

    exit;
}

add_action(
    'admin_post_wrmj_editor_action',
    'wrmj_handle_editor_action'
);

add_action(
    'admin_post_nopriv_wrmj_editor_action',
    'wrmj_handle_editor_action'
);


/* =========================================================
 * DASHBOARD CALLBACK
 * ========================================================= */

function wrmj_render_jurnalis_dashboard() {

    require_once WRMJ_PATH . 'admin/wr-jurnalis-dashboard.php';

    if (
        function_exists(
            'wrmj_jurnalis_dashboard_page'
        )
    ) {

        wrmj_jurnalis_dashboard_page();

    }
}


function wrmj_render_editor_dashboard() {

    require_once WRMJ_PATH . 'admin/wr-editor-dashboard.php';

    if (
        function_exists(
            'wrmj_editor_dashboard_page'
        )
    ) {

        wrmj_editor_dashboard_page();

    }
}


function wrmj_render_admin_dashboard() {

    require_once WRMJ_PATH . 'admin/wr-admin-dashboard.php';

    if (
        function_exists(
            'wrmj_admin_dashboard_page'
        )
    ) {

        wrmj_admin_dashboard_page();

    }
}


/* =========================================================
 * CSS
 * ========================================================= */

function wrmj_enqueue_dashboard_css() {

    if ( ! is_admin() ) {
        return;
    }

    wp_enqueue_style(
        'wrmj-dashboard',
        WRMJ_URL . 'assets/wr-dashboard.css',
        array(),
        WRMJ_VERSION
    );
}

add_action(
    'admin_enqueue_scripts',
    'wrmj_enqueue_dashboard_css'
);


/* =========================================================
 * MENU
 * ========================================================= */

function wrmj_register_admin_menu() {

    if ( wrmj_user_is_admin() ) {

        add_menu_page(
            'WartaRadar Manajemen Jurnal',
            'Manajemen Jurnal',
            'manage_options',
            'wrmj-admin-dashboard',
            'wrmj_render_admin_dashboard',
            'dashicons-media-document',
            3
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Semua Jurnal',
            'Semua Jurnal',
            'manage_options',
            'wrmj-admin-dashboard',
            'wrmj_render_admin_dashboard'
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Semua Jurnalis',
            'Semua Jurnalis',
            'manage_options',
            'wrmj-admin-jurnalis',
            'wrmj_render_admin_dashboard'
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Editor',
            'Editor',
            'manage_options',
            'wrmj-admin-editor',
            'wrmj_render_admin_dashboard'
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Kategori',
            'Kategori',
            'manage_options',
            'wrmj-admin-kategori',
            'wrmj_render_admin_dashboard'
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Statistik',
            'Statistik',
            'manage_options',
            'wrmj-admin-statistik',
            'wrmj_render_admin_dashboard'
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Pengaturan',
            'Pengaturan',
            'manage_options',
            'wrmj-admin-pengaturan',
            'wrmj_render_admin_dashboard'
        );

        add_submenu_page(
            'wrmj-admin-dashboard',
            'Pengaturan Beranda',
            'Pengaturan Beranda',
            'manage_options',
            'wrmj-editorial-settings',
            'wrmj_render_editorial_settings_page'
        );

        // Halaman dibuka dari kartu Pengaturan agar hierarki menu Admin tetap rapi.

        return;
    }


    if ( wrmj_user_is_editor() ) {

        add_menu_page(
            'WartaRadar Manajemen Jurnal',
            'Manajemen Jurnal',
            'read',
            'wrmj-editor-dashboard',
            'wrmj_render_editor_dashboard',
            'dashicons-media-document',
            3
        );

        add_submenu_page(
            'wrmj-editor-dashboard',
            'Semua Jurnal',
            'Semua Jurnal',
            'read',
            'wrmj-editor-dashboard',
            'wrmj_render_editor_dashboard'
        );

        add_submenu_page(
            'wrmj-editor-dashboard',
            'Perlu Review',
            'Perlu Review',
            'read',
            'wrmj-editor-review',
            'wrmj_render_editor_dashboard'
        );

        add_submenu_page(
            'wrmj-editor-dashboard',
            'Perlu Revisi',
            'Perlu Revisi',
            'read',
            'wrmj-editor-revision',
            'wrmj_render_editor_dashboard'
        );

        add_submenu_page(
            'wrmj-editor-dashboard',
            'Jurnal Terbit',
            'Jurnal Terbit',
            'read',
            'wrmj-editor-published',
            'wrmj_render_editor_dashboard'
        );

        add_submenu_page(
            'wrmj-editor-dashboard',
            'Pengaturan Beranda',
            'Pengaturan Beranda',
            'read',
            'wrmj-editorial-settings',
            'wrmj_render_editorial_settings_page'
        );

        return;
    }


    if ( wrmj_user_is_jurnalis() ) {

        add_menu_page(
            'WartaRadar Manajemen Jurnal',
            'Manajemen Jurnal',
            'read',
            'wrmj-jurnalis-dashboard',
            'wrmj_render_jurnalis_dashboard',
            'dashicons-media-document',
            3
        );

        add_submenu_page(
            'wrmj-jurnalis-dashboard',
            'Jurnal Saya',
            'Jurnal Saya',
            'read',
            'wrmj-jurnalis-dashboard',
            'wrmj_render_jurnalis_dashboard'
        );

        add_submenu_page(
            'wrmj-jurnalis-dashboard',
            'Draft',
            'Draft',
            'read',
            'wrmj-jurnalis-draft',
            'wrmj_render_jurnalis_dashboard'
        );

        add_submenu_page(
            'wrmj-jurnalis-dashboard',
            'Menunggu Review',
            'Menunggu Review',
            'read',
            'wrmj-jurnalis-review',
            'wrmj_render_jurnalis_dashboard'
        );

        add_submenu_page(
            'wrmj-jurnalis-dashboard',
            'Perlu Revisi',
            'Perlu Revisi',
            'read',
            'wrmj-jurnalis-revision',
            'wrmj_render_jurnalis_dashboard'
        );

        add_submenu_page(
            'wrmj-jurnalis-dashboard',
            'Jurnal Terbit',
            'Jurnal Terbit',
            'read',
            'wrmj-jurnalis-published',
            'wrmj_render_jurnalis_dashboard'
        );
    }
}

add_action(
    'admin_menu',
    'wrmj_register_admin_menu',
    20
);


/* =========================================================
 * DEFAULT DASHBOARD REDIRECT
 * ========================================================= */

function wrmj_redirect_default_dashboard() {

    if ( ! is_admin() ) {
        return;
    }

    if (
        ! isset( $_SERVER['PHP_SELF'] )
        || strpos(
            wp_unslash(
                $_SERVER['PHP_SELF']
            ),
            'wp-admin/index.php'
        ) === false
    ) {
        return;
    }

    if ( wrmj_user_is_admin() ) {

        wp_safe_redirect(
            wrmj_admin_url()
        );

        exit;
    }

    if ( wrmj_user_is_editor() ) {

        wp_safe_redirect(
            wrmj_editor_url()
        );

        exit;
    }

    if ( wrmj_user_is_jurnalis() ) {

        wp_safe_redirect(
            wrmj_jurnalis_url()
        );

        exit;
    }
}

add_action(
    'admin_init',
    'wrmj_redirect_default_dashboard'
);


/* =========================================================
 * HIDE DEFAULT WORDPRESS DASHBOARD
 * ========================================================= */

function wrmj_hide_default_dashboard_for_newsroom_users() {

    if (
        wrmj_user_is_jurnalis()
        || wrmj_user_is_editor()
    ) {

        remove_menu_page(
            'index.php'
        );
    }
}

add_action(
    'admin_menu',
    'wrmj_hide_default_dashboard_for_newsroom_users',
    999
);
