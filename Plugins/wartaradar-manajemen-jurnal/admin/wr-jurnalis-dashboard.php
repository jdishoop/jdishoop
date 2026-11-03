<?php

function wrmj_jurnalis_dashboard_page() {

    if ( ! defined( 'ABSPATH' ) ) {
        exit;
    }
/* =========================================================
 * PERMISSION
 * ========================================================= */

if ( ! wrmj_user_is_jurnalis() ) {

	wp_die(
		'Anda tidak memiliki akses ke halaman ini.'
	);
}


/* =========================================================
 * DATA USER
 * ========================================================= */

$current_user = wp_get_current_user();
$author_id    = get_current_user_id();


/* =========================================================
 * PAGE
 * ========================================================= */

$current_page = isset( $_GET['page'] )
	? sanitize_key( wp_unslash( $_GET['page'] ) )
	: 'wrmj-jurnalis-dashboard';


/* =========================================================
 * STATUS FILTER
 * ========================================================= */

$status_filter = '';

switch ( $current_page ) {

	case 'wrmj-jurnalis-draft':
		$status_filter = 'draft';
		break;

	case 'wrmj-jurnalis-review':
		$status_filter = 'menunggu_review';
		break;

	case 'wrmj-jurnalis-revision':
		$status_filter = 'perlu_revisi';
		break;

	case 'wrmj-jurnalis-published':
		$status_filter = 'published';
		break;

	default:
		$status_filter = '';
		break;
}


/* =========================================================
 * QUERY
 * ========================================================= */

$query_args = wrmj_get_journal_query_args(
	$status_filter,
	$author_id
);

$journal_query = new WP_Query( $query_args );


/* =========================================================
 * JUMLAH
 * ========================================================= */

$count_all = wrmj_count_journals(
	'',
	$author_id
);

$count_draft = wrmj_count_journals(
	'draft',
	$author_id
);

$count_review = wrmj_count_journals(
	'menunggu_review',
	$author_id
);

$count_revision = wrmj_count_journals(
	'perlu_revisi',
	$author_id
);

$count_published = wrmj_count_journals(
	'published',
	$author_id
);


/* =========================================================
 * JUDUL HALAMAN
 * ========================================================= */

$page_title = 'Jurnal Saya';

switch ( $current_page ) {

	case 'wrmj-jurnalis-draft':
		$page_title = 'Draft';
		break;

	case 'wrmj-jurnalis-review':
		$page_title = 'Menunggu Review';
		break;

	case 'wrmj-jurnalis-revision':
		$page_title = 'Perlu Revisi';
		break;

	case 'wrmj-jurnalis-published':
		$page_title = 'Jurnal Terbit';
		break;

	default:
		$page_title = 'Jurnal Saya';
		break;
}


/* =========================================================
 * NOTICE
 * ========================================================= */

$sent_notice = isset( $_GET['wrmj_sent'] )
	? absint( $_GET['wrmj_sent'] )
	: 0;

$revision_notice = isset( $_GET['wrmj_revision'] )
	? absint( $_GET['wrmj_revision'] )
	: 0;

$error_notice = isset( $_GET['wrmj_error'] )
	? sanitize_key( wp_unslash( $_GET['wrmj_error'] ) )
	: '';


?>

<div class="wrap wrmj-dashboard">

	<!-- =====================================================
	     HEADER
	====================================================== -->

	<div class="wrmj-header">

        <div class="wrmj-header-brand">

                <div class="wrmj-header-kicker">
                        WARTARADAR NEWSROOM
                </div>

                <h1>
                        Manajemen Jurnal
                </h1>

                <p>
                        Pusat pengelolaan jurnal Jurnalis WartaRadar.
                </p>

        </div>

        <div class="wrmj-header-actions">

                <a
                        class="button button-primary"
                        href="<?php echo esc_url( admin_url( 'post-new.php' ) ); ?>"
                >
                        + Jurnal Baru
                </a>

        </div>

</div><!-- =====================================================
	     NOTICE
	====================================================== -->

	<?php if ( 1 === $sent_notice ) : ?>

		<div class="notice notice-success is-dismissible">
			<p>
				<strong>Jurnal berhasil dikirim kembali ke Editor untuk direview.</strong>
			</p>
		</div>

	<?php endif; ?>


	<?php if ( 1 === $revision_notice ) : ?>

		<div class="notice notice-warning is-dismissible">
			<p>
				<strong>Jurnal dikembalikan oleh Editor untuk diperbaiki.</strong>
			</p>
		</div>

	<?php endif; ?>


	<?php if ( 'workflow' === $error_notice ) : ?>

		<div class="notice notice-error is-dismissible">
			<p>
				Jurnal tidak dapat diproses karena status workflow tidak sesuai.
			</p>
		</div>

	<?php endif; ?>


	<?php if ( 'send_failed' === $error_notice ) : ?>

		<div class="notice notice-error is-dismissible">
			<p>
				Jurnal gagal dikirim ke Editor. Silakan coba kembali.
			</p>
		</div>

	<?php endif; ?>


	<!-- =====================================================
	     STATISTIK
	====================================================== -->

	<div class="wrmj-stats-grid">

		<div class="wrmj-stat-card">

			<div class="wrmj-stat-number">
				<?php echo esc_html( $count_all ); ?>
			</div>

			<div class="wrmj-stat-label">
				Jurnal Saya
			</div>

		</div>


		<div class="wrmj-stat-card">

			<div class="wrmj-stat-number">
				<?php echo esc_html( $count_draft ); ?>
			</div>

			<div class="wrmj-stat-label">
				Draft
			</div>

		</div>


		<div class="wrmj-stat-card">

			<div class="wrmj-stat-number">
				<?php echo esc_html( $count_review ); ?>
			</div>

			<div class="wrmj-stat-label">
				Menunggu Review
			</div>

		</div>


		<div class="wrmj-stat-card">

			<div class="wrmj-stat-number">
				<?php echo esc_html( $count_revision ); ?>
			</div>

			<div class="wrmj-stat-label">
				Perlu Revisi
			</div>

		</div>


		<div class="wrmj-stat-card">

			<div class="wrmj-stat-number">
				<?php echo esc_html( $count_published ); ?>
			</div>

			<div class="wrmj-stat-label">
				Jurnal Terbit
			</div>

		</div>

	</div>


	<!-- =====================================================
	     NAVIGASI
	====================================================== -->

	<div class="wrmj-dashboard-nav">

		<a
			href="<?php echo esc_url( wrmj_jurnalis_url( 'wrmj-jurnalis-dashboard' ) ); ?>"
			class="<?php echo ( 'wrmj-jurnalis-dashboard' === $current_page ) ? 'active' : ''; ?>"
		>
			Jurnal Saya
		</a>


		<a
			href="<?php echo esc_url( wrmj_jurnalis_url( 'wrmj-jurnalis-draft' ) ); ?>"
			class="<?php echo ( 'wrmj-jurnalis-draft' === $current_page ) ? 'active' : ''; ?>"
		>
			Draft
		</a>


		<a
			href="<?php echo esc_url( wrmj_jurnalis_url( 'wrmj-jurnalis-review' ) ); ?>"
			class="<?php echo ( 'wrmj-jurnalis-review' === $current_page ) ? 'active' : ''; ?>"
		>
			Menunggu Review
		</a>


		<a
			href="<?php echo esc_url( wrmj_jurnalis_url( 'wrmj-jurnalis-revision' ) ); ?>"
			class="<?php echo ( 'wrmj-jurnalis-revision' === $current_page ) ? 'active' : ''; ?>"
		>
			Perlu Revisi
		</a>


		<a
			href="<?php echo esc_url( wrmj_jurnalis_url( 'wrmj-jurnalis-published' ) ); ?>"
			class="<?php echo ( 'wrmj-jurnalis-published' === $current_page ) ? 'active' : ''; ?>"
		>
			Jurnal Terbit
		</a>

	</div>


	<!-- =====================================================
	     KONTEN
	====================================================== -->

	<div class="wrmj-content-box">

		<div class="wrmj-content-header">

			<h2>
				<?php echo esc_html( $page_title ); ?>
			</h2>

			<p>
				Daftar jurnal yang Anda kelola.
			</p>

		</div>


		<?php if ( $journal_query->have_posts() ) : ?>

			<div class="wrmj-table-wrap">

				<table class="widefat fixed striped">

					<thead>

						<tr>

							<th style="width:34%;">
								Judul Jurnal
							</th>

							<th style="width:14%;">
								Status
							</th>

							<th style="width:14%;">
								Editor
							</th>

							<th style="width:16%;">
								Tanggal
							</th>

							<th style="width:22%;">
								Aksi
							</th>

						</tr>

					</thead>


					<tbody>

					<?php while ( $journal_query->have_posts() ) : ?>

						<?php
						$journal_query->the_post();

						$post_id = get_the_ID();

						$workflow_status =
							wrmj_get_post_workflow_status(
								$post_id
							);

						$status_label =
							wrmj_get_status_label(
								$workflow_status
							);

						$status_class =
							wrmj_get_status_class(
								$workflow_status
							);

						$editor_id = absint(
							get_post_meta(
								$post_id,
								'_wartaradar_editor_id',
								true
							)
						);

						$editor_name =
							$editor_id
							? wrmj_get_editor_name( $editor_id )
							: '—';

						$review_note = get_post_meta(
							$post_id,
							'_wartaradar_review_note',
							true
						);
						?>

						<tr>

							<td>

								<strong>
									<?php
									echo esc_html(
										wrmj_journal_title(
											$post_id
										)
									);
									?>
								</strong>

							</td>


							<td>

								<span class="wrmj-status <?php echo esc_attr( $status_class ); ?>">
									<?php echo esc_html( $status_label ); ?>
								</span>

							</td>


							<td>
								<?php echo esc_html( $editor_name ); ?>
							</td>


							<td>
								<?php
								echo esc_html(
									wrmj_format_date(
										get_post_time(
											'Y-m-d H:i:s',
											true,
											$post_id
										)
									)
								);
								?>
							</td>


							<td>

								<div class="wrmj-actions">

									<a
										class="button"
										href="<?php echo esc_url( get_edit_post_link( $post_id ) ); ?>"
									>
										Edit
									</a>


									<a
										class="button"
										href="<?php echo esc_url( get_permalink( $post_id ) ); ?>"
										target="_blank"
										rel="noopener"
									>
										Lihat
									</a>


									<?php
									/* =================================================
									 * DRAFT
									 * ================================================= */

									if ( 'draft' === $workflow_status ) :

										$review_url = wp_nonce_url(
											add_query_arg(
												array(
													'action'      => 'wrmj_jurnalis_action',
													'wrmj_action' => 'kirim_review',
													'post_id'    => $post_id,
												),
												admin_url( 'admin-post.php' )
											),
											'wrmj_kirim_review_' . $post_id,
											'wrmj_nonce'
										);
										?>

										<a
											class="button button-primary"
											href="<?php echo esc_url( $review_url ); ?>"
										>
											Kirim ke Review
										</a>

									<?php endif; ?>


									<?php
									/* =================================================
									 * PERLU REVISI
									 * ================================================= */

									if ( 'perlu_revisi' === $workflow_status ) :

										$review_url = wp_nonce_url(
											add_query_arg(
												array(
													'action'      => 'wrmj_jurnalis_action',
													'wrmj_action' => 'kirim_review',
													'post_id'     => $post_id,
												),
												admin_url( 'admin-post.php' )
											),
											'wrmj_kirim_review_' . $post_id,
											'wrmj_nonce'
										);
																		

										?>

										<a
											class="button button-primary"
											href="<?php echo esc_url( $review_url ); ?>"
										>
											Kirim Kembali ke Review
										</a>

									<?php endif; ?>

								</div>

							</td>

						</tr>


						<?php
						/* =====================================================
						 * CATATAN EDITOR
						 * ===================================================== */

						if (
							'perlu_revisi' === $workflow_status
							&&
							'' !== trim( $review_note )
						) :
						?>

							<tr class="wrmj-editor-note-row">

								<td colspan="5">

									<div class="wrmj-editor-note-box">

										<strong>
											Catatan Editor
										</strong>

										<div class="wrmj-editor-note-content">
											<?php
											echo nl2br(
												esc_html(
													$review_note
												)
											);
											?>
										</div>

									</div>

								</td>

							</tr>

						<?php endif; ?>


					<?php endwhile; ?>

					</tbody>

				</table>

			</div>


		<?php else : ?>

			<div class="wrmj-empty-state">

				<div class="wrmj-empty-icon">
					✓
				</div>

				<h3>
					Belum ada jurnal
				</h3>

				<p>
					Tidak ada jurnal pada kategori
					<strong><?php echo esc_html( $page_title ); ?></strong>.
				</p>

			</div>

		<?php endif; ?>


	</div>

</div>


<?php
wp_reset_postdata();

}

