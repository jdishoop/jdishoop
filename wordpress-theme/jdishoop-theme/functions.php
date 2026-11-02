<?php

function jdishoop_news_setup() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('custom-logo');
  add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
  add_theme_support('editor-styles');
  add_editor_style('style.css');

  register_nav_menus(
    array(
      'primary' => __('Primary Menu', 'jdishoop-news'),
    )
  );
}
add_action('after_setup_theme', 'jdishoop_news_setup');

function jdishoop_news_enqueue_assets() {
  wp_enqueue_style('jdishoop-news-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
  wp_enqueue_script('jdishoop-news-script', get_template_directory_uri() . '/assets/js/theme.js', array(), wp_get_theme()->get('Version'), true);
}
add_action('wp_enqueue_scripts', 'jdishoop_news_enqueue_assets');

function jdishoop_news_widgets_init() {
  register_sidebar(
    array(
      'name'          => __('Homepage Sidebar', 'jdishoop-news'),
      'id'            => 'homepage-sidebar',
      'before_widget' => '<div class="widget-box">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3>',
      'after_title'   => '</h3>',
    )
  );

  register_sidebar(
    array(
      'name'          => __('Footer Column 1', 'jdishoop-news'),
      'id'            => 'footer-1',
      'before_widget' => '<div class="footer-card">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3>',
      'after_title'   => '</h3>',
    )
  );

  register_sidebar(
    array(
      'name'          => __('Footer Column 2', 'jdishoop-news'),
      'id'            => 'footer-2',
      'before_widget' => '<div class="footer-card">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3>',
      'after_title'   => '</h3>',
    )
  );

  register_sidebar(
    array(
      'name'          => __('Footer Column 3', 'jdishoop-news'),
      'id'            => 'footer-3',
      'before_widget' => '<div class="footer-card">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3>',
      'after_title'   => '</h3>',
    )
  );
}
add_action('widgets_init', 'jdishoop_news_widgets_init');

function jdishoop_news_excerpt_more($more) {
  return '...';
}
add_filter('excerpt_more', 'jdishoop_news_excerpt_more');

function jdishoop_news_get_category_cards($categories = 6) {
  $terms = get_categories(
    array(
      'hide_empty' => true,
      'number'     => $categories,
    )
  );

  if (empty($terms)) {
    return;
  }

  echo '<div class="category-grid">';

  foreach ($terms as $term) {
    $posts = get_posts(
      array(
        'posts_per_page' => 1,
        'category'       => $term->term_id,
        'post_status'    => 'publish',
        'ignore_sticky_posts' => 1,
      )
    );

    $thumbnail_url = '';
    if (!empty($posts)) {
      $post_id = $posts[0]->ID;
      if (has_post_thumbnail($post_id)) {
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'medium_large');
      }
    }

    if (!$thumbnail_url) {
      $thumbnail_url = 'https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80';
    }

    echo '<article class="category-panel">';
    echo '<div class="thumb"><img src="' . esc_url($thumbnail_url) . '" alt="' . esc_attr($term->name) . '"></div>';
    echo '<div class="body">';
    echo '<span class="meta">Category</span>';
    echo '<h3><a href="' . esc_url(get_category_link($term)) . '">' . esc_html($term->name) . '</a></h3>';
    echo '<p>' . esc_html($term->count) . ' stories</p>';
    echo '</div>';
    echo '</article>';
  }

  echo '</div>';
}

function jdishoop_news_get_trending_posts($count = 5) {
  $query = new WP_Query(
    array(
      'posts_per_page' => $count,
      'post_status'    => 'publish',
      'ignore_sticky_posts' => 1,
      'orderby'        => 'comment_count',
      'order'          => 'DESC',
    )
  );

  if (!$query->have_posts()) {
    return array();
  }

  $posts = array();
  while ($query->have_posts()) {
    $query->the_post();
    $posts[] = get_post();
  }
  wp_reset_postdata();

  return $posts;
}

function jdishoop_news_get_category_list($count = 6) {
  $categories = get_categories(
    array(
      'hide_empty' => true,
      'number'     => $count,
    )
  );

  return $categories;
}

function jdishoop_news_render_default_sidebar() {
  $trending_posts = jdishoop_news_get_trending_posts(5);
  $categories = jdishoop_news_get_category_list(6);

  echo '<div class="widget-box">';
  echo '<h3>Trending</h3>';
  echo '<ul class="mini-list">';
  if (!empty($trending_posts)) {
    foreach ($trending_posts as $post) {
      echo '<li><a href="' . esc_url(get_permalink($post)) . '">' . esc_html($post->post_title) . '</a></li>';
    }
  }
  echo '</ul>';
  echo '</div>';

  echo '<div class="widget-box">';
  echo '<h3>Categories</h3>';
  echo '<ul class="mini-list">';
  if (!empty($categories)) {
    foreach ($categories as $category) {
      echo '<li><a href="' . esc_url(get_category_link($category)) . '">' . esc_html($category->name) . '</a></li>';
    }
  }
  echo '</ul>';
  echo '</div>';
}
