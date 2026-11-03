<?php
/**
 * WartaRadar Manajemen Iklan
 * Laporan Iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * DATA LAPORAN IKLAN
 * ========================================================= */

function wrmi_get_report_ads() {

	$ads = get_posts(
		array(
			'post_type'      => 'wrmi_ad',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);

	$report = array();

	foreach ( $ads as $ad ) {

		$ad_id = $ad->ID;

		$advertiser_id = wrmi_get_ad_advertiser_id( $ad_id );
		$campaign_id   = wrmi_get_ad_campaign_id( $ad_id );

		$report[] = array(
			'id'          => $ad_id,
			'title'       => get_the_title( $ad_id ),
			'advertiser'  => $advertiser_id
				? get_the_title( $advertiser_id )
				: '-',
			'campaign'    => $campaign_id
				? get_the_title( $campaign_id )
				: '-',
			'impressions' => wrmi_get_ad_impressions( $ad_id ),
			'clicks'      => wrmi_get_ad_clicks( $ad_id ),
			'ctr'         => wrmi_get_ad_ctr( $ad_id ),
		);
	}

	return $report;
}


/* =========================================================
 * TOTAL LAPORAN
 * ========================================================= */

function wrmi_get_report_totals() {

	$stats = wrmi_get_total_ad_statistics();

	return array(
		'impressions' => isset( $stats['impressions'] )
			? (int) $stats['impressions']
			: 0,

		'clicks' => isset( $stats['clicks'] )
			? (int) $stats['clicks']
			: 0,

		'ctr' => isset( $stats['ctr'] )
			? (float) $stats['ctr']
			: 0,
	);
}


/* =========================================================
 * NORMALISASI PLACEMENT
 * ========================================================= */

function wrmi_report_normalize_value( $value ) {

	if ( is_string( $value ) || is_numeric( $value ) ) {
		return trim( (string) $value );
	}

	if ( is_array( $value ) ) {

		$preferred_keys = array(
			'value',
			'key',
			'slug',
			'name',
			'label',
			'position',
		);

		foreach ( $preferred_keys as $key ) {

			if ( isset( $value[ $key ] ) ) {

				$normalized = wrmi_report_normalize_value(
					$value[ $key ]
				);

				if ( '' !== $normalized ) {
					return $normalized;
				}
			}
		}

		foreach ( $value as $item ) {

			$normalized = wrmi_report_normalize_value(
				$item
			);

			if ( '' !== $normalized ) {
				return $normalized;
			}
		}
	}

	return '';
}


function wrmi_report_get_placement_label( $placement_id ) {

	$position = get_post_meta(
		$placement_id,
		'_wrmi_placement_position',
		true
	);

	$position = wrmi_report_normalize_value(
		$position
	);

	$position = strtolower(
		sanitize_key( $position )
	);

	$placements = array(
		'top'     => 'TOP',
		'header'  => 'HEADER',
		'hero'    => 'HERO',
		'sidebar' => 'SIDEBAR',
		'feed'    => 'FEED',
		'article' => 'ARTICLE',
		'footer'  => 'FOOTER',
	);

	if ( isset( $placements[ $position ] ) ) {
		return $placements[ $position ];
	}

	if ( '' !== $position ) {
		return strtoupper( $position );
	}

	return '-';
}


/* =========================================================
 * LAPORAN PER PLACEMENT
 * ========================================================= */

function wrmi_get_report_placements() {

	$placements = get_posts(
		array(
			'post_type'      => 'wrmi_placement',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'meta_value_num',
			'meta_key'       => '_wrmi_placement_order',
			'order'          => 'ASC',
		)
	);

	$report = array();

	foreach ( $placements as $placement ) {

		$placement_id = $placement->ID;

		$ad_id = wrmi_get_placement_ad_id(
			$placement_id
		);

		$position_label = wrmi_report_get_placement_label(
			$placement_id
		);

		$impressions = 0;
		$clicks      = 0;
		$ctr         = 0;

		if ( $ad_id ) {

			$impressions = wrmi_get_ad_impressions(
				$ad_id
			);

			$clicks = wrmi_get_ad_clicks(
				$ad_id
			);

			$ctr = wrmi_get_ad_ctr(
				$ad_id
			);
		}

		$report[] = array(
			'id'          => $placement_id,
			'placement'   => $position_label,
			'ad'          => $ad_id
				? get_the_title( $ad_id )
				: '-',
			'impressions' => (int) $impressions,
			'clicks'      => (int) $clicks,
			'ctr'         => (float) $ctr,
		);
	}

	return $report;
}


/* =========================================================
 * MENU ADMIN
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_register_report_menu',
	20
);

function wrmi_register_report_menu() {

	if ( ! current_user_can( 'manage_options' ) ) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Laporan Iklan',
		'Laporan Iklan',
		'manage_options',
		'wrmi-report',
		'wrmi_report_page'
	);
}


/* =========================================================
 * HALAMAN LAPORAN
 * ========================================================= */

function wrmi_report_page() {

	if ( ! current_user_can( 'manage_options' ) ) {

		wp_die(
			esc_html__(
				'Anda tidak memiliki izin untuk mengakses Laporan Iklan.',
				'wartaradar-manajemen-iklan'
			)
		);
	}

	$totals     = wrmi_get_report_totals();
	$ads        = wrmi_get_report_ads();
	$placements = wrmi_get_report_placements();

	?>

	<div class="wrap">

		<h1>Laporan Iklan</h1>

		<p>
			Laporan performa iklan WartaRadar berdasarkan
			data tayangan dan klik.
		</p>

		<hr>

		<h2>Ringkasan</h2>

		<table class="widefat striped">

			<thead>

				<tr>
					<th>Total Tayangan</th>
					<th>Total Klik</th>
					<th>CTR</th>
				</tr>

			</thead>

			<tbody>

				<tr>

					<td>
						<?php
						echo esc_html(
							number_format_i18n(
								$totals['impressions']
							)
						);
						?>
					</td>

					<td>
						<?php
						echo esc_html(
							number_format_i18n(
								$totals['clicks']
							)
						);
						?>
					</td>

					<td>
						<?php
						echo esc_html(
							number_format_i18n(
								$totals['ctr'],
								2
							)
						);
						?>
						%
					</td>

				</tr>

			</tbody>

		</table>


		<br>


		<h2>Laporan Per Iklan</h2>

		<table class="widefat striped">

			<thead>

				<tr>
					<th>Iklan</th>
					<th>Advertiser</th>
					<th>Campaign</th>
					<th>Tayangan</th>
					<th>Klik</th>
					<th>CTR</th>
				</tr>

			</thead>

			<tbody>

				<?php if ( ! empty( $ads ) ) : ?>

					<?php foreach ( $ads as $item ) : ?>

						<tr>

							<td>
								<?php
								echo esc_html(
									$item['title']
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$item['advertiser']
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									$item['campaign']
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									number_format_i18n(
										$item['impressions']
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									number_format_i18n(
										$item['clicks']
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									number_format_i18n(
										$item['ctr'],
										2
									)
								);
								?>
								%
							</td>

						</tr>

					<?php endforeach; ?>

				<?php else : ?>

					<tr>
						<td colspan="6">
							Belum ada data iklan.
						</td>
					</tr>

				<?php endif; ?>

			</tbody>

		</table>


		<br>


		<h2>Laporan Per Placement</h2>

		<table class="widefat striped">

			<thead>

				<tr>
					<th>Placement</th>
					<th>Iklan</th>
					<th>Tayangan</th>
					<th>Klik</th>
					<th>CTR</th>
				</tr>

			</thead>

			<tbody>

				<?php if ( ! empty( $placements ) ) : ?>

					<?php foreach ( $placements as $item ) : ?>

						<tr>

							<td>
								<strong>
									<?php
									echo esc_html(
										$item['placement']
									);
									?>
								</strong>
							</td>

							<td>
								<?php
								echo esc_html(
									$item['ad']
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									number_format_i18n(
										$item['impressions']
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									number_format_i18n(
										$item['clicks']
									)
								);
								?>
							</td>

							<td>
								<?php
								echo esc_html(
									number_format_i18n(
										$item['ctr'],
										2
									)
								);
								?>
								%
							</td>

						</tr>

					<?php endforeach; ?>

				<?php else : ?>

					<tr>

						<td colspan="5">
							Belum ada data placement.
						</td>

					</tr>

				<?php endif; ?>

			</tbody>

		</table>

	</div>

	<?php
}
