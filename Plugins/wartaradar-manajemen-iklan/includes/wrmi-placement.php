<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Placement
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * 1. PLACEMENT DEFINITIONS
 * ========================================================= */

function wrmi_get_placement_definitions() {

	return array(
		'top'     => 'TOP',
		'header'  => 'HEADER',
		'hero'    => 'HERO',
		'sidebar' => 'SIDEBAR',
		'feed'    => 'FEED',
                'mid_content' => 'MID CONTENT',

		'article' => 'ARTICLE',
		'footer'  => 'FOOTER',
	);
}
/**
 * Mendapatkan ukuran standar iklan berdasarkan posisi Placement.
 */
function wrmi_get_placement_size_config( $position = '' ) {

    $position = sanitize_key( $position );

    $sizes = array(

        'top' => array(
            'min_width'      => 320,
            'min_height'     => 50,
            'default_width'  => 970,
            'default_height' => 90,
            'max_width'      => 1200,
            'max_height'     => 120,
        ),

        'header' => array(
            'min_width'      => 320,
            'min_height'     => 50,
            'default_width'  => 728,
            'default_height' => 90,
            'max_width'      => 970,
            'max_height'     => 120,
        ),

        'hero' => array(
            'min_width'      => 320,
            'min_height'     => 180,
            'default_width'  => 970,
            'default_height' => 250,
            'max_width'      => 1200,
            'max_height'     => 300,
        ),

        'feed' => array(
            'min_width'      => 320,
            'min_height'     => 50,
            'default_width'  => 728,
            'default_height' => 90,
            'max_width'      => 970,
            'max_height'     => 120,
        ),

        'sidebar' => array(
            'min_width'      => 280,
            'min_height'     => 200,
            'default_width'  => 300,
            'default_height' => 250,
            'max_width'      => 360,
            'max_height'     => 300,
        ),

        'mid_content' => array(
            'min_width'      => 320,
            'min_height'     => 90,
            'default_width'  => 728,
            'default_height' => 90,
            'max_width'      => 970,
            'max_height'     => 120,
        ),

        'article' => array(
            'min_width'      => 320,
            'min_height'     => 50,
            'default_width'  => 728,
            'default_height' => 90,
            'max_width'      => 970,
            'max_height'     => 120,
        ),

        'footer' => array(
            'min_width'      => 320,
            'min_height'     => 50,
            'default_width'  => 970,
            'default_height' => 90,
            'max_width'      => 1200,
            'max_height'     => 120,
        ),

    );

    /*
     * Ukuran fallback jika posisi tidak dikenal.
     * Tetap menggunakan ukuran standar umum.
     */
    $fallback = array(
        'min_width'      => 320,
        'min_height'     => 50,
        'default_width'  => 728,
        'default_height' => 90,
        'max_width'      => 970,
        'max_height'     => 120,
    );

    return isset( $sizes[ $position ] )
        ? $sizes[ $position ]
        : $fallback;
}

function wrmi_get_placement_label( $placement ) {

	$placements = wrmi_get_placement_definitions();

	$placement = sanitize_key(
		wrmi_placement_normalize_value( $placement )
	);

	if ( isset( $placements[ $placement ] ) ) {
		return $placements[ $placement ];
	}

	return strtoupper( $placement );
}


/* =========================================================
 * 2. NORMALISASI NILAI
 * ========================================================= */

function wrmi_placement_normalize_value( $value ) {

	if ( is_string( $value ) || is_numeric( $value ) ) {
		return trim( (string) $value );
	}

	if ( is_array( $value ) ) {

		$keys = array(
			'value',
			'key',
			'slug',
			'position',
			'name',
			'label',
		);

		foreach ( $keys as $key ) {

			if ( isset( $value[ $key ] ) ) {

				$normalized = wrmi_placement_normalize_value(
					$value[ $key ]
				);

				if ( '' !== $normalized ) {
					return $normalized;
				}
			}
		}
	}

	return '';
}


/* =========================================================
 * 3. REGISTER PLACEMENT
 * ========================================================= */

add_action(
	'init',
	'wrmi_register_placement'
);

function wrmi_register_placement() {

	register_post_type(
		'wrmi_placement',
		array(
			'labels' => array(
				'name'          => 'Placement',
				'singular_name' => 'Placement',
				'menu_name'     => 'Placement',
				'add_new'       => 'Tambah Placement',
				'add_new_item'  => 'Tambah Placement',
				'edit_item'     => 'Edit Placement',
				'new_item'      => 'Placement Baru',
				'view_item'     => 'Lihat Placement',
				'search_items'  => 'Cari Placement',
				'not_found'     => 'Placement tidak ditemukan',
			),

			'public'              => false,
			'show_ui'             => true,
			'show_in_menu'        => false,
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'exclude_from_search' => true,

			'supports' => array(
				'title',
			),

			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}


/* =========================================================
 * 4. META BOX
 * ========================================================= */

add_action(
	'add_meta_boxes',
	'wrmi_placement_add_meta_box'
);

function wrmi_placement_add_meta_box() {

	add_meta_box(
		'wrmi_placement_settings',
		'Pengaturan Placement',
		'wrmi_placement_meta_box_callback',
		'wrmi_placement',
		'normal',
		'high'
	);
}


function wrmi_placement_meta_box_callback( $post ) {

	wp_nonce_field(
		'wrmi_placement_save',
		'wrmi_placement_nonce'
	);

	$ad_id = get_post_meta(
		$post->ID,
		'_wrmi_placement_ad_id',
		true
	);

	$placement = get_post_meta(
		$post->ID,
		'_wrmi_placement_position',
		true
	);

	$status = get_post_meta(
		$post->ID,
		'_wrmi_placement_status',
		true
	);

	$order = get_post_meta(
		$post->ID,
		'_wrmi_placement_order',
		true
	);

	if ( '' === $placement ) {
		$placement = 'top';
	}

	$placement = wrmi_placement_normalize_value(
		$placement
	);

	if ( '' === $placement ) {
		$placement = 'top';
	}

	if ( '' === $status ) {
		$status = 'inactive';
	}

	if ( '' === $order ) {
		$order = 0;
	}

	$ads = get_posts(
		array(
			'post_type'      => 'wrmi_ad',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'orderby'        => 'title',
			'order'          => 'ASC',
		)
	);

	?>

	<table class="form-table">

		<tr>

			<th scope="row">

				<label for="wrmi_placement_ad_id">
					Iklan
				</label>

			</th>

			<td>

				<select
					id="wrmi_placement_ad_id"
					name="wrmi_placement_ad_id"
					class="regular-text"
				>

					<option value="0">
						Pilih Iklan
					</option>

					<?php foreach ( $ads as $ad ) : ?>

						<option
							value="<?php echo esc_attr( $ad->ID ); ?>"
							<?php selected( $ad_id, $ad->ID ); ?>
						>
							<?php
							echo esc_html(
								$ad->post_title
							);
							?>
						</option>

					<?php endforeach; ?>

				</select>

			</td>

		</tr>


		<tr>

			<th scope="row">

				<label for="wrmi_placement_position">
					Posisi
				</label>

			</th>

			<td>

				<select
					id="wrmi_placement_position"
					name="wrmi_placement_position"
					class="regular-text"
				>

					<?php foreach ( wrmi_get_placement_definitions() as $key => $label ) : ?>

						<option
							value="<?php echo esc_attr( $key ); ?>"
							<?php selected( $placement, $key ); ?>
						>
							<?php
							echo esc_html(
								$label
							);
							?>
						</option>

					<?php endforeach; ?>

				</select>

			</td>

		</tr>


		<tr id="wrmi-placement-size-guide">
			<th scope="row">
				<span>Panduan Ukuran</span>
			</th>
			<td>
				<p id="wrmi-placement-size-guide-text" class="description" role="status" aria-live="polite">
					Memuat panduan ukuran...
				</p>
				<p class="description">
					Ukuran ini merupakan panduan slot tampilan, bukan perubahan ukuran berkas gambar.
				</p>
			</td>
		</tr>
		<tr>

			<th scope="row">

				<label for="wrmi_placement_status">
					Status
				</label>

			</th>

			<td>

				<select
					id="wrmi_placement_status"
					name="wrmi_placement_status"
				>

					<option
						value="active"
						<?php selected( $status, 'active' ); ?>
					>
						Aktif
					</option>

					<option
						value="inactive"
						<?php selected( $status, 'inactive' ); ?>
					>
						Nonaktif
					</option>

				</select>

			</td>

		</tr>


		<tr>

			<th scope="row">

				<label for="wrmi_placement_order">
					Urutan
				</label>

			</th>

			<td>

				<input
					type="number"
					id="wrmi_placement_order"
					name="wrmi_placement_order"
					value="<?php echo esc_attr( $order ); ?>"
					min="0"
					step="1"
					class="small-text"
				/>

				<p class="description">
					Angka lebih kecil ditampilkan lebih dahulu.
				</p>

			</td>

		</tr>

	</table>

	<?php
	$wrmi_size_guide = array();

	foreach ( wrmi_get_placement_definitions() as $key => $label ) {
		$size = wrmi_get_placement_size_config( $key );

		$wrmi_size_guide[ $key ] = array(
			'label'   => $label,
			'min'     => $size['min_width'] . ' × ' . $size['min_height'] . ' px',
			'default' => $size['default_width'] . ' × ' . $size['default_height'] . ' px',
			'max'     => $size['max_width'] . ' × ' . $size['max_height'] . ' px',
		);
	}
?>
<script>
(function () {
	'use strict';

	var positionField = document.getElementById('wrmi_placement_position');
	var guideText = document.getElementById('wrmi-placement-size-guide-text');

	if (!positionField || !guideText) {
		return;
	}

	var sizes = <?php
		echo wp_json_encode(
			$wrmi_size_guide,
			JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
		);
	?>;

	function updatePlacementSizeGuide() {
		var item = sizes[positionField.value];

		if (!item) {
			guideText.textContent = 'Panduan ukuran belum tersedia untuk posisi ini.';
			return;
		}

		guideText.textContent =
			'Posisi: ' + item.label +
			' | Minimum: ' + item.min +
			' | Rekomendasi: ' + item.default +
			' | Maksimum: ' + item.max;
	}

	positionField.addEventListener('change', updatePlacementSizeGuide);
	updatePlacementSizeGuide();
})();
</script>
<?php
}


/* =========================================================
 * 5. SAVE PLACEMENT
 * ========================================================= */

add_action(
	'save_post_wrmi_placement',
	'wrmi_placement_save'
);

function wrmi_placement_save( $post_id ) {

	if ( empty( $_POST['wrmi_placement_nonce'] ) ) {
		return;
	}

	if (
		! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash(
					$_POST['wrmi_placement_nonce']
				)
			),
			'wrmi_placement_save'
		)
	) {
		return;
	}

	if (
		defined( 'DOING_AUTOSAVE' )
		&& DOING_AUTOSAVE
	) {
		return;
	}

	if (
		wp_is_post_revision( $post_id )
	) {
		return;
	}

	if (
		! current_user_can(
			'edit_post',
			$post_id
		)
	) {
		return;
	}


	/* -----------------------------------------------------
	 * IKLAN
	 * ----------------------------------------------------- */

	$ad_id = 0;

	if ( isset( $_POST['wrmi_placement_ad_id'] ) ) {

		$ad_id = absint(
			$_POST['wrmi_placement_ad_id']
		);
	}


	/* -----------------------------------------------------
	 * POSISI
	 * ----------------------------------------------------- */

	$placement = 'top';

	if ( isset( $_POST['wrmi_placement_position'] ) ) {

		$posted_placement = sanitize_key(
			wp_unslash(
				$_POST['wrmi_placement_position']
			)
		);

		$allowed_placements = array_keys(
			wrmi_get_placement_definitions()
		);

		if (
			in_array(
				$posted_placement,
				$allowed_placements,
				true
			)
		) {
			$placement = $posted_placement;
		}
	}


	/* -----------------------------------------------------
	 * STATUS
	 * ----------------------------------------------------- */

	$status = 'inactive';

	if ( isset( $_POST['wrmi_placement_status'] ) ) {

		$posted_status = sanitize_key(
			wp_unslash(
				$_POST['wrmi_placement_status']
			)
		);

		if (
			in_array(
				$posted_status,
				array(
					'active',
					'inactive',
				),
				true
			)
		) {
			$status = $posted_status;
		}
	}


	/* -----------------------------------------------------
	 * URUTAN
	 * ----------------------------------------------------- */

	$order = 0;

	if ( isset( $_POST['wrmi_placement_order'] ) ) {

		$order = absint(
			$_POST['wrmi_placement_order']
		);
	}


	/* -----------------------------------------------------
	 * SIMPAN
	 * ----------------------------------------------------- */

	update_post_meta(
		$post_id,
		'_wrmi_placement_ad_id',
		$ad_id
	);

	update_post_meta(
		$post_id,
		'_wrmi_placement_position',
		$placement
	);

	update_post_meta(
		$post_id,
		'_wrmi_placement_status',
		$status
	);

	update_post_meta(
		$post_id,
		'_wrmi_placement_order',
		$order
	);


	/* -----------------------------------------------------
	 * LOG
	 * ----------------------------------------------------- */

	if ( function_exists( 'wrmi_log_activity' ) ) {

		wrmi_log_activity(
			'update',
			'placement',
			$post_id,
			'Memperbarui Placement iklan.'
		);
	}
}


/* =========================================================
 * 6. ADMIN MENU
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_placement_admin_menu',
	35
);

function wrmi_placement_admin_menu() {

	if (
		! current_user_can(
			'manage_options'
		)
	) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Placement',
		'Placement',
		'manage_options',
		'edit.php?post_type=wrmi_placement'
	);
}


/* =========================================================
 * 7. DATA HELPERS
 * ========================================================= */

function wrmi_get_placement_ad_id( $placement_id ) {

	return absint(
		get_post_meta(
			$placement_id,
			'_wrmi_placement_ad_id',
			true
		)
	);
}


function wrmi_get_placement_position( $placement_id ) {

	$position = get_post_meta(
		$placement_id,
		'_wrmi_placement_position',
		true
	);

	$position = wrmi_placement_normalize_value(
		$position
	);

	if ( '' === $position ) {
		$position = 'top';
	}

	return sanitize_key( $position );
}


function wrmi_get_placement_status( $placement_id ) {

	$status = get_post_meta(
		$placement_id,
		'_wrmi_placement_status',
		true
	);

	$status = wrmi_placement_normalize_value(
		$status
	);

	if ( '' === $status ) {
		$status = 'inactive';
	}

	return sanitize_key( $status );
}


function wrmi_get_placement_order( $placement_id ) {

	return absint(
		get_post_meta(
			$placement_id,
			'_wrmi_placement_order',
			true
		)
	);
}


/* =========================================================
 * 8. ACTIVE PLACEMENTS
 * ========================================================= */

function wrmi_get_active_placements(
	$position = '',
	$limit = 20
) {

	$meta_query = array(
		array(
			'key'     => '_wrmi_placement_status',
			'value'   => 'active',
			'compare' => '=',
		),
	);

	if ( ! empty( $position ) ) {

		$meta_query[] = array(
			'key'     => '_wrmi_placement_position',
			'value'   => sanitize_key( $position ),
			'compare' => '=',
		);
	}

	return new WP_Query(
		array(
			'post_type'           => 'wrmi_placement',
			'post_status'         => 'publish',
			'posts_per_page'      => absint( $limit ),
			'orderby'             => 'meta_value_num',
			'meta_key'            => '_wrmi_placement_order',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,
			'meta_query'          => $meta_query,
		)
	);
}





