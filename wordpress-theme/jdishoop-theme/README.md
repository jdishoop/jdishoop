# WartaRadar News Theme

Tema WordPress dalam tahap pengembangan untuk portal berita WartaRadar. Tema ini menyediakan:

- homepage portal berita modern
- kategori berita otomatis dari WordPress
- section trending / most read
- sidebar kategori & widget dinamis
- artikel single dan archive yang rapi
- halaman episode Podcast yang menggunakan shortcode dari plugin WartaRadar Core
- tampilan responsif desktop & mobile

## Struktur file

- `style.css` - styling utama tema
- `functions.php` - setup tema, menu, sidebar, widget, category helper
- `header.php` - header, top bar, navigasi utama
- `footer.php` - footer dan widget footer
- `front-page.php` - homepage portal berita
- `single.php` - halaman artikel
- `page.php` - halaman statis
- `archive.php` - arsip tanggal / kategori / tag
- `category.php` - halaman kategori
- `search.php` - halaman hasil pencarian
- `index.php` - fallback utama
- `assets/js/theme.js` - interaksi mobile menu

## Cara pakai

1. Copy folder `wordpress-theme/jdishoop-theme` ke folder `wp-content/themes/` pada instalasi WordPress Anda.
2. Login ke dashboard WordPress.
3. Masuk ke Appearance > Themes.
4. Aktifkan tema `WartaRadar News`.
5. Buat menu di Appearance > Menus, lalu pilih lokasi menu `primary`.
6. Buat halaman berjudul `Podcast` dengan slug `podcast`. Tema akan memakai halaman itu untuk menampilkan episode dan audio yang ditandai oleh plugin WartaRadar Core.
7. Aktifkan plugin WartaRadar Core untuk menggunakan fitur Podcast.
8. Buat kategori seperti:
   - Nasional
   - Politik
   - Ekonomi
   - Olahraga
   - Teknologi
   - Hiburan
   - Lifestyle
9. Tulis beberapa artikel untuk mengisi homepage.
10. Jika perlu, atur widget di Appearance > Widgets untuk sidebar dan footer.

## Shortcode plugin lintas tema

Shortcode berikut dapat ditambahkan ke blok Shortcode WordPress atau dipanggil dari template tema dengan `do_shortcode()`:

- `[wartaradar_podcast_list count="10"]` - daftar episode; opsional filter serial dengan atribut `series`.
- `[wrmj_homepage_editorial pick_count="4"]` - headline dan sorotan redaksi yang dipilih dari Manajemen Jurnal.
- `[wrmi_ad placement="top"]` - iklan pada posisi `top`, `header`, `hero`, `sidebar`, `feed`, `mid_content`, `article`, atau `footer`.

Aktifkan plugin terkait sebelum memakai shortcode. Tema WartaRadar memakai pilihan editorial pada beranda, menampilkan Podcast di halaman ber-slug `podcast`, dan menyediakan slot iklan `top`, `mid_content`, serta `sidebar`.

## Customisasi cepat

Semua warna utama bisa diatur di CSS variable berikut pada `style.css`:

```css
:root {
  --news-primary: #d9b76c;
  --news-primary-dark: #b99243;
  --news-bg: #edf3fb;
  --news-surface: #ffffff;
  --news-text: #101828;
  --news-muted: #475467;
  --news-dark: #0f172a;
}
```

## Catatan

Tema ini belum final dan masih perlu diuji pada instalasi WordPress sebelum digunakan di produksi.
