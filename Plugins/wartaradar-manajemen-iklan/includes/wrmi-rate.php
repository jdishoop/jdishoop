<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Tarif Iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
	return;
}


/* =========================================================
 * 1. DATABASE
 * ========================================================= */

function wrmi_get_rate_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'wrmi_rates';
}


function wrmi_create_rate_table() {

	global $wpdb;

	$table_name      = wrmi_get_rate_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		placement varchar(100) NOT NULL,
		name varchar(190) NOT NULL,
		billing_unit varchar(20) NOT NULL DEFAULT 'day',
		price decimal(20,2) NOT NULL DEFAULT 0.00,
		status varchar(20) NOT NULL DEFAULT 'active',
		valid_from date NOT NULL,
		valid_until date DEFAULT NULL,
		sort_order int(11) NOT NULL DEFAULT 0,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY placement (placement),
		KEY status (status),
		KEY valid_from (valid_from),
		KEY valid_until (valid_until),
		KEY sort_order (sort_order)
	) {$charset_collate};";

	dbDelta( $sql );
}


function wrmi_ensure_rate_table() {

	global $wpdb;

	$table_name = wrmi_get_rate_table_name();

	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		)
	);

	if ( $table_exists !== $table_name ) {
		wrmi_create_rate_table();
	}
}


add_action(
	'admin_init',
	'wrmi_ensure_rate_table'
);


/* =========================================================
 * 2. DEFAULT VALUE
 * ========================================================= */

function wrmi_get_rate_placements() {

	if ( function_exists( 'wrmi_get_placement_definitions' ) ) {
		return wrmi_get_placement_definitions();
	}

	return array(
		'top'         => 'TOP',
		'header'      => 'HEADER',
		'hero'        => 'HERO',
		'sidebar'     => 'SIDEBAR',
		'feed'        => 'FEED',
		'mid_content' => 'MID CONTENT',
		'article'     => 'ARTICLE',
		'footer'      => 'FOOTER',
	);
}


function wrmi_get_rate_billing_units() {

	return array(
		'day' => 'Hari',
	);
}


/* =========================================================
 * 3. VALIDASI PERIODE
 * ========================================================= */

function wrmi_rate_period_overlaps(
	$placement,
	$valid_from,
	$valid_until,
	$exclude_id = 0
) {

	global $wpdb;

	$table_name = wrmi_get_rate_table_name();

	$placement   = sanitize_key( $placement );
	$valid_from  = sanitize_text_field( $valid_from );
	$valid_until = $valid_until
		? sanitize_text_field( $valid_until )
		: null;

	if ( '' === $valid_from ) {
		return false;
	}

	/*
	 * Periode tanpa batas dianggap berakhir pada 9999-12-31.
	 * Dua periode bertabrakan apabila:
	 *
	 * existing.valid_from <= new.valid_until
	 * AND
	 * existing.valid_until >= new.valid_from
	 */
	$new_end = $valid_until ? $valid_until : '9999-12-31';

	$sql = "
		SELECT id
		FROM {$table_name}
		WHERE placement = %s
		  AND status = %s
		  AND valid_from <= %s
		  AND COALESCE(valid_until, '9999-12-31') >= %s
	";

	$values = array(
		$placement,
		'active',
		$new_end,
		$valid_from,
	);

	if ( $exclude_id > 0 ) {
		$sql .= ' AND id != %d';
		$values[] = absint( $exclude_id );
	}

	$sql .= ' LIMIT 1';

	$existing_id = $wpdb->get_var(
		$wpdb->prepare(
			$sql,
			$values
		)
	);

	return ! empty( $existing_id );
}

/* =========================================================
 * 4. ADMIN MENU
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_rate_admin_menu',
	36
);

function wrmi_rate_admin_menu() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Tarif Iklan',
		'Tarif Iklan',
		'manage_options',
		'wrmi-rates',
		'wrmi_rate_admin_page'
	);
}


/* =========================================================
 * 5. DELETE
 * ========================================================= */

function wrmi_rate_delete( $rate_id ) {

	global $wpdb;

	$rate_id = absint( $rate_id );

	if ( $rate_id < 1 ) {
		return false;
	}

	$table_name = wrmi_get_rate_table_name();

	$result = $wpdb->delete(
		$table_name,
		array(
			'id' => $rate_id,
		),
		array(
			'%d',
		)
	);

	if ( false !== $result && function_exists( 'wrmi_log_activity' ) ) {

		wrmi_log_activity(
			'delete',
			'rate',
			$rate_id,
			'Menghapus Tarif Iklan.'
		);
	}

	return false !== $result;
}


/* =========================================================
 * 6. SAVE
 * ========================================================= */

function wrmi_rate_save() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	if ( empty( $_POST['wrmi_rate_nonce'] ) ) {
		return;
	}

	if (
		! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash(
					$_POST['wrmi_rate_nonce']
				)
			),
			'wrmi_rate_save'
		)
	) {
		return;
	}

	$rate_id = isset( $_POST['rate_id'] )
		? absint( $_POST['rate_id'] )
		: 0;

	$placement = isset( $_POST['placement'] )
		? sanitize_key(
			wp_unslash(
				$_POST['placement']
			)
		)
		: '';

	$name = isset( $_POST['name'] )
		? sanitize_text_field(
			wp_unslash(
				$_POST['name']
			)
		)
		: '';

	$billing_unit = isset( $_POST['billing_unit'] )
		? sanitize_key(
			wp_unslash(
				$_POST['billing_unit']
			)
		)
		: 'day';

	$price = isset( $_POST['price'] )
		? (float) str_replace(
			array( ',', ' ' ),
			array( '.', '' ),
			wp_unslash(
				$_POST['price']
			)
		)
		: 0;

	$status = isset( $_POST['status'] )
		? sanitize_key(
			wp_unslash(
				$_POST['status']
			)
		)
		: 'active';

	$valid_from = isset( $_POST['valid_from'] )
		? sanitize_text_field(
			wp_unslash(
				$_POST['valid_from']
			)
		)
		: '';

	$valid_until = isset( $_POST['valid_until'] )
		? sanitize_text_field(
			wp_unslash(
				$_POST['valid_until']
			)
		)
		: '';

	$sort_order = isset( $_POST['sort_order'] )
		? absint( $_POST['sort_order'] )
		: 0;

	$placements = wrmi_get_rate_placements();
	$units      = wrmi_get_rate_billing_units();

	if ( ! isset( $placements[ $placement ] ) ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wrmi-rates',
					'message' => 'invalid_placement',
				),
				admin_url( 'admin.php' )
			)
		);
		return;
	}

	if ( '' === $name ) {
		$name = $placements[ $placement ];
	}

	if ( ! isset( $units[ $billing_unit ] ) ) {
		$billing_unit = 'day';
	}

	if ( $price < 0 ) {
		$price = 0;
	}

	if ( ! in_array(
		$status,
		array(
			'active',
			'inactive',
		),
		true
	) ) {
		$status = 'inactive';
	}

	if ( '' === $valid_from ) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wrmi-rates',
					'message' => 'invalid_date',
				),
				admin_url( 'admin.php' )
			)
		);
		return;
	}

	if (
		'' !== $valid_until
		&& $valid_until < $valid_from
	) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wrmi-rates',
					'message' => 'invalid_period',
				),
				admin_url( 'admin.php' )
			)
		);
		return;
	}

	if (
		'active' === $status
		&& wrmi_rate_period_overlaps(
			$placement,
			$valid_from,
			$valid_until,
			$rate_id
		)
	) {
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wrmi-rates',
					'message' => 'overlap',
				),
				admin_url( 'admin.php' )
			)
		);
		return;
	}

	global $wpdb;

	$table_name = wrmi_get_rate_table_name();
	$now        = current_time( 'mysql' );

	$data = array(
		'placement'   => $placement,
		'name'        => $name,
		'billing_unit' => $billing_unit,
		'price'       => number_format(
			$price,
			2,
			'.',
			''
		),
		'status'      => $status,
		'valid_from'  => $valid_from,
		'valid_until' => '' !== $valid_until
			? $valid_until
			: null,
		'sort_order'  => $sort_order,
		'updated_at'  => $now,
	);

	$formats = array(
		'%s',
		'%s',
		'%s',
		'%f',
		'%s',
		'%s',
		'%s',
		'%d',
		'%s',
	);

	if ( $rate_id > 0 ) {

		$result = $wpdb->update(
			$table_name,
			$data,
			array(
				'id' => $rate_id,
			),
			$formats,
			array(
				'%d',
			)
		);

		$log_id = $rate_id;
		$action = 'update';

	} else {

		$data['created_at'] = $now;

		$result = $wpdb->insert(
			$table_name,
			$data,
			array(
				'%s',
				'%s',
				'%s',
				'%f',
				'%s',
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
			)
		);

		$log_id = absint( $wpdb->insert_id );
		$action = 'create';
	}

	if ( false !== $result ) {

		if ( function_exists( 'wrmi_log_activity' ) ) {

			wrmi_log_activity(
				$action,
				'rate',
				$log_id,
				'Menambahkan atau memperbarui Tarif Iklan.'
			);
		}

		wp_safe_redirect(
			add_query_arg(
				array(
					'page'    => 'wrmi-rates',
					'message' => 'saved',
				),
				admin_url( 'admin.php' )
			)
		);

		return;
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'wrmi-rates',
				'message' => 'error',
			),
			admin_url( 'admin.php' )
		)
	);
}


/* =========================================================
 * 7. ADMIN PAGE
 * ========================================================= */

function wrmi_rate_admin_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	wrmi_ensure_rate_table();

	global $wpdb;

	$table_name = wrmi_get_rate_table_name();

	$edit_id = isset( $_GET['edit'] )
		? absint( $_GET['edit'] )
		: 0;

	$edit_rate = null;

	if ( $edit_id > 0 ) {

		$edit_rate = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table_name} WHERE id = %d",
				$edit_id
			)
		);
	}

	$rates = $wpdb->get_results(
		"SELECT * FROM {$table_name}
		ORDER BY sort_order ASC, placement ASC, valid_from DESC, id DESC"
	);

	$placements = wrmi_get_rate_placements();
	$units      = wrmi_get_rate_billing_units();

	$message = isset( $_GET['message'] )
		? sanitize_key(
			wp_unslash(
				$_GET['message']
			)
		)
		: '';

	?>

	<div class="wrap">

		<h1>Tarif Iklan</h1>

		<?php if ( 'saved' === $message ) : ?>

			<div class="notice notice-success is-dismissible">
				<p>Tarif Iklan berhasil disimpan.</p>
			</div>

		<?php elseif ( 'overlap' === $message ) : ?>

			<div class="notice notice-error">
				<p>
					Periode Tarif Iklan aktif untuk Placement tersebut bertabrakan.
				</p>
			</div>

		<?php elseif ( 'invalid_period' === $message ) : ?>

			<div class="notice notice-error">
				<p>
					Tanggal selesai tidak boleh lebih awal dari tanggal mulai.
				</p>
			</div>

		<?php elseif ( 'invalid_date' === $message ) : ?>

			<div class="notice notice-error">
				<p>
					Tanggal mulai wajib diisi.
				</p>
			</div>

		<?php elseif ( 'invalid_placement' === $message ) : ?>

			<div class="notice notice-error">
				<p>
					Placement tidak valid.
				</p>
			</div>

		<?php elseif ( 'error' === $message ) : ?>

			<div class="notice notice-error">
				<p>
					Tarif Iklan gagal disimpan.
				</p>
			</div>

		<?php endif; ?>


		<h2>
			<?php echo $edit_rate ? 'Edit Tarif' : 'Tambah Tarif'; ?>
		</h2>

		<form method="post">

			<?php wp_nonce_field( 'wrmi_rate_save', 'wrmi_rate_nonce' ); ?>

			<input
				type="hidden"
				name="rate_id"
				value="<?php echo esc_attr( $edit_rate->id ?? 0 ); ?>"
			/>

			<table class="form-table">

				<tr>
					<th>
						<label for="wrmi-rate-placement">
							Placement
						</label>
					</th>

					<td>
						<select
							id="wrmi-rate-placement"
							name="placement"
							required
						>

							<?php foreach ( $placements as $key => $label ) : ?>

								<option
									value="<?php echo esc_attr( $key ); ?>"
									<?php selected(
										$edit_rate->placement ?? '',
										$key
									); ?>
								>
									<?php echo esc_html( $label ); ?>
								</option>

							<?php endforeach; ?>

						</select>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-name">
							Nama Tarif
						</label>
					</th>

					<td>
						<input
							type="text"
							id="wrmi-rate-name"
							name="name"
							value="<?php echo esc_attr( $edit_rate->name ?? '' ); ?>"
							class="regular-text"
							required
						/>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-unit">
							Satuan
						</label>
					</th>

					<td>
						<select
							id="wrmi-rate-unit"
							name="billing_unit"
						>

							<?php foreach ( $units as $key => $label ) : ?>

								<option
									value="<?php echo esc_attr( $key ); ?>"
									<?php selected(
										$edit_rate->billing_unit ?? 'day',
										$key
									); ?>
								>
									<?php echo esc_html( $label ); ?>
								</option>

							<?php endforeach; ?>

						</select>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-price">
							Harga
						</label>
					</th>

					<td>
						<input
							type="number"
							id="wrmi-rate-price"
							name="price"
							value="<?php echo esc_attr( $edit_rate->price ?? '0.00' ); ?>"
							min="0"
							step="0.01"
							class="regular-text"
							required
						/>

						<p class="description">
							Masukkan harga dasar per satuan.
						</p>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-valid-from">
							Berlaku Mulai
						</label>
					</th>

					<td>
						<input
							type="date"
							id="wrmi-rate-valid-from"
							name="valid_from"
							value="<?php echo esc_attr( $edit_rate->valid_from ?? '' ); ?>"
							required
						/>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-valid-until">
							Berlaku Sampai
						</label>
					</th>

					<td>
						<input
							type="date"
							id="wrmi-rate-valid-until"
							name="valid_until"
							value="<?php echo esc_attr( $edit_rate->valid_until ?? '' ); ?>"
						/>

						<p class="description">
							Kosongkan jika tarif berlaku tanpa batas akhir.
						</p>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-status">
							Status
						</label>
					</th>

					<td>
						<select
							id="wrmi-rate-status"
							name="status"
						>

							<option
								value="active"
								<?php selected(
									$edit_rate->status ?? 'active',
									'active'
								); ?>
							>
								Aktif
							</option>

							<option
								value="inactive"
								<?php selected(
									$edit_rate->status ?? '',
									'inactive'
								); ?>
							>
								Nonaktif
							</option>

						</select>
					</td>
				</tr>


				<tr>
					<th>
						<label for="wrmi-rate-sort">
							Urutan
						</label>
					</th>

					<td>
						<input
							type="number"
							id="wrmi-rate-sort"
							name="sort_order"
							value="<?php echo esc_attr( $edit_rate->sort_order ?? 0 ); ?>"
							min="0"
							step="1"
							class="small-text"
						/>
					</td>
				</tr>

			</table>

			<p class="submit">

				<button
					type="submit"
					name="wrmi_rate_submit"
					value="1"
					class="button button-primary"
				>
					Simpan Tarif
				</button>

				<?php if ( $edit_rate ) : ?>

					<a
						href="<?php echo esc_url(
							admin_url( 'admin.php?page=wrmi-rates' )
						); ?>"
						class="button"
					>
						Batal
					</a>

				<?php endif; ?>

			</p>

		</form>


		<hr />


		<h2>Daftar Tarif</h2>

		<table class="widefat striped">

			<thead>
				<tr>
					<th>Placement</th>
					<th>Nama Tarif</th>
					<th>Satuan</th>
					<th>Harga</th>
					<th>Periode</th>
					<th>Status</th>
					<th>Urutan</th>
					<th>Aksi</th>
				</tr>
			</thead>

			<tbody>

				<?php if ( empty( $rates ) ) : ?>

					<tr>
						<td colspan="8">
							Belum ada Tarif Iklan.
						</td>
					</tr>

				<?php else : ?>

					<?php foreach ( $rates as $rate ) : ?>

						<tr>

							<td>
								<?php
								echo esc_html(
									$placements[ $rate->placement ]
									?? strtoupper( $rate->placement )
								);
								?>
							</td>

							<td>
								<strong>
									<?php echo esc_html( $rate->name ); ?>
								</strong>
							</td>

							<td>
								<?php
								echo esc_html(
									$units[ $rate->billing_unit ]
									?? $rate->billing_unit
								);
								?>
							</td>

							<td>
								Rp <?php echo esc_html(
									number_format(
										(float) $rate->price,
										2,
										',',
										'.'
									)
								); ?>
							</td>

							<td>
								<?php
								echo esc_html(
									wp_date(
										'd M Y',
										strtotime( $rate->valid_from )
									)
								);
								?>

								—

								<?php
								if ( $rate->valid_until ) {
									echo esc_html(
										wp_date(
											'd M Y',
											strtotime(
												$rate->valid_until
											)
										)
									);
								} else {
									echo 'Tidak terbatas';
								}
								?>
							</td>

							<td>
								<?php
								echo 'active' === $rate->status
									? 'Aktif'
									: 'Nonaktif';
								?>
							</td>

							<td>
								<?php echo esc_html( $rate->sort_order ); ?>
							</td>

							<td>

								<a
									href="<?php echo esc_url(
										add_query_arg(
											array(
												'page' => 'wrmi-rates',
												'edit' => $rate->id,
											),
											admin_url( 'admin.php' )
										)
									); ?>"
								>
									Edit
								</a>

							</td>

						</tr>

					<?php endforeach; ?>

				<?php endif; ?>

			</tbody>

		</table>

	</div>

	<?php
}


/* =========================================================
 * 8. FORM HANDLER
 * ========================================================= */

add_action(
	'admin_init',
	'wrmi_rate_handle_form'
);

function wrmi_rate_handle_form() {

	if (
		! isset( $_POST['wrmi_rate_submit'] )
	) {
		return;
	}

	wrmi_rate_save();
}