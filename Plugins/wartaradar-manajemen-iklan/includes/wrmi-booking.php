<?php
/**
 * WartaRadar Manajemen Iklan
 * Booking / Order
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/*
 * =========================================================
 * STATUS BOOKING
 * =========================================================
 */

function wrmi_booking_get_statuses() {

	return array(
		'draft'           => 'Draft',
		'pending_payment' => 'Menunggu Pembayaran',
		'paid'            => 'Sudah Dibayar',
		'confirmed'       => 'Dikonfirmasi',
		'active'          => 'Aktif',
		'completed'       => 'Selesai',
		'cancelled'       => 'Dibatalkan',
		'expired'         => 'Kedaluwarsa',
	);
}


/*
 * =========================================================
 * BOOKING ITEMS TABLE
 * =========================================================
 */

function wrmi_get_booking_items_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'wrmi_booking_items';
}


function wrmi_create_booking_items_table() {

	global $wpdb;

	$table_name      = wrmi_get_booking_items_table_name();
	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		booking_id bigint(20) unsigned NOT NULL,
		placement_id bigint(20) unsigned DEFAULT NULL,
		placement varchar(100) NOT NULL DEFAULT '',
		campaign_id bigint(20) unsigned DEFAULT NULL,
		ad_id bigint(20) unsigned DEFAULT NULL,
		start_date date NOT NULL,
		end_date date DEFAULT NULL,
		quantity decimal(20,4) NOT NULL DEFAULT 1.0000,
		unit_price decimal(20,2) NOT NULL DEFAULT 0.00,
		discount decimal(20,2) NOT NULL DEFAULT 0.00,
		tax decimal(20,2) NOT NULL DEFAULT 0.00,
		total decimal(20,2) NOT NULL DEFAULT 0.00,
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		KEY booking_id (booking_id),
		KEY placement_id (placement_id),
		KEY campaign_id (campaign_id),
		KEY ad_id (ad_id),
		KEY start_date (start_date),
		KEY end_date (end_date),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql );
}


function wrmi_ensure_booking_items_table() {

	global $wpdb;

	$table_name = wrmi_get_booking_items_table_name();

	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$table_name
		)
	);

	if ( $table_exists !== $table_name ) {
		wrmi_create_booking_items_table();
	}
}

add_action(
	'admin_init',
	'wrmi_ensure_booking_items_table',
	5
);


/*
 * =========================================================
 * RATE LOOKUP
 *
 * Tarif harus mencakup seluruh periode Booking Item.
 * =========================================================
 */

function wrmi_booking_get_active_rate(
	$placement,
	$start_date,
	$end_date = ''
) {

	global $wpdb;

	$table_name = $wpdb->prefix . 'wrmi_rates';

	$placement = sanitize_key( $placement );
	$start_date = sanitize_text_field( $start_date );
	$end_date = sanitize_text_field( $end_date );

	if ( '' === $placement || '' === $start_date ) {
		return null;
	}

	/*
	 * Jika end_date kosong, tarif cukup berlaku pada start_date.
	 * Jika end_date tersedia, tarif harus berlaku sampai
	 * tanggal tersebut.
	 */
	$rate_end_date = '' !== $end_date
		? $end_date
		: $start_date;

	$sql = "
		SELECT *
		FROM {$table_name}
		WHERE placement = %s
		  AND status = %s
		  AND billing_unit = %s
		  AND valid_from <= %s
		  AND (
				valid_until IS NULL
				OR valid_until >= %s
		  )
		ORDER BY valid_from DESC, id DESC
		LIMIT 1
	";

	$rate = $wpdb->get_row(
		$wpdb->prepare(
			$sql,
			$placement,
			'active',
			'day',
			$start_date,
			$rate_end_date
		)
	);

	return $rate;
}


/*
 * =========================================================
 * BOOKING NUMBER
 * =========================================================
 */

function wrmi_booking_generate_number() {

	$month = current_time( 'Y-m' );

	$option_name = 'wrmi_booking_sequence_' . $month;

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
		'WRMI-%s-%04d',
		str_replace( '-', '', $month ),
		$sequence
	);
}


/*
 * =========================================================
 * BOOKING CPT
 * =========================================================
 */

function wrmi_register_booking_post_type() {

	register_post_type(
		'wrmi_booking',
		array(
			'labels' => array(
				'name'          => 'Booking / Order',
				'singular_name' => 'Booking / Order',
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
	'wrmi_register_booking_post_type'
);


/*
 * =========================================================
 * BOOKING HELPERS
 * =========================================================
 */

function wrmi_get_booking_status( $booking_id ) {

	$status = get_post_meta(
		absint( $booking_id ),
		'_wrmi_booking_status',
		true
	);

	$statuses = wrmi_booking_get_statuses();

	return isset( $statuses[ $status ] )
		? $status
		: 'draft';
}


function wrmi_get_booking_number( $booking_id ) {

	return get_post_meta(
		absint( $booking_id ),
		'_wrmi_booking_number',
		true
	);
}


function wrmi_get_booking_advertiser_id( $booking_id ) {

	return absint(
		get_post_meta(
			absint( $booking_id ),
			'_wrmi_booking_advertiser_id',
			true
		)
	);
}


function wrmi_get_booking_campaign_id( $booking_id ) {

	return absint(
		get_post_meta(
			absint( $booking_id ),
			'_wrmi_booking_campaign_id',
			true
		)
	);
}


function wrmi_get_booking_total( $booking_id ) {

	return (float) get_post_meta(
		absint( $booking_id ),
		'_wrmi_booking_total',
		true
	);
}


/*
 * =========================================================
 * BOOKING ITEMS
 * =========================================================
 */

function wrmi_get_booking_items( $booking_id ) {

	global $wpdb;

	$table_name = wrmi_get_booking_items_table_name();

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			WHERE booking_id = %d
			ORDER BY id ASC",
			absint( $booking_id )
		)
	);
}


function wrmi_delete_booking_items( $booking_id ) {

	global $wpdb;

	$table_name = wrmi_get_booking_items_table_name();

	$wpdb->delete(
		$table_name,
		array(
			'booking_id' => absint( $booking_id ),
		),
		array( '%d' )
	);
}


function wrmi_booking_calculate_quantity(
	$start_date,
	$end_date
) {

	$start = strtotime( $start_date );

	if ( ! $start ) {
		return 0;
	}

	if ( '' === $end_date ) {
		return 1;
	}

	$end = strtotime( $end_date );

	if ( ! $end || $end < $start ) {
		return 0;
	}

	return (int) floor(
		( $end - $start ) / DAY_IN_SECONDS
	) + 1;
}


/*
 * =========================================================
 * ADMIN MENU
 * =========================================================
 */

function wrmi_booking_admin_menu() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Booking / Order',
		'Booking / Order',
		'manage_options',
		'wrmi-bookings',
		'wrmi_booking_admin_page',
		37
	);
}

add_action(
	'admin_menu',
	'wrmi_booking_admin_menu',
	20
);


/*
 * =========================================================
 * SAVE BOOKING
 * =========================================================
 */

function wrmi_booking_save() {

	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( 'Anda tidak memiliki izin.' );
	}

	if (
		empty( $_POST['wrmi_booking_nonce'] )
		|| ! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash( $_POST['wrmi_booking_nonce'] )
			),
			'wrmi_booking_save'
		)
	) {
		wp_die( 'Permintaan tidak valid.' );
	}

	$statuses = wrmi_booking_get_statuses();

	$advertiser_id = isset( $_POST['advertiser_id'] )
		? absint( $_POST['advertiser_id'] )
		: 0;

		$campaign_id = isset( $_POST['campaign_id'] )

		? absint( $_POST['campaign_id'] )

		: 0;



	if ( ! $advertiser_id || ! $campaign_id ) {

		wp_die( 'Advertiser dan Campaign wajib dipilih.' );

	}



	$advertiser_post = get_post( $advertiser_id );

	$campaign_post   = get_post( $campaign_id );



	if (

		! $advertiser_post

		|| 'wrmi_advertiser' !== $advertiser_post->post_type

		|| 'publish' !== $advertiser_post->post_status

	) {

		wp_die( 'Advertiser tidak valid atau belum dipublikasikan.' );

	}



	if (

		! $campaign_post

		|| 'wrmi_campaign' !== $campaign_post->post_type

		|| 'publish' !== $campaign_post->post_status

	) {

		wp_die( 'Campaign tidak valid atau belum dipublikasikan.' );

	}



	if ( wrmi_get_campaign_advertiser_id( $campaign_id ) !== $advertiser_id ) {

		wp_die( 'Campaign bukan milik Advertiser yang dipilih.' );

	}



	$status = isset( $_POST['status'] )
		? sanitize_key( wp_unslash( $_POST['status'] ) )
		: 'draft';

	if ( ! isset( $statuses[ $status ] ) ) {
		$status = 'draft';
	}

	$start_date = isset( $_POST['start_date'] )
		? sanitize_text_field( wp_unslash( $_POST['start_date'] ) )
		: '';

	$end_date = isset( $_POST['end_date'] )
		? sanitize_text_field( wp_unslash( $_POST['end_date'] ) )
		: '';

	$discount = isset( $_POST['discount'] )
		? (float) wp_unslash( $_POST['discount'] )
		: 0;

	$tax = isset( $_POST['tax'] )
		? (float) wp_unslash( $_POST['tax'] )
		: 0;

	$notes = isset( $_POST['notes'] )
		? sanitize_textarea_field( wp_unslash( $_POST['notes'] ) )
		: '';

	if ( '' === $start_date ) {
		$start_date = current_time( 'Y-m-d' );
	}

	if (
		'' !== $end_date
		&& $end_date < $start_date
	) {
		wp_die(
			'Periode Booking tidak valid. Tanggal selesai tidak boleh lebih awal dari tanggal mulai.'
		);
	}

	$placements = isset( $_POST['placement'] )
		? (array) $_POST['placement']
		: array();

	$item_start_dates = isset( $_POST['item_start_date'] )
		? (array) $_POST['item_start_date']
		: array();

	$item_end_dates = isset( $_POST['item_end_date'] )
		? (array) $_POST['item_end_date']
		: array();

	$subtotal = 0;

	$prepared_items = array();

	$placement_definitions = function_exists(
		'wrmi_get_placement_definitions'
	)
		? wrmi_get_placement_definitions()
		: array();

	foreach ( $placements as $index => $placement ) {

		$placement = sanitize_key(
			wp_unslash( $placement )
		);

		if ( '' === $placement ) {
			continue;
		}

		if (
			! empty( $placement_definitions )
			&& ! isset( $placement_definitions[ $placement ] )
		) {
			wp_die(
				'Placement tidak valid: ' . esc_html( $placement )
			);
		}

		$item_start = isset( $item_start_dates[ $index ] )
			? sanitize_text_field(
				wp_unslash( $item_start_dates[ $index ] )
			)
			: $start_date;

		$item_end = isset( $item_end_dates[ $index ] )
			? sanitize_text_field(
				wp_unslash( $item_end_dates[ $index ] )
			)
			: $end_date;

		if ( '' === $item_start ) {
			wp_die(
				'Tanggal mulai Booking Item belum diisi.'
			);
		}

		if (
			'' !== $item_end
			&& $item_end < $item_start
		) {
			wp_die(
				'Periode Booking Item tidak valid untuk placement: '
				. esc_html( $placement )
			);
		}

		$quantity = wrmi_booking_calculate_quantity(
			$item_start,
			$item_end
		);

		if ( $quantity <= 0 ) {
			wp_die(
				'Jumlah hari Booking Item tidak valid untuk placement: '
				. esc_html( $placement )
			);
		}

		$rate = wrmi_booking_get_active_rate(
			$placement,
			$item_start,
			$item_end
		);

		if ( ! $rate ) {

			$period_label = $item_start;

			if ( '' !== $item_end ) {
				$period_label .= ' - ' . $item_end;
			}

			wp_die(
				'Tidak ditemukan Tarif Iklan aktif yang mencakup seluruh periode '
				. esc_html( $period_label )
				. ' untuk placement '
				. esc_html( strtoupper( $placement ) )
				. '. Booking tidak dibuat.'
			);
		}

		$unit_price = (float) $rate->price;

		$item_total = $quantity * $unit_price;

		$subtotal += $item_total;

		$prepared_items[] = array(
			'placement'  => $placement,
			'start_date' => $item_start,
			'end_date'   => $item_end,
			'quantity'   => $quantity,
			'unit_price' => $unit_price,
			'total'      => $item_total,
		);
	}

	if ( empty( $prepared_items ) ) {
		wp_die(
			'Minimal satu Booking Item harus dipilih.'
		);
	}

	$discount = max(
		0,
		$discount
	);

	$tax = max(
		0,
		$tax
	);

	$total = max(
		0,
		$subtotal - $discount + $tax
	);

	$booking_id = wp_insert_post(
		array(
			'post_type'   => 'wrmi_booking',
			'post_status' => 'publish',
			'post_title'  => 'Booking ' . current_time( 'Y-m-d H:i:s' ),
		),
		true
	);

	if ( is_wp_error( $booking_id ) ) {
		wp_die(
			esc_html(
				$booking_id->get_error_message()
			)
		);
	}

	$booking_number = wrmi_booking_generate_number();

	update_post_meta(
		$booking_id,
		'_wrmi_booking_number',
		$booking_number
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_advertiser_id',
		$advertiser_id
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_campaign_id',
		$campaign_id
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_status',
		$status
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_start_date',
		$start_date
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_end_date',
		$end_date
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_subtotal',
		number_format(
			$subtotal,
			2,
			'.',
			''
		)
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_discount',
		number_format(
			$discount,
			2,
			'.',
			''
		)
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_tax',
		number_format(
			$tax,
			2,
			'.',
			''
		)
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_total',
		number_format(
			$total,
			2,
			'.',
			''
		)
	);

	update_post_meta(
		$booking_id,
		'_wrmi_booking_notes',
		$notes
	);

	global $wpdb;

	$table_name = wrmi_get_booking_items_table_name();

	foreach ( $prepared_items as $item ) {

		$wpdb->insert(
			$table_name,
			array(
				'booking_id' => $booking_id,
				'placement'  => $item['placement'],
				'start_date' => $item['start_date'],
				'end_date'   => $item['end_date'],
				'quantity'   => $item['quantity'],
				'unit_price' => $item['unit_price'],
				'discount'   => 0,
				'tax'        => 0,
				'total'      => $item['total'],
				'created_at' => current_time( 'mysql' ),
			),
			array(
				'%d',
				'%s',
				'%s',
				'%s',
				'%.4f',
				'%.2f',
				'%.2f',
				'%.2f',
				'%.2f',
				'%s',
			)
		);
	}

	if ( function_exists( 'wrmi_log_activity' ) ) {

		wrmi_log_activity(
			'booking',
			$booking_id,
			'create',
			array(
				'booking_number' => $booking_number,
				'total'          => $total,
				'items'          => count( $prepared_items ),
			)
		);
	}

	wp_safe_redirect(
		add_query_arg(
			array(
				'page'    => 'wrmi-bookings',
				'message' => 'saved',
			),
			admin_url( 'admin.php' )
		)
	);

	exit;
}

add_action(
	'admin_post_wrmi_booking_save',
	'wrmi_booking_save'
);


/*
 * =========================================================
 * ADMIN PAGE
 * =========================================================
 */

function wrmi_booking_admin_page() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	global $wpdb;

	$table_name = wrmi_get_booking_items_table_name();

		$advertisers = get_posts(

		array(

			'post_type'      => 'wrmi_advertiser',

			'post_status'    => 'publish',

			'posts_per_page' => -1,

			'orderby'        => 'title',

			'order'          => 'ASC',

		)

	);



	$campaigns = get_posts(

		array(

			'post_type'      => 'wrmi_campaign',

			'post_status'    => 'publish',

			'posts_per_page' => -1,

			'orderby'        => 'title',

			'order'          => 'ASC',

		)

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

	$placements = function_exists( 'wrmi_get_placement_definitions' )
		? wrmi_get_placement_definitions()
		: array();

	$rates = $wpdb->get_results(
		"SELECT placement, price
		FROM {$wpdb->prefix}wrmi_rates
		WHERE status = 'active'
		  AND billing_unit = 'day'
		ORDER BY valid_from DESC, id DESC"
	);

	$rate_map = array();

	foreach ( $rates as $rate ) {

		if ( ! isset( $rate_map[ $rate->placement ] ) ) {
			$rate_map[ $rate->placement ] = (float) $rate->price;
		}
	}

	?>

	<div class="wrap">

		<h1>Booking / Order</h1>

		<?php if ( isset( $_GET['message'] ) && 'saved' === $_GET['message'] ) : ?>

			<div class="notice notice-success is-dismissible">
				<p>Booking berhasil dibuat.</p>
			</div>

		<?php endif; ?>

		<h2>Tambah Booking</h2>

		<form
			method="post"
			action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>"
		>

			<input
				type="hidden"
				name="action"
				value="wrmi_booking_save"
			>

			<?php wp_nonce_field( 'wrmi_booking_save', 'wrmi_booking_nonce' ); ?>

			<table class="form-table">

								<tr>

					<th><label for="wrmi-booking-advertiser">Advertiser</label></th>

					<td>

						<select name="advertiser_id" id="wrmi-booking-advertiser" class="regular-text" required>

							<option value="">Pilih Advertiser</option>

							<?php foreach ( $advertisers as $advertiser ) : ?>

								<option value="<?php echo esc_attr( $advertiser->ID ); ?>">

									<?php echo esc_html( $advertiser->post_title . ' (ID: ' . $advertiser->ID . ')' ); ?>

								</option>

							<?php endforeach; ?>

						</select>

					</td>

				</tr>



				<tr>

					<th><label for="wrmi-booking-campaign">Campaign</label></th>

					<td>

						<select name="campaign_id" id="wrmi-booking-campaign" class="regular-text" required disabled>

							<option value="">Pilih Advertiser terlebih dahulu</option>

							<?php foreach ( $campaigns as $campaign ) : ?>

								<option

									class="wrmi-booking-campaign-option"

									value="<?php echo esc_attr( $campaign->ID ); ?>"

									data-advertiser-id="<?php echo esc_attr( wrmi_get_campaign_advertiser_id( $campaign->ID ) ); ?>"

									hidden

									disabled

								>

									<?php echo esc_html( $campaign->post_title . ' (ID: ' . $campaign->ID . ')' ); ?>

								</option>

							<?php endforeach; ?>

						</select>

						<p class="description">Campaign mengikuti Advertiser yang dipilih.</p>

					</td>

				</tr>

				<tr>
					<th>
						<label for="wrmi-booking-status">
							Status
						</label>
					</th>
					<td>

						<select
							name="status"
							id="wrmi-booking-status"
						>

							<?php foreach ( wrmi_booking_get_statuses() as $key => $label ) : ?>

								<option value="<?php echo esc_attr( $key ); ?>">
									<?php echo esc_html( $label ); ?>
								</option>

							<?php endforeach; ?>

						</select>

					</td>
				</tr>

			</table>

			<h2>Booking Items</h2>

			<table class="widefat striped">

				<thead>
					<tr>
						<th>Placement</th>
						<th>Mulai</th>
						<th>Selesai</th>
						<th>Tarif / Hari</th>
					</tr>
				</thead>

				<tbody>

					<?php foreach ( $placements as $placement => $label ) : ?>

						<tr>

							<td>

								<label>

									<input
										type="checkbox"
										name="placement[]"
										value="<?php echo esc_attr( $placement ); ?>"
									>

									<?php echo esc_html( $label ); ?>

								</label>

							</td>

							<td>

								<input
									type="date"
									name="item_start_date[<?php echo esc_attr( $placement ); ?>]"
									value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
								>

							</td>

							<td>

								<input
									type="date"
									name="item_end_date[<?php echo esc_attr( $placement ); ?>]"
									value="<?php echo esc_attr( current_time( 'Y-m-d' ) ); ?>"
								>

							</td>

							<td>

								<?php if ( isset( $rate_map[ $placement ] ) ) : ?>

									Rp <?php echo esc_html( number_format_i18n( $rate_map[ $placement ], 0 ) ); ?>

								<?php else : ?>

									<span style="color:#999;">
										Belum ada tarif aktif
									</span>

								<?php endif; ?>

							</td>

						</tr>

					<?php endforeach; ?>

				</tbody>

			</table>

			<p>
				Tarif dan subtotal dihitung otomatis berdasarkan Tarif Iklan aktif.
			</p>

			<table class="form-table">

				<tr>
					<th>
						<label for="wrmi-booking-discount">
							Discount
						</label>
					</th>
					<td>
						<input
							type="number"
							step="0.01"
							min="0"
							name="discount"
							id="wrmi-booking-discount"
							value="0"
						>
					</td>
				</tr>

				<tr>
					<th>
						<label for="wrmi-booking-tax">
							Tax
						</label>
					</th>
					<td>
						<input
							type="number"
							step="0.01"
							min="0"
							name="tax"
							id="wrmi-booking-tax"
							value="0"
						>
					</td>
				</tr>

				<tr>
					<th>
						<label for="wrmi-booking-notes">
							Catatan
						</label>
					</th>
					<td>
						<textarea
							name="notes"
							id="wrmi-booking-notes"
							rows="5"
							class="large-text"
						></textarea>
					</td>
				</tr>

			</table>

			<?php submit_button( 'Buat Booking' ); ?>

				<script>

		(function () {

			var advertiser = document.getElementById('wrmi-booking-advertiser');

			var campaign = document.getElementById('wrmi-booking-campaign');



			if (!advertiser || !campaign) {

				return;

			}



			function updateCampaignOptions() {

				var advertiserId = advertiser.value;

				var options = campaign.querySelectorAll('.wrmi-booking-campaign-option');

				var count = 0;



				options.forEach(function (option) {

					var matched = advertiserId !== '' &&

						option.getAttribute('data-advertiser-id') === advertiserId;



					option.hidden = !matched;

					option.disabled = !matched;



					if (matched) {

						count++;

					}

				});



				campaign.value = '';

				campaign.disabled = advertiserId === '';



				campaign.options[0].textContent = advertiserId === ''

					? 'Pilih Advertiser terlebih dahulu'

					: (count ? 'Pilih Campaign' : 'Belum ada Campaign untuk Advertiser ini');

			}



			advertiser.addEventListener('change', updateCampaignOptions);

			updateCampaignOptions();

		})();

		</script>


		</form>

		<hr>

		<h2>Daftar Booking</h2>

		<table class="widefat striped">

			<thead>
				<tr>
					<th>No. Booking</th>
					<th>Advertiser</th>
					<th>Campaign</th>
					<th>Status</th>
					<th>Periode</th>
					<th>Items</th>
					<th>Total</th>
				</tr>
			</thead>

			<tbody>

				<?php if ( empty( $bookings ) ) : ?>

					<tr>
						<td colspan="7">
							Belum ada booking.
						</td>
					</tr>

				<?php else : ?>

					<?php foreach ( $bookings as $booking ) : ?>

						<?php

						$booking_id = $booking->ID;

						$start_date = get_post_meta(
							$booking_id,
							'_wrmi_booking_start_date',
							true
						);

						$end_date = get_post_meta(
							$booking_id,
							'_wrmi_booking_end_date',
							true
						);

						$item_count = (int) $wpdb->get_var(
							$wpdb->prepare(
								"SELECT COUNT(*)
								FROM {$table_name}
								WHERE booking_id = %d",
								$booking_id
							)
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
								<?php echo esc_html( wrmi_get_booking_campaign_id( $booking_id ) ); ?>
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
								<?php echo esc_html( $start_date ); ?>

								<?php if ( $end_date ) : ?>
									- <?php echo esc_html( $end_date ); ?>
								<?php endif; ?>
							</td>

							<td>
								<?php echo esc_html( $item_count ); ?>
							</td>

							<td>
								Rp <?php echo esc_html( number_format_i18n( wrmi_get_booking_total( $booking_id ), 0 ) ); ?>
							</td>

						</tr>

					<?php endforeach; ?>

				<?php endif; ?>

			</tbody>

		</table>

	</div>

	<?php
}
