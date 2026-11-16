/**
 * Nomor ayat dan butir rumusan, sama dengan TataLetakNaskah (GAYA_NOMOR, nomorBaris, barisTeks),
 * supaya yang tampil di editor sama dengan hasil cetak.
 */

/** Gaya nomor dari tingkat teratas, mengikuti Lampiran II UU 12/2011. */
export const GAYA = [
    { nama: 'ayat', pola: /^(\(\d+[a-z]?\))\s*(.*)$/s, contoh: '(1)' },
    { nama: 'huruf', pola: /^([a-z]\.)(?:\s+(.*))?$/s, contoh: 'a.' },
    { nama: 'angka', pola: /^(\d{1,3}\.)(?:\s+(.*))?$/s, contoh: '1.' },
    { nama: 'huruf_kurung', pola: /^([a-z]\))(?:\s+(.*))?$/s, contoh: 'a)' },
    { nama: 'angka_kurung', pola: /^(\d{1,3}\))(?:\s+(.*))?$/s, contoh: '1)' },
];

const URUTAN = Object.fromEntries(GAYA.map((g, i) => [g.nama, i]));

/** @returns {{ gaya: string|null, nomor: string|null, isi: string }} */
export function nomorBaris(baris) {
    for (const g of GAYA) {
        const m = baris.match(g.pola);
        if (m) return { gaya: g.nama, nomor: m[1], isi: m[2] ?? '' };
    }

    return { gaya: null, nomor: null, isi: baris };
}

/** Gaya dari nomor yang sudah dipisah, misalnya "(2)" atau "b.". */
export function gayaNomor(nomor) {
    return nomor === null ? null : nomorBaris(nomor).gaya;
}

export const urutanGaya = (gaya) => URUTAN[gaya];

const huruf = (ke) => {
    let hasil = '';
    for (; ke > 0; ke = Math.floor((ke - 1) / 26)) hasil = String.fromCharCode(97 + ((ke - 1) % 26)) + hasil;
    return hasil;
};

/** Nomor ke-n dengan gaya tertentu: nomorKe('huruf', 2) → "b.". */
export function nomorKe(gaya, ke) {
    return {
        ayat: `(${ke})`,
        huruf: `${huruf(ke)}.`,
        angka: `${ke}.`,
        huruf_kurung: `${huruf(ke)})`,
        angka_kurung: `${ke})`,
    }[gaya];
}

/** Ayat sisipan seperti "(1a)" dibiarkan apa adanya dan tidak ikut dihitung. */
const sisipan = (nomor) => /^\(\d+[a-z]\)$/.test(nomor ?? '');

/**
 * Tingkat jorok tiap baris, sama dengan TataLetakNaskah::barisTeks(): gaya yang lebih dalam dari
 * yang sedang terbuka menjorok satu tingkat; kalimat biasa menutup daftar kecuali ayatnya.
 * Tabel menjorok sejajar dengan teks butir di atasnya. Baris kosong mengikuti baris di atasnya.
 *
 * @param {Array<{jenis: string, nomor?: string|null, isi?: string}>} potong
 * @returns {number[]}
 */
export function hitungTingkat(potong) {
    let terbuka = [];
    let sebelum = null;

    return potong.map((p) => {
        if (p.jenis === 'tabel') {
            const tingkat = sebelum && sebelum.nomor !== null ? sebelum.tingkat + 1 : 0;
            sebelum = { nomor: null, tingkat };
            return tingkat;
        }

        const gaya = gayaNomor(p.nomor);

        if (gaya === null) {
            if (p.isi.trim() === '') return sebelum?.nomor ? sebelum.tingkat + 1 : (sebelum?.tingkat ?? 0);
            terbuka = terbuka.filter((g) => g === 'ayat');
            sebelum = { nomor: null, tingkat: 0 };
            return 0;
        }

        terbuka = terbuka.filter((g) => URUTAN[g] < URUTAN[gaya]);
        const tingkat = terbuka.length;
        terbuka.push(gaya);
        sebelum = { nomor: p.nomor, tingkat };
        return tingkat;
    });
}

/**
 * Mengurutkan ulang nomor seperti daftar bernomor di Word: nomor di bawah suatu tingkat mulai lagi
 * tiap kali tingkat itu berganti nomor; kalimat biasa mengakhiri daftar kecuali ayatnya.
 * Mengubah `nomor` pada potongan secara langsung.
 */
export function nomoriUlang(potong) {
    let hitung = Object.fromEntries(GAYA.map((g) => [g.nama, 0]));

    for (const p of potong) {
        if (p.jenis !== 'baris') continue;

        const gaya = gayaNomor(p.nomor);

        if (gaya === null) {
            if (p.isi.trim() !== '') hitung = { ...Object.fromEntries(GAYA.map((g) => [g.nama, 0])), ayat: hitung.ayat };
            continue;
        }
        if (sisipan(p.nomor)) continue;

        for (const g of GAYA) {
            if (URUTAN[g.nama] > URUTAN[gaya]) hitung[g.nama] = 0;
        }
        p.nomor = nomorKe(gaya, ++hitung[gaya]);
    }
}
