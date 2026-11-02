</main>

<footer class="site-footer">
  <div class="container">
    <div class="footer-grid">
      <?php if (is_active_sidebar('footer-1')) : ?>
        <?php dynamic_sidebar('footer-1'); ?>
      <?php endif; ?>

      <?php if (is_active_sidebar('footer-2')) : ?>
        <?php dynamic_sidebar('footer-2'); ?>
      <?php endif; ?>

      <?php if (is_active_sidebar('footer-3')) : ?>
        <?php dynamic_sidebar('footer-3'); ?>
      <?php endif; ?>
    </div>

    <div class="footer-bottom">
      <div>&copy; <?php echo esc_html(date_i18n('Y')); ?> <?php bloginfo('name'); ?>. All rights reserved.</div>
      <div><a href="<?php echo esc_url(home_url('/')); ?>">Back to top</a></div>
    </div>
  </div>
</footer>

<?php wp_footer(); ?>
</body>
</html>
