<?php

function jdishoop_theme_setup() {
  add_theme_support('title-tag');
  add_theme_support('post-thumbnails');
  add_theme_support('custom-logo');
  add_theme_support('html5', array('search-form', 'comment-form', 'comment-list', 'gallery', 'caption', 'style', 'script'));
  add_theme_support('editor-styles');
  add_editor_style('style.css');

  register_nav_menus(
    array(
      'primary' => __('Primary Menu', 'jdishoop-theme'),
    )
  );
}
add_action('after_setup_theme', 'jdishoop_theme_setup');

function jdishoop_theme_enqueue_assets() {
  wp_enqueue_style('jdishoop-theme-style', get_stylesheet_uri(), array(), wp_get_theme()->get('Version'));
  wp_enqueue_script('jdishoop-theme-script', get_template_directory_uri() . '/assets/js/theme.js', array(), wp_get_theme()->get('Version'), true);
}
add_action('wp_enqueue_scripts', 'jdishoop_theme_enqueue_assets');

function jdishoop_theme_widgets_init() {
  register_sidebar(
    array(
      'name'          => __('Footer Column 1', 'jdishoop-theme'),
      'id'            => 'footer-1',
      'before_widget' => '<div class="widget">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3 class="widget-title">',
      'after_title'   => '</h3>',
    )
  );

  register_sidebar(
    array(
      'name'          => __('Footer Column 2', 'jdishoop-theme'),
      'id'            => 'footer-2',
      'before_widget' => '<div class="widget">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3 class="widget-title">',
      'after_title'   => '</h3>',
    )
  );

  register_sidebar(
    array(
      'name'          => __('Footer Column 3', 'jdishoop-theme'),
      'id'            => 'footer-3',
      'before_widget' => '<div class="widget">',
      'after_widget'  => '</div>',
      'before_title'  => '<h3 class="widget-title">',
      'after_title'   => '</h3>',
    )
  );
}
add_action('widgets_init', 'jdishoop_theme_widgets_init');

function jdishoop_theme_excerpt_more($more) {
  return '...';
}
add_filter('excerpt_more', 'jdishoop_theme_excerpt_more');

function jdishoop_theme_body_classes($classes) {
  if (is_front_page()) {
    $classes[] = 'is-front-page';
  }

  return $classes;
}
add_filter('body_class', 'jdishoop_theme_body_classes');
