<?php
/**
 * WartaRadar Manajemen Iklan
 * Modul Campaign
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}


/* =========================================================
 * 1. REGISTER CAMPAIGN
 * ========================================================= */

add_action(
'init',
'wrmi_register_campaign'
);

function wrmi_register_campaign() {

register_post_type(
'wrmi_campaign',
array(
'labels' => array(
'name'               => 'Campaign',
'singular_name'      => 'Campaign',
'menu_name'          => 'Campaign',
'add_new'            => 'Tambah Campaign',
'add_new_item'       => 'Tambah Campaign',
'edit_item'          => 'Edit Campaign',
'new_item'           => 'Campaign Baru',
'view_item'          => 'Lihat Campaign',
'search_items'       => 'Cari Campaign',
'not_found'          => 'Campaign tidak ditemukan',
'not_found_in_trash' => 'Campaign tidak ditemukan di tempat sampah',
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
),

'capability_type' => 'post',
'map_meta_cap'    => true,
)
);
}


/* =========================================================
 * 2. CAMPAIGN META BOX
 * ========================================================= */

add_action(
'add_meta_boxes',
'wrmi_campaign_add_meta_box'
);

function wrmi_campaign_add_meta_box() {

add_meta_box(
'wrmi_campaign_settings',
'Pengaturan Campaign',
'wrmi_campaign_meta_box_callback',
'wrmi_campaign',
'normal',
'high'
);
}

function wrmi_campaign_meta_box_callback( $post ) {

wp_nonce_field(
'wrmi_campaign_save',
'wrmi_campaign_nonce'
);

$advertiser_id = get_post_meta(
$post->ID,
'_wrmi_campaign_advertiser_id',
true
);

$status = get_post_meta(
$post->ID,
'_wrmi_campaign_status',
true
);

$start_date = get_post_meta(
$post->ID,
'_wrmi_campaign_start_date',
true
);

$end_date = get_post_meta(
$post->ID,
'_wrmi_campaign_end_date',
true
);

if ( '' === $status ) {
$status = 'pending';
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

?>

<table class="form-table">

<tr>
<th scope="row">
<label for="wrmi_campaign_advertiser_id">
Advertiser
</label>
</th>

<td>
<select
id="wrmi_campaign_advertiser_id"
name="wrmi_campaign_advertiser_id"
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

<p class="description">
Advertiser yang memiliki Campaign ini.
</p>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_campaign_status">
Status
</label>
</th>

<td>
<select
id="wrmi_campaign_status"
name="wrmi_campaign_status"
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
<label for="wrmi_campaign_start_date">
Tanggal Mulai
</label>
</th>

<td>
<input
type="date"
id="wrmi_campaign_start_date"
name="wrmi_campaign_start_date"
value="<?php echo esc_attr( $start_date ); ?>"
/>
</td>
</tr>

<tr>
<th scope="row">
<label for="wrmi_campaign_end_date">
Tanggal Selesai
</label>
</th>

<td>
<input
type="date"
id="wrmi_campaign_end_date"
name="wrmi_campaign_end_date"
value="<?php echo esc_attr( $end_date ); ?>"
/>
</td>
</tr>

</table>

<?php
}


/* =========================================================
 * 3. SAVE CAMPAIGN
 * ========================================================= */

add_action(
'save_post_wrmi_campaign',
'wrmi_campaign_save'
);

function wrmi_campaign_save( $post_id ) {

if ( empty( $_POST['wrmi_campaign_nonce'] ) ) {
return;
}

if (
! wp_verify_nonce(
sanitize_text_field(
wp_unslash(
$_POST['wrmi_campaign_nonce']
)
),
'wrmi_campaign_save'
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

if ( isset( $_POST['wrmi_campaign_advertiser_id'] ) ) {
$advertiser_id = absint(
$_POST['wrmi_campaign_advertiser_id']
);
}

if ( $advertiser_id > 0 ) {

if (

'wrmi_advertiser' !== get_post_type( $advertiser_id )

|| 'publish' !== get_post_status( $advertiser_id )

) {

wrmi_campaign_set_relation_notice( 'Advertiser tidak valid atau belum berstatus terbit. Relasi campaign tidak diperbarui.' );
return;

}

}
$status = 'pending';

if ( isset( $_POST['wrmi_campaign_status'] ) ) {

$allowed_statuses = array(
'pending',
'active',
'paused',
'finished',
);

$posted_status = sanitize_key(
wp_unslash(
$_POST['wrmi_campaign_status']
)
);

if ( in_array( $posted_status, $allowed_statuses, true ) ) {
$status = $posted_status;
}
}

$start_date = '';

if ( isset( $_POST['wrmi_campaign_start_date'] ) ) {
$start_date = sanitize_text_field(
wp_unslash(
$_POST['wrmi_campaign_start_date']
)
);
}

$end_date = '';

if ( isset( $_POST['wrmi_campaign_end_date'] ) ) {
$end_date = sanitize_text_field(
wp_unslash(
$_POST['wrmi_campaign_end_date']
)
);
}

update_post_meta(
$post_id,
'_wrmi_campaign_advertiser_id',
$advertiser_id
);

update_post_meta(
$post_id,
'_wrmi_campaign_status',
$status
);

update_post_meta(
$post_id,
'_wrmi_campaign_start_date',
$start_date
);

update_post_meta(
$post_id,
'_wrmi_campaign_end_date',
$end_date
);

if ( function_exists( 'wrmi_log_activity' ) ) {

wrmi_log_activity(
'update',
'campaign',
$post_id,
'Memperbarui data Campaign.'
);
}
}


/* =========================================================
 * 4. ADMIN MENU
 * ========================================================= */

add_action(
'admin_menu',
'wrmi_campaign_admin_menu',
30
);

function wrmi_campaign_admin_menu() {

if (
! current_user_can(
'manage_options'
)
) {
return;
}

add_submenu_page(
'wrmi-dashboard',
'Campaign',
'Campaign',
'manage_options',
'edit.php?post_type=wrmi_campaign'
);
}


/* =========================================================
 * 5. CAMPAIGN DATA HELPERS
 * ========================================================= */

function wrmi_get_campaign_advertiser_id( $campaign_id ) {

return absint(
get_post_meta(
$campaign_id,
'_wrmi_campaign_advertiser_id',
true
)
);
}


function wrmi_get_campaign_status( $campaign_id ) {

$status = get_post_meta(
$campaign_id,
'_wrmi_campaign_status',
true
);

if ( '' === $status ) {
$status = 'pending';
}

return $status;
}


function wrmi_get_campaign_start_date( $campaign_id ) {

return get_post_meta(
$campaign_id,
'_wrmi_campaign_start_date',
true
);
}


function wrmi_get_campaign_end_date( $campaign_id ) {

return get_post_meta(
$campaign_id,
'_wrmi_campaign_end_date',
true
);
}


function wrmi_get_active_campaigns( $limit = 20 ) {

return new WP_Query(
array(
'post_type'           => 'wrmi_campaign',
'post_status'         => 'publish',
'posts_per_page'      => absint( $limit ),
'orderby'             => 'date',
'order'               => 'DESC',
'ignore_sticky_posts' => true,

'meta_query' => array(
array(
'key'     => '_wrmi_campaign_status',
'value'   => 'active',
'compare' => '=',
),
),
)
);
}


/**
 * Notifikasi admin untuk kegagalan validasi relasi campaign.
 */
function wrmi_campaign_set_relation_notice( $message ) {
    set_transient(
        'wrmi_campaign_relation_notice_' . get_current_user_id(),
        sanitize_text_field( $message ),
        60
    );
}

function wrmi_campaign_relation_admin_notice() {
    $key = 'wrmi_campaign_relation_notice_' . get_current_user_id();
    $message = get_transient( $key );

    if ( false === $message ) {
        return;
    }

    delete_transient( $key );

    echo '<div class="notice notice-error is-dismissible"><p>';
    echo esc_html( $message );
    echo '</p></div>';
}
add_action( 'admin_notices', 'wrmi_campaign_relation_admin_notice' );
