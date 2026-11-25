/**
 * Menggabungkan PDF naskah dan PDF lampiran berkas menjadi satu file di browser (pdf-lib, dimuat saat dipakai).
 * Di server tidak ada pustaka gratis yang bisa membaca PDF modern hasil Word, dan hosting tidak punya alat
 * baris perintah untuk itu.
 *
 * <div x-data="gabungPdf(@js($urls), 'Naskah PB-2026-001.pdf')"><button x-on:click="unduh()">...</button></div>
 */
export default (urls, namaBerkas) => ({
    proses: false,
    galat: '',

    async unduh() {
        if (this.proses) return;
        this.proses = true;
        this.galat = '';

        try {
            const { PDFDocument } = await import('pdf-lib');
            const hasil = await PDFDocument.create();

            for (const url of urls) {
                const jawaban = await fetch(url, { credentials: 'same-origin' });
                if (!jawaban.ok) throw new Error(`Gagal mengambil ${url} (${jawaban.status})`);

                const sumber = await PDFDocument.load(await jawaban.arrayBuffer(), { ignoreEncryption: true });
                const halaman = await hasil.copyPages(sumber, sumber.getPageIndices());
                halaman.forEach((h) => hasil.addPage(h));
            }

            const tautan = document.createElement('a');
            tautan.href = URL.createObjectURL(new Blob([await hasil.save()], { type: 'application/pdf' }));
            tautan.download = namaBerkas;
            tautan.click();
            setTimeout(() => URL.revokeObjectURL(tautan.href), 10000);
        } catch (e) {
            console.error(e);
            this.galat = 'PDF gagal digabung. Unduh naskah dan lampirannya satu per satu.';
        } finally {
            this.proses = false;
        }
    },
});
