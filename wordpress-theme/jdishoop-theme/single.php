<?php
get_header();
?>

<div class="content-wrap">
  <?php if (have_posts()) : ?>
    <?php while (have_posts()) : the_post(); ?>
      <article id="post-<?php the_ID(); ?>" <?php post_class(); ?>>
        <div class="page-content">
          <h1 class="entry-title"><?php the_title(); ?></h1>
          <div class="entry-content">
            <?php the_content(); ?>
          </div>
        </div>
      </article>
    <?php endwhile; ?>
  <?php else : ?>
    <div class="page-content">
      <h1><?php esc_html_e('Nothing found', 'jdishoop-theme'); ?></h1>
    </div>
  <?php endif; ?>
</div>

<?php get_footer(); ?>
