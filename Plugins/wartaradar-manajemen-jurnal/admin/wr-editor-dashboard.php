<?php
/**
 * WartaRadar Manajemen Jurnal
 * Dashboard Editor Berita
 *
 * Tahap: 19.3T-11F-R4-C-7B
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}


/* =========================================================
 * HALAMAN DASHBOARD EDITOR
 * ========================================================= */

function wrmj_editor_dashboard_page() {

    if (
        ! wrmj_current_user_is_editor()
        && ! wrmj_current_user_is_admin()
    ) {
        wp_die(
            'Anda tidak memiliki izin untuk mengakses halaman ini.'
        );
    }


    /* =====================================================
     * PAGE AKTIF
     * ===================================================== */

    $current_page = isset( $_GET['page'] )
        ? sanitize_key(
            wp_unslash(
                $_GET['page']
            )
        )
        : 'wrmj-editor-dashboard';


    /* =====================================================
     * FILTER WORKFLOW
     * ===================================================== */

    $workflow_status = '';

    if (
        $current_page === 'wrmj-editor-review'
    ) {
        $workflow_status = 'menunggu_review';
    }

    if (
        $current_page === 'wrmj-editor-revision'
    ) {
        $workflow_status = 'perlu_revisi';
    }

    if (
        $current_page === 'wrmj-editor-published'
    ) {
        $workflow_status = 'published';
    }


    /* =====================================================
     * STATISTIK
     * ===================================================== */

    $total_all =
        wrmj_count_journals();

    $total_review =
        wrmj_count_journals(
            'menunggu_review'
        );

    $total_revision =
        wrmj_count_journals(
            'perlu_revisi'
        );

    $total_published =
        wrmj_count_journals(
            'published'
        );


    /* =====================================================
     * QUERY JURNAL
     * ===================================================== */

    $args =
        wrmj_get_journal_query_args(
            $workflow_status
        );

    $journal_query =
        new WP_Query(
            $args
        );

    ?>

    <div class="wrap wrmj-dashboard-wrap">

        <!-- =================================================
             HEADER
             ================================================= -->

        <div class="wrmj-dashboard-header">

            <div class="wrmj-dashboard-brand">

                <div class="wrmj-dashboard-kicker">
                    WARTARADAR NEWSROOM
                </div>

                <h1>
                    Manajemen Jurnal
                </h1>

                <p>
                    Pusat pemeriksaan, revisi, persetujuan,
                    dan pemantauan berita Jurnalis.
                </p>

            </div>

        </div>


        <!-- =================================================
             NOTIFIKASI
             ================================================= -->

        <?php if ( isset( $_GET['wrmj_revision'] ) ) : ?>

            <div class="notice notice-success is-dismissible">

                <p>
                    Jurnal berhasil dikembalikan kepada
                    Jurnalis untuk diperbaiki.
                </p>

            </div>

        <?php endif; ?>


        <?php if ( isset( $_GET['wrmj_published'] ) ) : ?>

            <div class="notice notice-success is-dismissible">

                <p>
                    Jurnal berhasil disetujui dan diterbitkan.
                </p>

            </div>

        <?php endif; ?>


        <?php if ( isset( $_GET['wrmj_error'] ) ) : ?>

            <div class="notice notice-error is-dismissible">

                <p>
                    Jurnal gagal diproses.
                    Silakan coba kembali.
                </p>

            </div>

        <?php endif; ?>


        <!-- =================================================
             KARTU STATISTIK
             ================================================= -->

        <div class="wrmj-stats-grid">


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-dashboard' )
                ); ?>"
                class="wrmj-stat-card wrmj-stat-card-link"
            >

                <span class="wrmj-stat-label">
                    Semua Jurnal
                </span>

                <strong class="wrmj-stat-number">
                    <?php
                    echo esc_html(
                        $total_all
                    );
                    ?>
                </strong>

            </a>


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-review' )
                ); ?>"
                class="wrmj-stat-card wrmj-stat-card-link"
            >

                <span class="wrmj-stat-label">
                    Perlu Review
                </span>

                <strong class="wrmj-stat-number">
                    <?php
                    echo esc_html(
                        $total_review
                    );
                    ?>
                </strong>

            </a>


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-revision' )
                ); ?>"
                class="wrmj-stat-card wrmj-stat-card-link"
            >

                <span class="wrmj-stat-label">
                    Perlu Revisi
                </span>

                <strong class="wrmj-stat-number">
                    <?php
                    echo esc_html(
                        $total_revision
                    );
                    ?>
                </strong>

            </a>


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-published' )
                ); ?>"
                class="wrmj-stat-card wrmj-stat-card-link"
            >

                <span class="wrmj-stat-label">
                    Jurnal Terbit
                </span>

                <strong class="wrmj-stat-number">
                    <?php
                    echo esc_html(
                        $total_published
                    );
                    ?>
                </strong>

            </a>

        </div>


        <!-- =================================================
             NAVIGASI STATUS
             ================================================= -->

        <nav class="wrmj-dashboard-nav">

            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-dashboard' )
                ); ?>"
                class="<?php
                echo $current_page === 'wrmj-editor-dashboard'
                    ? 'active'
                    : '';
                ?>"
            >
                Semua Jurnal
            </a>


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-review' )
                ); ?>"
                class="<?php
                echo $current_page === 'wrmj-editor-review'
                    ? 'active'
                    : '';
                ?>"
            >
                Perlu Review
            </a>


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-revision' )
                ); ?>"
                class="<?php
                echo $current_page === 'wrmj-editor-revision'
                    ? 'active'
                    : '';
                ?>"
            >
                Perlu Revisi
            </a>


            <a
                href="<?php echo esc_url(
                    admin_url( 'admin.php?page=wrmj-editor-published' )
                ); ?>"
                class="<?php
                echo $current_page === 'wrmj-editor-published'
                    ? 'active'
                    : '';
                ?>"
            >
                Jurnal Terbit
            </a>

        </nav>


        <!-- =================================================
             JUDUL BAGIAN
             ================================================= -->

        <div class="wrmj-section-header">

            <div>

                <?php if ( $workflow_status === 'menunggu_review' ) : ?>

                    <h2>
                        Jurnal Perlu Review
                    </h2>

                    <p>
                        Periksa berita Jurnalis sebelum
                        diterbitkan.
                    </p>

                <?php elseif ( $workflow_status === 'perlu_revisi' ) : ?>

                    <h2>
                        Jurnal Perlu Revisi
                    </h2>

                    <p>
                        Daftar jurnal yang dikembalikan
                        untuk diperbaiki.
                    </p>

                <?php elseif ( $workflow_status === 'published' ) : ?>

                    <h2>
                        Jurnal Terbit
                    </h2>

                    <p>
                        Daftar jurnal yang telah diterbitkan.
                    </p>

                <?php else : ?>

                    <h2>
                        Semua Jurnal
                    </h2>

                    <p>
                        Seluruh aktivitas jurnal WartaRadar.
                    </p>

                <?php endif; ?>

            </div>

        </div>


        <!-- =================================================
             TABEL JURNAL
             ================================================= -->

        <div class="wrmj-table-wrap">

            <?php if ( $journal_query->have_posts() ) : ?>

                <table class="widefat fixed striped">

                    <thead>

                        <tr>

                            <th>
                                Judul Jurnal
                            </th>

                            <th>
                                Jurnalis
                            </th>

                            <th>
                                Status
                            </th>

                            <th>
                                Tanggal
                            </th>

                            <th>
                                Aksi
                            </th>

                        </tr>

                    </thead>

                    <tbody>

                    <?php while ( $journal_query->have_posts() ) : ?>

                        <?php
                        $journal_query->the_post();

                        $post_id =
                            get_the_ID();

                        $post_workflow =
                            wrmj_get_post_workflow_status(
                                $post_id
                            );

                        $author_name =
                            wrmj_get_author_name(
                                $post_id
                            );

                        $status_labels = array('draft' => 'Draft', 'menunggu_review' => 'Menunggu Review', 'perlu_revisi' => 'Perlu Revisi', 'published' => 'Jurnal Terbit'); $status_label = isset($status_labels[$post_workflow]) ? $status_labels[$post_workflow] : 'Tidak Diketahui';

                        $status_classes = array('draft' => 'wrmj-status-draft', 'menunggu_review' => 'wrmj-status-review', 'perlu_revisi' => 'wrmj-status-revisi', 'published' => 'wrmj-status-published'); $status_class = isset($status_classes[$post_workflow]) ? $status_classes[$post_workflow] : 'wrmj-status-draft';

                        $edit_url =
                            get_edit_post_link(
                                $post_id
                            );
                        ?>

                        <tr>

                            <td>

                                <strong>

                                    <a
                                        href="<?php echo esc_url(
                                            get_permalink(
                                                $post_id
                                            )
                                        ); ?>"
                                        target="_blank"
                                    >

                                        <?php
                                        echo esc_html(
                                            get_the_title(
                                                $post_id
                                            )
                                        );
                                        ?>

                                    </a>

                                </strong>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    $author_name
                                );
                                ?>

                            </td>


                            <td>

                                <span
                                    class="wrmj-status-badge <?php echo esc_attr(
                                        $status_class
                                    ); ?>"
                                >
                                    <?php
                                    echo esc_html(
                                        $status_label
                                    );
                                    ?>
                                </span>

                            </td>


                            <td>

                                <?php
                                echo esc_html(
                                    get_the_date( 'd M Y H:i', $post_id )
                                );
                                ?>

                            </td>


                            <td>

                                <div class="wrmj-actions">

                                    <a
                                        href="<?php echo esc_url(
                                            get_permalink(
                                                $post_id
                                            )
                                        ); ?>"
                                        class="button"
                                        target="_blank"
                                    >
                                        Lihat
                                    </a>


                                    <?php if ( $edit_url ) : ?>

                                        <a
                                            href="<?php echo esc_url(
                                                $edit_url
                                            ); ?>"
                                            class="button"
                                        >
                                            Edit
                                        </a>

                                    <?php endif; ?>


                                    <?php if (
                                        $post_workflow ===
                                        'menunggu_review'
                                    ) : ?>

                                        <a
                                            href="<?php echo esc_url(
                                                admin_url( 'admin.php?page=wrmj-editor-review' )
                                            ); ?>"
                                            class="button button-primary"
                                        >
                                            Review
                                        </a>

                                    <?php endif; ?>

                                </div>

                            </td>

                        </tr>


                        <!-- =====================================
                             PANEL REVIEW
                             ===================================== -->

                        <?php if (
                            $post_workflow ===
                            'menunggu_review'
                            &&
                            $current_page ===
                            'wrmj-editor-review'
                        ) : ?>

                            <tr class="wrmj-review-row">

                                <td colspan="5">

                                    <div class="wrmj-review-panel">

                                        <div class="wrmj-review-header">

                                            <h3>
                                                Pemeriksaan Jurnal
                                            </h3>

                                            <p>
                                                <?php
                                                echo esc_html(
                                                    get_the_title(
                                                        $post_id
                                                    )
                                                );
                                                ?>
                                            </p>

                                        </div>


                                        <!-- =================================
                                             FORM PERLU REVISI
                                             ================================= -->

                                        <form
                                            method="post"
                                            action="<?php echo esc_url(
                                                admin_url(
                                                    'admin-post.php'
                                                )
                                            ); ?>"
                                            class="wrmj-review-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="wrmj_editor_action"
                                            >

                                            <input
                                                type="hidden"
                                                name="post_id"
                                                value="<?php echo esc_attr(
                                                    $post_id
                                                ); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="wrmj_action"
                                                value="revisi"
                                            >

                                            <?php

                                            wp_nonce_field(
                                                'wrmj_editor_action_' .
                                                $post_id
                                            );

                                            ?>


                                            <div class="wrmj-form-field">

                                                <label
                                                    for="review_note_<?php echo esc_attr(
                                                        $post_id
                                                    ); ?>"
                                                >
                                                    Catatan Editor
                                                </label>

                                                <textarea
                                                    id="review_note_<?php echo esc_attr(
                                                        $post_id
                                                    ); ?>"
                                                    name="review_note"
                                                    rows="5"
                                                    placeholder="Tuliskan catatan perbaikan untuk Jurnalis..."
                                                    required
                                                ></textarea>

                                            </div>


                                            <div class="wrmj-review-actions">

                                                <button
                                                    type="submit"
                                                    class="button"
                                                >
                                                    Perlu Revisi
                                                </button>

                                            </div>

                                        </form>


                                        <!-- =================================
                                             FORM SETUJUI & TERBITKAN
                                             ================================= -->

                                        <form
                                            method="post"
                                            action="<?php echo esc_url(
                                                admin_url(
                                                    'admin-post.php'
                                                )
                                            ); ?>"
                                            class="wrmj-review-form wrmj-publish-form"
                                        >

                                            <input
                                                type="hidden"
                                                name="action"
                                                value="wrmj_editor_action"
                                            >

                                            <input
                                                type="hidden"
                                                name="post_id"
                                                value="<?php echo esc_attr(
                                                    $post_id
                                                ); ?>"
                                            >

                                            <input
                                                type="hidden"
                                                name="wrmj_action"
                                                value="publish"
                                            >

                                            <?php

                                            wp_nonce_field(
                                                'wrmj_editor_action_' .
                                                $post_id
                                            );

                                            ?>


                                            <div class="wrmj-publish-confirm">

                                                <strong>
                                                    Persetujuan Penerbitan
                                                </strong>

                                                <p>
                                                    Setelah disetujui,
                                                    jurnal akan berstatus
                                                    <strong>
                                                        Jurnal Terbit
                                                    </strong>
                                                    dan dapat tampil
                                                    di website.
                                                </p>

                                            </div>


                                            <div class="wrmj-review-actions">

                                                <button
                                                    type="submit"
                                                    class="button button-primary"
                                                >
                                                    Setujui &amp; Terbitkan
                                                </button>

                                            </div>

                                        </form>

                                    </div>

                                </td>

                            </tr>

                        <?php endif; ?>


                    <?php endwhile; ?>

                    </tbody>

                </table>

            <?php else : ?>

                <div class="wrmj-empty-state">

                    <h3>
                        Belum Ada Jurnal
                    </h3>

                    <p>
                        Tidak ada jurnal pada kategori ini.
                    </p>

                </div>

            <?php endif; ?>

        </div>

    </div>

    <?php

    wp_reset_postdata();
}




