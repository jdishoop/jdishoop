<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
  <meta charset="<?php bloginfo('charset'); ?>">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>

<header class="site-header">
  <div class="topbar">
    <div class="container">
      <div><?php echo esc_html(date_i18n('l, d F Y')); ?></div>
      <nav aria-label="Top navigation">
        <ul>
          <?php
          $categories = get_categories(array('hide_empty' => true, 'number' => 5));
          if ($categories) {
            foreach ($categories as $category) {
              echo '<li><a href="' . esc_url(get_category_link($category)) . '">' . esc_html($category->name) . '</a></li>';
            }
          }
          ?>
        </ul>
      </nav>
    </div>
  </div>

  <div class="header-main">
    <div class="container">
      <div class="site-brand">
        <div class="brand-mark">N</div>
        <div class="brand-text">
          <?php bloginfo('name'); ?>
          <small><?php bloginfo('description'); ?></small>
        </div>
      </div>

      <div class="header-meta">
        <a href="<?php echo esc_url(home_url('/podcast/')); ?>"><?php esc_html_e('Podcast', 'jdishoop-news'); ?></a>
      </div>
    </div>
  </div>

  <nav class="primary-nav" aria-label="Main navigation">
    <div class="container">
      <button class="menu-toggle" aria-label="Toggle menu" aria-expanded="false">☰</button>
      <?php
      wp_nav_menu(
        array(
          'theme_location' => 'primary',
          'container'      => false,
          'menu_class'     => 'menu',
          'fallback_cb'    => false,
        )
      );
      ?>
    </div>
  </nav>
</header>

<main class="site-content">
