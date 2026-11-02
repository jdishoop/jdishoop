<?php
get_header();
?>

<div class="content-wrap">
  <div class="archive-content">
    <h1 class="page-title"><?php printf(esc_html__('Search results for: %s', 'jdishoop-theme'), get_search_query()); ?></h1>

    <?php if (have_posts()) : ?>
      <ul class="post-list">
        <?php while (have_posts()) : the_post(); ?>
          <li>
            <a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
          </li>
        <?php endwhile; ?>
      </ul>
    <?php else : ?>
      <p><?php esc_html_e('No results found.', 'jdishoop-theme'); ?></p>
    <?php endif; ?>
  </div>
</div>

<?php get_footer(); ?>
