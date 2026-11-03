<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Log Aktivitas
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * 1. NAMA TABEL
 * ========================================================= */

function wrmi_get_log_table_name() {

	global $wpdb;

	return $wpdb->prefix . 'wrmi_activity_log';
}


/* =========================================================
 * 2. MEMBUAT TABEL LOG
 * ========================================================= */

function wrmi_create_activity_log_table() {

	global $wpdb;

	$table_name = wrmi_get_log_table_name();

	$charset_collate = $wpdb->get_charset_collate();

	require_once ABSPATH . 'wp-admin/includes/upgrade.php';

	$sql = "CREATE TABLE {$table_name} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		user_name varchar(190) NOT NULL DEFAULT '',
		action varchar(190) NOT NULL DEFAULT '',
		object_type varchar(190) NOT NULL DEFAULT '',
		object_id bigint(20) unsigned NOT NULL DEFAULT 0,
		description text NOT NULL,
		created_at datetime NOT NULL,
		PRIMARY KEY (id),
		KEY user_id (user_id),
		KEY object_type (object_type),
		KEY object_id (object_id),
		KEY created_at (created_at)
	) {$charset_collate};";

	dbDelta( $sql );

	update_option(
		'wrmi_activity_log_db_version',
		WRMI_VERSION
	);
}


/* =========================================================
 * 3. PASTIKAN TABEL TERSEDIA
 * ========================================================= */

add_action(
	'admin_init',
	'wrmi_ensure_activity_log_table',
	5
);

function wrmi_ensure_activity_log_table() {

	global $wpdb;

	$table_name = wrmi_get_log_table_name();

	$table_exists = $wpdb->get_var(
		$wpdb->prepare(
			'SHOW TABLES LIKE %s',
			$wpdb->esc_like( $table_name )
		)
	);

	$db_version = get_option(
		'wrmi_activity_log_db_version',
		''
	);

	if (
		$table_exists !== $table_name
		|| WRMI_VERSION !== $db_version
	) {
		wrmi_create_activity_log_table();
	}
}


/* =========================================================
 * 4. HOOK PENYIMPANAN AKTIVITAS
 * ========================================================= */

add_action(
	'wrmi_activity_logged',
	'wrmi_store_activity_log',
	10,
	1
);

function wrmi_store_activity_log( $activity ) {

	if ( ! is_array( $activity ) ) {
		return;
	}

	global $wpdb;

	$table_name = wrmi_get_log_table_name();

	$user_id = isset( $activity['user_id'] )
		? absint( $activity['user_id'] )
		: 0;

	$user_name = isset( $activity['user_name'] )
		? sanitize_text_field( $activity['user_name'] )
		: '';

	$action = isset( $activity['action'] )
		? sanitize_text_field( $activity['action'] )
		: '';

	$object_type = isset( $activity['object_type'] )
		? sanitize_text_field( $activity['object_type'] )
		: '';

	$object_id = isset( $activity['object_id'] )
		? absint( $activity['object_id'] )
		: 0;

	$description = isset( $activity['description'] )
		? sanitize_text_field( $activity['description'] )
		: '';

	$timestamp = isset( $activity['timestamp'] )
		? absint( $activity['timestamp'] )
		: current_time( 'timestamp' );

	$created_at = wp_date(
		'Y-m-d H:i:s',
		$timestamp
	);

	$result = $wpdb->insert(
		$table_name,
		array(
			'user_id'     => $user_id,
			'user_name'   => $user_name,
			'action'      => $action,
			'object_type' => $object_type,
			'object_id'   => $object_id,
			'description' => $description,
			'created_at'  => $created_at,
		),
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%d',
			'%s',
			'%s',
		)
	);

	return false !== $result;
}


/* =========================================================
 * 5. AMBIL LOG
 * ========================================================= */

function wrmi_get_activity_logs(
	$limit = 50,
	$offset = 0
) {

	global $wpdb;

	$table_name = wrmi_get_log_table_name();

	$limit = max(
		1,
		absint( $limit )
	);

	$offset = max(
		0,
		absint( $offset )
	);

	return $wpdb->get_results(
		$wpdb->prepare(
			"SELECT *
			FROM {$table_name}
			ORDER BY id DESC
			LIMIT %d OFFSET %d",
			$limit,
			$offset
		)
	);
}


/* =========================================================
 * 6. TOTAL LOG
 * ========================================================= */

function wrmi_get_activity_log_count() {

	global $wpdb;

	$table_name = wrmi_get_log_table_name();

	return absint(
		$wpdb->get_var(
			"SELECT COUNT(*)
			FROM {$table_name}"
		)
	);
}


/* =========================================================
 * 7. HAPUS LOG LAMA
 * ========================================================= */

function wrmi_delete_old_activity_logs(
	$days = 365
) {

	global $wpdb;

	$table_name = wrmi_get_log_table_name();

	$days = max(
		1,
		absint( $days )
	);

	return $wpdb->query(
		$wpdb->prepare(
			"DELETE FROM {$table_name}
			WHERE created_at < DATE_SUB(
				%s,
				INTERVAL %d DAY
			)",
			current_time( 'mysql' ),
			$days
		)
	);
}


/* =========================================================
 * 8. ADMIN MENU
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_log_admin_menu',
	60
);

function wrmi_log_admin_menu() {

	if (
		! current_user_can(
			'manage_options'
		)
	) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Log Aktivitas',
		'Log Aktivitas',
		'manage_options',
		'wrmi-log',
		'wrmi_log_page'
	);
}


/* =========================================================
 * 9. HALAMAN LOG
 * ========================================================= */

function wrmi_log_page() {

	if (
		! current_user_can(
			'manage_options'
		)
	) {
		wp_die(
			esc_html__(
				'Anda tidak memiliki izin untuk mengakses Log Aktivitas.',
				'wartaradar-manajemen-iklan'
			)
		);
	}

	$logs = wrmi_get_activity_logs(
		100,
		0
	);

	$total = wrmi_get_activity_log_count();

	?>

	<div class="wrap">

		<h1>Log Aktivitas</h1>

		<p>
			Riwayat aktivitas pengelolaan
			Manajemen Iklan WartaRadar.
		</p>

		<p>
			<strong>
				Total Aktivitas:
			</strong>

			<?php
			echo esc_html(
				number_format_i18n(
					$total
				)
			);
			?>
		</p>

		<table class="widefat striped">

			<thead>

				<tr>
					<th>Waktu</th>
					<th>Pengguna</th>
					<th>Aksi</th>
					<th>Objek</th>
					<th>ID</th>
					<th>Deskripsi</th>
				</tr>

			</thead>

			<tbody>

				<?php if ( ! empty( $logs ) ) : ?>

					<?php foreach ( $logs as $log ) : ?>

						<tr>

							<td>
								<?php
								echo esc_html(
									$log->created_at
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$log->user_name
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$log->action
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$log->object_type
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$log->object_id
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$log->description
								);
								?>
							</td>

						</tr>

					<?php endforeach; ?>

				<?php else : ?>

					<tr>

						<td colspan="6">
							Belum ada aktivitas
							yang tercatat.
						</td>

					</tr>

				<?php endif; ?>

			</tbody>

		</table>

	</div>

	<?php
}


/* =========================================================
 * 10. AKTIVASI DATABASE
 * ========================================================= */

add_action(
	'wrmi_activate',
	'wrmi_create_activity_log_table'
);