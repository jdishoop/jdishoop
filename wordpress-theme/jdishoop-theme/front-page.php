<?php
get_header();

$editorial_data = function_exists('wrmj_get_homepage_editorial_data')
  ? wrmj_get_homepage_editorial_data()
  : array('headline_id' => 0, 'editor_pick_ids' => array());

$editorial_headline = !empty($editorial_data['headline_id'])
  ? get_post($editorial_data['headline_id'])
  : null;

$featured_posts = $editorial_headline instanceof WP_Post
  ? array($editorial_headline)
  : get_posts(
    array(
      'posts_per_page' => 1,
      'post_status'    => 'publish',
      'ignore_sticky_posts' => 1,
      'meta_query'     => jdishoop_news_standard_posts_meta_query(),
    )
  );

$featured_post_ids = !empty($featured_posts)
  ? array((int) $featured_posts[0]->ID)
  : array();

$sidebar_posts = array();
foreach (array_slice($editorial_data['editor_pick_ids'], 0, 2) as $editorial_pick_id) {
  $editorial_pick = get_post($editorial_pick_id);
  if ($editorial_pick instanceof WP_Post) {
    $sidebar_posts[] = $editorial_pick;
  }
}

if (empty($sidebar_posts)) {
  $sidebar_posts = get_posts(
    array(
      'posts_per_page'      => 2,
      'post_status'         => 'publish',
      'post__not_in'        => $featured_post_ids,
      'ignore_sticky_posts' => 1,
      'meta_query'          => jdishoop_news_standard_posts_meta_query(),
    )
  );
}

$sidebar_post_ids = array_map('intval', wp_list_pluck($sidebar_posts, 'ID'));
$excluded_homepage_posts = array_values(array_unique(array_merge($featured_post_ids, $sidebar_post_ids)));

$latest_posts = get_posts(
  array(
    'posts_per_page'      => 6,
    'post_status'         => 'publish',
    'post__not_in'        => $excluded_homepage_posts,
    'ignore_sticky_posts' => 1,
    'meta_query'          => jdishoop_news_standard_posts_meta_query(),
  )
);

$trending_posts = jdishoop_news_get_trending_posts(5);
$display_categories = jdishoop_news_get_category_list(6);
?>

<?php jdishoop_news_render_ad_slot('top'); ?>

<section class="hero-news premium-hero">
  <div class="container">
    <div class="hero-grid">
      <?php if (!empty($featured_posts)) : ?>
        <?php foreach ($featured_posts as $post) : setup_postdata($post); ?>
          <article class="featured-story premium-featured">
            <div class="thumb">
              <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('large'); ?>
              <?php else : ?>
                <img src="https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1200&q=80" alt="<?php the_title_attribute(); ?>">
              <?php endif; ?>
            </div>
            <div class="content">
              <span class="category-tag"><?php echo esc_html(get_the_category_list(', ')); ?></span>
              <h1><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h1>
              <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 28)); ?></p>
            </div>
          </article>
        <?php endforeach; ?>
        <?php wp_reset_postdata(); ?>
      <?php else : ?>
        <article class="featured-story premium-featured">
          <div class="thumb">
            <img src="https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1200&q=80" alt="Featured News">
          </div>
          <div class="content">
            <span class="category-tag">Premium</span>
            <h1><a href="#">Modern coverage with a bold, authoritative editorial presence.</a></h1>
            <p>Premium digital journalism built for a reliable, contemporary news experience.</p>
          </div>
        </article>
      <?php endif; ?>

      <div class="sidebar-stack">
        <?php if (!empty($sidebar_posts)) : ?>
          <?php foreach ($sidebar_posts as $post) : setup_postdata($post); ?>
            <article class="side-card premium-side-card">
              <div class="image">
                <?php if (has_post_thumbnail()) : ?>
                  <?php the_post_thumbnail('medium'); ?>
                <?php else : ?>
                  <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80" alt="<?php the_title_attribute(); ?>">
                <?php endif; ?>
              </div>
              <div class="content">
                <span class="meta">
                  <?php if (in_array((int) $post->ID, $editorial_data['editor_pick_ids'], true)) : ?>
                    <?php esc_html_e('Pilihan Redaksi', 'jdishoop-news'); ?>
                  <?php else : ?>
                    <?php echo esc_html(get_the_date()); ?>
                  <?php endif; ?>
                </span>
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              </div>
            </article>
          <?php endforeach; ?>
          <?php wp_reset_postdata(); ?>
        <?php else : ?>
          <article class="side-card premium-side-card">
            <div class="image">
              <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80" alt="Latest news">
            </div>
            <div class="content">
              <span class="meta">Latest</span>
              <h3><a href="#">Fast, accurate, and premium journalism for a credible digital brand.</a></h3>
            </div>
          </article>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<?php jdishoop_news_render_ad_slot('mid_content'); ?>

<section class="section-wrap">
  <div class="container">
    <div class="section-head premium-head">
      <h2 class="section-title">Coverage by category</h2>
      <a href="<?php echo esc_url(home_url('/news')); ?>" class="section-link">All sections</a>
    </div>
    <?php jdishoop_news_get_category_cards(6); ?>
  </div>
</section>

<section class="section-wrap">
  <div class="container">
    <div class="section-head premium-head">
      <h2 class="section-title">Latest stories</h2>
      <a href="<?php echo esc_url(home_url('/news')); ?>" class="section-link">Browse updates</a>
    </div>

    <div class="news-grid">
      <?php if (!empty($latest_posts)) : ?>
        <?php foreach ($latest_posts as $post) : setup_postdata($post); ?>
          <article class="news-card premium-news-card">
            <div class="thumb">
              <?php if (has_post_thumbnail()) : ?>
                <?php the_post_thumbnail('medium_large'); ?>
              <?php else : ?>
                <img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80" alt="<?php the_title_attribute(); ?>">
              <?php endif; ?>
            </div>
            <div class="body">
              <span class="meta"><?php echo esc_html(get_the_date()); ?></span>
              <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              <p><?php echo esc_html(wp_trim_words(get_the_excerpt(), 18)); ?></p>
            </div>
          </article>
        <?php endforeach; ?>
        <?php wp_reset_postdata(); ?>
      <?php else : ?>
        <article class="news-card premium-news-card">
          <div class="thumb"><img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80" alt="Story 1"></div>
          <div class="body">
            <span class="meta">September 2</span>
            <h3><a href="#">A modern newsroom blends clarity, speed, and public trust.</a></h3>
            <p>Readers choose credible platforms that remain direct, local, and clearly accountable.</p>
          </div>
        </article>

        <article class="news-card premium-news-card">
          <div class="thumb"><img src="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80" alt="Story 2"></div>
          <div class="body">
            <span class="meta">September 1</span>
            <h3><a href="#">Digital coverage keeps evolving with stronger audience expectations.</a></h3>
            <p>High-quality reporting remains essential when audiences seek clarity and authenticity.</p>
          </div>
        </article>

        <article class="news-card premium-news-card">
          <div class="thumb"><img src="https://images.unsplash.com/photo-1493246507139-91e8fad9978e?auto=format&fit=crop&w=900&q=80" alt="Story 3"></div>
          <div class="body">
            <span class="meta">August 29</span>
            <h3><a href="#">Smart editorial design turns important news into a stronger reading experience.</a></h3>
            <p>The right format helps readers stay connected to major stories without friction.</p>
          </div>
        </article>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section-wrap">
  <div class="container editorial-layout">
    <div class="main-column">
      <div class="list-card premium-list-card">
        <h3>Trending / Most read</h3>
        <ul>
          <?php if (!empty($trending_posts)) : ?>
            <?php foreach ($trending_posts as $post) : ?>
              <li>
                <span class="meta"><?php echo esc_html(get_the_date('d M', $post)); ?></span>
                <h4><a href="<?php echo esc_url(get_permalink($post)); ?>"><?php echo esc_html($post->post_title); ?></a></h4>
              </li>
            <?php endforeach; ?>
          <?php else : ?>
            <li><span class="meta">News</span><h4><a href="#">No trending posts available yet.</a></h4></li>
          <?php endif; ?>
        </ul>
      </div>
    </div>

    <div class="sidebar-column">
      <div class="newsletter premium-newsletter">
        <h3>Daily Brief</h3>
        <p>Top stories and strategic updates designed for readers who want clarity fast.</p>
        <form class="newsletter-form">
          <input type="email" placeholder="Email address" aria-label="Email address">
          <button type="submit">Join</button>
        </form>
      </div>

      <?php if (is_active_sidebar('homepage-sidebar')) : ?>
        <?php dynamic_sidebar('homepage-sidebar'); ?>
      <?php else : ?>
        <?php jdishoop_news_render_default_sidebar(); ?>
      <?php endif; ?>

      <?php jdishoop_news_render_ad_slot('sidebar'); ?>
    </div>
  </div>
</section>

<?php get_footer(); ?>
