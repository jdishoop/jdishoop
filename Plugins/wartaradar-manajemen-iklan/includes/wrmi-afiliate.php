<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Affiliate
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/* =========================================================
 * 1. REGISTER AFFILIATE
 * ========================================================= */

add_action(
	'init',
	'wrmi_register_affiliate'
);


/**
 * Register Affiliate Custom Post Type.
 */
function wrmi_register_affiliate() {

	register_post_type(
		'wrmi_affiliate',
		array(
			'labels' => array(
				'name'               => 'Affiliate',
				'singular_name'      => 'Affiliate',
				'menu_name'          => 'Affiliate',
				'add_new'            => 'Tambah Affiliate',
				'add_new_item'       => 'Tambah Affiliate',
				'edit_item'          => 'Edit Affiliate',
				'new_item'           => 'Affiliate Baru',
				'view_item'          => 'Lihat Affiliate',
				'search_items'       => 'Cari Affiliate',
				'not_found'          => 'Affiliate tidak ditemukan',
				'not_found_in_trash' => 'Affiliate tidak ditemukan di tempat sampah',
			),

			'public'             => false,
			'show_ui'            => true,
			'show_in_menu'       => false,
			'show_in_admin_bar'  => false,
			'show_in_nav_menus'  => false,
			'exclude_from_search' => true,

			'supports' => array(
				'title',
				'editor',
				'thumbnail',
			),

			'capability_type' => 'post',
			'map_meta_cap'    => true,
		)
	);
}


/* =========================================================
 * 2. AFFILIATE META BOX
 * ========================================================= */

add_action(
	'add_meta_boxes',
	'wrmi_affiliate_add_meta_box'
);


/**
 * Register Affiliate settings meta box.
 */
function wrmi_affiliate_add_meta_box() {

	add_meta_box(
		'wrmi_affiliate_settings',
		'Pengaturan Affiliate',
		'wrmi_affiliate_meta_box_callback',
		'wrmi_affiliate',
		'normal',
		'high'
	);
}


/**
 * Render Affiliate settings.
 *
 * @param WP_Post $post Current post.
 */
function wrmi_affiliate_meta_box_callback( $post ) {

	wp_nonce_field(
		'wrmi_affiliate_save',
		'wrmi_affiliate_nonce'
	);

	$website = get_post_meta(
		$post->ID,
		'_wrmi_affiliate_website',
		true
	);

	$status = get_post_meta(
		$post->ID,
		'_wrmi_affiliate_status',
		true
	);

	$order = get_post_meta(
		$post->ID,
		'_wrmi_affiliate_order',
		true
	);

	if ( '' === $status ) {
		$status = 'active';
	}

	if ( '' === $order ) {
		$order = 0;
	}

	?>

	<table class="form-table">

		<tr>
			<th scope="row">
				<label for="wrmi_affiliate_website">
					Website
				</label>
			</th>

			<td>
				<input
					type="url"
					id="wrmi_affiliate_website"
					name="wrmi_affiliate_website"
					value="<?php echo esc_attr( $website ); ?>"
					class="regular-text"
					placeholder="https://contoh.com"
				/>

				<p class="description">
					URL website Affiliate.
				</p>
			</td>
		</tr>

		<tr>
			<th scope="row">
				<label for="wrmi_affiliate_status">
					Status
				</label>
			</th>

			<td>
				<select
					id="wrmi_affiliate_status"
					name="wrmi_affiliate_status"
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
				<label for="wrmi_affiliate_order">
					Urutan
				</label>
			</th>

			<td>
				<input
					type="number"
					id="wrmi_affiliate_order"
					name="wrmi_affiliate_order"
					value="<?php echo esc_attr( $order ); ?>"
					min="0"
					step="1"
					class="small-text"
				/>

				<p class="description">
					Angka lebih kecil tampil lebih dahulu di Footer.
				</p>
			</td>
		</tr>

	</table>

	<?php
}


/* =========================================================
 * 3. SAVE AFFILIATE
 * ========================================================= */

add_action(
	'save_post_wrmi_affiliate',
	'wrmi_affiliate_save'
);


/**
 * Save Affiliate settings.
 *
 * @param int $post_id Post ID.
 */
function wrmi_affiliate_save( $post_id ) {

	if (
		empty( $_POST['wrmi_affiliate_nonce'] )
	) {
		return;
	}

	if (
		! wp_verify_nonce(
			sanitize_text_field(
				wp_unslash(
					$_POST['wrmi_affiliate_nonce']
				)
			),
			'wrmi_affiliate_save'
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
		! current_user_can(
			'edit_post',
			$post_id
		)
	) {
		return;
	}

	$website = '';

	if ( isset( $_POST['wrmi_affiliate_website'] ) ) {
		$website = esc_url_raw(
			wp_unslash(
				$_POST['wrmi_affiliate_website']
			)
		);
	}

	$status = 'inactive';

	if (
		isset( $_POST['wrmi_affiliate_status'] )
		&& 'active' === $_POST['wrmi_affiliate_status']
	) {
		$status = 'active';
	}

	$order = 0;

	if ( isset( $_POST['wrmi_affiliate_order'] ) ) {
		$order = absint(
			$_POST['wrmi_affiliate_order']
		);
	}

	update_post_meta(
		$post_id,
		'_wrmi_affiliate_website',
		$website
	);

	update_post_meta(
		$post_id,
		'_wrmi_affiliate_status',
		$status
	);

	update_post_meta(
		$post_id,
		'_wrmi_affiliate_order',
		$order
	);

	if ( function_exists( 'wrmi_log_activity' ) ) {

		wrmi_log_activity(
			'update',
			'affiliate',
			$post_id,
			'Memperbarui data Affiliate.'
		);
	}
}


/* =========================================================
 * 4. ADMIN MENU
 * ========================================================= */

add_action(
	'admin_menu',
	'wrmi_affiliate_admin_menu',
	30
);


/**
 * Register Affiliate submenu.
 */
function wrmi_affiliate_admin_menu() {

	if (
		! current_user_can(
			'manage_options'
		)
	) {
		return;
	}

	add_submenu_page(
		'wrmi-dashboard',
		'Affiliate',
		'Affiliate',
		'manage_options',
		'edit.php?post_type=wrmi_affiliate'
	);
}


/* =========================================================
 * 5. FOOTER DATA
 * ========================================================= */

/**
 * Get active Affiliate records for Footer.
 *
 * @param int $limit Number of Affiliate records.
 * @return WP_Query
 */
function wrmi_get_footer_affiliates( $limit = 12 ) {

	return new WP_Query(
		array(
			'post_type'           => 'wrmi_affiliate',
			'post_status'         => 'publish',
			'posts_per_page'      => absint( $limit ),
			'orderby'             => 'meta_value_num',
			'meta_key'            => '_wrmi_affiliate_order',
			'order'               => 'ASC',
			'ignore_sticky_posts' => true,

			'meta_query' => array(
				array(
					'key'     => '_wrmi_affiliate_status',
					'value'   => 'active',
					'compare' => '=',
				),
			),
		)
	);
}


/* =========================================================
 * 6. FOOTER OUTPUT HELPER
 * ========================================================= */

/**
 * Get Affiliate website.
 *
 * @param int $affiliate_id Affiliate ID.
 * @return string
 */
function wrmi_get_affiliate_website( $affiliate_id ) {

	return esc_url(
		get_post_meta(
			$affiliate_id,
			'_wrmi_affiliate_website',
			true
		)
	);
}


/**
 * Get Affiliate logo HTML.
 *
 * @param int $affiliate_id Affiliate ID.
 * @return string
 */
function wrmi_get_affiliate_logo( $affiliate_id ) {

	if ( ! has_post_thumbnail( $affiliate_id ) ) {
		return '';
	}

	return get_the_post_thumbnail(
		$affiliate_id,
		'medium',
		array(
			'loading' => 'lazy',
		)
	);
}
