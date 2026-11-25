/**
 * Editor lampiran di kartu baris (../editor-lampiran.js, Tiptap): bilah alat dan penyelarasan dengan `teks` milik
 * x-data baris-kerja. Setiap perubahan memicu event input, supaya simpan otomatis saat menyusun ikut berjalan;
 * Batal yang mengembalikan `teks` ikut mengembalikan isi editor.
 *
 * Editor disimpan di luar data Alpine: proxy reaktif merusak ProseMirror ("mismatched transaction").
 */
import { bacaPdfLampiran } from '../lampiran-dari-file';

export default () => {
    let editor = null;
    let alat = null;
    let pengamat = null;

    return {
        siap: false,
        gagal: false,
        st: {},

        // Bilah alat: menu yang terbuka, ukuran tabel yang disorot, dan pilihan terakhir garis dan bagi sel.
        menu: null,
        sorot: { baris: 0, kolom: 0 },
        bagi: { kolom: 2, baris: 1 },
        jenisGaris: 'solid',
        tebalGaris: '0.5',
        klikKanan: { x: 0, y: 0 },

        membaca: '',
        galatBaca: '',
        catatanBaca: '',

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
                    label: this.$root.getAttribute('aria-label'),
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

        bukaMenu(nama) {
            this.menu = this.menu === nama ? null : nama;
        },

        sisipTabel() {
            const { baris, kolom } = this.sorot;
            if (!baris || !kolom) return;
            this.menu = null;
            this.jalankan('insertTable', { rows: baris, cols: kolom, withHeaderRow: false });
        },

        /** Menjalankan satu perintah tabel (../tabel/perintah.js), lalu menutup menunya. */
        tabel(perintah, ...nilai) {
            if (!editor) return;
            alat.tabel[perintah](editor.view, ...nilai);
            this.menu = null;
            editor.view.focus();
        },

        /** Lebar atau tinggi dalam cm dari isian; kosong atau bukan angka diabaikan. */
        ukuranTabel(perintah, el) {
            const cm = Number(String(el.value).replace(',', '.'));
            if (cm > 0 || (cm === 0 && perintah === 'tinggiBaris' && el.value !== '')) this.tabel(perintah, cm);
            el.value = this.st.tabel ? this.st.tabel[perintah === 'lebarKolom' ? 'lebar' : 'tinggi'] : '';
        },

        garis(mana) {
            this.tabel('garis', mana, this.jenisGaris, this.tebalGaris);
        },

        /** Klik kanan di dalam tabel membuka menu tabel; Shift + klik kanan tetap menu browser. */
        bukaKlikKanan(e) {
            if (!editor || e.shiftKey || !e.target.closest('.tiptap td, .tiptap th')) return;
            e.preventDefault();
            alat.kursorKe(editor, e);
            this.menu = 'klik-kanan';
            this.$nextTick(() => {
                const menu = this.$root.querySelector('[data-klik-kanan]');
                const induk = this.$root.getBoundingClientRect();
                this.klikKanan = {
                    x: Math.max(0, Math.min(e.clientX - induk.left, induk.width - menu.offsetWidth)),
                    y: Math.min(e.clientY, window.innerHeight - menu.offsetHeight - 8) - induk.top,
                };
            });
        },

        /** Isi lampiran dari file Word atau PDF: dibaca, lalu masuk ke editor untuk disunting. Belum tersimpan sampai disimpan. */
        async ambilFile(masukan) {
            const file = masukan.files[0];
            masukan.value = '';
            if (!file || !editor || this.membaca) return;

            this.galatBaca = this.catatanBaca = '';
            if (file.size > 20 * 1024 * 1024) {
                this.galatBaca = 'File paling besar 20 MB.';
                return;
            }
            if (this.isi() !== '' && !await window.confirmAction({
                title: 'Ganti isi lampiran?', text: 'Isi sekarang diganti dengan isi file. Bisa diurungkan dengan Ctrl+Z.', confirmText: 'Ya, ganti', danger: false,
            })) return;

            this.membaca = file.name;
            try {
                const hasil = /\.pdf$/i.test(file.name)
                    ? await bacaPdfLampiran(await file.arrayBuffer(), this.$wire)
                    : await this.bacaWord(file);
                if (!hasil?.html) {
                    this.galatBaca = hasil?.galat ?? 'File tidak memuat isi yang bisa dibaca.';
                    return;
                }
                alat.gantiIsi(editor, hasil.html);
                this.catatanBaca = hasil.catatan ?? '';
            } catch (e) {
                console.error(e);
                this.galatBaca = 'File tidak bisa dibaca. Coba simpan ulang, lalu unggah lagi.';
            } finally {
                this.membaca = '';
            }
        },

        bacaWord(file) {
            return new Promise((selesai, gagal) => {
                this.$wire.upload('wordLampiran', file, () => this.$wire.bacaWordLampiran().then(selesai, gagal), () => gagal(new Error('Unggahan gagal')));
            });
        },

        destroy() {
            pengamat?.disconnect();
            editor?.destroy();
            editor = null;
        },
    };
};
