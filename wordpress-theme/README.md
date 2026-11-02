# Jdishoop News Premium

Tema WordPress premium modern untuk media berita yang bisa dipakai untuk semua kategori. Struktur dibuat agar mudah dikustomisasi dan dibedakan berdasarkan kategori tanpa harus menulis ulang tema.

## Fitur utama

- Berita utama dan sidebar highlight
- Kategori dinamis untuk semua topik
- Homepage yang bisa menampilkan berbagai kategori
- Layout editorial premium
- Responsif untuk desktop, tablet, dan mobile
- Mudah diubah warna utama dan tampilan lewat CSS variables

## Struktur utama

- `jdishoop-theme/` — tema utama
- `assets/js/theme.js` — script menu mobile

## Cara pakai

1. Copy folder `jdishoop-theme` ke `wp-content/themes/` di WordPress Anda.
2. Aktifkan tema dari Dashboard > Appearance > Themes.
3. Atur menu navigasi dari Appearance > Menus.
4. Buat kategori seperti Politik, Ekonomi, Olahraga, Teknologi, Hiburan, dll.
5. Tulis artikel di setiap kategori, dan homepage akan menyesuaikan secara otomatis.

## Mudah diubah

Untuk mengganti tampilan utama, cukup ubah variable di file `style.css`:

```css
:root {
  --news-primary: #b78b4a;
  --news-bg: #f4f1ea;
  --news-dark: #111111;
  --news-text: #171717;
}
```

Semua komponen utama memakai variable ini, jadi perubahan warna menjadi lebih cepat dan praktis.

## Catatan

Tema ini dirancang sebagai versi umum semua kategori dan bisa dikembangkan lebih lanjut dengan:

- section trending berbasis kategori
- widget berita terpopuler
- homepage dengan drag-and-drop blocks
- branding logo dan gaya khusus media
