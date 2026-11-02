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
