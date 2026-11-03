<?php
/**
 * WartaRadar Manajemen Iklan
 * Frontend Renderer
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * WRMI FRONTEND
 * Resolver Placement → Iklan → Campaign
 * ========================================================= */

function wrmi_get_frontend_ad( $position ) {

	$position = sanitize_key( $position );

	if ( empty( $position ) ) {
		return 0;
	}

	if ( ! function_exists( 'wrmi_get_active_placements' ) ) {
		return 0;
	}

	$placements = wrmi_get_active_placements(
		$position,
		20
	);

	if ( ! $placements instanceof WP_Query ) {
		return 0;
	}

	if ( empty( $placements->posts ) ) {
		return 0;
	}

	/*
	 * Gunakan waktu WordPress agar mengikuti timezone situs.
	 */
	$now   = current_time( 'timestamp' );
	$today = wp_date( 'Y-m-d', $now );

	foreach ( $placements->posts as $placement ) {

		$ad_id = 0;

		if ( function_exists( 'wrmi_get_placement_ad_id' ) ) {
			$ad_id = absint(
				wrmi_get_placement_ad_id(
					$placement->ID
				)
			);
		}

		if ( ! $ad_id ) {
			continue;
		}

		/*
		 * AD STATUS
		 */
		if ( ! function_exists( 'wrmi_get_ad_status' ) ) {
			continue;
		}

		$ad_status = wrmi_get_ad_status( $ad_id );

		if ( 'active' !== $ad_status ) {
			continue;
		}

		/*
		 * AD POST
		 */
		$ad = get_post( $ad_id );

		if ( ! $ad instanceof WP_Post ) {
			continue;
		}

		if ( 'publish' !== $ad->post_status ) {
			continue;
		}

		/*
		 * AD START DATE
		 *
		 * Kosong = tidak membatasi.
		 */
		if ( function_exists( 'wrmi_get_ad_start_date' ) ) {

			$ad_start_date = wrmi_get_ad_start_date(
				$ad_id
			);

			if (
				! empty( $ad_start_date ) &&
				$ad_start_date > $today
			) {
				continue;
			}
		}

		/*
		 * AD END DATE
		 *
		 * Kosong = tidak membatasi.
		 */
		if ( function_exists( 'wrmi_get_ad_end_date' ) ) {

			$ad_end_date = wrmi_get_ad_end_date(
				$ad_id
			);

			if (
				! empty( $ad_end_date ) &&
				$ad_end_date < $today
			) {
				continue;
			}
		}

		/*
		 * CAMPAIGN RELATION
		 */
		if ( ! function_exists( 'wrmi_get_ad_campaign_id' ) ) {
			continue;
		}

		$campaign_id = absint(
			wrmi_get_ad_campaign_id( $ad_id )
		);

		if ( ! $campaign_id ) {
			continue;
		}

		/*
		 * CAMPAIGN POST
		 */
		$campaign = get_post( $campaign_id );

		if ( ! $campaign instanceof WP_Post ) {
			continue;
		}

		if ( 'publish' !== $campaign->post_status ) {
			continue;
		}

		/*
		 * CAMPAIGN STATUS
		 */
		if ( ! function_exists( 'wrmi_get_campaign_status' ) ) {
			continue;
		}

		$campaign_status = wrmi_get_campaign_status(
			$campaign_id
		);

		if ( 'active' !== $campaign_status ) {
			continue;
		}

		/*
		 * CAMPAIGN START DATE
		 *
		 * Kosong = tidak membatasi.
		 */
		if ( function_exists( 'wrmi_get_campaign_start_date' ) ) {

			$campaign_start_date = wrmi_get_campaign_start_date(
				$campaign_id
			);

			if (
				! empty( $campaign_start_date ) &&
				$campaign_start_date > $today
			) {
				continue;
			}
		}

		/*
		 * CAMPAIGN END DATE
		 *
		 * Kosong = tidak membatasi.
		 */
		if ( function_exists( 'wrmi_get_campaign_end_date' ) ) {

			$campaign_end_date = wrmi_get_campaign_end_date(
				$campaign_id
			);

			if (
				! empty( $campaign_end_date ) &&
				$campaign_end_date < $today
			) {
				continue;
			}
		}

		/*
		 * SEMUA GATE LULUS.
		 */
		return $ad_id;
	}

	return 0;
}


/* =========================================================
 * RENDER IKLAN
 * ========================================================= */

function wrmi_render_placement( $position ) {

	$position = sanitize_key( $position );

	if ( empty( $position ) ) {
		return;
	}

	$ad_id = wrmi_get_frontend_ad(
		$position
	);

	if ( ! $ad_id ) {
		return;
	}

	$type = 'banner';

	if ( function_exists( 'wrmi_get_ad_type' ) ) {
		$type = wrmi_get_ad_type( $ad_id );
	}

	$target_url = '';

	if ( function_exists( 'wrmi_get_ad_target_url' ) ) {
		$target_url = wrmi_get_ad_target_url(
			$ad_id
		);
	}

	/*
	 * URL tracking internal WRMI.
	 *
	 * Klik akan dicatat oleh endpoint sebelum
	 * browser diarahkan ke target advertiser.
	 */
	$click_url = '';

	if ( ! empty( $target_url ) ) {
		$click_url = add_query_arg(
			'warmi_click',
			$ad_id,
			home_url( '/' )
		);
	}

	$position_label = $position;

	if ( function_exists( 'wrmi_get_placement_label' ) ) {
		$position_label = wrmi_get_placement_label(
			$position
		);
	}

	$ad_title = get_the_title( $ad_id );

	$size_config = array(
		'min_width'      => 320,
		'min_height'     => 50,
		'default_width'  => 728,
		'default_height' => 90,
		'max_width'      => 970,
		'max_height'     => 120,
	);

	if ( function_exists( 'wrmi_get_placement_size_config' ) ) {
		$size_config = wrmi_get_placement_size_config(
			$position
		);
	}

	$min_width = absint(
		$size_config['min_width']
	);

	$min_height = absint(
		$size_config['min_height']
	);

	$default_width = absint(
		$size_config['default_width']
	);

	$default_height = absint(
		$size_config['default_height']
	);

	$max_width = absint(
		$size_config['max_width']
	);

	$max_height = absint(
		$size_config['max_height']
	);

	$ad_style = sprintf(
		'--wrmi-min-width:%dpx;--wrmi-min-height:%dpx;--wrmi-default-width:%dpx;--wrmi-default-height:%dpx;--wrmi-max-width:%dpx;--wrmi-max-height:%dpx;',
		$min_width,
		$min_height,
		$default_width,
		$default_height,
		$max_width,
		$max_height
	);

	?>

	<div
		class="wrmi-ad wrmi-ad-<?php echo esc_attr( $position ); ?>"
		data-wrmi-position="<?php echo esc_attr( $position ); ?>"
		data-wrmi-ad-id="<?php echo esc_attr( $ad_id ); ?>"
		style="<?php echo esc_attr( $ad_style ); ?>"
	>

	<?php if ( ! empty( $click_url ) ) : ?>

		<a
			class="wrmi-ad-link"
			href="<?php echo esc_url( $click_url ); ?>"
			target="_blank"
			rel="nofollow sponsored noopener"
			aria-label="<?php echo esc_attr( $ad_title ); ?>"
		>

	<?php endif; ?>


	<?php if ( 'banner' === $type || 'image' === $type ) : ?>

		<?php if ( has_post_thumbnail( $ad_id ) ) : ?>

			<?php
			echo get_the_post_thumbnail(
				$ad_id,
				'full',
				array(
					'class'   => 'wrmi-ad-image',
					'loading' => 'lazy',
					'alt'     => $ad_title,
				)
			);
			?>

		<?php else : ?>

			<div class="wrmi-ad-placeholder">
				<span>
					<?php echo esc_html( $position_label ); ?>
				</span>
			</div>

		<?php endif; ?>


	<?php elseif ( 'text' === $type ) : ?>

		<div class="wrmi-ad-text">

			<?php
			echo wp_kses_post(
				apply_filters(
					'the_content',
					get_post_field(
						'post_content',
						$ad_id
					)
				)
			);
			?>

		</div>


	<?php elseif ( 'html' === $type ) : ?>

		<div class="wrmi-ad-html">

			<?php
			echo wp_kses_post(
				get_post_field(
					'post_content',
					$ad_id
				)
			);
			?>

		</div>


	<?php else : ?>

		<div class="wrmi-ad-placeholder">

			<span>
				<?php echo esc_html( $ad_title ); ?>
			</span>

		</div>

	<?php endif; ?>


	<?php if ( ! empty( $click_url ) ) : ?>

		</a>

	<?php endif; ?>

	</div>

	<?php

	if ( function_exists( 'wrmi_record_ad_impression' ) ) {

		wrmi_record_ad_impression(
			$ad_id
		);

	}

}


/* =========================================================
 * SHORTCODE
 *
 * [wrmi_ad placement="top"]
 * ========================================================= */

function wrmi_ad_shortcode( $atts ) {

	$atts = shortcode_atts(
		array(
			'placement' => '',
		),
		$atts,
		'wrmi_ad'
	);

	$position = sanitize_key(
		$atts['placement']
	);

	if ( empty( $position ) ) {
		return '';
	}

	ob_start();

	wrmi_render_placement(
		$position
	);

	return ob_get_clean();
}

add_shortcode(
	'wrmi_ad',
	'wrmi_ad_shortcode'
);

function wrmi_enqueue_frontend_styles() {
	wp_enqueue_style(
		'wrmi-frontend',
		WRMI_URL . '/assets/wrmi-frontend.css',
		array(),
		WRMI_VERSION
	);
}
add_action( 'wp_enqueue_scripts', 'wrmi_enqueue_frontend_styles' );