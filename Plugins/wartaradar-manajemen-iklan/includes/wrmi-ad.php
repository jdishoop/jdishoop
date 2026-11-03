<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}


/* =========================================================
 * 1. REGISTER IKLAN
 * ========================================================= */

add_action(
'init',
'wrmi_register_ad'
);

function wrmi_register_ad() {

register_post_type(
'wrmi_ad',
array(
'labels' => array(
'name'               => 'Semua Iklan',
'singular_name'      => 'Iklan',
'menu_name'          => 'Semua Iklan',
'add_new'            => 'Tambah Iklan',
'add_new_item'       => 'Tambah Iklan',
'edit_item'          => 'Edit Iklan',
'new_item'           => 'Iklan Baru',
'view_item'          => 'Lihat Iklan',
'search_items'       => 'Cari Iklan',
'not_found'          => 'Iklan tidak ditemukan',
'not_found_in_trash' => 'Iklan tidak ditemukan di tempat sampah',
),

'public'              => false,
'show_ui'             => true,
'show_in_menu'        => false,
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
 * 2. IKLAN META BOX
 * ========================================================= */

add_action(
'add_meta_boxes',
'wrmi_ad_add_meta_box'
);

function wrmi_ad_add_meta_box() {

add_meta_box(
'wrmi_ad_settings',
'Pengaturan Iklan',
'wrmi_ad_meta_box_callback',
'wrmi_ad',
'normal',
'high'
);
}

function wrmi_ad_meta_box_callback( $post ) {

wp_nonce_field(
'wrmi_ad_save',
'wrmi_ad_nonce'
);

$advertiser_id = get_post_meta(
$post->ID,
'_wrmi_ad_advertiser_id',
true
);

$campaign_id = get_post_meta(
$post->ID,
'_wrmi_ad_campaign_id',
true
);

$status = get_post_meta(
$post->ID,
'_wrmi_ad_status',
true
);

$type = get_post_meta(
$post->ID,
'_wrmi_ad_type',
true
);

$target_url = get_post_meta(
$post->ID,
'_wrmi_ad_target_url',
true
);

$start_date = get_post_meta(
$post->ID,
'_wrmi_ad_start_date',
true
);

$end_date = get_post_meta(
$post->ID,
'_wrmi_ad_end_date',
true
);

if ( '' === $status ) {
$status = 'pending';
}

if ( '' === $type ) {
$type = 'banner';
}

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

?>

<table class="form-table">

<tr>
<th scope="row">
<label for="wrmi_ad_advertiser_id">
Advertiser
</label>
</th>

<td>
<select
id="wrmi_ad_advertiser_id"
name="wrmi_ad_advertiser_id"
class="regular-text"
>

<option value="0">
Pilih Advertiser
</option>

<?php foreach ( $advertisers as $advertiser ) : ?>

<option
value="<?php echo esc_attr( $advertiser->ID ); ?>"
<?php selected( $advertiser_id, $advertiser->ID ); ?>
>
<?php echo esc_html( $advertiser->post_title ); ?>
</option>

<?php endforeach; ?>

</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_ad_campaign_id">
Campaign
</label>
</th>

<td>
<select
id="wrmi_ad_campaign_id"
name="wrmi_ad_campaign_id"
class="regular-text"
>

<option value="0">
Pilih Campaign
</option>

<?php foreach ( $campaigns as $campaign ) : ?>

<option
value="<?php echo esc_attr( $campaign->ID ); ?>"
<?php selected( $campaign_id, $campaign->ID ); ?>
>
<?php echo esc_html( $campaign->post_title ); ?>
</option>

<?php endforeach; ?>

</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_ad_status">
Status
</label>
</th>

<td>
<select
id="wrmi_ad_status"
name="wrmi_ad_status"
>

<option
value="pending"
<?php selected( $status, 'pending' ); ?>
>
Menunggu
</option>

<option
value="active"
<?php selected( $status, 'active' ); ?>
>
Aktif
</option>

<option
value="paused"
<?php selected( $status, 'paused' ); ?>
>
Dijeda
</option>

<option
value="finished"
<?php selected( $status, 'finished' ); ?>
>
Selesai
</option>

</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_ad_type">
Jenis Iklan
</label>
</th>

<td>
<select
id="wrmi_ad_type"
name="wrmi_ad_type"
>

<option
value="banner"
<?php selected( $type, 'banner' ); ?>
>
Banner
</option>

<option
value="image"
<?php selected( $type, 'image' ); ?>
>
Gambar
</option>

<option
value="text"
<?php selected( $type, 'text' ); ?>
>
Teks
</option>

<option
value="html"
<?php selected( $type, 'html' ); ?>
>
HTML
</option>

</select>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_ad_target_url">
URL Tujuan
</label>
</th>

<td>
<input
type="url"
id="wrmi_ad_target_url"
name="wrmi_ad_target_url"
value="<?php echo esc_attr( $target_url ); ?>"
class="regular-text"
placeholder="https://contoh.com"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_ad_start_date">
Tanggal Mulai
</label>
</th>

<td>
<input
type="date"
id="wrmi_ad_start_date"
name="wrmi_ad_start_date"
value="<?php echo esc_attr( $start_date ); ?>"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_ad_end_date">
Tanggal Selesai
</label>
</th>

<td>
<input
type="date"
id="wrmi_ad_end_date"
name="wrmi_ad_end_date"
value="<?php echo esc_attr( $end_date ); ?>"
/>
</td>
</tr>

</table>

<p>
<strong>Media Iklan:</strong>
Pasang gambar/banner menggunakan Featured Image.
</p>

<?php
}


/* =========================================================
 * 3. SAVE IKLAN
 * ========================================================= */

add_action(
'save_post_wrmi_ad',
'wrmi_ad_save'
);

function wrmi_ad_save( $post_id ) {

if ( empty( $_POST['wrmi_ad_nonce'] ) ) {
return;
}

if (
! wp_verify_nonce(
sanitize_text_field(
wp_unslash(
$_POST['wrmi_ad_nonce']
)
),
'wrmi_ad_save'
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

$advertiser_id = 0;

if ( isset( $_POST['wrmi_ad_advertiser_id'] ) ) {
$advertiser_id = absint(
$_POST['wrmi_ad_advertiser_id']
);
}

$campaign_id = 0;

if ( isset( $_POST['wrmi_ad_campaign_id'] ) ) {
$campaign_id = absint(
$_POST['wrmi_ad_campaign_id']
);
}

if ( $advertiser_id > 0 ) {

if (

'wrmi_advertiser' !== get_post_type( $advertiser_id )

|| 'publish' !== get_post_status( $advertiser_id )

) {

wrmi_ad_set_relation_notice( 'Advertiser tidak valid atau belum berstatus terbit. Relasi iklan tidak diperbarui.' );
return;

}

}



if ( $campaign_id > 0 ) {

if (

'wrmi_campaign' !== get_post_type( $campaign_id )

|| 'publish' !== get_post_status( $campaign_id )

) {

wrmi_ad_set_relation_notice( 'Campaign tidak valid atau belum berstatus terbit. Relasi iklan tidak diperbarui.' );
return;

}



$campaign_advertiser_id = absint(

get_post_meta(

$campaign_id,

'_wrmi_campaign_advertiser_id',

true

)

);



if (

$advertiser_id <= 0

|| $campaign_advertiser_id !== $advertiser_id

) {

wrmi_ad_set_relation_notice( 'Campaign yang dipilih bukan milik advertiser tersebut. Relasi iklan tidak diperbarui.' );
return;

}

}
$status = 'pending';

if ( isset( $_POST['wrmi_ad_status'] ) ) {

$allowed_statuses = array(
'pending',
'active',
'paused',
'finished',
);

$posted_status = sanitize_key(
wp_unslash(
$_POST['wrmi_ad_status']
)
);

if (
in_array(
$posted_status,
$allowed_statuses,
true
)
) {
$status = $posted_status;
}
}

$type = 'banner';

if ( isset( $_POST['wrmi_ad_type'] ) ) {

$allowed_types = array(
'banner',
'image',
'text',
'html',
);

$posted_type = sanitize_key(
wp_unslash(
$_POST['wrmi_ad_type']
)
);

if (
in_array(
$posted_type,
$allowed_types,
true
)
) {
$type = $posted_type;
}
}

$target_url = '';

if ( isset( $_POST['wrmi_ad_target_url'] ) ) {
$target_url = esc_url_raw(
wp_unslash(
$_POST['wrmi_ad_target_url']
)
);
}

$start_date = '';

if ( isset( $_POST['wrmi_ad_start_date'] ) ) {
$start_date = sanitize_text_field(
wp_unslash(
$_POST['wrmi_ad_start_date']
)
);
}

$end_date = '';

if ( isset( $_POST['wrmi_ad_end_date'] ) ) {
$end_date = sanitize_text_field(
wp_unslash(
$_POST['wrmi_ad_end_date']
)
);
}

update_post_meta(
$post_id,
'_wrmi_ad_advertiser_id',
$advertiser_id
);

update_post_meta(
$post_id,
'_wrmi_ad_campaign_id',
$campaign_id
);

update_post_meta(
$post_id,
'_wrmi_ad_status',
$status
);

update_post_meta(
$post_id,
'_wrmi_ad_type',
$type
);

update_post_meta(
$post_id,
'_wrmi_ad_target_url',
$target_url
);

update_post_meta(
$post_id,
'_wrmi_ad_start_date',
$start_date
);

update_post_meta(
$post_id,
'_wrmi_ad_end_date',
$end_date
);

if ( function_exists( 'wrmi_log_activity' ) ) {

wrmi_log_activity(
'update',
'ad',
$post_id,
'Memperbarui data Iklan.'
);
}
}


/* =========================================================
 * 4. ADMIN MENU
 * ========================================================= */

add_action(
'admin_menu',
'wrmi_ad_admin_menu',
30
);

function wrmi_ad_admin_menu() {

if (
! current_user_can(
'manage_options'
)
) {
return;
}

add_submenu_page(
'wrmi-dashboard',
'Semua Iklan',
'Semua Iklan',
'manage_options',
'edit.php?post_type=wrmi_ad'
);

add_submenu_page(
'wrmi-dashboard',
'Tambah Iklan',
'Tambah Iklan',
'manage_options',
'post-new.php?post_type=wrmi_ad'
);
}


/* =========================================================
 * 5. IKLAN DATA HELPERS
 * ========================================================= */

function wrmi_get_ad_advertiser_id( $ad_id ) {

return absint(
get_post_meta(
$ad_id,
'_wrmi_ad_advertiser_id',
true
)
);
}


function wrmi_get_ad_campaign_id( $ad_id ) {

return absint(
get_post_meta(
$ad_id,
'_wrmi_ad_campaign_id',
true
)
);
}


function wrmi_get_ad_status( $ad_id ) {

$status = get_post_meta(
$ad_id,
'_wrmi_ad_status',
true
);

if ( '' === $status ) {
$status = 'pending';
}

return $status;
}


function wrmi_get_ad_type( $ad_id ) {

$type = get_post_meta(
$ad_id,
'_wrmi_ad_type',
true
);

if ( '' === $type ) {
$type = 'banner';
}

return $type;
}


function wrmi_get_ad_target_url( $ad_id ) {

return esc_url(
get_post_meta(
$ad_id,
'_wrmi_ad_target_url',
true
)
);
}


function wrmi_get_ad_start_date( $ad_id ) {

return get_post_meta(
$ad_id,
'_wrmi_ad_start_date',
true
);
}


function wrmi_get_ad_end_date( $ad_id ) {

return get_post_meta(
$ad_id,
'_wrmi_ad_end_date',
true
);
}


function wrmi_get_active_ads( $limit = 20 ) {

return new WP_Query(
array(
'post_type'           => 'wrmi_ad',
'post_status'         => 'publish',
'posts_per_page'      => absint( $limit ),
'orderby'             => 'date',
'order'               => 'DESC',
'ignore_sticky_posts' => true,

'meta_query' => array(
array(
'key'     => '_wrmi_ad_status',
'value'   => 'active',
'compare' => '=',
),
),
)
);
}


/**
 * Notifikasi admin untuk kegagalan validasi relasi iklan.
 */
function wrmi_ad_set_relation_notice( $message ) {
    set_transient(
        'wrmi_ad_relation_notice_' . get_current_user_id(),
        sanitize_text_field( $message ),
        60
    );
}

function wrmi_ad_relation_admin_notice() {
    $key = 'wrmi_ad_relation_notice_' . get_current_user_id();
    $message = get_transient( $key );

    if ( false === $message ) {
        return;
    }

    delete_transient( $key );

    echo '<div class="notice notice-error is-dismissible"><p>';
    echo esc_html( $message );
    echo '</p></div>';
}
add_action( 'admin_notices', 'wrmi_ad_relation_admin_notice' );
