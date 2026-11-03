<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Statistik Iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * 1. META STATISTIK
 * ========================================================= */

function wrmi_get_ad_impressions( $ad_id ) {

	$ad_id = absint( $ad_id );

	if ( ! $ad_id || 'wrmi_ad' !== get_post_type( $ad_id ) ) {
		return 0;
	}

	return absint(
		get_post_meta(
			$ad_id,
			'_wrmi_ad_impressions',
			true
		)
	);
}


function wrmi_get_ad_clicks( $ad_id ) {

	$ad_id = absint( $ad_id );

	if ( ! $ad_id || 'wrmi_ad' !== get_post_type( $ad_id ) ) {
		return 0;
	}

	return absint(
		get_post_meta(
			$ad_id,
			'_wrmi_ad_clicks',
			true
		)
	);
}


/* =========================================================
 * 2. RECORD IMPRESSION
 * ========================================================= */

function wrmi_record_ad_impression( $ad_id ) {

	$ad_id = absint( $ad_id );

	if ( ! $ad_id ) {
		return false;
	}

	if ( 'wrmi_ad' !== get_post_type( $ad_id ) ) {
		return false;
	}

	$current = wrmi_get_ad_impressions( $ad_id );

	$new_value = $current + 1;

	update_post_meta(
		$ad_id,
		'_wrmi_ad_impressions',
		$new_value
	);

	do_action(
		'wrmi_ad_impression_recorded',
		$ad_id,
		$new_value
	);

	return $new_value;
}


/* =========================================================
 * 3. RECORD CLICK
 * ========================================================= */

function wrmi_record_ad_click( $ad_id ) {

	$ad_id = absint( $ad_id );

	if ( ! $ad_id ) {
		return false;
	}

	if ( 'wrmi_ad' !== get_post_type( $ad_id ) ) {
		return false;
	}

	$current = wrmi_get_ad_clicks( $ad_id );

	$new_value = $current + 1;

	update_post_meta(
		$ad_id,
		'_wrmi_ad_clicks',
		$new_value
	);

	do_action(
		'wrmi_ad_click_recorded',
		$ad_id,
		$new_value
	);

	return $new_value;
}


/* =========================================================
 * 4. CTR
 * ========================================================= */

function wrmi_get_ad_ctr( $ad_id ) {

	$impressions = wrmi_get_ad_impressions( $ad_id );
	$clicks      = wrmi_get_ad_clicks( $ad_id );

	if ( $impressions <= 0 ) {
		return 0;
	}

	return round(
		(
			$clicks / $impressions
		) * 100,
		2
	);
}


/* =========================================================
 * 5. STATISTIK SATU IKLAN
 * ========================================================= */

function wrmi_get_ad_statistics( $ad_id ) {

	$ad_id = absint( $ad_id );

	return array(
		'ad_id'       => $ad_id,
		'impressions' => wrmi_get_ad_impressions( $ad_id ),
		'clicks'      => wrmi_get_ad_clicks( $ad_id ),
		'ctr'         => wrmi_get_ad_ctr( $ad_id ),
	);
}


/* =========================================================
 * 6. TOTAL STATISTIK
 * ========================================================= */

function wrmi_get_total_ad_statistics() {

	$ads = get_posts(
		array(
			'post_type'      => 'wrmi_ad',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		)
	);

	$total_impressions = 0;
	$total_clicks      = 0;

	foreach ( $ads as $ad_id ) {

		$ad_id = absint( $ad_id );

		if ( ! $ad_id ) {
			continue;
		}

		$total_impressions += wrmi_get_ad_impressions( $ad_id );
		$total_clicks      += wrmi_get_ad_clicks( $ad_id );
	}

	$ctr = 0;

	if ( $total_impressions > 0 ) {

		$ctr = round(
			(
				$total_clicks / $total_impressions
			) * 100,
			2
		);
	}

	return array(
		'impressions' => $total_impressions,
		'clicks'      => $total_clicks,
		'ctr'         => $ctr,
	);
}


/* =========================================================
 * 7. STATISTIK PER PLACEMENT
 * ========================================================= */

function wrmi_get_placement_statistics( $placement_position ) {

	$placement_position = sanitize_key(
		$placement_position
	);

	if ( '' === $placement_position ) {

		return array(
			'position'    => '',
			'impressions' => 0,
			'clicks'      => 0,
			'ctr'         => 0,
		);
	}

	$placements = get_posts(
		array(
			'post_type'      => 'wrmi_placement',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',

			'meta_query' => array(
				array(
					'key'     => '_wrmi_placement_position',
					'value'   => $placement_position,
					'compare' => '=',
				),
			),
		)
	);

	$total_impressions = 0;
	$total_clicks      = 0;

	foreach ( $placements as $placement_id ) {

		$placement_id = absint( $placement_id );

		if ( ! $placement_id ) {
			continue;
		}

		$ad_id = 0;

		if ( function_exists( 'wrmi_get_placement_ad_id' ) ) {

			$ad_id = wrmi_get_placement_ad_id(
				$placement_id
			);
		}

		if ( ! $ad_id ) {
			continue;
		}

		$total_impressions += wrmi_get_ad_impressions( $ad_id );
		$total_clicks      += wrmi_get_ad_clicks( $ad_id );
	}

	$ctr = 0;

	if ( $total_impressions > 0 ) {

		$ctr = round(
			(
				$total_clicks / $total_impressions
			) * 100,
			2
		);
	}

	$label = $placement_position;

	if ( function_exists( 'wrmi_get_placement_label' ) ) {

		$label = wrmi_get_placement_label(
			$placement_position
		);
	}

	return array(
		'position'    => $label,
		'impressions' => $total_impressions,
		'clicks'      => $total_clicks,
		'ctr'         => $ctr,
	);
}


/* =========================================================
 * 8. ADMIN MENU
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_statistics_admin_menu',
	40
);

function wrmi_statistics_admin_menu() {

	if (
		! current_user_can(
			'manage_options'
		)
	) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Statistik Iklan',
		'Statistik Iklan',
		'manage_options',
		'wrmi-statistics',
		'wrmi_statistics_page'
	);
}


/* =========================================================
 * 9. STATISTIK PAGE
 * ========================================================= */

function wrmi_statistics_page() {

	if (
		! current_user_can(
			'manage_options'
		)
	) {
		wp_die(
			esc_html__(
				'Anda tidak memiliki izin untuk mengakses Statistik Iklan.',
				'wartaradar-manajemen-iklan'
			)
		);
	}

	$totals = wrmi_get_total_ad_statistics();

	?>

	<div class="wrap">

		<h1>Statistik Iklan</h1>

		<p>
			Ringkasan tayangan, klik, dan CTR seluruh iklan WartaRadar.
		</p>

		<table class="widefat striped">

			<thead>

				<tr>
					<th>Statistik</th>
					<th>Nilai</th>
				</tr>

			</thead>

			<tbody>

				<tr>

					<td>
						Total Tayangan
					</td>

					<td>

						<strong>
							<?php
							echo esc_html(
								number_format_i18n(
									$totals['impressions']
								)
							);
							?>
						</strong>

					</td>

				</tr>

				<tr>

					<td>
						Total Klik
					</td>

					<td>

						<strong>
							<?php
							echo esc_html(
								number_format_i18n(
									$totals['clicks']
								)
							);
							?>
						</strong>

					</td>

				</tr>

				<tr>

					<td>
						CTR
					</td>

					<td>

						<strong>
							<?php
							echo esc_html(
								$totals['ctr']
							);
							?>
							%
						</strong>

					</td>

				</tr>

			</tbody>

		</table>

	</div>

	<?php
}


/* =========================================================
 * 10. COLUMN STATISTIK PADA IKLAN
 * ========================================================= */

add_filter(
	'manage_wrmi_ad_posts_columns',
	'wrmi_ad_statistics_columns'
);

function wrmi_ad_statistics_columns( $columns ) {

	$new_columns = array();

	foreach ( $columns as $key => $label ) {

		$new_columns[ $key ] = $label;

		if ( 'title' === $key ) {

			$new_columns['wrmi_impressions'] = 'Tayangan';

			$new_columns['wrmi_clicks'] = 'Klik';

			$new_columns['wrmi_ctr'] = 'CTR';
		}
	}

	return $new_columns;
}


add_action(
	'manage_wrmi_ad_posts_custom_column',
	'wrmi_ad_statistics_column_content',
	10,
	2
);

function wrmi_ad_statistics_column_content(
	$column,
	$post_id
) {

	if ( 'wrmi_impressions' === $column ) {

		echo esc_html(
			number_format_i18n(
				wrmi_get_ad_impressions(
					$post_id
				)
			)
		);
	}


	if ( 'wrmi_clicks' === $column ) {

		echo esc_html(
			number_format_i18n(
				wrmi_get_ad_clicks(
					$post_id
				)
			)
		);
	}


	if ( 'wrmi_ctr' === $column ) {

		echo esc_html(
			wrmi_get_ad_ctr(
				$post_id
			)
		);

		echo '%';
	}
}