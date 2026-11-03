<?php
/**
 * Halaman Pengaturan Beranda: Headline Utama dan Sorotan Redaksi.
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$editorial = wrmj_get_homepage_editorial_data();
$posts     = wrmj_editorial_get_published_posts();
?>
<div class="wrap">
    <h1>Pengaturan Beranda</h1>
    <p>Pilih artikel yang akan ditampilkan sebagai Headline Utama dan Sorotan Redaksi. Tema WartaRadar menggunakannya otomatis; tema lain dapat menampilkannya dengan shortcode <code>[wrmj_homepage_editorial]</code>.</p>

    <?php if ( isset( $_GET['updated'] ) && '1' === sanitize_text_field( wp_unslash( $_GET['updated'] ) ) ) : ?>
        <div class="notice notice-success is-dismissible">
            <p>Pilihan editorial berhasil disimpan.</p>
        </div>
    <?php endif; ?>

    <div class="notice notice-info">
        <p><strong>Ketentuan:</strong> hanya artikel berita berstatus Terbit yang dapat dipilih. Podcast tidak ditampilkan dalam daftar. Satu artikel tidak dapat dipilih sekaligus sebagai Headline Utama dan Sorotan Redaksi.</p>
    </div>

    <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
        <input type="hidden" name="action" value="wrmj_save_homepage_editorial">
        <?php
        wp_nonce_field(
            'wrmj_save_homepage_editorial',
            'wrmj_editorial_nonce'
        );
        ?>

        <table class="form-table" role="presentation">
            <tbody>
                <tr>
                    <th scope="row">
                        <label for="wrmj-headline-id">Headline Utama</label>
                    </th>
                    <td>
                        <select name="headline_id" id="wrmj-headline-id" class="regular-text">
                            <option value="0">— Tidak ada Headline Utama —</option>
                            <?php foreach ( $posts as $post_item ) : ?>
                                <option
                                    value="<?php echo esc_attr( $post_item->ID ); ?>"
                                    <?php selected( $editorial['headline_id'], $post_item->ID ); ?>
                                >
                                    <?php
                                    echo esc_html(
                                        get_the_date( 'd M Y', $post_item )
                                        . ' — '
                                        . get_the_title( $post_item )
                                    );
                                    ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <p class="description">Pilih satu artikel. Pilihan ini menggantikan Headline Utama sebelumnya.</p>
                    </td>
                </tr>
            </tbody>
        </table>

        <hr>

        <h2>Sorotan Redaksi</h2>
        <p>Pilih satu atau beberapa artikel. Urutan tampil mengikuti urutan tanggal terbit terbaru.</p>

        <?php if ( empty( $posts ) ) : ?>
            <p>Belum ada artikel berita berstatus Terbit yang dapat dipilih.</p>
        <?php else : ?>
            <div style="max-width: 900px; max-height: 520px; overflow: auto; border: 1px solid #c3c4c7; background: #fff; padding: 12px 16px;">
                <?php foreach ( $posts as $post_item ) : ?>
                    <?php
                    $post_id = (int) $post_item->ID;

                    if ( $post_id === (int) $editorial['headline_id'] ) {
                        continue;
                    }
                    ?>
                    <label style="display: flex; align-items: flex-start; gap: 9px; padding: 9px 0; border-bottom: 1px solid #f0f0f1;">
                        <input
                            type="checkbox"
                            name="editor_pick_ids[]"
                            value="<?php echo esc_attr( $post_id ); ?>"
                            <?php checked( in_array( $post_id, $editorial['editor_pick_ids'], true ) ); ?>
                            style="margin-top: 3px;"
                        >
                        <span>
                            <strong><?php echo esc_html( get_the_title( $post_id ) ); ?></strong>
                            <br>
                            <small>
                                <?php echo esc_html( get_the_date( 'd M Y H:i', $post_id ) ); ?>
                                · ID <?php echo esc_html( $post_id ); ?>
                            </small>
                        </span>
                    </label>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <?php submit_button( 'Simpan Pengaturan Beranda' ); ?>
    </form>
</div>
