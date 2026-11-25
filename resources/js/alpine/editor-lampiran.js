/**
 * Editor lampiran di kartu baris: membungkus CKEditor (../editor-lampiran.js, dimuat saat pertama dipakai) dan
 * menyelaraskannya dengan `teks` milik x-data baris-kerja. Setiap perubahan memicu event input, supaya simpan
 * otomatis saat menyusun ikut berjalan; Batal yang mengembalikan `teks` ikut mengembalikan isi editor.
 */
export default () => ({
    editor: null,
    memuat: true,
    gagal: false,

    async init() {
        try {
            const { buatEditorLampiran } = await import('../editor-lampiran.js');
            this.editor = await buatEditorLampiran(this.$refs.wadah, this.teks ?? '');
        } catch (e) {
            console.error(e);
            this.gagal = true;
            return;
        } finally {
            this.memuat = false;
        }

        this.editor.model.document.on('change:data', () => {
            this.teks = this.editor.getData();
            this.$el.dispatchEvent(new Event('input', { bubbles: true }));
        });

        this.$watch('teks', (nilai) => {
            if (this.editor && (nilai ?? '') !== this.editor.getData()) this.editor.setData(nilai ?? '');
        });
    },

    fokus() {
        this.editor?.editing.view.focus();
    },

    destroy() {
        this.editor?.destroy();
        this.editor = null;
    },
});
