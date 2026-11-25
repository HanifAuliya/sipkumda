/**
 * Editor lampiran di kartu baris (../editor-lampiran.js, Tiptap): bilah alat dan penyelarasan dengan `teks` milik
 * x-data baris-kerja. Setiap perubahan memicu event input, supaya simpan otomatis saat menyusun ikut berjalan;
 * Batal yang mengembalikan `teks` ikut mengembalikan isi editor.
 *
 * Editor disimpan di luar data Alpine: proxy reaktif merusak ProseMirror ("mismatched transaction").
 */
export default (label) => {
    let editor = null;
    let alat = null;
    let pengamat = null;

    return {
        siap: false,
        gagal: false,
        st: {},

        init() {
            // Baru dimuat saat terlihat: kartu yang tidak disunting tidak ikut mengunduh editornya.
            pengamat = new IntersectionObserver((masuk) => {
                if (masuk.some((m) => m.isIntersecting)) {
                    pengamat.disconnect();
                    this.mulai();
                }
            });
            pengamat.observe(this.$el);

            this.$watch('teks', (nilai) => {
                if (editor && (nilai ?? '') !== this.isi()) editor.commands.setContent(nilai ?? '', { emitUpdate: false, parseOptions: { preserveWhitespace: true } });
            });
        },

        async mulai() {
            try {
                alat = await import('../editor-lampiran.js');
                // Wadahnya di dalam kertas yang punya x-data sendiri, jadi tidak terjangkau $refs.
                const wadah = this.$root.querySelector('[data-wadah]');
                if (!wadah?.isConnected) return;

                editor = alat.buatEditorLampiran(wadah, this.teks, {
                    label,
                    lebar: Number(wadah.dataset.wadah),
                    saatUbah: (html) => {
                        this.teks = html;
                        this.$root.dispatchEvent(new Event('input', { bubbles: true }));
                    },
                    saatStatus: (st) => { this.st = st; },
                });
                this.st = alat.keadaan(editor);
                this.siap = true;
                if (this.ubah) editor.commands.focus('end');
            } catch (e) {
                console.error(e);
                this.gagal = true;
            }
        },

        isi() {
            return alat.isiEditor(editor);
        },

        /** Menjalankan satu perintah Tiptap, misalnya jalankan('toggleBold'). */
        jalankan(perintah, ...nilai) {
            editor?.chain().focus()[perintah](...nilai).run();
        },

        pilihJudul(tingkat) {
            tingkat ? this.jalankan('setHeading', { level: tingkat }) : this.jalankan('setParagraph');
        },

        pilihUkuran(ukuran) {
            ukuran ? this.jalankan('setFontSize', ukuran) : this.jalankan('unsetFontSize');
        },

        pilihJenisNomor(jenis) {
            this.jalankan('updateAttributes', 'orderedList', { type: jenis === '1' ? null : jenis });
        },

        /** Jorok: butir daftar turun atau naik tingkat, paragraf bergeser. */
        geser(arah) {
            const perintah = arah > 0 ? ['sinkListItem', 'tambahJorok'] : ['liftListItem', 'kurangiJorok'];
            this.st.nomor || this.st.poin ? this.jalankan(perintah[0], 'listItem') : this.jalankan(perintah[1]);
        },

        garis() {
            if (editor) alat.ubahGaris(editor);
        },

        destroy() {
            pengamat?.disconnect();
            editor?.destroy();
            editor = null;
        },
    };
};
