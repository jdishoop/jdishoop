<?php
get_header();
?>

<section class="hero">
  <div class="container hero-grid">
    <div>
      <span class="eyebrow">Custom WordPress Theme</span>
      <h1>Build a standout website with a modern, clean design.</h1>
      <p>Jdishoop Theme is a responsive custom WordPress theme designed for business, portfolio, or agency websites. It is fast, clean, and ready to customize.</p>

      <div class="hero-actions">
        <a class="btn btn-primary" href="#services">Lihat Layanan</a>
        <a class="btn btn-secondary" href="<?php echo esc_url(home_url('/contact')); ?>">Hubungi Kami</a>
      </div>
    </div>

    <div class="card hero-panel">
      <div class="metric-card">
        <div class="stat">
          <strong>100%</strong>
          <span>Custom Design</span>
        </div>
        <div class="stat">
          <strong>24/7</strong>
          <span>Ready to Use</span>
        </div>
      </div>

      <div class="stat">
        <strong>Fast</strong>
        <span>Responsive layout and clean code for WordPress.</span>
      </div>
    </div>
  </div>
</section>

<section class="section" id="features">
  <div class="container">
    <div class="section-heading">
      <h2>Kenapa memilih theme ini?</h2>
      <p>Desain yang fleksibel untuk kebutuhan website profesional.</p>
    </div>

    <div class="feature-grid">
      <article class="feature-item">
        <div class="feature-icon">01</div>
        <h3>Modern Layout</h3>
        <p>Struktur rapi dengan gaya premium yang cocok untuk bisnis, portfolio, dan landing page.</p>
      </article>

      <article class="feature-item">
        <div class="feature-icon">02</div>
        <h3>Mobile Friendly</h3>
        <p>Semua elemen dirancang responsif agar tampilan tetap optimal di desktop, tablet, dan ponsel.</p>
      </article>

      <article class="feature-item">
        <div class="feature-icon">03</div>
        <h3>Easy Customize</h3>
        <p>Theme ini menggunakan struktur WordPress yang sederhana untuk mempermudah pengeditan selanjutnya.</p>
      </article>
    </div>
  </div>
</section>

<section class="section" id="services">
  <div class="container">
    <div class="section-heading">
      <h2>Layanan utama</h2>
      <p>Solusi website sesuai kebutuhan brand dan bisnis Anda.</p>
    </div>

    <div class="service-grid">
      <article class="service-item">
        <div class="icon">◎</div>
        <h3>Website Company</h3>
        <p>Menampilkan profil perusahaan dengan area layanan, portofolio, dan kontak yang jelas.</p>
      </article>

      <article class="service-item">
        <div class="icon">◇</div>
        <h3>Portfolio</h3>
        <p>Tempat menampilkan karya, proyek, hasil kerja, dan pengalaman secara profesional.</p>
      </article>

      <article class="service-item">
        <div class="icon">✦</div>
        <h3>Landing Page</h3>
        <p>Desain fokus pada konversi dengan struktur hero, CTA, benefit, dan testimoni.</p>
      </article>
    </div>
  </div>
</section>

<?php if (have_posts()) : ?>
  <section class="section">
    <div class="container">
      <?php while (have_posts()) : the_post(); ?>
        <article class="entry-content">
          <h2><?php the_title(); ?></h2>
          <div class="content">
            <?php the_content(); ?>
          </div>
        </article>
      <?php endwhile; ?>
    </div>
  </section>
<?php endif; ?>

<?php get_footer(); ?>
