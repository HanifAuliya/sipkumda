import tabelDariClipboard from '../tempel-tabel';

/**
 * Editor rumusan pasal: teks biasa diketik di kotak teks, tabel tampil sebagai tabel yang selnya bisa diisi.
 * Hasilnya tetap teks dengan baris tabel "| a | b |", sama persis dengan yang dibaca TataLetakNaskah,
 * jadi penyimpanan, pratinjau, dan cetak tidak berubah. Dipakai di dalam x-data baris-kerja (properti `teks`).
 */

/** Sama dengan TataLetakNaskah::selTabel(): null untuk baris pemisah "| --- |". */
function selTabel(baris) {
    const sel = baris.trim().replace(/^\||\|$/g, '').split('|').map((s) => s.trim());

    return sel.some((s) => !/^-*$/.test(s)) ? sel : null;
}

/** Sama dengan TataLetakNaskah::potongan(): baris tabel yang berurutan menjadi satu tabel. */
export function potongTeks(teks) {
    const hasil = [];

    for (const baris of String(teks ?? '').split(/\r?\n/)) {
        const terakhir = hasil.at(-1);

        if (baris.trimStart().startsWith('|')) {
            const sel = selTabel(baris);
            if (sel === null) continue;

            if (terakhir?.jenis === 'tabel') terakhir.sel.push(sel);
            else hasil.push({ jenis: 'tabel', sel: [sel] });
        } else if (terakhir?.jenis === 'teks') {
            terakhir.isi += `\n${baris}`;
        } else {
            hasil.push({ jenis: 'teks', isi: baris });
        }
    }

    // Semua baris tabel sama jumlah kolomnya, supaya tabelnya lurus saat diedit.
    for (const p of hasil) {
        if (p.jenis !== 'tabel') continue;
        const kolom = Math.max(...p.sel.map((b) => b.length));
        p.sel = p.sel.map((b) => [...b, ...Array(kolom - b.length).fill('')]);
    }

    return hasil;
}

/** Isi sel tidak boleh memuat garis tegak atau ganti baris, karena keduanya bagian dari format tabel. */
const bersihSel = (isi) => isi.replace(/\s+/g, ' ').replace(/\|/g, '/').trim();

export function gabungTeks(potongan) {
    return potongan
        .flatMap((p) => (p.jenis === 'tabel'
            ? p.sel.map((b) => `| ${b.map(bersihSel).join(' | ')} |`)
            : (p.isi.trim() === '' ? [] : [p.isi.replace(/\n+$/, '')])))
        .join('\n');
}

let urut = 0;
const baru = (p) => ({ ...p, id: ++urut });

export default function editorRumusan(barisMinimal = 3) {
    // Disimpan di luar data reaktif. $root tidak bisa dipakai: tombol yang ditekan bisa ikut hilang
    // (misalnya Hapus Tabel), dan elemen yang sudah lepas dari halaman tidak punya akar lagi.
    let akar = null;

    return {
        potong: [],
        barisMinimal,
        // Posisi kursor terakhir di kotak teks, untuk tempat menyisipkan tabel.
        kursor: null,
        // Sel tabel yang terakhir disentuh, untuk tombol tambah/hapus baris dan kolom.
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
            this.ditulis = teks;
            this.kursor = null;
            this.sel = null;
        },

        /** Selalu ada kotak teks sebelum dan sesudah tabel, supaya bisa menulis di atas atau di bawahnya. */
        rapikan(potong) {
            const hasil = [];

            for (const p of potong) {
                const terakhir = hasil.at(-1);
                if (p.jenis === 'teks' && terakhir?.jenis === 'teks') {
                    terakhir.isi = [terakhir.isi, p.isi].filter((x) => x.trim() !== '').join('\n');
                    continue;
                }
                if (p.jenis === 'tabel' && terakhir?.jenis !== 'teks') hasil.push(baru({ jenis: 'teks', isi: '' }));
                hasil.push(p);
            }
            if (hasil.at(-1)?.jenis !== 'teks') hasil.push(baru({ jenis: 'teks', isi: '' }));

            return hasil;
        },

        /** Tombol tabel tidak memicu event input, jadi editor mengabarkannya sendiri untuk simpan otomatis. */
        simpan() {
            this.ditulis = gabungTeks(this.potong);
            this.teks = this.ditulis;
            akar?.dispatchEvent(new Event('input', { bubbles: true }));
        },

        get adaTabel() {
            return this.potong.some((p) => p.jenis === 'tabel');
        },

        /** Kotak teks tunggal setinggi isian lama; kotak di sekitar tabel cukup dua baris. */
        tinggiTeks(p) {
            return this.adaTabel ? (p.isi.trim() === '' ? 1 : 2) : this.barisMinimal;
        },

        ubahTeks(i, nilai) {
            this.potong[i].isi = nilai;
            this.simpan();
        },

        ubahSel(i, r, c, nilai) {
            this.potong[i].sel[r][c] = nilai;
            this.simpan();
        },

        /** Sel baru baru ada setelah Alpine menggambar ulang tabel, jadi dicoba beberapa frame. */
        fokusSel(i, r, c) {
            const id = this.potong[i]?.id;
            const coba = (sisa) => {
                const el = akar?.querySelector(`[data-sel="${id}-${r}-${c}"]`);
                if (el) el.focus();
                else if (sisa > 0) requestAnimationFrame(() => coba(sisa - 1));
            };
            this.$nextTick(() => coba(10));
        },

        /** Menyisipkan potongan (tabel atau hasil tempel) di posisi kursor kotak teks ke-i. */
        sisipkan(i, posisi, potonganBaru) {
            const p = this.potong[i];
            const sebelum = p.isi.slice(0, posisi).replace(/\n+$/, '');
            const sesudah = p.isi.slice(posisi).replace(/^\n+/, '');
            this.potong.splice(i, 1, baru({ jenis: 'teks', isi: sebelum }), ...potonganBaru.map(baru), baru({ jenis: 'teks', isi: sesudah }));
            this.potong = this.rapikan(this.potong);
            this.simpan();
        },

        sisipTabel() {
            let { i, posisi } = this.kursor ?? {};
            if (this.potong[i]?.jenis !== 'teks') {
                i = this.potong.length - 1;
                posisi = this.potong[i].isi.length;
            }
            const tabel = { jenis: 'tabel', sel: [['', '', ''], ['', '', '']] };
            this.sisipkan(i, posisi, [tabel]);
            const indeks = this.potong.findIndex((p, k) => k > i && p.jenis === 'tabel');
            this.fokusSel(indeks, 0, 0);
        },

        tempelDiTeks(e, i) {
            const hasil = tabelDariClipboard(e.clipboardData);
            if (hasil === null) return;
            e.preventDefault();
            this.sisipkan(i, e.target.selectionStart, potongTeks(hasil));
            const tabel = this.potong.findIndex((p, k) => k > i && p.jenis === 'tabel');
            if (tabel > -1) this.fokusSel(tabel, 0, 0);
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
            baris.forEach((isi, k) => {
                t.sel[r + k] ??= Array(kolom).fill('');
                isi.forEach((nilai, j) => { t.sel[r + k][c + j] = nilai; });
            });
            this.simpan();
        },

        /** Enter di sel pindah ke sel di bawahnya; di baris terakhir menambah baris. */
        turun(i, r, c) {
            if (r === this.potong[i].sel.length - 1) this.tambahBaris(i, r);
            this.fokusSel(i, r + 1, c);
        },

        posisiSel(i) {
            const t = this.potong[i];
            return this.sel?.i === i ? this.sel : { i, r: t.sel.length - 1, c: t.sel[0].length - 1 };
        },

        tambahBaris(i, r = this.posisiSel(i).r) {
            const t = this.potong[i];
            t.sel.splice(r + 1, 0, Array(t.sel[0].length).fill(''));
            this.simpan();
            this.fokusSel(i, r + 1, this.sel?.i === i ? this.sel.c : 0);
        },

        tambahKolom(i) {
            const { r, c } = this.posisiSel(i);
            this.potong[i].sel.forEach((b) => b.splice(c + 1, 0, ''));
            this.simpan();
            this.fokusSel(i, r, c + 1);
        },

        hapusBaris(i) {
            const t = this.potong[i];
            if (t.sel.length === 1) return this.hapusTabel(i);
            const { r, c } = this.posisiSel(i);
            t.sel.splice(r, 1);
            this.simpan();
            this.fokusSel(i, Math.min(r, t.sel.length - 1), c);
        },

        hapusKolom(i) {
            const t = this.potong[i];
            if (t.sel[0].length === 1) return this.hapusTabel(i);
            const { r, c } = this.posisiSel(i);
            t.sel.forEach((b) => b.splice(c, 1));
            this.simpan();
            this.fokusSel(i, r, Math.min(c, t.sel[0].length - 1));
        },

        hapusTabel(i) {
            this.potong.splice(i, 1);
            this.potong = this.rapikan(this.potong);
            this.sel = null;
            this.simpan();
            this.$nextTick(() => akar?.querySelectorAll('textarea[data-teks]')[Math.max(0, i - 1)]?.focus());
        },
    };
}
