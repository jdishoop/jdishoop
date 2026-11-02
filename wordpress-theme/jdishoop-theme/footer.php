<?php
/**
 * Template for site footer.
 */
?>
</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-widgets">
      <?php if (is_active_sidebar('footer-1')) : ?>
        <div><?php dynamic_sidebar('footer-1'); ?></div>
      <?php endif; ?>

      <?php if (is_active_sidebar('footer-2')) : ?>
        <div><?php dynamic_sidebar('footer-2'); ?></div>
      <?php endif; ?>

      <?php if (is_active_sidebar('footer-3')) : ?>
        <div><?php dynamic_sidebar('footer-3'); ?></div>
      <?php endif; ?>
    </div>

    <div>
      <p>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>. All rights reserved.</p>
    </div>

    <div>
      <a href="<?php echo esc_url(home_url('/')); ?>"><?php esc_html_e('Back to top', 'jdishoop-theme'); ?></a>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
