<?php
get_header();

$featured_posts = get_posts(
  array(
    'posts_per_page' => 1,
    'post_status'    => 'publish',
  )
);

$latest_posts = get_posts(
  array(
    'posts_per_page' => 6,
    'post_status'    => 'publish',
    'offset'         => 1,
  )
);
?>

<section class="hero-news">
  <div class="container">
    <div class="hero-grid">
      <?php if (!empty($featured_posts)) : ?>
        <?php foreach ($featured_posts as $post) : setup_postdata($post); ?>
          <article class="featured-story">
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
        <article class="featured-story">
          <div class="thumb">
            <img src="https://images.unsplash.com/photo-1495020689067-958852a7765e?auto=format&fit=crop&w=1200&q=80" alt="Featured News">
          </div>
          <div class="content">
            <span class="category-tag">Featured</span>
            <h1><a href="#">The launch of a premium digital newsroom is now live.</a></h1>
            <p>Modern journalism meets sharp design in this premium editorial experience built for the digital era.</p>
          </div>
        </article>
      <?php endif; ?>

      <div class="sidebar-stack">
        <?php
        $sidebar_posts = get_posts(
          array(
            'posts_per_page' => 2,
            'post_status'    => 'publish',
            'offset'         => 1,
          )
        );
        ?>

        <?php if (!empty($sidebar_posts)) : ?>
          <?php foreach ($sidebar_posts as $post) : setup_postdata($post); ?>
            <article class="side-card">
              <div class="image">
                <?php if (has_post_thumbnail()) : ?>
                  <?php the_post_thumbnail('medium'); ?>
                <?php else : ?>
                  <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80" alt="<?php the_title_attribute(); ?>">
                <?php endif; ?>
              </div>
              <div class="content">
                <span class="meta"><?php echo esc_html(get_the_date()); ?></span>
                <h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
              </div>
            </article>
          <?php endforeach; ?>
          <?php wp_reset_postdata(); ?>
        <?php else : ?>
          <article class="side-card">
            <div class="image">
              <img src="https://images.unsplash.com/photo-1516321318423-f06f85e504b3?auto=format&fit=crop&w=900&q=80" alt="Secondary story">
            </div>
            <div class="content">
              <span class="meta">Daily Brief</span>
              <h3><a href="#">Editors are curating the most important stories of the day.</a></h3>
            </div>
          </article>
        <?php endif; ?>
      </div>
    </div>
  </div>
</section>

<section class="section-wrap">
  <div class="container">
    <div class="section-head">
      <h2 class="section-title">Latest stories</h2>
      <a href="<?php echo esc_url(home_url('/news')); ?>" class="section-link">Browse all</a>
    </div>

    <div class="news-grid">
      <?php if (!empty($latest_posts)) : ?>
        <?php foreach ($latest_posts as $post) : setup_postdata($post); ?>
          <article class="news-card">
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
        <article class="news-card">
          <div class="thumb"><img src="https://images.unsplash.com/photo-1522202176988-66273c2fd55f?auto=format&fit=crop&w=900&q=80" alt="Story 1"></div>
          <div class="body">
            <span class="meta">September 2</span>
            <h3><a href="#">Cities are redesigning public spaces to improve daily life.</a></h3>
            <p>Urban change becomes a central narrative in how communities rebuild their shared future.</p>
          </div>
        </article>

        <article class="news-card">
          <div class="thumb"><img src="https://images.unsplash.com/photo-1504384308090-c894fdcc538d?auto=format&fit=crop&w=900&q=80" alt="Story 2"></div>
          <div class="body">
            <span class="meta">September 1</span>
            <h3><a href="#">New business models are shifting the pace of digital media.</a></h3>
            <p>Growth and innovation are reshaping what audiences expect from modern news brands.</p>
          </div>
        </article>

        <article class="news-card">
          <div class="thumb"><img src="https://images.unsplash.com/photo-1493246507139-91e8fad9978e?auto=format&fit=crop&w=900&q=80" alt="Story 3"></div>
          <div class="body">
            <span class="meta">August 29</span>
            <h3><a href="#">Editorial teams are blending human reporting with data storytelling.</a></h3>
            <p>Readers want context, clarity, and visual depth in every headline they consume.</p>
          </div>
        </article>
      <?php endif; ?>
    </div>
  </div>
</section>

<section class="section-wrap">
  <div class="container editorial-layout">
    <div class="main-column">
      <div class="list-card">
        <h3>Top analysis</h3>
        <ul>
          <li>
            <span class="meta">Analysis</span>
            <h4><a href="#">What the new energy policy means for households and businesses.</a></h4>
          </li>
          <li>
            <span class="meta">Economy</span>
            <h4><a href="#">The global supply chain is changing under a new wave of demand.</a></h4>
          </li>
          <li>
            <span class="meta">Culture</span>
            <h4><a href="#">A different kind of media economy is emerging from creator communities.</a></h4>
          </li>
        </ul>
      </div>
    </div>

    <div class="sidebar-column">
      <div class="newsletter">
        <h3>Newsletter</h3>
        <p>Get the top stories and expert insights delivered to your inbox.</p>
        <form class="newsletter-form">
          <input type="email" placeholder="Email address" aria-label="Email address">
          <button type="submit">Join</button>
        </form>
      </div>

      <div class="widget-box">
        <h3>Most read</h3>
        <ul>
          <li><a href="#">How digital publishers are redefining trust in 2026.</a></li>
          <li><a href="#">Investors look to editorial businesses with stronger audience loyalty.</a></li>
          <li><a href="#">The new design language of premium web journalism.</a></li>
        </ul>
      </div>
    </div>
  </div>
</section>

<?php get_footer(); ?>
