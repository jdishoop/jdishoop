<?php
/**
 * WartaRadar Manajemen Iklan
 * Payment Module V1
 *
 * @package WartaRadar_Manajemen_Iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* =========================================================
 * PAYMENT STATUS
 * ========================================================= */

function wrmi_payment_get_statuses() {

	return array(
		'pending'   => 'Menunggu Pembayaran',
		'paid'      => 'Sudah Dibayar',
		'failed'    => 'Gagal',
		'cancelled' => 'Dibatalkan',
		'refunded'  => 'Dikembalikan',
	);
}


/* =========================================================
 * PAYMENT TABLE
 * ========================================================= */

function wrmi_get_payment_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'wrmi_payments';
}


function wrmi_create_payment_table() {

	global $wpdb;

	$table_name      = wrmi_get_payment_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		invoice_id bigint(20) unsigned NOT NULL,
		booking_id bigint(20) unsigned NOT NULL,
		advertiser_id bigint(20) unsigned NOT NULL,
		payment_number varchar(100) NOT NULL,
		payment_date date NOT NULL,
		payment_method varchar(100) NOT NULL DEFAULT '',
		reference_number varchar(255) NOT NULL DEFAULT '',
		amount decimal(20,2) NOT NULL DEFAULT 0.00,
		status varchar(30) NOT NULL DEFAULT 'pending',
		notes text NULL,
		created_at datetime NOT NULL,
		updated_at datetime NOT NULL,
		created_by bigint(20) unsigned NOT NULL DEFAULT 0,
		proof_file varchar(255) NOT NULL DEFAULT '',
		proof_original_name varchar(255) NOT NULL DEFAULT '',
		proof_mime varchar(100) NOT NULL DEFAULT '',
		proof_size bigint(20) unsigned NOT NULL DEFAULT 0,
		proof_uploaded_by bigint(20) unsigned NOT NULL DEFAULT 0,
		proof_uploaded_at datetime NULL,
		approved_by bigint(20) unsigned NOT NULL DEFAULT 0,
		approved_at datetime NULL,
		cancelled_by bigint(20) unsigned NOT NULL DEFAULT 0,
		cancelled_at datetime NULL,
		PRIMARY KEY  (id),
		UNIQUE KEY payment_number (payment_number),
		KEY invoice_id (invoice_id),
		KEY booking_id (booking_id),
		KEY advertiser_id (advertiser_id),
		KEY payment_date (payment_date),
		KEY status (status),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql );
}


function wrmi_ensure_payment_table() {

	global $wpdb;

	$table_name = wrmi_get_payment_table_name();

	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		)
	);

	/*
	 * Jalankan dbDelta juga pada tabel yang sudah ada agar kolom
	 * audit pembayaran dapat ditambahkan tanpa menghapus data lama.
	 */
	wrmi_create_payment_table();
}


/* =========================================================
 * PAYMENT NUMBER
 * ========================================================= */

function wrmi_payment_generate_number() {

	$period = current_time( 'Y-m' );
	$option = 'wrmi_payment_sequence_' . $period;

	$sequence = absint(
		get_option(
			$option,
			0
		)
	);

	$sequence++;

	update_option(
		$option,
		$sequence,
		false
	);

	return sprintf(
		'PAY-%s-%04d',
		str_replace( '-', '', $period ),
		$sequence
	);
}


/* =========================================================
 * PAYMENT HELPERS
 * ========================================================= */

function wrmi_get_payment( $payment_id ) {

	global $wpdb;

	$table_name = wrmi_get_payment_table_name();

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE id = %d
			LIMIT 1",
			absint( $payment_id )
		)
	);
}


function wrmi_get_payment_status( $payment_id ) {

	$payment = wrmi_get_payment( $payment_id );

	$statuses = wrmi_payment_get_statuses();

	if ( ! $payment || ! isset( $statuses[ $payment->status ] ) ) {
		return 'pending';
	}

	return $payment->status;
}


function wrmi_get_invoice_for_payment( $invoice_id ) {

	global $wpdb;

	if ( function_exists( 'wrmi_get_invoice_table_name' ) ) {
		$table_name = wrmi_get_invoice_table_name();
	} else {
		$table_name = $wpdb->prefix . 'wrmi_invoices';
	}

	return $wpdb->get_row(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE id = %d
			LIMIT 1",
			absint( $invoice_id )
		)
	);
}


function wrmi_payment_get_paid_total( $invoice_id, $exclude_payment_id = 0 ) {

	global $wpdb;

	$table_name = wrmi_get_payment_table_name();

	$sql = "
		SELECT COALESCE(SUM(amount), 0)
		FROM {$table_name}
		WHERE invoice_id = %d
		  AND status = %s
	";

	$values = array(
		absint( $invoice_id ),
		'paid',
	);

	if ( $exclude_payment_id > 0 ) {
		$sql .= ' AND id != %d';
		$values[] = absint( $exclude_payment_id );
	}

	return (float) $wpdb->get_var(
		$wpdb->prepare(
			$sql,
			$values
		)
	);
}


function wrmi_payment_get_balance( $invoice_id ) {

	$invoice = wrmi_get_invoice_for_payment( $invoice_id );

	if ( ! $invoice ) {
		return 0;
	}

	$total = (float) $invoice->total;
	$paid  = wrmi_payment_get_paid_total( $invoice_id );

	$balance = $total - $paid;

	return max(
		0,
		$balance
	);
}


/* =========================================================
 * PAYMENT VALIDATION
 * ========================================================= */

function wrmi_payment_validate_invoice( $invoice_id ) {

	$invoice = wrmi_get_invoice_for_payment( $invoice_id );

	if ( ! $invoice ) {
		return new WP_Error(
			'invoice_not_found',
			'Invoice tidak ditemukan.'
		);
	}

	if ( ! in_array(
		$invoice->status,
		array( 'issued', 'sent', 'overdue' ),
		true
	) ) {
		return new WP_Error(
			'invoice_not_payable',
			'Invoice belum diterbitkan atau tidak dapat dibayar.'
		);
	}

	$booking_id = absint( $invoice->booking_id );

	if ( $booking_id <= 0 ) {
		return new WP_Error(
			'booking_not_found',
			'Booking pada Invoice tidak valid.'
		);
	}

	$booking_status = function_exists( 'wrmi_get_booking_status' )
		? wrmi_get_booking_status( $booking_id )
		: get_post_meta(
			$booking_id,
			'_wrmi_booking_status',
			true
		);

	if ( in_array(
		$booking_status,
		array(
			'cancelled',
			'expired',
		),
		true
	) ) {
		return new WP_Error(
			'booking_not_payable',
			'Booking sudah dibatalkan atau kedaluwarsa.'
		);
	}

	if ( (float) $invoice->total <= 0 ) {
		return new WP_Error(
			'invalid_invoice_total',
			'Total Invoice tidak valid.'
		);
	}

	$balance = wrmi_payment_get_balance( $invoice_id );

	if ( $balance <= 0 ) {
		return new WP_Error(
			'invoice_already_paid',
			'Invoice sudah lunas.'
		);
	}

	return $invoice;
}


/* =========================================================
 * PAYMENT LOG
 * ========================================================= */

function wrmi_payment_log_activity(
	$action,
	$payment_id,
	$message = ''
) {

	if ( ! function_exists( 'wrmi_log_activity' ) ) {
		return;
	}

	$payment_id = absint( $payment_id );

	/*
	 * Mengikuti pola log modul Manajemen Iklan.
	 */
	wrmi_log_activity(
		$action,
		'payment',
		$payment_id,
		$message
	);
}


/* =========================================================
 * UPDATE INVOICE STATUS
 * ========================================================= */

function wrmi_payment_update_invoice_status(
	$invoice_id,
	$status
) {

	global $wpdb;

	$invoice_id = absint( $invoice_id );

	if ( $invoice_id <= 0 ) {
		return false;
	}

	$allowed_statuses = array(
		'draft',
		'issued',
		'sent',
		'paid',
		'overdue',
		'cancelled',
	);

	if ( ! in_array(
		$status,
		$allowed_statuses,
		true
	) ) {
		return false;
	}

	if ( function_exists( 'wrmi_get_invoice_table_name' ) ) {
		$table_name = wrmi_get_invoice_table_name();
	} else {
		$table_name = $wpdb->prefix . 'wrmi_invoices';
	}

	return false !== $wpdb->update(
		$table_name,
		array(
			'status'     => $status,
			'updated_at' => current_time( 'mysql' ),
		),
		array(
			'id' => $invoice_id,
		),
		array(
			'%s',
			'%s',
		),
		array(
			'%d',
		)
	);
}


/* =========================================================
 * UPDATE BOOKING STATUS
 * ========================================================= */

function wrmi_payment_update_booking_status(
	$booking_id,
	$status
) {

	$booking_id = absint( $booking_id );

	if ( $booking_id <= 0 ) {
		return false;
	}

	$allowed_statuses = array(
		'draft',
		'pending_payment',
		'paid',
		'confirmed',
		'active',
		'completed',
		'cancelled',
		'expired',
	);

	if ( ! in_array(
		$status,
		$allowed_statuses,
		true
	) ) {
		return false;
	}

	return update_post_meta(
		$booking_id,
		'_wrmi_booking_status',
		$status
	);
}


/* =========================================================
 * PAYMENT CREATE
 * ========================================================= */

function wrmi_payment_create(
	$invoice_id,
	$amount,
	$payment_method = '',
	$reference_number = '',
	$payment_date = '',
	$notes = ''
) {

	global $wpdb;

	wrmi_ensure_payment_table();

	$invoice_id = absint( $invoice_id );
	$amount     = (float) $amount;

	$invoice_validation = wrmi_payment_validate_invoice(
		$invoice_id
	);

	if ( is_wp_error( $invoice_validation ) ) {
		return $invoice_validation;
	}

	$invoice = $invoice_validation;

	if ( $amount <= 0 ) {
		return new WP_Error(
			'invalid_payment_amount',
			'Nominal pembayaran harus lebih besar dari 0.'
		);
	}

	$balance = wrmi_payment_get_balance( $invoice_id );

	if ( $amount > $balance ) {
		return new WP_Error(
			'payment_exceeds_balance',
			'Nominal pembayaran melebihi sisa tagihan Invoice.'
		);
	}

	if ( '' === $payment_date ) {
		$payment_date = current_time( 'Y-m-d' );
	}

	$payment_date = sanitize_text_field(
		$payment_date
	);

	$payment_method = sanitize_text_field(
		$payment_method
	);

	$reference_number = sanitize_text_field(
		$reference_number
	);

	$notes = sanitize_textarea_field(
		$notes
	);

	$payment_number = wrmi_payment_generate_number();

	$now = current_time( 'mysql' );

	$result = $wpdb->insert(
		wrmi_get_payment_table_name(),
		array(
			'invoice_id'       => $invoice_id,
			'booking_id'       => absint( $invoice->booking_id ),
			'advertiser_id'    => absint( $invoice->advertiser_id ),
			'payment_number'   => $payment_number,
			'payment_date'     => $payment_date,
			'payment_method'   => $payment_method,
			'reference_number'=> $reference_number,
			'amount'           => $amount,
			'status'           => 'pending',
			'notes'            => $notes,
			'created_at'       => $now,
			'updated_at'       => $now,
			'created_by'       => get_current_user_id(),
		),
		array(
			'%d',
			'%d',
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%f',
			'%s',
			'%s',
			'%s',
			'%s',
			'%d',
		)
	);

	if ( false === $result ) {
		return new WP_Error(
			'payment_create_failed',
			'Payment gagal dibuat.'
		);
	}

	$payment_id = absint(
		$wpdb->insert_id
	);

	wrmi_payment_log_activity(
		'payment_created',
		$payment_id,
		'Payment ' . $payment_number . ' dibuat dengan status pending.'
	);

	return $payment_id;
}


/* =========================================================
 * MARK PAYMENT PAID
 * ========================================================= */
function wrmi_payment_mark_paid( $payment_id ) {

	global $wpdb;

	$payment_id = absint( $payment_id );

	$payment = wrmi_get_payment( $payment_id );

	if ( ! $payment ) {
		return new WP_Error(
			'payment_not_found',
			'Payment tidak ditemukan.'
		);
	}

	// Pengendali Keuangan wajib memiliki bukti transfer.
	// Administrator boleh mengesahkan tanpa bukti.
	if (
		current_user_can( 'wrmi_manage_payment' )
		&& ! current_user_can( 'manage_options' )
		&& empty( $payment->proof_file )
	) {
		return new WP_Error(
			'payment_proof_required',
			'Bukti transfer wajib diunggah sebelum Pengendali Keuangan mengesahkan pembayaran.'
		);
	}

	if ( 'paid' === $payment->status ) {
		return new WP_Error(
			'payment_already_paid',
			'Payment sudah berstatus paid.'
		);
	}

	if ( 'pending' !== $payment->status ) {
		return new WP_Error(
			'payment_status_invalid',
			'Payment hanya dapat disahkan jika berstatus pending.'
		);
	}

	if ( in_array(
		$payment->status,
		array(
			'cancelled',
			'failed',
			'refunded',
		),
		true
	) ) {
		return new WP_Error(
			'payment_not_payable',
			'Payment tidak dapat ditandai sebagai paid.'
		);
	}

	$invoice = wrmi_get_invoice_for_payment(
		$payment->invoice_id
	);

	if ( ! $invoice ) {
		return new WP_Error(
			'invoice_not_found',
			'Invoice tidak ditemukan.'
		);
	}

	if ( 'cancelled' === $invoice->status ) {
		return new WP_Error(
			'invoice_cancelled',
			'Invoice sudah dibatalkan.'
		);
	}

	$paid_before = wrmi_payment_get_paid_total(
		$payment->invoice_id,
		$payment_id
	);

	$invoice_total  = (float) $invoice->total;
	$payment_amount = (float) $payment->amount;

	if ( ( $paid_before + $payment_amount ) > $invoice_total ) {
		return new WP_Error(
			'payment_exceeds_invoice',
			'Total pembayaran akan melebihi total Invoice.'
		);
	}

	$updated = $wpdb->update(
		wrmi_get_payment_table_name(),
		array(
			'status'      => 'paid',
			'updated_at'  => current_time( 'mysql' ),
			'approved_by' => get_current_user_id(),
			'approved_at' => current_time( 'mysql' ),
		),
		array(
			'id' => $payment_id,
		),
		array(
			'%s',
			'%s',
			'%d',
			'%s',
		),
		array(
			'%d',
		)
	);

	if ( false === $updated ) {
		return new WP_Error(
			'payment_update_failed',
			'Status Payment gagal diperbarui.'
		);
	}

	$total_paid = $paid_before + $payment_amount;

	/*
	 * Invoice hanya menjadi paid jika seluruh nominal
	 * Invoice sudah tertutup oleh Payment berstatus paid.
	 */
	if ( $total_paid >= $invoice_total ) {

		wrmi_payment_update_invoice_status(
			$payment->invoice_id,
			'paid'
		);

		/*
		 * Setelah Invoice lunas:
		 *
		 * Payment = PAID
		 * Invoice = PAID
		 * Booking = PAID
		 * Booking = CONFIRMED
		 *
		 * Payment tidak mengaktifkan Campaign atau Ad.
		 */
		wrmi_payment_update_booking_status(
			$payment->booking_id,
			'paid'
		);

		wrmi_payment_update_booking_status(
			$payment->booking_id,
			'confirmed'
		);

		wrmi_payment_log_activity(
			'invoice_paid',
			$payment_id,
			'Invoice ' . $payment->invoice_id . ' lunas. Booking dikonfirmasi.'
		);

	} else {

		/*
		 * Partial payment:
		 *
		 * Payment = PAID
		 * Invoice = belum lunas
		 * Booking = TIDAK diubah menjadi paid/confirmed.
		 *
		 * Booking tetap mengikuti status sebelumnya,
		 * umumnya pending_payment.
		 */
		wrmi_payment_log_activity(
			'payment_partial_paid',
			$payment_id,
			'Payment diterima sebagai pembayaran sebagian. Invoice masih memiliki saldo.'
		);
	}

	wrmi_payment_log_activity(
		'payment_paid',
		$payment_id,
		'Payment ditandai sebagai paid.'
	);

	return true;
}
/* =========================================================
 * CANCEL PAYMENT
 * ========================================================= */

function wrmi_payment_cancel( $payment_id ) {

	global $wpdb;

	$payment_id = absint( $payment_id );

	$payment = wrmi_get_payment( $payment_id );

	if ( ! $payment ) {
		return new WP_Error(
			'payment_not_found',
			'Payment tidak ditemukan.'
		);
	}

	if ( 'paid' === $payment->status ) {
		return new WP_Error(
			'payment_already_paid',
			'Payment yang sudah paid tidak dapat dibatalkan langsung.'
		);
	}

	if ( 'cancelled' === $payment->status ) {
		return true;
	}

	$updated = $wpdb->update(
		wrmi_get_payment_table_name(),
		array(
			'status'       => 'cancelled',
			'updated_at'   => current_time( 'mysql' ),
			'cancelled_by' => get_current_user_id(),
			'cancelled_at' => current_time( 'mysql' ),
		),
		array(
			'id' => $payment_id,
		),
		array(
			'%s',
			'%s',
			'%d',
			'%s',
		),
		array(
			'%d',
		)
	);

	if ( false === $updated ) {
		return new WP_Error(
			'payment_cancel_failed',
			'Payment gagal dibatalkan.'
		);
	}

	wrmi_payment_log_activity(
		'payment_cancelled',
		$payment_id,
		'Payment dibatalkan.'
	);

	return true;
}


/* =========================================================
 * PAYMENT POST TYPE
 * ========================================================= */

function wrmi_register_payment_post_type() {

	register_post_type(
		'wrmi_payment',
		array(
			'labels' => array(
				'name'          => 'Payment',
				'singular_name' => 'Payment',
			),
			'public'             => false,
			'show_ui'            => false,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'has_archive'        => false,
			'supports'           => array(),
			'map_meta_cap'       => true,
		)
	);
}


/* =========================================================
 * ADMIN MENU
 * ========================================================= */

function wrmi_payment_admin_menu() {

	if ( current_user_can( 'manage_options' ) ) {
		add_submenu_page(
			'wrmi-dashboard',
			'Payment',
			'Payment',
			'manage_options',
			'wrmi-payment',
			'wrmi_payment_admin_page'
		);
		return;
	}

	if ( current_user_can( 'wrmi_manage_payment' ) ) {
		add_menu_page(
			'Pembayaran',
			'Pembayaran',
			'wrmi_manage_payment',
			'wrmi-payment',
			'wrmi_payment_admin_page',
			'dashicons-money-alt',
			27
		);
	}
}



/* =========================================================
 * PAYMENT PROOF: PRIVATE STORAGE
 * ========================================================= */

function wrmi_payment_private_dir() {
    $dir = 'D:/xampp/wrmi-private-payments';
    $root = wp_normalize_path( ABSPATH );
    $normalized = trailingslashit( wp_normalize_path( $dir ) );

    if ( strpos( $normalized, trailingslashit( $root ) ) === 0 ) {
        return new WP_Error( 'proof_storage_public', 'Direktori bukti tidak boleh berada di dalam web root.' );
    }

    if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
        return new WP_Error( 'proof_storage_create', 'Direktori bukti privat gagal dibuat.' );
    }

    if ( ! is_dir( $dir ) || ! is_writable( $dir ) ) {
        return new WP_Error( 'proof_storage_unavailable', 'Direktori bukti privat tidak tersedia atau tidak dapat ditulis.' );
    }

    return $dir;
}

function wrmi_payment_store_proof( $payment_id ) {
    global $wpdb;

    $payment_id = absint( $payment_id );

    if ( ! $payment_id ) {
        return new WP_Error( 'proof_payment_invalid', 'ID payment tidak valid.' );
    }

    $table = $wpdb->prefix . 'wrmi_payments';
    $payment = $wpdb->get_row(
        $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d LIMIT 1", $payment_id )
    );

    if ( ! $payment ) {
        return new WP_Error( 'proof_payment_missing', 'Payment tidak ditemukan.' );
    }

    if ( 'pending' !== $payment->status ) {
        return new WP_Error(
            'proof_payment_status_invalid',
            'Bukti hanya dapat diunggah untuk payment berstatus pending.'
        );
    }

    if ( ! isset( $_FILES['payment_proof'] ) || ! is_array( $_FILES['payment_proof'] ) ) {
        return new WP_Error( 'proof_missing', 'Pilih berkas bukti transfer terlebih dahulu.' );
    }

    $upload = $_FILES['payment_proof'];

    if ( ! isset( $upload['error'], $upload['tmp_name'], $upload['name'], $upload['size'] ) ||
         (int) $upload['error'] !== UPLOAD_ERR_OK ) {
        return new WP_Error( 'proof_upload_error', 'Unggah gagal. Periksa berkas dan coba lagi.' );
    }

    if ( ! is_uploaded_file( $upload['tmp_name'] ) ) {
        return new WP_Error( 'proof_upload_invalid', 'Berkas unggahan tidak valid.' );
    }

    $max_size = 5 * 1024 * 1024;

    if ( (int) $upload['size'] < 1 || (int) $upload['size'] > $max_size ) {
        return new WP_Error( 'proof_size_invalid', 'Ukuran bukti harus lebih dari 0 dan maksimal 5 MB.' );
    }

    $original_name = sanitize_file_name( wp_unslash( $upload['name'] ) );
    $extension = strtolower( pathinfo( $original_name, PATHINFO_EXTENSION ) );
    $allowed = array(
        'pdf'  => 'application/pdf',
        'jpg'  => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png'  => 'image/png',
    );

    if ( ! isset( $allowed[ $extension ] ) ) {
        return new WP_Error( 'proof_extension_invalid', 'Format bukti hanya boleh PDF, JPG, JPEG, atau PNG.' );
    }

    if ( ! function_exists( 'finfo_open' ) ) {
        return new WP_Error( 'proof_mime_unavailable', 'Validasi MIME tidak tersedia di PHP. Aktifkan ekstensi fileinfo.' );
    }

    $finfo = finfo_open( FILEINFO_MIME_TYPE );
    $actual_mime = $finfo ? finfo_file( $finfo, $upload['tmp_name'] ) : false;

    if ( $finfo ) {
        finfo_close( $finfo );
    }

    if ( ! $actual_mime || $actual_mime !== $allowed[ $extension ] ) {
        return new WP_Error( 'proof_mime_invalid', 'Isi berkas tidak sesuai dengan ekstensi yang diizinkan.' );
    }

    $dir = wrmi_payment_private_dir();

    if ( is_wp_error( $dir ) ) {
        return $dir;
    }

    try {
        $random_name = bin2hex( random_bytes( 24 ) ) . '.' . $extension;
    } catch ( Exception $e ) {
        return new WP_Error( 'proof_random_failed', 'Nama berkas aman gagal dibuat.' );
    }

    $destination = trailingslashit( $dir ) . $random_name;

    if ( ! move_uploaded_file( $upload['tmp_name'], $destination ) ) {
        return new WP_Error( 'proof_move_failed', 'Berkas bukti gagal disimpan.' );
    }

    $updated = $wpdb->update(
        $table,
        array(
            'proof_file'          => $random_name,
            'proof_original_name' => $original_name,
            'proof_mime'          => $actual_mime,
            'proof_size'          => (int) $upload['size'],
            'proof_uploaded_by'   => get_current_user_id(),
            'proof_uploaded_at'   => current_time( 'mysql' ),
        ),
        array( 'id' => $payment_id ),
        array( '%s', '%s', '%s', '%d', '%d', '%s' ),
        array( '%d' )
    );

    if ( false === $updated ) {
        @unlink( $destination );
        return new WP_Error( 'proof_db_failed', 'Metadata bukti gagal disimpan ke database.' );
    }

    return true;
}

function wrmi_payment_download_proof() {
    if ( ! is_user_logged_in() ||
         ( ! current_user_can( 'manage_options' ) &&
           ! current_user_can( 'wrmi_manage_payment' ) ) ) {
        wp_die( 'Anda tidak memiliki akses ke bukti pembayaran.', '', array( 'response' => 403 ) );
    }

    $payment_id = absint( $_GET['payment_id'] ?? 0 );

    if ( ! $payment_id ) {
        wp_die( 'ID payment tidak valid.', '', array( 'response' => 400 ) );
    }

    check_admin_referer( 'wrmi_payment_proof_' . $payment_id );

    global $wpdb;
    $table = $wpdb->prefix . 'wrmi_payments';
    $payment = $wpdb->get_row(
        $wpdb->prepare(
            "SELECT proof_file, proof_original_name, proof_mime FROM {$table} WHERE id = %d LIMIT 1",
            $payment_id
        )
    );

    if ( ! $payment || empty( $payment->proof_file ) ) {
        wp_die( 'Bukti pembayaran tidak ditemukan.', '', array( 'response' => 404 ) );
    }

    $filename = basename( $payment->proof_file );
    $dir = wrmi_payment_private_dir();

    if ( is_wp_error( $dir ) ) {
        wp_die( esc_html( $dir->get_error_message() ), '', array( 'response' => 500 ) );
    }

    $path = trailingslashit( $dir ) . $filename;
    $mime_allowed = array( 'application/pdf', 'image/jpeg', 'image/png' );

    if ( ! is_file( $path ) || ! in_array( $payment->proof_mime, $mime_allowed, true ) ) {
        wp_die( 'Berkas bukti tidak tersedia atau tipe berkas tidak diizinkan.', '', array( 'response' => 404 ) );
    }

    nocache_headers();
    header( 'X-Content-Type-Options: nosniff' );
    header( 'Content-Type: ' . $payment->proof_mime );
    header( 'Content-Length: ' . (string) filesize( $path ) );
    header( 'Content-Disposition: attachment; filename="' . sanitize_file_name( $payment->proof_original_name ?: $filename ) . '"' );

    readfile( $path );
    exit;
}

/* =========================================================
 * ADMIN ACTIONS
 * ========================================================= */

function wrmi_payment_admin_actions() {

	if ( ! is_admin() ) {
		return;
	}

	if (
		! current_user_can( 'manage_options' )
		&& ! current_user_can( 'wrmi_manage_payment' )
	) {
		return;
	}

	if ( empty( $_POST['wrmi_payment_action'] ) ) {
		return;
	}

	check_admin_referer(
		'wrmi_payment_action',
		'wrmi_payment_nonce'
	);

	$action = sanitize_key(
		wp_unslash(
			$_POST['wrmi_payment_action']
		)
	);

	if ( 'create' === $action ) {

		$invoice_id = absint(
			$_POST['invoice_id'] ?? 0
		);

		$amount = isset( $_POST['amount'] )
			? (float) wp_unslash( $_POST['amount'] )
			: 0;

		$payment_method = sanitize_text_field(
			wp_unslash(
				$_POST['payment_method'] ?? ''
			)
		);

		$reference_number = sanitize_text_field(
			wp_unslash(
				$_POST['reference_number'] ?? ''
			)
		);

		$payment_date = sanitize_text_field(
			wp_unslash(
				$_POST['payment_date'] ?? ''
			)
		);

		$notes = sanitize_textarea_field(
			wp_unslash(
				$_POST['notes'] ?? ''
			)
		);

		$result = wrmi_payment_create(
			$invoice_id,
			$amount,
			$payment_method,
			$reference_number,
			$payment_date,
			$notes
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
					'page'    => 'wrmi-payment',
					'created' => 1,
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}

	$payment_id = absint(
		$_POST['payment_id'] ?? 0
	);

	if ( 'upload_proof' === $action ) {
		$result = wrmi_payment_store_proof( $payment_id );

		if ( is_wp_error( $result ) ) {
			wp_die( esc_html( $result->get_error_message() ) );
		}

		wp_safe_redirect(
			add_query_arg(
				array( 'page' => 'wrmi-payment', 'proof_uploaded' => 1 ),
				admin_url( 'admin.php' )
			)
		);
		exit;
	}

	if ( 'mark_paid' === $action ) {

		$result = wrmi_payment_mark_paid(
			$payment_id
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
					'page'    => 'wrmi-payment',
					'paid'    => 1,
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}

	if ( 'cancel' === $action ) {

		$result = wrmi_payment_cancel(
			$payment_id
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
					'page'      => 'wrmi-payment',
					'cancelled'  => 1,
				),
				admin_url( 'admin.php' )
			)
		);

		exit;
	}
}


/* =========================================================
 * ADMIN PAGE
 * ========================================================= */

function wrmi_payment_admin_page() {

	if (
		! current_user_can( 'manage_options' )
		&& ! current_user_can( 'wrmi_manage_payment' )
	) {
		wp_die( esc_html__( 'Anda tidak memiliki akses ke modul pembayaran.', 'wartaradar-manajemen-iklan' ) );
	}

	global $wpdb;

	wrmi_ensure_payment_table();

	$payments_table = wrmi_get_payment_table_name();

	$payments = $wpdb->get_results(
		"SELECT *
		FROM {$payments_table}
		ORDER BY id DESC
		LIMIT 100"
	);

	$invoices_table = function_exists( 'wrmi_get_invoice_table_name' )
		? wrmi_get_invoice_table_name()
		: $wpdb->prefix . 'wrmi_invoices';

	$invoices = $wpdb->get_results(
		"SELECT id, invoice_number, advertiser_id, total, status
		FROM {$invoices_table}
		WHERE status IN ('issued', 'sent', 'overdue')
		ORDER BY id DESC
		LIMIT 100"
	);

	$statuses = wrmi_payment_get_statuses();

	?>
	<div class="wrap">

		<h1>Payment</h1>

		<?php if ( isset( $_GET['created'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Payment berhasil dibuat.</p>
			</div>
		<?php endif; ?>
        <?php if ( isset( $_GET['proof_uploaded'] ) && '1' === (string) $_GET['proof_uploaded'] ) : ?>
            <div class="notice notice-success is-dismissible">
                <p>Bukti pembayaran berhasil diunggah.</p>
            </div>
        <?php endif; ?>

		<?php if ( isset( $_GET['paid'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Payment berhasil ditandai sebagai paid.</p>
			</div>
		<?php endif; ?>

		<?php if ( isset( $_GET['cancelled'] ) ) : ?>
			<div class="notice notice-success is-dismissible">
				<p>Payment berhasil dibatalkan.</p>
			</div>
		<?php endif; ?>

		<h2>Catat Payment</h2>

		<form method="post">

			<?php
			wp_nonce_field(
				'wrmi_payment_action',
				'wrmi_payment_nonce'
			);
			?>

			<input
				type="hidden"
				name="wrmi_payment_action"
				value="create"
			>

			<table class="form-table">

				<tr>
					<th scope="row">
						<label for="invoice_id">
							Invoice
						</label>
					</th>

					<td>
						<select
							name="invoice_id"
							id="invoice_id"
							required
						>
							<option value="">
								— Pilih Invoice —
							</option>

							<?php foreach ( $invoices as $invoice ) : ?>

								<?php
								$balance = wrmi_payment_get_balance(
									$invoice->id
								);

								if ( $balance <= 0 ) {
									continue;
								}
								?>

								<option value="<?php echo esc_attr( $invoice->id ); ?>">
									<?php
									echo esc_html(
										$invoice->invoice_number
										. ' — Rp '
										. number_format(
											$balance,
											2,
											',',
											'.'
										)
									);
									?>
								</option>

							<?php endforeach; ?>

						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="amount">
							Jumlah
						</label>
					</th>

					<td>
						<input
							type="number"
							name="amount"
							id="amount"
							min="0.01"
							step="0.01"
							required
						>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="payment_date">
							Tanggal
						</label>
					</th>

					<td>
						<input
							type="date"
							name="payment_date"
							id="payment_date"
							value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
							required
						>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="payment_method">
							Metode Pembayaran
						</label>
					</th>

					<td>
						<select
							name="payment_method"
							id="payment_method"
							class="regular-text"
							required
						>
							<option value="">— Pilih Metode Pembayaran —</option>
							<option value="Transfer Bank">Transfer Bank</option>
							<option value="Tunai">Tunai</option>
							<option value="QRIS">QRIS</option>
							<option value="Virtual Account">Virtual Account</option>
							<option value="Lainnya">Lainnya</option>
						</select>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="reference_number">
							Nomor Referensi
						</label>
					</th>

					<td>
						<input
							type="text"
							name="reference_number"
							id="reference_number"
							class="regular-text"
						>
					</td>
				</tr>

				<tr>
					<th scope="row">
						<label for="notes">
							Catatan
						</label>
					</th>

					<td>
						<textarea
							name="notes"
							id="notes"
							rows="4"
							class="large-text"
						></textarea>
					</td>
				</tr>

			</table>

			<?php submit_button( 'Simpan Payment' ); ?>

		</form>

		<hr>

		<h2>Riwayat Payment</h2>

		<table class="widefat striped">

			<thead>
				<tr>
					<th>ID</th>
					<th>Payment</th>
					<th>Invoice</th>
					<th>Tanggal</th>
					<th>Metode</th>
					<th>Jumlah</th>
					<th>Status</th>
					<th>Aksi</th>
				</tr>
			</thead>

			<tbody>

				<?php if ( empty( $payments ) ) : ?>

					<tr>
						<td colspan="8">
							Belum ada Payment.
						</td>
					</tr>

				<?php else : ?>

					<?php foreach ( $payments as $payment ) : ?>

						<?php
						$invoice = wrmi_get_invoice_for_payment(
							$payment->invoice_id
						);
						?>

						<tr>

							<td>
								<?php echo esc_html( $payment->id ); ?>
							</td>

							<td>
								<strong>
									<?php
									echo esc_html(
										$payment->payment_number
									);
									?>
								</strong>
							</td>

							<td>
								<?php
								echo esc_html(
									$invoice
										? $invoice->invoice_number
										: '#' . $payment->invoice_id
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$payment->payment_date
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$payment->payment_method
								);
								?>
							</td>

							<td>
								Rp
								<?php
								echo esc_html(
									number_format(
										(float) $payment->amount,
										2,
										',',
										'.'
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$statuses[ $payment->status ]
										?? $payment->status
								);
								?>
							</td>

							<td>

								<?php
								if ( ! empty( $payment->proof_file ) ) :
									$proof_url = wp_nonce_url(
										add_query_arg(
											array(
												'action'     => 'wrmi_payment_download_proof',
												'payment_id' => absint( $payment->id ),
											),
											admin_url( 'admin-post.php' )
										),
										'wrmi_payment_proof_' . absint( $payment->id )
									);
								?>
									<p>
										<a href="<?php echo esc_url( $proof_url ); ?>">Lihat/Unduh Bukti</a>
									</p>
								<?php endif; ?>

								<?php if ( 'pending' === $payment->status ) : ?>
									<form method="post" enctype="multipart/form-data" style="margin:8px 0;">
										<?php wp_nonce_field( 'wrmi_payment_action', 'wrmi_payment_nonce' ); ?>
										<input type="hidden" name="wrmi_payment_action" value="upload_proof">
										<input type="hidden" name="payment_id" value="<?php echo esc_attr( $payment->id ); ?>">
										<input type="file" name="payment_proof" accept=".pdf,.jpg,.jpeg,.png" required>
										<button type="submit" class="button">Unggah Bukti</button>
									</form>

									<form
										method="post"
										style="display:inline-block;"
									>

										<?php
										wp_nonce_field(
											'wrmi_payment_action',
											'wrmi_payment_nonce'
										);
										?>

										<input
											type="hidden"
											name="wrmi_payment_action"
											value="mark_paid"
										>

										<input
											type="hidden"
											name="payment_id"
											value="<?php echo esc_attr( $payment->id ); ?>"
										>

										<button
											type="submit"
											class="button button-primary"
										>
											Tandai Paid
										</button>

									</form>

									<form
										method="post"
										style="display:inline-block;"
									>

										<?php
										wp_nonce_field(
											'wrmi_payment_action',
											'wrmi_payment_nonce'
										);
										?>

										<input
											type="hidden"
											name="wrmi_payment_action"
											value="cancel"
										>

										<input
											type="hidden"
											name="payment_id"
											value="<?php echo esc_attr( $payment->id ); ?>"
										>

										<button
											type="submit"
											class="button"
										>
											Batalkan
										</button>

									</form>

								<?php else : ?>

									—

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


/* =========================================================
 * HOOKS
 * ========================================================= */

add_action(
	'init',
	'wrmi_register_payment_post_type'
);

add_action(
	'admin_menu',
	'wrmi_payment_admin_menu',
	30
);

add_action(
	'admin_init',
	'wrmi_payment_admin_actions'
);

add_action(
	'admin_init',
	'wrmi_ensure_payment_table'
);

add_action(
	'admin_post_wrmi_payment_download_proof',
	'wrmi_payment_download_proof'
);