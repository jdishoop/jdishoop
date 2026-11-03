<?php
/**
 * WartaRadar Manajemen Iklan
 * Dashboard Iklan
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}


/* =========================================================
 * DASHBOARD DATA
 * ========================================================= */

function wrmi_dashboard_count_posts( $post_type, $post_status = 'publish' ) {

return (int) wp_count_posts( $post_type )->$post_status;
}


function wrmi_dashboard_get_statistic( $key ) {

if ( function_exists( 'wrmi_get_total_ad_statistics' ) ) {

$stats = wrmi_get_total_ad_statistics();

if ( is_array( $stats ) && isset( $stats[ $key ] ) ) {
return $stats[ $key ];
}
}

return 0;
}


/* =========================================================
 * DASHBOARD PAGE
 * ========================================================= */

function wrmi_dashboard_page() {

if ( ! current_user_can( 'manage_options' ) ) {
wp_die(
esc_html__(
'Anda tidak memiliki izin untuk mengakses halaman ini.',
'wartaradar-manajemen-iklan'
)
);
}

$advertiser_total = wrmi_dashboard_count_posts(
'wrmi_advertiser'
);

$campaign_total = wrmi_dashboard_count_posts(
'wrmi_campaign'
);

$ad_total = wrmi_dashboard_count_posts(
'wrmi_ad'
);

$placement_total = wrmi_dashboard_count_posts(
'wrmi_placement'
);

$active_ads = 0;
$pending_ads = 0;
$paused_ads = 0;
$finished_ads = 0;

$ad_query = new WP_Query(
array(
'post_type'      => 'wrmi_ad',
'post_status'    => 'publish',
'posts_per_page' => -1,
'fields'         => 'ids',
)
);

if ( ! empty( $ad_query->posts ) ) {

foreach ( $ad_query->posts as $ad_id ) {

$status = function_exists( 'wrmi_get_ad_status' )
? wrmi_get_ad_status( $ad_id )
: '';

switch ( $status ) {

case 'active':
$active_ads++;
break;

case 'pending':
$pending_ads++;
break;

case 'paused':
$paused_ads++;
break;

case 'finished':
$finished_ads++;
break;
}
}
}

wp_reset_postdata();

$impressions = wrmi_dashboard_get_statistic(
'impressions'
);

$clicks = wrmi_dashboard_get_statistic(
'clicks'
);

$ctr = 0;

if ( $impressions > 0 ) {
$ctr = ( $clicks / $impressions ) * 100;
}

$placements = function_exists( 'wrmi_get_placement_definitions' )
? wrmi_get_placement_definitions()
: array();
?>

<div class="wrap">

<h1>MANAJEMEN IKLAN</h1>

<p>
Pusat pengelolaan Affiliate, Advertiser, Campaign, Iklan,
Placement, Statistik, Laporan, dan Log Aktivitas WartaRadar.
</p>


<h2>Ringkasan</h2>

<table class="widefat striped">

<thead>
<tr>
<th>Data</th>
<th>Jumlah</th>
</tr>
</thead>

<tbody>

<tr>
<td>Advertiser</td>
<td><?php echo esc_html( $advertiser_total ); ?></td>
</tr>

<tr>
<td>Campaign</td>
<td><?php echo esc_html( $campaign_total ); ?></td>
</tr>

<tr>
<td>Semua Iklan</td>
<td><?php echo esc_html( $ad_total ); ?></td>
</tr>

<tr>
<td>Placement</td>
<td><?php echo esc_html( $placement_total ); ?></td>
</tr>

</tbody>

</table>


<h2>Status Iklan</h2>

<table class="widefat striped">

<thead>
<tr>
<th>Status</th>
<th>Jumlah</th>
</tr>
</thead>

<tbody>

<tr>
<td>Aktif</td>
<td><?php echo esc_html( $active_ads ); ?></td>
</tr>

<tr>
<td>Menunggu</td>
<td><?php echo esc_html( $pending_ads ); ?></td>
</tr>

<tr>
<td>Dijeda</td>
<td><?php echo esc_html( $paused_ads ); ?></td>
</tr>

<tr>
<td>Selesai</td>
<td><?php echo esc_html( $finished_ads ); ?></td>
</tr>

</tbody>

</table>


<h2>Statistik Iklan</h2>

<table class="widefat striped">

<thead>
<tr>
<th>Metrik</th>
<th>Nilai</th>
</tr>
</thead>

<tbody>

<tr>
<td>Total Tayangan</td>
<td><?php echo esc_html( $impressions ); ?></td>
</tr>

<tr>
<td>Total Klik</td>
<td><?php echo esc_html( $clicks ); ?></td>
</tr>

<tr>
<td>CTR</td>
<td><?php echo esc_html( number_format_i18n( $ctr, 2 ) ); ?>%</td>
</tr>

</tbody>

</table>


<h2>Placement</h2>

<table class="widefat striped">

<thead>
<tr>
<th>Placement</th>
<th>Aktif</th>
</tr>
</thead>

<tbody>

<?php foreach ( $placements as $position => $label ) : ?>

<?php
$count = function_exists( 'wrmi_get_active_placements' )
? wrmi_get_active_placements(
$position,
20
)
: null;

$total = $count instanceof WP_Query
? (int) $count->found_posts
: 0;
?>

<tr>

<td>
<?php echo esc_html( $label ); ?>
</td>

<td>
<?php echo esc_html( $total ); ?>
</td>

</tr>

<?php endforeach; ?>

</tbody>

</table>

</div>

<?php
}
