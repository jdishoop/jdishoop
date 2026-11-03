<?php
/**
 * WartaRadar Manajemen Iklan
 * Invoice
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/*
 * =========================================================
 * STATUS INVOICE
 * =========================================================
 */

function wrmi_invoice_get_statuses() {

	return array(
		'draft'     => 'Draft',
		'issued'    => 'Diterbitkan',
		'sent'      => 'Dikirim',
		'paid'      => 'Lunas',
		'overdue'   => 'Jatuh Tempo',
		'cancelled' => 'Dibatalkan',
	);
}


/*
 * =========================================================
 * TABLE NAMES
 * =========================================================
 */

function wrmi_get_invoice_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'wrmi_invoices';
}


function wrmi_get_invoice_items_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'wrmi_invoice_items';
}


/*
 * =========================================================
 * CREATE TABLES
 * =========================================================
 */

function wrmi_create_invoice_tables() {

	global $wpdb;

	$invoice_table      = wrmi_get_invoice_table_name();
	$invoice_items_table = wrmi_get_invoice_items_table_name();
	$charset_collate    = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql_invoice = "CREATE TABLE {$invoice_table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		booking_id bigint(20) unsigned NOT NULL,
		invoice_number varchar(50) NOT NULL,
		advertiser_id bigint(20) unsigned DEFAULT NULL,
		billing_company varchar(190) NOT NULL DEFAULT '',
		billing_contact varchar(190) NOT NULL DEFAULT '',
		billing_email varchar(190) NOT NULL DEFAULT '',
		billing_phone varchar(100) NOT NULL DEFAULT '',
		invoice_date date NOT NULL,
		due_date date DEFAULT NULL,
		status varchar(20) NOT NULL DEFAULT 'draft',
		subtotal decimal(20,2) NOT NULL DEFAULT 0.00,
		discount decimal(20,2) NOT NULL DEFAULT 0.00,
		tax decimal(20,2) NOT NULL DEFAULT 0.00,
		total decimal(20,2) NOT NULL DEFAULT 0.00,
		notes text,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		PRIMARY KEY (id),
		UNIQUE KEY invoice_number (invoice_number),
		KEY booking_id (booking_id),
		KEY advertiser_id (advertiser_id),
		KEY status (status),
		KEY invoice_date (invoice_date),
		KEY due_date (due_date),
		KEY created_at (created_at)
	) {$charset_collate};";

	$sql_items = "CREATE TABLE {$invoice_items_table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		invoice_id bigint(20) unsigned NOT NULL,
		booking_item_id bigint(20) unsigned DEFAULT NULL,
		placement varchar(100) NOT NULL DEFAULT '',
		start_date date NOT NULL,
		end_date date DEFAULT NULL,
		quantity decimal(20,4) NOT NULL DEFAULT 1.0000,
		billing_unit varchar(20) NOT NULL DEFAULT 'day',
		unit_price decimal(20,2) NOT NULL DEFAULT 0.00,
		discount decimal(20,2) NOT NULL DEFAULT 0.00,
		tax decimal(20,2) NOT NULL DEFAULT 0.00,
		total decimal(20,2) NOT NULL DEFAULT 0.00,
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		KEY invoice_id (invoice_id),
		KEY booking_item_id (booking_item_id),
		KEY placement (placement),
		KEY start_date (start_date),
		KEY end_date (end_date),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql_invoice );
	dbDelta( $sql_items );
}


function wrmi_ensure_invoice_tables() {

	global $wpdb;

	$invoice_table       = wrmi_get_invoice_table_name();
	$invoice_items_table = wrmi_get_invoice_items_table_name();

	$invoice_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$invoice_table
		)
	);

	$invoice_items_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$invoice_items_table
		)
	);

	if (
		$invoice_exists !== $invoice_table
		|| $invoice_items_exists !== $invoice_items_table
	) {
		wrmi_create_invoice_tables();
	}
}

add_action(
	'admin_init',
	'wrmi_ensure_invoice_tables',
	5
);


/*
 * =========================================================
 * INVOICE NUMBER
 * =========================================================
 */

function wrmi_invoice_generate_number() {

	$month = current_time( 'Y-m' );

	$option_name = 'wrmi_invoice_sequence_' . $month;

	$sequence = (int) get_option(
		$option_name,
		0
	);

	$sequence++;

	update_option(
		$option_name,
		$sequence,
		false
	);

	return sprintf(
		'INV-%s-%04d',
		str_replace( '-', '', $month ),
		$sequence
	);
}


/*
 * =========================================================
 * INVOICE HELPERS
 * =========================================================
 */

function wrmi_get_invoice_status( $invoice_id ) {

	global $wpdb;

	$table_name = wrmi_get_invoice_table_name();

	$status = $wpdb->get_var(
		$wpdb->prepare(
			"SELECT status
			FROM {$table_name}
			WHERE id = %d
			LIMIT 1",
			absint( $invoice_id )
		)
	);

	$statuses = wrmi_invoice_get_statuses();

	return isset( $statuses[ $status ] )
		? $status
		: 'draft';
}


function wrmi_get_invoice_number( $invoice_id ) {

	global $wpdb;

	$table_name = wrmi_get_invoice_table_name();

	return (string) $wpdb->get_var(
		$wpdb->prepare(
			"SELECT invoice_number
			FROM {$table_name}
			WHERE id = %d",
			absint( $invoice_id )
		)
	);
}


function wrmi_get_invoice_by_booking( $booking_id ) {

	global $wpdb;

	$table_name = wrmi_get_invoice_table_name();

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE booking_id = %d
			AND status != %s
			ORDER BY id DESC
			LIMIT 1",
			absint( $booking_id ),
			'cancelled'
		)
	);
}


function wrmi_get_invoice_items( $invoice_id ) {

	global $wpdb;

	$table_name = wrmi_get_invoice_items_table_name();

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE invoice_id = %d
			ORDER BY id ASC",
			absint( $invoice_id )
		)
	);
}


/*
 * =========================================================
 * INVOICE CPT
 * =========================================================
 */

function wrmi_register_invoice_post_type() {

	register_post_type(
		'wrmi_invoice',
		array(
			'labels' => array(
				'name'          => 'Invoice',
				'singular_name' => 'Invoice',
			),
			'public'              => false,
			'show_ui'             => false,
			'show_in_menu'        => false,
			'show_in_admin_bar'   => false,
			'show_in_nav_menus'   => false,
			'exclude_from_search' => true,
			'publicly_queryable'  => false,
			'supports'            => array( 'title' ),
			'map_meta_cap'        => true,
		)
	);
}

add_action(
	'init',
	'wrmi_register_invoice_post_type'
);


/*
 * =========================================================
 * CREATE INVOICE FROM BOOKING
 * =========================================================
 */

function wrmi_invoice_create_from_booking( $booking_id ) {

	global $wpdb;

	$booking_id = absint( $booking_id );

	if ( ! $booking_id ) {
		return new WP_Error(
			'invalid_booking',
			'Booking tidak valid.'
		);
	}

	$booking = get_post( $booking_id );

	if (
		! $booking
		|| 'wrmi_booking' !== $booking->post_type
	) {
		return new WP_Error(
			'invalid_booking',
			'Booking tidak ditemukan.'
		);
	}

	$booking_status = function_exists( 'wrmi_get_booking_status' )
		? wrmi_get_booking_status( $booking_id )
		: 'draft';

	if ( in_array(
		$booking_status,
		array( 'cancelled', 'expired' ),
		true
	) ) {
		return new WP_Error(
			'invalid_booking_status',
			'Booking yang dibatalkan atau kedaluwarsa tidak dapat dibuatkan Invoice.'
		);
	}

	$existing_invoice = wrmi_get_invoice_by_booking( $booking_id );

	if ( $existing_invoice ) {
		return new WP_Error(
			'invoice_exists',
			'Booking ini sudah memiliki Invoice aktif.'
		);
	}

	$booking_items = function_exists( 'wrmi_get_booking_items' )
		? wrmi_get_booking_items( $booking_id )
		: array();

	if ( empty( $booking_items ) ) {
		return new WP_Error(
			'empty_booking',
			'Booking belum memiliki Booking Item.'
		);
	}

	$advertiser_id = function_exists( 'wrmi_get_booking_advertiser_id' )
		? wrmi_get_booking_advertiser_id( $booking_id )
		: 0;

	$advertiser_company = '';
	$advertiser_contact = '';
	$advertiser_email   = '';
	$advertiser_phone   = '';

	if ( $advertiser_id ) {
		$advertiser_company = get_post_meta(
			$advertiser_id,
			'_wrmi_advertiser_company',
			true
		);

		$advertiser_contact = get_post_meta(
			$advertiser_id,
			'_wrmi_advertiser_contact',
			true
		);

		$advertiser_email = get_post_meta(
			$advertiser_id,
			'_wrmi_advertiser_email',
			true
		);

		$advertiser_phone = get_post_meta(
			$advertiser_id,
			'_wrmi_advertiser_phone',
			true
		);
	}

	$subtotal = 0;

	$discount = (float) get_post_meta(
		$booking_id,
		'_wrmi_booking_discount',
		true
	);

	$tax = (float) get_post_meta(
		$booking_id,
		'_wrmi_booking_tax',
		true
	);

	$prepared_items = array();

	foreach ( $booking_items as $booking_item ) {
		$item_total = (float) $booking_item->total;
		$subtotal  += $item_total;

		$prepared_items[] = array(
			'booking_item_id' => absint( $booking_item->id ),
			'placement'       => sanitize_key( $booking_item->placement ),
			'start_date'      => sanitize_text_field( $booking_item->start_date ),
			'end_date'        => sanitize_text_field( $booking_item->end_date ),
			'quantity'        => (float) $booking_item->quantity,
			'billing_unit'    => 'day',
			'unit_price'      => (float) $booking_item->unit_price,
			'discount'        => (float) $booking_item->discount,
			'tax'             => (float) $booking_item->tax,
			'total'           => $item_total,
		);
	}

	$total = max( 0, $subtotal - $discount + $tax );

	$invoice_number = wrmi_invoice_generate_number();
	$invoice_date   = current_time( 'Y-m-d' );

	$booking_end_date = get_post_meta(
		$booking_id,
		'_wrmi_booking_end_date',
		true
	);

	$due_date = $booking_end_date ? $booking_end_date : null;

	$invoice_table       = wrmi_get_invoice_table_name();
	$invoice_items_table = wrmi_get_invoice_items_table_name();

	/*
	 * Mulai transaksi sebelum menyimpan invoice dan item.
	 */
	if ( false === $wpdb->query( 'START TRANSACTION' ) ) {
		return new WP_Error(
			'invoice_transaction_failed',
			'Tidak dapat memulai transaksi database untuk membuat Invoice.'
		);
	}

	$inserted = $wpdb->insert(
		$invoice_table,
		array(
			'booking_id'      => $booking_id,
			'invoice_number'  => $invoice_number,
			'advertiser_id'   => $advertiser_id,
			'billing_company' => sanitize_text_field( $advertiser_company ),
			'billing_contact' => sanitize_text_field( $advertiser_contact ),
			'billing_email'   => sanitize_email( $advertiser_email ),
			'billing_phone'   => sanitize_text_field( $advertiser_phone ),
			'invoice_date'    => $invoice_date,
			'due_date'        => $due_date,
			'status'          => 'draft',
			'subtotal'        => $subtotal,
			'discount'        => $discount,
			'tax'             => $tax,
			'total'           => $total,
			'notes'           => get_post_meta(
				$booking_id,
				'_wrmi_booking_notes',
				true
			),
			'created_at'      => current_time( 'mysql' ),
			'updated_at'      => current_time( 'mysql' ),
		),
		array(
			'%d',
			'%s',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s',
			'%.2f',
			'%.2f',
			'%.2f',
			'%.2f',
			'%s',
			'%s',
			'%s',
		)
	);

	if ( false === $inserted ) {
		$wpdb->query( 'ROLLBACK' );

		return new WP_Error(
			'invoice_insert_failed',
			'Invoice gagal disimpan. Transaksi dibatalkan.'
		);
	}

	$invoice_id = (int) $wpdb->insert_id;

	if ( $invoice_id <= 0 ) {
		$wpdb->query( 'ROLLBACK' );

		return new WP_Error(
			'invoice_insert_failed',
			'ID Invoice tidak valid. Transaksi dibatalkan.'
		);
	}

	/*
	 * Setiap item wajib berhasil disimpan.
	 */
	foreach ( $prepared_items as $item ) {
		$item_inserted = $wpdb->insert(
			$invoice_items_table,
			array(
				'invoice_id'      => $invoice_id,
				'booking_item_id' => $item['booking_item_id'],
				'placement'       => $item['placement'],
				'start_date'      => $item['start_date'],
				'end_date'        => $item['end_date'],
				'quantity'        => $item['quantity'],
				'billing_unit'    => $item['billing_unit'],
				'unit_price'      => $item['unit_price'],
				'discount'        => $item['discount'],
				'tax'             => $item['tax'],
				'total'           => $item['total'],
				'created_at'      => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%d',
				'%s',
				'%s',
				'%s',
				'%.4f',
				'%s',
				'%.2f',
				'%.2f',
				'%.2f',
				'%.2f',
				'%s',
			)
		);

		if ( false === $item_inserted ) {
			$wpdb->query( 'ROLLBACK' );

			return new WP_Error(
				'invoice_item_insert_failed',
				'Item Invoice gagal disimpan. Seluruh transaksi dibatalkan.'
			);
		}
	}

	/*
	 * Data administrasi WordPress juga wajib berhasil dibuat.
	 */
	$invoice_post_id = wp_insert_post(
		array(
			'post_type'   => 'wrmi_invoice',
			'post_status' => 'publish',
			'post_title'  => $invoice_number,
		),
		true
	);

	if (
		is_wp_error( $invoice_post_id )
		|| absint( $invoice_post_id ) <= 0
	) {
		$wpdb->query( 'ROLLBACK' );

		return new WP_Error(
			'invoice_post_failed',
			'Data administrasi Invoice gagal dibuat. Transaksi dibatalkan.'
		);
	}

	/*
	 * Simpan semua data sebelum menulis log aktivitas.
	 */
	if ( false === $wpdb->query( 'COMMIT' ) ) {
		$wpdb->query( 'ROLLBACK' );
		clean_post_cache( absint( $invoice_post_id ) );

		return new WP_Error(
			'invoice_commit_failed',
			'Transaksi Invoice gagal diselesaikan. Periksa database sebelum mencoba kembali.'
		);
	}

	/*
	 * Log hanya setelah transaksi berhasil diselesaikan.
	 */
	if ( function_exists( 'wrmi_log_activity' ) ) {
		wrmi_log_activity(
			'create',
			'invoice',
			$invoice_id,
			sprintf(
				'Membuat invoice %s untuk booking ID %d dengan total %s.',
				$invoice_number,
				$booking_id,
				number_format( (float) $total, 2, '.', '' )
			)
		);
	}

	return $invoice_id;
}

/*
 * =========================================================
 * ADMIN MENU
 * =========================================================
 */

function wrmi_invoice_admin_menu() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Invoice',
		'Invoice',
		'manage_options',
		'wrmi-invoices',
		'wrmi_invoice_admin_page',
		38
	);
}

add_action(
	'admin_menu',
	'wrmi_invoice_admin_menu',
	20
);


/*
 * =========================================================
 * ADMIN PAGE
 * =========================================================
 */

function wrmi_invoice_admin_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;

	$invoice_table       = wrmi_get_invoice_table_name();
	$invoice_items_table = wrmi_get_invoice_items_table_name();

	$invoices = $wpdb->get_results(
		"SELECT *
		FROM {$invoice_table}
		ORDER BY id DESC
		LIMIT 100"
	);

	$bookings = get_posts(
		array(
			'post_type'      => 'wrmi_booking',
			'post_status'    => 'publish',
			'posts_per_page' => 100,
			'orderby'        => 'ID',
			'order'          => 'DESC',
		)
	);

	?>

	<div class="wrap">

		<h1>Invoice</h1>

		<h2>Buat Invoice dari Booking</h2>

		<table class="widefat striped">

			<thead>
				<tr>
					<th>Booking</th>
					<th>Advertiser</th>
					<th>Status Booking</th>
					<th>Total</th>
					<th>Aksi</th>
				</tr>
			</thead>

			<tbody>

				<?php if ( empty( $bookings ) ) : ?>

					<tr>
						<td colspan="5">
							Belum ada Booking.
						</td>
					</tr>

				<?php else : ?>

					<?php foreach ( $bookings as $booking ) : ?>

						<?php

						$booking_id = $booking->ID;

						$existing_invoice = wrmi_get_invoice_by_booking(
							$booking_id
						);

						?>

						<tr>

							<td>
								<strong>
									<?php echo esc_html( wrmi_get_booking_number( $booking_id ) ); ?>
								</strong>
							</td>

							<td>
								<?php echo esc_html( wrmi_get_booking_advertiser_id( $booking_id ) ); ?>
							</td>

							<td>
								<?php

								$status   = wrmi_get_booking_status( $booking_id );
								$statuses = wrmi_booking_get_statuses();

								echo esc_html(
									$statuses[ $status ] ?? $status
								);

								?>
							</td>

							<td>
								Rp <?php echo esc_html( number_format_i18n( wrmi_get_booking_total( $booking_id ), 0 ) ); ?>
							</td>

							<td>

								<?php if ( $existing_invoice ) : ?>

									Invoice:
									<strong>
										<?php echo esc_html( $existing_invoice->invoice_number ); ?>
									</strong>

								<?php else : ?>

									<form
										method="post"
										action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
									>

										<input
											type="hidden"
											name="action"
											value="wrmi_invoice_create"
										>

										<input
											type="hidden"
											name="booking_id"
											value="<?php echo esc_attr( $booking_id ); ?>"
										>

										<?php
										wp_nonce_field(
											'wrmi_invoice_create',
											'wrmi_invoice_nonce'
										);
										?>

										<?php
										submit_button(
											'Buat Invoice',
											'secondary',
											'submit',
											false
										);
										?>

									</form>

								<?php endif; ?>

							</td>

						</tr>

					<?php endforeach; ?>

				<?php endif; ?>

			</tbody>

		</table>

		<hr>

		<h2>Daftar Invoice</h2>

		<table class="widefat striped">

			<thead>
				<tr>
					<th>No. Invoice</th>
					<th>Booking</th>
					<th>Advertiser</th>
					<th>Tanggal</th>
					<th>Jatuh Tempo</th>
								<th>Status</th>
								<th>Total</th>
								<th>Aksi</th>
				</tr>
			</thead>

			<tbody>

				<?php if ( empty( $invoices ) ) : ?>

					<tr>
						<td colspan="8">Belum ada Invoice.</td>
					</tr>

				<?php else : ?>

					<?php foreach ( $invoices as $invoice ) : ?>

						<tr>

							<td>
								<strong>
									<?php echo esc_html( $invoice->invoice_number ); ?>
								</strong>
							</td>

							<td>
								<?php echo esc_html( wrmi_get_booking_number( $invoice->booking_id ) ); ?>
							</td>

							<td>
								<?php echo esc_html( $invoice->billing_company ); ?>
							</td>

							<td>
								<?php echo esc_html( $invoice->invoice_date ); ?>
							</td>

							<td>
								<?php echo esc_html( $invoice->due_date ? $invoice->due_date : '-' ); ?>
							</td>

							<td>
								<?php

								$invoice_statuses = wrmi_invoice_get_statuses();

								echo esc_html(
									$invoice_statuses[ $invoice->status ] ?? $invoice->status
								);

								?>
							</td>

							<td>
								Rp <?php echo esc_html( number_format_i18n( (float) $invoice->total, 0 ) ); ?>
							</td>
<td>
    <?php if ( 'draft' === $invoice->status ) : ?>
        <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" style="display:inline-block;margin-right:5px;">
            <input type="hidden" name="action" value="wrmi_invoice_issue">
            <input type="hidden" name="invoice_id" value="<?php echo esc_attr( absint( $invoice->id ) ); ?>">
            <?php wp_nonce_field( 'wrmi_invoice_issue_' . absint( $invoice->id ), 'wrmi_invoice_issue_nonce' ); ?>
            <button type="submit" class="button button-primary">Terbitkan</button>
        </form>
    <?php else : ?>
        <a class="button" target="_blank" rel="noopener" href="<?php
            echo esc_url(
                wp_nonce_url(
                    add_query_arg(
                        array(
                            'action' => 'wrmi_invoice_print',
                            'invoice_id' => absint( $invoice->id ),
                        ),
                        admin_url( 'admin-post.php' )
                    ),
                    'wrmi_invoice_print_' . absint( $invoice->id )
                )
            );
        ?>">Cetak</a>
    <?php endif; ?>
</td>
								</tr>

					<?php endforeach; ?>

				<?php endif; ?>

			</tbody>

		</table>

	</div>

	<?php
}


/*
 * =========================================================
 * ADMIN ACTION
 * =========================================================
 */

function wrmi_invoice_create_action() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Anda tidak memiliki izin.' );
	}

	if (
		empty( $_POST['wrmi_invoice_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_POST['wrmi_invoice_nonce'] )
			),
			'wrmi_invoice_create'
		)
	) {
		wp_die( 'Permintaan tidak valid.' );
	}

	$booking_id = isset( $_POST['booking_id'] )
		? absint( $_POST['booking_id'] )
		: 0;

	$result = wrmi_invoice_create_from_booking(
		$booking_id
	);

	if ( is_wp_error( $result ) ) {
		wp_die(
			esc_html(
				$result->get_error_message()
			)
		);
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'wrmi-invoices',
				'message' => 'created',
			),
			admin_url( 'admin.php' )
		)
	);

	exit;
}

add_action(
	'admin_post_wrmi_invoice_create',
	'wrmi_invoice_create_action'
);

/* =========================================================
 * AKSI TERBITKAN INVOICE
 * ========================================================= */
function wrmi_invoice_issue_action() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Anda tidak memiliki izin untuk menerbitkan invoice.' );
    }

    $invoice_id = isset( $_POST['invoice_id'] ) ? absint( $_POST['invoice_id'] ) : 0;

    if ( $invoice_id <= 0 ) {
        wp_die( 'ID invoice tidak valid.' );
    }

    check_admin_referer(
        'wrmi_invoice_issue_' . $invoice_id,
        'wrmi_invoice_issue_nonce'
    );

    global $wpdb;
    $table = wrmi_get_invoice_table_name();

    $invoice = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT id, invoice_number, status FROM {$table} WHERE id = %d",
            $invoice_id
        )
    );

    if ( ! $invoice ) {
        wp_die( 'Invoice tidak ditemukan.' );
    }

    if ( 'draft' !== $invoice->status ) {
        wp_die( 'Hanya invoice Draft yang dapat diterbitkan.' );
    }

    $result = $wpdb->update(
        $table,
        array(
            'status'     => 'issued',
            'updated_at' => current_time( 'mysql' ),
        ),
        array(
            'id'     => $invoice_id,
            'status' => 'draft',
        ),
        array( '%s', '%s' ),
        array( '%d', '%s' )
    );

    if ( false === $result || 1 !== $result ) {
        wp_die( 'Invoice gagal diterbitkan. Muat ulang halaman dan periksa statusnya.' );
    }

    if ( function_exists( 'wrmi_log_activity' ) ) {
        wrmi_log_activity(
            'issue',
            'invoice',
            $invoice_id,
            sprintf( 'Menerbitkan invoice %s.', $invoice->invoice_number )
        );
    }

    wp_safe_redirect( admin_url( 'admin.php?page=wrmi-invoices&message=issued' ) );
    exit;
}
add_action( 'admin_post_wrmi_invoice_issue', 'wrmi_invoice_issue_action' );


/* =========================================================
 * HALAMAN CETAK INVOICE
 * ========================================================= */
function wrmi_invoice_print_action() {
    if ( ! current_user_can( 'manage_options' ) ) {
        wp_die( 'Anda tidak memiliki izin untuk mencetak invoice.' );
    }

    $invoice_id = isset( $_GET['invoice_id'] ) ? absint( $_GET['invoice_id'] ) : 0;

    if ( $invoice_id <= 0 ) {
        wp_die( 'ID invoice tidak valid.' );
    }

    check_admin_referer( 'wrmi_invoice_print_' . $invoice_id );

    global $wpdb;
    $table = wrmi_get_invoice_table_name();

    $invoice = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT * FROM {$table} WHERE id = %d",
            $invoice_id
        )
    );

    if ( ! $invoice ) {
        wp_die( 'Invoice tidak ditemukan.' );
    }

    if ( 'draft' === $invoice->status ) {
        wp_die( 'Invoice Draft belum dapat dicetak.' );
    }

    $items = wrmi_get_invoice_items( $invoice_id );
    $statuses = wrmi_invoice_get_statuses();
    $status_label = isset( $statuses[ $invoice->status ] )
        ? $statuses[ $invoice->status ]
        : $invoice->status;

    nocache_headers();
    header( 'Content-Type: text/html; charset=' . get_bloginfo( 'charset' ) );
    ?>
    <!DOCTYPE html>
    <html <?php language_attributes(); ?>>
    <head>
        <meta charset="<?php bloginfo( 'charset' ); ?>">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?php echo esc_html( 'Invoice ' . $invoice->invoice_number ); ?></title>
        <style>
            body{font-family:Arial,sans-serif;color:#222;margin:32px auto;max-width:900px;padding:0 20px}
            h1{margin-bottom:5px}
            .status{display:inline-block;border:2px solid #333;padding:8px 16px;font-weight:bold;margin:12px 0}
            table{width:100%;border-collapse:collapse;margin-top:22px}
            th,td{border:1px solid #bbb;padding:9px;text-align:left}
            th{background:#f2f2f2}
            .num{text-align:right;white-space:nowrap}
            .total{font-size:18px;font-weight:bold;text-align:right;margin-top:18px}
            .actions{margin:20px 0}
            @media print{body{margin:0;max-width:none;padding:0}.actions{display:none}th{background:#eee!important;-webkit-print-color-adjust:exact;print-color-adjust:exact}}
        </style>
    </head>
    <body>
        <div class="actions">
            <button type="button" onclick="window.print()">Cetak / Simpan PDF</button>
        </div>

        <h1>INVOICE</h1>
        <p><strong><?php echo esc_html( $invoice->invoice_number ); ?></strong></p>
        <div class="status"><?php echo esc_html( strtoupper( $status_label ) ); ?></div>

        <p>
            <strong>Ditagihkan kepada:</strong><br>
            <?php echo esc_html( $invoice->billing_company ); ?><br>
            <?php echo esc_html( $invoice->billing_contact ); ?><br>
            <?php echo esc_html( $invoice->billing_email ); ?><br>
            <?php echo esc_html( $invoice->billing_phone ); ?>
        </p>

        <p>
            Tanggal invoice: <?php echo esc_html( $invoice->invoice_date ); ?><br>
            Jatuh tempo: <?php echo esc_html( $invoice->due_date ? $invoice->due_date : '-' ); ?>
        </p>

        <table>
            <thead>
                <tr>
                    <th>Deskripsi / Penempatan</th>
                    <th>Periode</th>
                    <th class="num">Qty</th>
                    <th class="num">Harga</th>
                    <th class="num">Diskon</th>
                    <th class="num">Pajak</th>
                    <th class="num">Jumlah</th>
                </tr>
            </thead>
            <tbody>
                <?php if ( ! empty( $items ) ) : ?>
                    <?php foreach ( $items as $item ) : ?>
                        <tr>
                            <td><?php echo esc_html( $item->placement ); ?></td>
                            <td><?php echo esc_html( $item->start_date . ' - ' . $item->end_date ); ?></td>
                            <td class="num"><?php echo esc_html( $item->quantity ); ?></td>
                            <td class="num">Rp <?php echo esc_html( number_format_i18n( (float) $item->unit_price, 0 ) ); ?></td>
                            <td class="num">Rp <?php echo esc_html( number_format_i18n( (float) $item->discount, 0 ) ); ?></td>
                            <td class="num">Rp <?php echo esc_html( number_format_i18n( (float) $item->tax, 0 ) ); ?></td>
                            <td class="num">Rp <?php echo esc_html( number_format_i18n( (float) $item->total, 0 ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                <?php else : ?>
                    <tr><td colspan="7">Tidak ada rincian item invoice.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>

        <p>Subtotal: Rp <?php echo esc_html( number_format_i18n( (float) $invoice->subtotal, 0 ) ); ?></p>
        <p>Diskon: Rp <?php echo esc_html( number_format_i18n( (float) $invoice->discount, 0 ) ); ?></p>
        <p>Pajak: Rp <?php echo esc_html( number_format_i18n( (float) $invoice->tax, 0 ) ); ?></p>
        <p class="total">TOTAL: Rp <?php echo esc_html( number_format_i18n( (float) $invoice->total, 0 ) ); ?></p>

        <?php if ( 'paid' === $invoice->status ) : ?>
            <p class="status">LUNAS</p>
        <?php elseif ( 'cancelled' === $invoice->status ) : ?>
            <p class="status">DIBATALKAN</p>
        <?php endif; ?>

        <?php if ( ! empty( $invoice->notes ) ) : ?>
            <p><strong>Catatan:</strong><br><?php echo nl2br( esc_html( $invoice->notes ) ); ?></p>
        <?php endif; ?>
    </body>
    </html>
    <?php
    exit;
}
add_action( 'admin_post_wrmi_invoice_print', 'wrmi_invoice_print_action' );
