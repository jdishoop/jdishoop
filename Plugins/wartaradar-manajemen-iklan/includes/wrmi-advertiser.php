<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Advertiser
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}


/* =========================================================
 * 1. REGISTER ADVERTISER
 * ========================================================= */

add_action(
'init',
'wrmi_register_advertiser'
);

function wrmi_register_advertiser() {

register_post_type(
'wrmi_advertiser',
array(
'labels' => array(
'name'               => 'Advertiser',
'singular_name'      => 'Advertiser',
'menu_name'          => 'Advertiser',
'add_new'            => 'Tambah Advertiser',
'add_new_item'       => 'Tambah Advertiser',
'edit_item'          => 'Edit Advertiser',
'new_item'           => 'Advertiser Baru',
'view_item'          => 'Lihat Advertiser',
'search_items'       => 'Cari Advertiser',
'not_found'          => 'Advertiser tidak ditemukan',
'not_found_in_trash' => 'Advertiser tidak ditemukan di tempat sampah',
),

'public'              => false,
'show_ui'             => true,
'show_in_menu'        => false,
'show_in_admin_bar'   => false,
'show_in_nav_menus'   => false,
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
 * 2. ADVERTISER META BOX
 * ========================================================= */

add_action(
'add_meta_boxes',
'wrmi_advertiser_add_meta_box'
);

function wrmi_advertiser_add_meta_box() {

add_meta_box(
'wrmi_advertiser_settings',
'Pengaturan Advertiser',
'wrmi_advertiser_meta_box_callback',
'wrmi_advertiser',
'normal',
'high'
);
}

function wrmi_advertiser_meta_box_callback( $post ) {

wp_nonce_field(
'wrmi_advertiser_save',
'wrmi_advertiser_nonce'
);

$company = get_post_meta(
$post->ID,
'_wrmi_advertiser_company',
true
);

$contact = get_post_meta(
$post->ID,
'_wrmi_advertiser_contact',
true
);

$email = get_post_meta(
$post->ID,
'_wrmi_advertiser_email',
true
);

$phone = get_post_meta(
$post->ID,
'_wrmi_advertiser_phone',
true
);

$website = get_post_meta(
$post->ID,
'_wrmi_advertiser_website',
true
);

$status = get_post_meta(
$post->ID,
'_wrmi_advertiser_status',
true
);

if ( '' === $status ) {
$status = 'active';
}

?>

<table class="form-table">

<tr>
<th scope="row">
<label for="wrmi_advertiser_company">
Nama Perusahaan
</label>
</th>
<td>
<input
type="text"
id="wrmi_advertiser_company"
name="wrmi_advertiser_company"
value="<?php echo esc_attr( $company ); ?>"
class="regular-text"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_advertiser_contact">
Nama Kontak
</label>
</th>
<td>
<input
type="text"
id="wrmi_advertiser_contact"
name="wrmi_advertiser_contact"
value="<?php echo esc_attr( $contact ); ?>"
class="regular-text"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_advertiser_email">
Email
</label>
</th>
<td>
<input
type="email"
id="wrmi_advertiser_email"
name="wrmi_advertiser_email"
value="<?php echo esc_attr( $email ); ?>"
class="regular-text"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_advertiser_phone">
Telepon
</label>
</th>
<td>
<input
type="text"
id="wrmi_advertiser_phone"
name="wrmi_advertiser_phone"
value="<?php echo esc_attr( $phone ); ?>"
class="regular-text"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_advertiser_website">
Website
</label>
</th>
<td>
<input
type="url"
id="wrmi_advertiser_website"
name="wrmi_advertiser_website"
value="<?php echo esc_attr( $website ); ?>"
class="regular-text"
placeholder="https://contoh.com"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_advertiser_status">
Status
</label>
</th>
<td>
<select
id="wrmi_advertiser_status"
name="wrmi_advertiser_status"
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

</table>

<?php
}


/* =========================================================
 * 3. SAVE ADVERTISER
 * ========================================================= */

add_action(
'save_post_wrmi_advertiser',
'wrmi_advertiser_save'
);

function wrmi_advertiser_save( $post_id ) {

if ( empty( $_POST['wrmi_advertiser_nonce'] ) ) {
return;
}

if (
! wp_verify_nonce(
sanitize_text_field(
wp_unslash(
$_POST['wrmi_advertiser_nonce']
)
),
'wrmi_advertiser_save'
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

$company = '';

if ( isset( $_POST['wrmi_advertiser_company'] ) ) {
$company = sanitize_text_field(
wp_unslash(
$_POST['wrmi_advertiser_company']
)
);
}

$contact = '';

if ( isset( $_POST['wrmi_advertiser_contact'] ) ) {
$contact = sanitize_text_field(
wp_unslash(
$_POST['wrmi_advertiser_contact']
)
);
}

$email = '';

if ( isset( $_POST['wrmi_advertiser_email'] ) ) {
$email = sanitize_email(
wp_unslash(
$_POST['wrmi_advertiser_email']
)
);
}

$phone = '';

if ( isset( $_POST['wrmi_advertiser_phone'] ) ) {
$phone = sanitize_text_field(
wp_unslash(
$_POST['wrmi_advertiser_phone']
)
);
}

$website = '';

if ( isset( $_POST['wrmi_advertiser_website'] ) ) {
$website = esc_url_raw(
wp_unslash(
$_POST['wrmi_advertiser_website']
)
);
}

$status = 'inactive';

if (
isset( $_POST['wrmi_advertiser_status'] )
&& 'active' === $_POST['wrmi_advertiser_status']
) {
$status = 'active';
}

update_post_meta(
$post_id,
'_wrmi_advertiser_company',
$company
);

update_post_meta(
$post_id,
'_wrmi_advertiser_contact',
$contact
);

update_post_meta(
$post_id,
'_wrmi_advertiser_email',
$email
);

update_post_meta(
$post_id,
'_wrmi_advertiser_phone',
$phone
);

update_post_meta(
$post_id,
'_wrmi_advertiser_website',
$website
);

update_post_meta(
$post_id,
'_wrmi_advertiser_status',
$status
);

if ( function_exists( 'wrmi_log_activity' ) ) {

wrmi_log_activity(
'update',
'advertiser',
$post_id,
'Memperbarui data Advertiser.'
);
}
}


/* =========================================================
 * 4. ADMIN MENU
 * ========================================================= */

add_action(
'admin_menu',
'wrmi_advertiser_admin_menu',
30
);

function wrmi_advertiser_admin_menu() {

if (
! current_user_can(
'manage_options'
)
) {
return;
}

add_submenu_page(
'wrmi-dashboard',
'Advertiser',
'Advertiser',
'manage_options',
'edit.php?post_type=wrmi_advertiser'
);
}


/* =========================================================
 * 5. ADVERTISER DATA HELPERS
 * ========================================================= */

function wrmi_get_advertiser_status( $advertiser_id ) {

$status = get_post_meta(
$advertiser_id,
'_wrmi_advertiser_status',
true
);

if ( '' === $status ) {
$status = 'inactive';
}

return $status;
}


function wrmi_get_advertiser_company( $advertiser_id ) {

return get_post_meta(
$advertiser_id,
'_wrmi_advertiser_company',
true
);
}


function wrmi_get_advertiser_contact( $advertiser_id ) {

return get_post_meta(
$advertiser_id,
'_wrmi_advertiser_contact',
true
);
}


function wrmi_get_advertiser_email( $advertiser_id ) {

return sanitize_email(
get_post_meta(
$advertiser_id,
'_wrmi_advertiser_email',
true
)
);
}


function wrmi_get_advertiser_phone( $advertiser_id ) {

return get_post_meta(
$advertiser_id,
'_wrmi_advertiser_phone',
true
);
}


function wrmi_get_advertiser_website( $advertiser_id ) {

return esc_url(
get_post_meta(
$advertiser_id,
'_wrmi_advertiser_website',
true
)
);
}
