<?php
/**
 * WartaRadar Manajemen Jurnal
 * Dashboard Administrator
 *
 * Tahap: 19.3T-11F-R4-C-5
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * DASHBOARD ADMINISTRATOR
 * ========================================================= */

function wrmj_admin_dashboard_page() {

	if ( ! wrmj_current_user_is_admin() ) {

		wp_die(
			esc_html__(
				'Anda tidak memiliki izin untuk mengakses halaman ini.',
				'wartaradar-manajemen-jurnal'
			)
		);
	}


	/* =====================================================
	 * HALAMAN AKTIF
	 * =================================================== */

	$current_page = isset( $_GET['page'] )
		? sanitize_key( wp_unslash( $_GET['page'] ) )
		: 'wrmj-admin-dashboard';


	/* =====================================================
	 * STATISTIK JURNAL
	 * =================================================== */

	$count_all = wrmj_count_journals();

	$count_review = wrmj_count_journals(
		'menunggu_review'
	);

	$count_revision = wrmj_count_journals(
		'perlu_revisi'
	);

	$count_published = wrmj_count_journals(
		'published'
	);


	/* =====================================================
	 * STATISTIK PENGGUNA
	 * =================================================== */

	$jurnalis_users = get_users(
		array(
			'role'   => 'jurnalis',
			'fields' => 'ID',
		)
	);

	$editor_users = get_users(
		array(
			'role'   => 'editor_berita',
			'fields' => 'ID',
		)
	);


	$count_jurnalis = count( $jurnalis_users );
	$count_editor   = count( $editor_users );


	/* =====================================================
	 * QUERY SEMUA JURNAL
	 * =================================================== */

	$requested_filter = isset( $_GET["wrmj_filter"] ) ? sanitize_key( wp_unslash( $_GET["wrmj_filter"] ) ) : "";
$filter_map = array( "review" => "menunggu_review", "revision" => "perlu_revisi", "published" => "published" );
$workflow_filter = isset( $filter_map[$requested_filter] ) ? $filter_map[$requested_filter] : "";
$query_args = wrmj_get_journal_query_args( $workflow_filter );

	$journals = new WP_Query(
		$query_args
	);

	?>

	<div class="wrap wrmj-dashboard">


		<!-- =================================================
		     HEADER
		================================================== -->

		<div class="wrmj-page-header">

			<div class="wrmj-page-brand">

				<div class="wrmj-brand-title">
					WARTARADAR NEWSROOM
				</div>

				<div class="wrmj-brand-name">
					Manajemen Jurnal
				</div>

				<div class="wrmj-brand-description">
					Pusat pengelolaan seluruh aktivitas newsroom WartaRadar.
				</div>

			</div>

		</div>


		<!-- =================================================
		     KARTU STATISTIK
		================================================== -->

		<div class="wrmj-stat-grid">


			<!-- =============================================
			     SEMUA JURNAL
			============================================== -->

			<a
				class="wrmj-stat-card wrmj-stat-card-link"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-dashboard' )
				); ?>"
			>

				<div class="wrmj-stat-label">
					Semua Jurnal
				</div>

				<div class="wrmj-stat-number">
					<?php echo esc_html( $count_all ); ?>
				</div>

				<div class="wrmj-stat-link">
					Lihat Semua Jurnal →
				</div>

			</a>


			<!-- =============================================
			     PERLU REVIEW
			============================================== -->

			<a
				class="wrmj-stat-card wrmj-stat-review wrmj-stat-card-link"
				href="<?php echo esc_url( add_query_arg( "wrmj_filter", "review", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
			>

				<div class="wrmj-stat-label">
					Perlu Review
				</div>

				<div class="wrmj-stat-number">
					<?php echo esc_html( $count_review ); ?>
				</div>

				<div class="wrmj-stat-link">
					Lihat Jurnal →
				</div>

			</a>


			<!-- =============================================
			     PERLU REVISI
			============================================== -->

			<a
				class="wrmj-stat-card wrmj-stat-revision wrmj-stat-card-link"
				href="<?php echo esc_url( add_query_arg( "wrmj_filter", "revision", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
			>

				<div class="wrmj-stat-label">
					Perlu Revisi
				</div>

				<div class="wrmj-stat-number">
					<?php echo esc_html( $count_revision ); ?>
				</div>

				<div class="wrmj-stat-link">
					Lihat Jurnal →
				</div>

			</a>


			<!-- =============================================
			     JURNAL TERBIT
			============================================== -->

			<a
				class="wrmj-stat-card wrmj-stat-published wrmj-stat-card-link"
				href="<?php echo esc_url( add_query_arg( "wrmj_filter", "published", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
			>

				<div class="wrmj-stat-label">
					Jurnal Terbit
				</div>

				<div class="wrmj-stat-number">
					<?php echo esc_html( $count_published ); ?>
				</div>

				<div class="wrmj-stat-link">
					Lihat Jurnal →
				</div>

			</a>


			<!-- =============================================
			     JURNALIS
			============================================== -->

			<a
				class="wrmj-stat-card wrmj-stat-card-link"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-jurnalis' )
				); ?>"
			>

				<div class="wrmj-stat-label">
					Jurnalis
				</div>

				<div class="wrmj-stat-number">
					<?php echo esc_html( $count_jurnalis ); ?>
				</div>

				<div class="wrmj-stat-link">
					Lihat Semua Jurnalis →
				</div>

			</a>


			<!-- =============================================
			     EDITOR
			============================================== -->

			<a
				class="wrmj-stat-card wrmj-stat-card-link"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-editor' )
				); ?>"
			>

				<div class="wrmj-stat-label">
					Editor
				</div>

				<div class="wrmj-stat-number">
					<?php echo esc_html( $count_editor ); ?>
				</div>

				<div class="wrmj-stat-link">
					Lihat Editor →
				</div>

			</a>

		</div>


		<!-- =================================================
		     NAVIGASI ADMINISTRATOR
		================================================== -->

		<div class="wrmj-status-navigation">

			<a
				class="<?php echo 'wrmj-admin-dashboard' === $current_page ? 'active' : ''; ?>"
				href="<?php echo esc_url( add_query_arg( "wrmj_filter", "published", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
			>
				Semua Jurnal
			</a>


			<a
				class="<?php echo 'wrmj-admin-jurnalis' === $current_page ? 'active' : ''; ?>"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-jurnalis' )
				); ?>"
			>
				Semua Jurnalis
			</a>


			<a
				class="<?php echo 'wrmj-admin-editor' === $current_page ? 'active' : ''; ?>"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-editor' )
				); ?>"
			>
				Editor
			</a>


			<a
				class="<?php echo 'wrmj-admin-kategori' === $current_page ? 'active' : ''; ?>"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-kategori' )
				); ?>"
			>
				Kategori
			</a>


			<a
				class="<?php echo 'wrmj-admin-statistik' === $current_page ? 'active' : ''; ?>"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-statistik' )
				); ?>"
			>
				Statistik
			</a>


			<a
				class="<?php echo 'wrmj-admin-pengaturan' === $current_page ? 'active' : ''; ?>"
				href="<?php echo esc_url(
					wrmj_admin_url( 'wrmj-admin-pengaturan' )
				); ?>"
			>
				Pengaturan
			</a>

		</div>


		<!-- =================================================
		     KONTEN
		================================================== -->

		<div class="wrmj-content-card">


		<?php if ( 'wrmj-admin-jurnalis' === $current_page ) : ?>


			<!-- =============================================
			     SEMUA JURNALIS
			============================================== -->

			<div class="wrmj-content-header">

				<div>

					<h2>
						Semua Jurnalis
					</h2>

					<p>
						Daftar pengguna dengan peran Jurnalis WartaRadar.
					</p>

				</div>

			</div>


			<?php

			$jurnalis = get_users(
				array(
					'role'    => 'jurnalis',
					'orderby' => 'display_name',
					'order'   => 'ASC',
				)
			);

			?>


			<?php if ( ! empty( $jurnalis ) ) : ?>

				<div class="wrmj-table-wrapper">

					<table class="widefat fixed striped wrmj-journal-table">

						<thead>

							<tr>

								<th>
									Nama
								</th>

								<th>
									Username
								</th>

								<th>
									Email
								</th>

								<th>
									Jurnal
								</th>

							</tr>

						</thead>


						<tbody>

						<?php foreach ( $jurnalis as $user ) : ?>

							<?php

							$user_journal_count = count_user_posts(
								$user->ID,
								'post',
								true
							);

							?>

							<tr>

								<td>

									<strong>
										<?php
										echo esc_html(
											$user->display_name
										);
										?>
									</strong>

								</td>


								<td>
									<?php
									echo esc_html(
										$user->user_login
									);
									?>
								</td>


								<td>
									<?php
									echo esc_html(
										$user->user_email
									);
									?>
								</td>


								<td>
									<?php
									echo esc_html(
										$user_journal_count
									);
									?>
								</td>

							</tr>

						<?php endforeach; ?>

						</tbody>

					</table>

				</div>

			<?php else : ?>

				<div class="wrmj-empty-state">

					<div class="wrmj-empty-icon">
						–
					</div>

					<h3>
						Belum ada Jurnalis
					</h3>

					<p>
						Belum terdapat pengguna dengan peran Jurnalis.
					</p>

				</div>

			<?php endif; ?>


		<?php elseif ( 'wrmj-admin-editor' === $current_page ) : ?>


			<!-- =============================================
			     EDITOR
			============================================== -->

			<div class="wrmj-content-header">

				<div>

					<h2>
						Editor Berita
					</h2>

					<p>
						Daftar pengguna yang bertugas sebagai Editor Berita.
					</p>

				</div>

			</div>


			<?php

			$editors = get_users(
				array(
					'role'    => 'editor_berita',
					'orderby' => 'display_name',
					'order'   => 'ASC',
				)
			);

			?>


			<?php if ( ! empty( $editors ) ) : ?>

				<div class="wrmj-table-wrapper">

					<table class="widefat fixed striped wrmj-journal-table">

						<thead>

							<tr>

								<th>
									Nama
								</th>

								<th>
									Username
								</th>

								<th>
									Email
								</th>

							</tr>

						</thead>


						<tbody>

						<?php foreach ( $editors as $user ) : ?>

							<tr>

								<td>

									<strong>
										<?php
										echo esc_html(
											$user->display_name
										);
										?>
									</strong>

								</td>


								<td>
									<?php
									echo esc_html(
										$user->user_login
									);
									?>
								</td>


								<td>
									<?php
									echo esc_html(
										$user->user_email
									);
									?>
								</td>

							</tr>

						<?php endforeach; ?>

						</tbody>

					</table>

				</div>

			<?php else : ?>

				<div class="wrmj-empty-state">

					<div class="wrmj-empty-icon">
						–
					</div>

					<h3>
						Belum ada Editor
					</h3>

					<p>
						Belum terdapat pengguna dengan peran Editor Berita.
					</p>

				</div>

			<?php endif; ?>


		<?php elseif ( 'wrmj-admin-kategori' === $current_page ) : ?>


			<!-- =============================================
			     KATEGORI
			============================================== -->

			<div class="wrmj-content-header">

				<div>

					<h2>
						Kategori
					</h2>

					<p>
						Kategori berita yang tersedia di WartaRadar.
					</p>

				</div>

			</div>


			<?php

			$categories = get_categories(
				array(
					'hide_empty' => false,
					'orderby'    => 'name',
					'order'      => 'ASC',
				)
			);

			?>


			<?php if ( ! empty( $categories ) ) : ?>

				<div class="wrmj-table-wrapper">

					<table class="widefat fixed striped wrmj-journal-table">

						<thead>

							<tr>

								<th>
									Nama Kategori
								</th>

								<th>
									Slug
								</th>

								<th>
									Jumlah Jurnal
								</th>

							</tr>

						</thead>


						<tbody>

						<?php foreach ( $categories as $category ) : ?>

							<tr>

								<td>

									<strong>
										<?php
										echo esc_html(
											$category->name
										);
										?>
									</strong>

								</td>


								<td>
									<?php
									echo esc_html(
										$category->slug
									);
									?>
								</td>


								<td>
									<?php
									echo esc_html(
										$category->count
									);
									?>
								</td>

							</tr>

						<?php endforeach; ?>

						</tbody>

					</table>

				</div>

			<?php else : ?>

				<div class="wrmj-empty-state">

					<div class="wrmj-empty-icon">
						–
					</div>

					<h3>
						Belum ada kategori
					</h3>

					<p>
						Belum terdapat kategori berita.
					</p>

				</div>

			<?php endif; ?>


		<?php elseif ( 'wrmj-admin-statistik' === $current_page ) : ?>


			<!-- =============================================
			     STATISTIK
			============================================== -->

			<div class="wrmj-content-header">

				<div>

					<h2>
						Statistik
					</h2>

					<p>
						Ringkasan aktivitas jurnal newsroom.
					</p>

				</div>

			</div>


			<div class="wrmj-stat-grid">

				<a
					class="wrmj-stat-card wrmj-stat-card-link"
					href="<?php echo esc_url(
						wrmj_admin_url( 'wrmj-admin-dashboard' )
					); ?>"
				>

					<div class="wrmj-stat-label">
						Semua Jurnal
					</div>

					<div class="wrmj-stat-number">
						<?php echo esc_html( $count_all ); ?>
					</div>

					<div class="wrmj-stat-link">
						Lihat Jurnal →
					</div>

				</a>


				<a
					class="wrmj-stat-card wrmj-stat-review wrmj-stat-card-link"
					href="<?php echo esc_url( add_query_arg( "wrmj_filter", "review", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
				>

					<div class="wrmj-stat-label">
						Menunggu Review
					</div>

					<div class="wrmj-stat-number">
						<?php echo esc_html( $count_review ); ?>
					</div>

					<div class="wrmj-stat-link">
						Lihat Jurnal →
					</div>

				</a>


				<a
					class="wrmj-stat-card wrmj-stat-revision wrmj-stat-card-link"
					href="<?php echo esc_url( add_query_arg( "wrmj_filter", "revision", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
				>

					<div class="wrmj-stat-label">
						Perlu Revisi
					</div>

					<div class="wrmj-stat-number">
						<?php echo esc_html( $count_revision ); ?>
					</div>

					<div class="wrmj-stat-link">
						Lihat Jurnal →
					</div>

				</a>


				<a
					class="wrmj-stat-card wrmj-stat-published wrmj-stat-card-link"
					href="<?php echo esc_url( add_query_arg( "wrmj_filter", "published", wrmj_admin_url( "wrmj-admin-dashboard" ) ) ); ?>"
				>

					<div class="wrmj-stat-label">
						Jurnal Terbit
					</div>

					<div class="wrmj-stat-number">
						<?php echo esc_html( $count_published ); ?>
					</div>

					<div class="wrmj-stat-link">
						Lihat Jurnal →
					</div>

				</a>

			</div>


		<?php elseif ( 'wrmj-admin-pengaturan' === $current_page ) : ?>

        <!-- =============================================
             PENGATURAN
        ============================================== -->

        <div class="wrmj-content-header">

                <div>

                        <h2>
                                Pengaturan
                        </h2>

                        <p>
                                Pengaturan Manajemen Jurnal WartaRadar.
                        </p>

                </div>

        </div>


        <div class="wrmj-settings-grid">

                <div class="wrmj-settings-card">

                        <div class="wrmj-settings-icon">
                                ⚙
                        </div>

                        <div class="wrmj-settings-body">

                                <h3>
                                        Pengaturan Newsroom
                                </h3>

                                <p>
                                        Pusat pengaturan sistem manajemen jurnal
                                        WartaRadar.
                                </p>

                                <span class="wrmj-settings-status">
                                        Siap dikembangkan
                                </span>

                        </div>

                </div>


                <div class="wrmj-settings-card">

                        <div class="wrmj-settings-icon">
                                📰
                        </div>

                        <div class="wrmj-settings-body">

                                <h3>
                                        Pengaturan Beranda
                                </h3>

                                <p>
                                        Pilih satu Headline Utama dan beberapa
                                        Sorotan Redaksi untuk halaman depan.
                                </p>

                                <a
                                        href="<?php echo esc_url( admin_url( 'admin.php?page=wrmj-editorial-settings' ) ); ?>"
                                        class="button button-primary"
                                >
                                        Kelola Pilihan Editorial
                                </a>

                        </div>

                </div>


                <div class="wrmj-settings-card">

                        <div class="wrmj-settings-icon">
                                👤
                        </div>

                        <div class="wrmj-settings-body">

                                <h3>
                                        Pengaturan Jurnalis
                                </h3>

                                <p>
                                        Pengelolaan akun, peran, dan data Jurnalis
                                        WartaRadar.
                                </p>

                                <a
                                        href="<?php echo esc_url( wrmj_admin_url( 'wrmj-admin-jurnalis' ) ); ?>"
                                        class="button button-primary"
                                >
                                        Kelola Jurnalis
                                </a>

                        </div>

                </div>


                <div class="wrmj-settings-card">

                        <div class="wrmj-settings-icon">
                                ✎
                        </div>

                        <div class="wrmj-settings-body">

                                <h3>
                                        Pengaturan Editor
                                </h3>

                                <p>
                                        Pengelolaan Editor Berita dan pemeriksaan
                                        jurnal.
                                </p>

                                <a
                                        href="<?php echo esc_url( wrmj_admin_url( 'wrmj-admin-editor' ) ); ?>"
                                        class="button button-primary"
                                >
                                        Kelola Editor
                                </a>

                        </div>

                </div>


                <div class="wrmj-settings-card">

                        <div class="wrmj-settings-icon">
                                ▣
                        </div>

                        <div class="wrmj-settings-body">

                                <h3>
                                        Pengaturan Kategori
                                </h3>

                                <p>
                                        Pengaturan kategori jurnal WartaRadar.
                                </p>

                                <a
                                        href="<?php echo esc_url( wrmj_admin_url( 'wrmj-admin-kategori' ) ); ?>"
                                        class="button button-primary"
                                >
                                        Kelola Kategori
                                </a>

                        </div>

                </div>


                <div class="wrmj-settings-card">

                        <div class="wrmj-settings-icon">
                                ▤
                        </div>

                        <div class="wrmj-settings-body">

                                <h3>
                                        Statistik
                                </h3>

                                <p>
                                        Lihat statistik jurnal, status workflow,
                                        dan aktivitas newsroom.
                                </p>

                                <a
                                        href="<?php echo esc_url( wrmj_admin_url( 'wrmj-admin-statistik' ) ); ?>"
                                        class="button button-primary"
                                >
                                        Buka Statistik
                                </a>

                        </div>

                </div>

        </div>


<?php else : ?>


			<!-- =============================================
			     SEMUA JURNAL
			============================================== -->

			<div class="wrmj-content-header">

				<div>

					<h2>
						Semua Jurnal
					</h2>

					<p>
						Seluruh jurnal yang dikelola dalam newsroom WartaRadar.
					</p>

				</div>

			</div>


			<?php if ( $journals->have_posts() ) : ?>

				<div class="wrmj-table-wrapper">

					<table class="widefat fixed striped wrmj-journal-table">

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

						<?php while ( $journals->have_posts() ) : ?>

							<?php $journals->the_post(); ?>


							<?php

							$post_id = get_the_ID();

							$status = wrmj_get_post_workflow_status(
								$post_id
							);


							$status_labels = array(
								'draft'           => 'Draft',
								'menunggu_review' => 'Menunggu Review',
								'perlu_revisi'    => 'Perlu Revisi',
								'published'       => 'Jurnal Terbit',
							);


							$status_classes = array(
								'draft'           => 'wrmj-status-draft',
								'menunggu_review' => 'wrmj-status-review',
								'perlu_revisi'    => 'wrmj-status-revisi',
								'published'       => 'wrmj-status-published',
							);


							$status_label = isset(
								$status_labels[ $status ]
							)
								? $status_labels[ $status ]
								: ucfirst( $status );


							$status_class = isset(
								$status_classes[ $status ]
							)
								? $status_classes[ $status ]
								: 'wrmj-status-draft';


							$author_name = wrmj_get_author_name(
								$post_id
							);


							$edit_url = get_edit_post_link(
								$post_id
							);


							$view_url = get_permalink(
								$post_id
							);

							?>


							<tr>

								<td>

									<strong>

										<a
											href="<?php echo esc_url(
												$edit_url
											); ?>"
										>
											<?php
											echo esc_html(
												get_the_title()
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
										class="wrmj-status <?php echo esc_attr(
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
										wrmj_format_date(
											$post_id
										)
									);
									?>
								</td>


								<td>

									<div class="wrmj-action-group">


										<?php if ( $view_url ) : ?>

											<a
												class="button button-small"
												href="<?php echo esc_url(
													$view_url
												); ?>"
												target="_blank"
												rel="noopener"
											>
												Lihat
											</a>

										<?php endif; ?>


										<?php if ( $edit_url ) : ?>

											<a
												class="button button-small"
												href="<?php echo esc_url(
													$edit_url
												); ?>"
											>
												Edit
											</a>

										<?php endif; ?>

									</div>

								</td>

							</tr>


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
						Belum terdapat jurnal dalam newsroom.
					</p>

				</div>

			<?php endif; ?>


		<?php endif; ?>

		</div>

	</div>


	<?php

	wp_reset_postdata();

}