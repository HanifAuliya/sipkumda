import tabelDariClipboard from '../tempel-tabel';
import { GAYA, gayaNomor, hitungTingkat, nomorBaris, nomoriUlang, nomorKe, urutanGaya } from '../nomor-rumusan';

/**
 * Editor rumusan seperti di Word: tiap ayat dan butir tampil dengan nomor dan jorok seperti hasil cetak,
 * tabel tampil sebagai tabel yang selnya bisa diisi. Hasilnya tetap teks ("a. isi", "| a | b |"), sama persis
 * dengan yang dibaca TataLetakNaskah, jadi penyimpanan, pratinjau, dan cetak tidak berubah.
 * Dipakai di dalam x-data baris-kerja (properti `teks`).
 *
 * Papan ketik: Enter membuat butir baru dengan nomor berikutnya (di butir kosong: keluar dari daftar),
 * Tab menurunkan tingkat, Shift+Tab menaikkan, Backspace di awal butir menghapus nomornya,
 * mengetik "a. " atau "(1) " di awal baris langsung menjadikannya butir.
 */

/** Sama dengan TataLetakNaskah::rataTabel(): rata kolom dari baris pemisah "| :-: | --: |", atau null. */
function rataTabel(baris) {
    const sel = pecahSel(baris);
    if (!sel.every((s) => /^(:?-+:?)?$/.test(s)) || !sel.join('').includes('-')) return null;

    return sel.map((s) => {
        if (s.length > 1 && s.startsWith(':') && s.endsWith(':')) return 'center';
        return s.endsWith(':') ? 'right' : 'left';
    });
}

const pecahSel = (baris) => baris.trim().replace(/^\||\|$/g, '').split('|').map((s) => s.trim());

/**
 * Teks menjadi potongan: { jenis: 'baris', nomor, isi } per baris dan { jenis: 'tabel', sel, rata, judul }
 * untuk baris "| a | b |" yang berurutan. Baris pemisah sesudah baris pertama menandai baris judul.
 */
export function potongTeks(teks) {
    const hasil = [];
    let tunda = null;

    for (const mentah of String(teks ?? '').split(/\r?\n/)) {
        const baris = mentah.trim();
        const terakhir = hasil.at(-1);

        if (baris.startsWith('|')) {
            const rata = rataTabel(baris);
            const terbuka = terakhir?.jenis === 'tabel';

            if (rata !== null) {
                if (terbuka) {
                    terakhir.rata = rata;
                    terakhir.judul = terakhir.sel.length === 1;
                } else {
                    tunda = rata;
                }
                continue;
            }

            const sel = pecahSel(baris);
            if (sel.every((s) => s === '')) continue;

            if (terbuka) terakhir.sel.push(sel);
            else hasil.push({ jenis: 'tabel', sel: [sel], rata: tunda, judul: false });
            tunda = null;
            continue;
        }

        tunda = null;
        if (baris === '') continue;

        const { nomor, isi } = nomorBaris(baris);
        hasil.push({ jenis: 'baris', nomor, isi });
    }

    // Semua baris tabel sama jumlah kolomnya, supaya tabelnya lurus saat diedit.
    for (const p of hasil) {
        if (p.jenis !== 'tabel') continue;
        const kolom = Math.max(...p.sel.map((b) => b.length));
        p.sel = p.sel.map((b) => [...b, ...Array(kolom - b.length).fill('')]);
        p.rata = p.rata ? [...p.rata, ...Array(kolom).fill('left')].slice(0, kolom) : null;
    }

    return hasil;
}

/** Isi sel tidak boleh memuat garis tegak atau ganti baris, karena keduanya bagian dari format tabel. */
const bersihSel = (isi) => isi.replace(/\s+/g, ' ').replace(/\|/g, '/').trim();

const PEMISAH = { left: '---', center: ':-:', right: '--:' };

function tulisTabel(t) {
    const baris = t.sel.map((b) => `| ${b.map(bersihSel).join(' | ')} |`);
    const rata = t.sel[0].map((_, c) => t.rata?.[c] ?? 'left');

    // Baris pemisah hanya ditulis bila perlu, supaya tabel biasa tetap berupa teks sederhana.
    if (t.judul || rata.some((r) => r !== 'left')) {
        baris.splice(t.judul ? 1 : 0, 0, `| ${rata.map((r) => PEMISAH[r]).join(' | ')} |`);
    }

    return baris;
}

export function gabungTeks(potongan) {
    return potongan
        .flatMap((p) => {
            if (p.jenis === 'tabel') return tulisTabel(p);
            const isi = p.isi.replace(/\s*\n\s*/g, ' ').trim();
            const baris = p.nomor === null ? isi : `${p.nomor} ${isi}`.trim();
            return baris === '' ? [] : [baris];
        })
        .join('\n');
}

let urut = 0;
const baru = (p) => ({ ...p, id: ++urut });
const barisKosong = () => baru({ jenis: 'baris', nomor: null, isi: '' });

export default function editorRumusan(barisMinimal = 3, gayaAwal = 'ayat') {
    // Disimpan di luar data reaktif. $root tidak bisa dipakai: tombol yang ditekan bisa ikut hilang
    // (misalnya Hapus Tabel), dan elemen yang sudah lepas dari halaman tidak punya akar lagi.
    let akar = null;

    return {
        GAYA,
        potong: [],
        tingkat: [],
        barisMinimal,
        // Baris yang terakhir disunting (id dan posisi kursor), untuk tombol nomor dan tempat menyisipkan tabel.
        aktif: null,
        // Sel tabel yang terakhir disentuh, untuk tombol baris, kolom, dan rata.
        sel: null,
        ditulis: null,

        init() {
            akar = this.$el;
            this.muat(this.teks);
            // Teks yang berubah dari luar (Batal, Rapikan Nomor, Kembalikan) dimuat ulang.
            this.$watch('teks', (nilai) => {
                if (nilai !== this.ditulis) this.muat(nilai);
            });
        },

        muat(teks) {
            this.potong = this.rapikan(potongTeks(teks).map(baru));
            this.tingkat = hitungTingkat(this.potong);
            this.ditulis = teks;
            this.aktif = null;
            this.sel = null;
        },

        /** Selalu ada baris sebelum dan sesudah tabel, supaya bisa menulis di atas atau di bawahnya. */
        rapikan(potong) {
            const hasil = [];

            for (const p of potong) {
                if (p.jenis === 'tabel' && hasil.at(-1)?.jenis !== 'baris') hasil.push(barisKosong());
                hasil.push(p);
            }
            if (hasil.at(-1)?.jenis !== 'baris') hasil.push(barisKosong());

            return hasil;
        },

        /** Tombol tidak memicu event input, jadi editor mengabarkannya sendiri untuk simpan otomatis. */
        simpan() {
            this.tingkat = hitungTingkat(this.potong);
            this.ditulis = gabungTeks(this.potong);
            this.teks = this.ditulis;
            akar?.dispatchEvent(new Event('input', { bubbles: true }));
        },

        /** Perubahan susunan daftar: nomor diurutkan ulang seperti di Word. */
        simpanBernomor() {
            nomoriUlang(this.potong);
            this.simpan();
        },

        get kosong() {
            return this.potong.length === 1 && this.potong[0].isi === '' && this.potong[0].nomor === null;
        },

        indeks(id) {
            return this.potong.findIndex((p) => p.id === id);
        },

        get barisAktif() {
            return this.potong.find((p) => p.id === this.aktif?.id && p.jenis === 'baris') ?? null;
        },

        /** Gaya nomor baris yang sedang disunting: null untuk teks biasa, undefined bila tidak ada baris aktif. */
        get gayaAktif() {
            return this.barisAktif ? gayaNomor(this.barisAktif.nomor) : undefined;
        },

        catatKursor(p, el) {
            this.aktif = { id: p.id, posisi: el.selectionStart };
        },

        // ---- Baris teks ---------------------------------------------------------------------------

        ubahBaris(i, e) {
            const p = this.potong[i];
            const el = e.target;

            // "a. " atau "(1) " yang diketik di awal baris biasa langsung menjadi nomor butir.
            if (p.nomor === null && e.inputType === 'insertText' && e.data === ' ') {
                const hasil = nomorBaris(el.value);
                if (hasil.gaya && el.value.startsWith(`${hasil.nomor} `) && el.selectionStart === hasil.nomor.length + 1) {
                    p.nomor = hasil.nomor;
                    p.isi = hasil.isi;
                    el.value = hasil.isi;
                    el.setSelectionRange(0, 0);
                    this.simpan();
                    return;
                }
            }

            p.isi = el.value;
            this.simpan();
        },

        /** Nomor bisa diketik sendiri, misalnya ayat sisipan "(1a)". Yang tidak dikenali dikembalikan. */
        ubahNomor(i, e) {
            const p = this.potong[i];
            const nilai = e.target.value.trim();

            if (nilai === '') p.nomor = null;
            else if (nomorBaris(nilai).isi === '' && gayaNomor(nilai) !== null) p.nomor = nilai;

            e.target.value = p.nomor ?? '';
            this.simpan();
        },

        tombol(e, i) {
            const el = e.target;
            const p = this.potong[i];
            const diAwal = el.selectionStart === 0 && el.selectionEnd === 0;
            const diAkhir = el.selectionStart === el.value.length && el.selectionEnd === el.value.length;

            if (e.key === 'Enter' && !e.isComposing) {
                e.preventDefault();
                this.pecah(i, el.selectionStart, el.selectionEnd);
            } else if (e.key === 'Tab' && !e.shiftKey && (p.nomor !== null || diAwal)) {
                e.preventDefault();
                this.turun(i);
            } else if (e.key === 'Tab' && e.shiftKey && p.nomor !== null) {
                e.preventDefault();
                this.naik(i);
            } else if (e.key === 'Backspace' && diAwal) {
                if (p.nomor !== null) {
                    e.preventDefault();
                    p.nomor = null;
                    this.simpanBernomor();
                } else if (this.potong[i - 1]?.jenis === 'baris') {
                    e.preventDefault();
                    this.gabung(i - 1);
                }
            } else if (e.key === 'Delete' && diAkhir && this.potong[i + 1]?.jenis === 'baris') {
                e.preventDefault();
                this.gabung(i);
            } else if (e.key === 'ArrowUp' && diAwal && i > 0) {
                e.preventDefault();
                this.fokusSekitar(i - 1, 'akhir');
            } else if (e.key === 'ArrowDown' && diAkhir && i < this.potong.length - 1) {
                e.preventDefault();
                this.fokusSekitar(i + 1, 'awal');
            }
        },

        /** Enter: memecah baris di kursor. Di butir kosong, Enter keluar satu tingkat seperti di Word. */
        pecah(i, dari, sampai) {
            const p = this.potong[i];

            if (p.nomor !== null && p.isi.trim() === '') {
                this.naik(i);
                return;
            }

            const sesudah = p.isi.slice(sampai);
            p.isi = p.isi.slice(0, dari);
            const butir = baru({ jenis: 'baris', nomor: p.nomor, isi: sesudah });
            this.potong.splice(i + 1, 0, butir);
            this.simpanBernomor();
            this.fokusBaris(butir.id, 0);
        },

        /** Baris i+1 disambung ke baris i (Backspace di awal baris, Delete di akhir baris). */
        gabung(i) {
            const atas = this.potong[i];
            const bawah = this.potong[i + 1];
            const posisi = atas.isi.length;
            // Baris kosong tanpa nomor yang disambung ke butir mengambil nomor butir itu.
            if (atas.nomor === null && atas.isi === '') atas.nomor = bawah.nomor;
            atas.isi += bawah.isi;
            this.potong.splice(i + 1, 1);
            this.simpanBernomor();
            this.fokusBaris(atas.id, posisi);
        },

        /** Gaya butir terdekat di atasnya yang masih satu daftar, untuk melanjutkan daftar itu. */
        gayaDiAtas(i, syarat = () => true) {
            for (let j = i - 1; j >= 0; j--) {
                const p = this.potong[j];
                if (p.jenis !== 'baris') continue;
                const gaya = gayaNomor(p.nomor);
                if (gaya === null) {
                    if (p.isi.trim() === '') continue;
                    return null;
                }
                if (syarat(gaya)) return gaya;
            }

            return null;
        },

        /** Tab: baris biasa menjadi butir; butir turun ke gaya di bawahnya, misalnya (1) → a. → 1. */
        turun(i) {
            const p = this.potong[i];
            const gaya = gayaNomor(p.nomor);

            if (gaya === null) {
                p.nomor = nomorKe(this.gayaDiAtas(i) ?? gayaAwal, 1);
            } else {
                const lebihDalam = GAYA[urutanGaya(gaya) + 1];
                if (!lebihDalam) return;
                p.nomor = nomorKe(lebihDalam.nama, 1);
            }

            this.simpanBernomor();
            this.fokusBaris(p.id);
        },

        /** Shift+Tab: butir naik ke tingkat induknya; di tingkat teratas menjadi teks biasa. */
        naik(i) {
            const p = this.potong[i];
            const gaya = gayaNomor(p.nomor);
            if (gaya === null) return;

            const induk = this.gayaDiAtas(i, (g) => urutanGaya(g) < urutanGaya(gaya));
            p.nomor = induk ? nomorKe(induk, 1) : null;
            this.simpanBernomor();
            this.fokusBaris(p.id);
        },

        /** Tombol gaya nomor di bilah alat: berlaku untuk baris yang sedang disunting. */
        aturGaya(gaya) {
            const p = this.barisAktif;
            if (!p) return;
            p.nomor = gaya === null ? null : nomorKe(gaya, 1);
            this.simpanBernomor();
            this.fokusBaris(p.id);
        },

        naikAktif() {
            const i = this.indeks(this.aktif?.id);
            if (this.potong[i]?.jenis === 'baris') this.naik(i);
        },

        turunAktif() {
            const i = this.indeks(this.aktif?.id);
            if (this.potong[i]?.jenis === 'baris') this.turun(i);
        },

        // ---- Fokus --------------------------------------------------------------------------------

        /** Elemen baru ada setelah Alpine menggambar ulang, jadi dicoba beberapa frame. */
        fokusElemen(pilih, sesudah = () => {}) {
            const coba = (sisa) => {
                const el = akar?.querySelector(pilih);
                if (el) {
                    el.focus();
                    sesudah(el);
                } else if (sisa > 0) {
                    requestAnimationFrame(() => coba(sisa - 1));
                }
            };
            this.$nextTick(() => coba(10));
        },

        /** posisi: angka, 'akhir', atau null untuk kembali ke kursor terakhir di baris itu. */
        fokusBaris(id, posisi = null) {
            const terakhir = this.aktif?.id === id ? this.aktif.posisi : 'akhir';
            this.fokusElemen(`[data-baris="${id}"]`, (el) => {
                let letak = posisi ?? terakhir;
                if (letak === 'akhir') letak = el.value.length;
                el.setSelectionRange(letak, letak);
            });
        },

        fokusSekitar(i, letak) {
            const p = this.potong[i];
            if (p.jenis === 'baris') {
                this.fokusBaris(p.id, letak === 'akhir' ? 'akhir' : 0);
            } else {
                const r = letak === 'akhir' ? p.sel.length - 1 : 0;
                this.fokusSel(i, r, 0);
            }
        },

        /** Klik di ruang kosong editor menaruh kursor di akhir baris terakhir. */
        fokusAkhir() {
            this.fokusBaris(this.potong.at(-1).id, 'akhir');
        },

        fokusSel(i, r, c) {
            this.fokusElemen(`[data-sel="${this.potong[i]?.id}-${r}-${c}"]`);
        },

        // ---- Tempel dan tabel ---------------------------------------------------------------------

        /** Menyisipkan potongan baru di posisi kursor baris ke-i; sisa barisnya pindah ke bawah. */
        sisipkan(i, posisi, potonganBaru) {
            const p = this.potong[i];
            const sesudah = p.isi.slice(posisi);
            p.isi = p.isi.slice(0, posisi);
            const tambahan = potonganBaru.map(baru);
            if (sesudah.trim() !== '') tambahan.push(baru({ jenis: 'baris', nomor: null, isi: sesudah }));
            this.potong.splice(i + 1, 0, ...tambahan);
            this.potong = this.rapikan(this.potong);
            this.simpan();
            return tambahan;
        },

        sisipTabel() {
            let i = this.indeks(this.aktif?.id);
            let posisi = this.aktif?.posisi ?? 0;
            if (this.potong[i]?.jenis !== 'baris') {
                i = this.potong.length - 1;
                posisi = this.potong[i].isi.length;
            }
            const [tabel] = this.sisipkan(i, posisi, [{ jenis: 'tabel', sel: [['', '', ''], ['', '', '']], rata: null, judul: false }]);
            this.fokusSel(this.indeks(tabel.id), 0, 0);
        },

        /** Tempel tabel dari Word/Excel menjadi tabel; teks banyak baris dipecah per butir. */
        tempelDiBaris(e, i) {
            const el = e.target;
            const tabel = tabelDariClipboard(e.clipboardData);

            if (tabel !== null) {
                e.preventDefault();
                const tambahan = this.sisipkan(i, el.selectionStart, potongTeks(tabel));
                const pertama = tambahan.find((p) => p.jenis === 'tabel');
                if (pertama) this.fokusSel(this.indeks(pertama.id), 0, 0);
                return;
            }

            const teks = e.clipboardData.getData('text/plain');
            if (!/\r?\n/.test(teks.trim())) return;

            e.preventDefault();
            const p = this.potong[i];
            const sebelum = p.isi.slice(0, el.selectionStart);
            const sesudah = p.isi.slice(el.selectionEnd);
            const baris = potongTeks(teks);
            const pertama = baris[0]?.jenis === 'baris' ? baris.shift() : null;

            // Baris pertama menyambung ke kursor; bila barisnya masih kosong, nomornya ikut terbawa.
            if (pertama) {
                if (p.nomor === null && sebelum.trim() === '') p.nomor = pertama.nomor;
                p.isi = sebelum + (p.nomor === pertama.nomor || pertama.nomor === null ? pertama.isi : `${pertama.nomor} ${pertama.isi}`);
            } else {
                p.isi = sebelum;
            }

            const tambahan = baris.map(baru);
            const akhir = tambahan.at(-1);
            if (akhir?.jenis === 'baris') {
                const posisi = akhir.isi.length;
                akhir.isi += sesudah;
                this.potong.splice(i + 1, 0, ...tambahan);
                this.potong = this.rapikan(this.potong);
                this.simpan();
                this.fokusBaris(akhir.id, posisi);
            } else {
                const posisi = p.isi.length;
                if (sesudah !== '') tambahan.push(baru({ jenis: 'baris', nomor: null, isi: sesudah }));
                this.potong.splice(i + 1, 0, ...tambahan);
                this.potong = this.rapikan(this.potong);
                this.simpan();
                this.fokusBaris(p.id, posisi);
            }
        },

        ubahSel(i, r, c, nilai) {
            this.potong[i].sel[r][c] = nilai;
            this.simpan();
        },

        /** Tabel dari Excel/Word yang ditempel ke sel mengisi sel mulai dari sel itu, seperti di Excel. */
        tempelDiSel(e, i, r, c) {
            const hasil = tabelDariClipboard(e.clipboardData);
            if (hasil === null) return;
            e.preventDefault();
            const baris = potongTeks(hasil).filter((p) => p.jenis === 'tabel').flatMap((p) => p.sel);
            const t = this.potong[i];
            const kolom = Math.max(t.sel[0].length, c + Math.max(...baris.map((b) => b.length)));

            t.sel = t.sel.map((b) => [...b, ...Array(kolom - b.length).fill('')]);
            if (t.rata) t.rata = [...t.rata, ...Array(kolom - t.rata.length).fill('left')];
            baris.forEach((isi, k) => {
                t.sel[r + k] ??= Array(kolom).fill('');
                isi.forEach((nilai, j) => { t.sel[r + k][c + j] = nilai; });
            });
            this.simpan();
        },

        /** Enter di sel pindah ke sel di bawahnya; di baris terakhir menambah baris. */
        selBawah(i, r, c) {
            if (r === this.potong[i].sel.length - 1) this.tambahBaris(i, r);
            this.fokusSel(i, r + 1, c);
        },

        posisiSel(i) {
            const t = this.potong[i];
            return this.sel?.i === i ? this.sel : { i, r: t.sel.length - 1, c: t.sel[0].length - 1 };
        },

        /** Rata isi sel: baris judul selalu rata tengah, baris lain mengikuti rata kolomnya. */
        rataSel(t, r, c) {
            return t.judul && r === 0 ? 'center' : (t.rata?.[c] ?? 'left');
        },

        aturRata(i, rata) {
            const t = this.potong[i];
            const { r, c } = this.posisiSel(i);
            t.rata = t.sel[0].map((_, k) => (k === c ? rata : (t.rata?.[k] ?? 'left')));
            this.simpan();
            this.fokusSel(i, r, c);
        },

        aturJudul(i) {
            const t = this.potong[i];
            t.judul = !t.judul;
            this.simpan();
        },

        tambahBaris(i, r = this.posisiSel(i).r) {
            const t = this.potong[i];
            t.sel.splice(r + 1, 0, Array(t.sel[0].length).fill(''));
            this.simpan();
            this.fokusSel(i, r + 1, this.sel?.i === i ? this.sel.c : 0);
        },

        tambahKolom(i) {
            const t = this.potong[i];
            const { r, c } = this.posisiSel(i);
            t.sel.forEach((b) => b.splice(c + 1, 0, ''));
            t.rata?.splice(c + 1, 0, 'left');
            this.simpan();
            this.fokusSel(i, r, c + 1);
        },

        hapusBaris(i) {
            const t = this.potong[i];
            if (t.sel.length === 1) return this.hapusTabel(i);
            const { r, c } = this.posisiSel(i);
            t.sel.splice(r, 1);
            if (r === 0) t.judul = false;
            this.simpan();
            this.fokusSel(i, Math.min(r, t.sel.length - 1), c);
        },

        hapusKolom(i) {
            const t = this.potong[i];
            if (t.sel[0].length === 1) return this.hapusTabel(i);
            const { r, c } = this.posisiSel(i);
            t.sel.forEach((b) => b.splice(c, 1));
            t.rata?.splice(c, 1);
            this.simpan();
            this.fokusSel(i, r, Math.min(c, t.sel[0].length - 1));
        },

        hapusTabel(i) {
            const sebelum = this.potong[i - 1];
            this.potong.splice(i, 1);
            this.potong = this.rapikan(this.potong);
            this.sel = null;
            this.simpan();
            if (sebelum?.jenis === 'baris') this.fokusBaris(sebelum.id, 'akhir');
        },
    };
}
