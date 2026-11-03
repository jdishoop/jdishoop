<?php
get_header();
$podcast_meta_key = defined('WARTARADAR_META_IS_PODCAST')
  ? WARTARADAR_META_IS_PODCAST
  : '_wartaradar_is_podcast';
$podcast_audio_meta_key = defined('WARTARADAR_META_PODCAST_AUDIO_URL')
  ? WARTARADAR_META_PODCAST_AUDIO_URL
  : '_wartaradar_podcast_audio_url';
$current_page = max(1, get_query_var('paged'));
$podcast_query = new WP_Query(
  array(
    'post_type'           => 'post',
    'post_status'         => 'publish',
    'posts_per_page'      => 10,
    'paged'               => $current_page,
    'ignore_sticky_posts' => true,
    'meta_query'          => array(
      array(
        'key'     => $podcast_meta_key,
        'value'   => '1',
        'compare' => '=',
      ),
    ),
  )
);
?>

<div class="content-wrap">
  <div class="page-content">
    <h1 class="page-title"><?php esc_html_e('Podcast WartaRadar', 'jdishoop-news'); ?></h1>
    <?php if (shortcode_exists('wartaradar_podcast_list')) : ?>
      <?php echo do_shortcode('[wartaradar_podcast_list count="10"]'); ?>
    <?php else : ?>
      <p><?php esc_html_e('Aktifkan plugin WartaRadar Core untuk menampilkan episode Podcast.', 'jdishoop-news'); ?></p>
    <?php endif; ?>
  </div>
</div>

<?php get_footer(); ?>