/**
 * PDF lampiran menjadi isi editor: dibaca di browser (./pdf-ke-html, dimuat saat dipakai), lalu kepala "LAMPIRAN ..."
 * dan tanda tangannya dibuang server, karena keduanya dibuat otomatis.
 *
 * @returns {Promise<{html?: string, catatan?: string, galat?: string}>}
 */
export async function bacaPdfLampiran(isi, wire) {
    const { pdfKeHtml } = await import('./pdf-ke-html.js');
    const { html, pindaian } = await pdfKeHtml(isi);

    if (!html) {
        return {
            galat: pindaian
                ? 'PDF ini hasil pindai (berupa gambar), jadi tulisannya tidak bisa diambil. Unggah file Word-nya, atau pakai PDF apa adanya.'
                : 'PDF ini tidak memuat tulisan.',
        };
    }

    return wire.rapikanLampiran(html);
}
