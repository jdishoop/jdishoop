# Jdishoop Premium News Theme

Tema WordPress premium untuk portal berita dengan gaya biru tua + emas. Tema ini dirancang untuk:

- homepage portal berita modern
- kategori berita otomatis dari WordPress
- section trending / most read
- sidebar kategori & widget dinamis
- artikel single dan archive yang rapi
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
4. Aktifkan tema `Jdishoop Premium News`.
5. Buat menu di Appearance > Menus, lalu pilih lokasi menu `primary`.
6. Buat kategori seperti:
   - Nasional
   - Politik
   - Ekonomi
   - Olahraga
   - Teknologi
   - Hiburan
   - Lifestyle
7. Tulis beberapa artikel untuk mengisi homepage.
8. Jika perlu, atur widget di Appearance > Widgets untuk sidebar dan footer.

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

Tema ini sudah siap untuk portal berita modern, namun tetap dapat dikembangkan lebih lanjut sesuai kebutuhan brand atau newsroom Anda.
