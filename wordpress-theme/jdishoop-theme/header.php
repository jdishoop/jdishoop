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
          <li><a href="#">Breaking</a></li>
          <li><a href="#">Analysis</a></li>
          <li><a href="#">World</a></li>
          <li><a href="#">Business</a></li>
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
        <a href="#">Podcast</a>
        <a href="#">Live</a>
        <a href="#" class="header-button">Subscribe</a>
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
