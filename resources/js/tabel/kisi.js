/**
 * Tabel lampiran sebagai kisi: tiap sel dengan letaknya (kiri, atas, kanan, bawah) dan lebar tiap kolom, supaya
 * menyisip, membagi, dan mengatur garis tetap benar walaupun ada sel gabungan. Dibaca dari simpul ProseMirror, diubah,
 * lalu ditulis lagi menjadi simpul baru.
 */
import { TableMap } from '@tiptap/pm/tables';

export const SISI = ['atas', 'kanan', 'bawah', 'kiri'];
const SEBERANG = { atas: 'bawah', bawah: 'atas', kiri: 'kanan', kanan: 'kiri' };

export function bacaKisi(tabel) {
    const peta = TableMap.get(tabel);
    const kolom = Array(peta.width).fill(0);
    const sel = [];

    for (const pos of new Set(peta.map)) {
        const node = tabel.nodeAt(pos);
        const { left: kiri, top: atas, right: kanan, bottom: bawah } = peta.findCell(pos);
        sel.push({ node, pos, kiri, atas, kanan, bawah, attrs: { ...node.attrs } });
        (node.attrs.colwidth ?? []).forEach((px, i) => { kolom[kiri + i] ||= px; });
    }

    const baris = [];
    tabel.forEach((b) => baris.push({ ...b.attrs }));

    return { tabel, kolom, baris, sel };
}

export function tulisKisi({ tabel, kolom, baris, sel }) {
    const jenisBaris = tabel.firstChild.type;

    return tabel.type.create(tabel.attrs, baris.map((attrs, b) => jenisBaris.create(attrs, sel
        .filter((s) => s.atas === b)
        .sort((x, y) => x.kiri - y.kiri)
        .map((s) => {
            const lebar = kolom.slice(s.kiri, s.kanan);

            return s.node.type.create(
                { ...s.attrs, colspan: s.kanan - s.kiri, rowspan: s.bawah - s.atas, colwidth: lebar.some(Boolean) ? lebar : null },
                s.node.content,
                s.node.marks,
            );
        }))), tabel.marks);
}

/** Sel kosong bergaya sama dengan sel acuan: garis, arsiran, dan rata paragrafnya. */
function selKosong(acuan, letak, jenis = acuan.node.type) {
    const paragraf = acuan.node.firstChild?.isTextblock && acuan.node.firstChild.type.name === 'paragraph' ? acuan.node.firstChild : null;
    const isi = paragraf ? paragraf.type.create(paragraf.attrs) : jenis.schema.nodes.paragraph.create();

    return { node: jenis.create(null, isi), attrs: { ...acuan.attrs }, ...letak };
}

const selDi = (kisi, b, k) => kisi.sel.find((s) => s.atas <= b && b < s.bawah && s.kiri <= k && k < s.kanan);

/** Baris baru di batas `di`, meniru baris `acuan`. Sel gabungan yang melewati batas itu ikut memanjang. */
export function sisipBaris(kisi, di, jumlah, acuan) {
    const contoh = [];
    for (let k = 0; k < kisi.kolom.length;) {
        const s = selDi(kisi, acuan, k);
        if (!s) break;
        if (!(s.atas < di && di < s.bawah)) contoh.push({ ...s });
        k = s.kanan;
    }

    // Baris sesudah baris judul adalah baris isi, bukan judul lagi.
    const biasa = kisi.tabel.type.schema.nodes.tableCell;
    const judul = di > acuan && contoh.length > 0 && contoh.every((s) => s.node.type.name === 'tableHeader');

    for (const s of kisi.sel) {
        if (s.atas < di && di < s.bawah) s.bawah += jumlah;
        else if (s.atas >= di) { s.atas += jumlah; s.bawah += jumlah; }
    }
    for (let i = 0; i < jumlah; i++) {
        contoh.forEach((s) => kisi.sel.push(selKosong(s, { kiri: s.kiri, kanan: s.kanan, atas: di + i, bawah: di + i + 1 }, judul ? biasa : s.node.type)));
    }
    kisi.baris.splice(di, 0, ...Array.from({ length: jumlah }, () => ({ ...kisi.baris[acuan] })));
}

export function sisipKolom(kisi, di, jumlah, acuan) {
    const contoh = [];
    for (let b = 0; b < kisi.baris.length;) {
        const s = selDi(kisi, b, acuan);
        if (!s) break;
        if (!(s.kiri < di && di < s.kanan)) contoh.push({ ...s });
        b = s.bawah;
    }

    for (const s of kisi.sel) {
        if (s.kiri < di && di < s.kanan) s.kanan += jumlah;
        else if (s.kiri >= di) { s.kiri += jumlah; s.kanan += jumlah; }
    }
    for (let i = 0; i < jumlah; i++) {
        contoh.forEach((s) => kisi.sel.push(selKosong(s, { atas: s.atas, bawah: s.bawah, kiri: di + i, kanan: di + i + 1 })));
    }
    kisi.kolom.splice(di, 0, ...Array(jumlah).fill(kisi.kolom[acuan]));
}

/** Potongan [awal, akhir] sebanyak n dari rentang; rentangnya harus >= n. */
const potong = (awal, akhir, n) => Array.from({ length: n }, (_, i) => [
    awal + Math.round((akhir - awal) * i / n), awal + Math.round((akhir - awal) * (i + 1) / n),
]);

/** Menambah garis kisi di dalam rentang sel, tanpa mengubah lebar tabel; sel lain yang dilewati ikut melebar. */
function tambahGarisKolom(kisi, sel, kurang) {
    const akhir = sel.kanan - 1;
    const lebar = kisi.kolom[akhir];
    for (const s of kisi.sel) {
        if (s.kiri > akhir) { s.kiri += kurang; s.kanan += kurang; }
        else if (s.kanan > akhir) s.kanan += kurang;
    }
    kisi.kolom.splice(akhir, 1, ...Array(kurang + 1).fill(lebar ? Math.max(1, Math.round(lebar / (kurang + 1))) : 0));
}

function tambahGarisBaris(kisi, sel, kurang) {
    const akhir = sel.bawah - 1;
    for (const s of kisi.sel) {
        if (s.atas > akhir) { s.atas += kurang; s.bawah += kurang; }
        else if (s.bawah > akhir) s.bawah += kurang;
    }
    const attrs = kisi.baris[akhir];
    kisi.baris.splice(akhir, 1, ...Array.from({ length: kurang + 1 }, () => ({ ...attrs, tinggi: attrs.tinggi ? Math.round(attrs.tinggi / (kurang + 1)) : attrs.tinggi })));
}

/** Membagi satu sel menjadi `nKolom` x `nBaris`; isinya tetap di sel pertama. */
export function bagiSel(kisi, sel, nKolom, nBaris) {
    // Sel satu kolom dibagi rata; sel gabungan dipecah di garis kisi yang sudah ada dulu.
    if (nKolom > sel.kanan - sel.kiri) tambahGarisKolom(kisi, sel, nKolom - (sel.kanan - sel.kiri));
    if (nBaris > sel.bawah - sel.atas) tambahGarisBaris(kisi, sel, nBaris - (sel.bawah - sel.atas));

    const bagianKolom = potong(sel.kiri, sel.kanan, nKolom);
    const bagianBaris = potong(sel.atas, sel.bawah, nBaris);

    bagianBaris.forEach(([atas, bawah], b) => bagianKolom.forEach(([kiri, kanan], k) => {
        if (b || k) kisi.sel.push(selKosong(sel, { atas, bawah, kiri, kanan }));
    }));
    [sel.atas, sel.bawah] = bagianBaris[0];
    [sel.kiri, sel.kanan] = bagianKolom[0];
}

/** Garis satu sisi sel: null garis biasa, 'none' tanpa garis, atau "solid 1.5" (jenis dan tebal pt). */
export const garisSisi = (s, sisi) => s.attrs.garis?.[sisi] ?? null;

function tulisGaris(s, sisi, nilai) {
    const garis = { ...s.attrs.garis };
    if (nilai) garis[sisi] = nilai;
    else delete garis[sisi];
    s.attrs.garis = Object.keys(garis).length ? Object.fromEntries(SISI.filter((x) => garis[x]).map((x) => [x, garis[x]])) : null;
}

/** Sel yang berbagi satu sisi dengan sel ini. */
function tetangga(kisi, s, sisi) {
    return kisi.sel.filter((t) => t !== s && (sisi === 'atas' || sisi === 'bawah'
        ? (sisi === 'atas' ? t.bawah === s.atas : t.atas === s.bawah) && t.kiri < s.kanan && s.kiri < t.kanan
        : (sisi === 'kiri' ? t.kanan === s.kiri : t.kiri === s.kanan) && t.atas < s.bawah && s.atas < t.bawah));
}

const didalam = (s, r) => s.kiri >= r.kiri && s.kanan <= r.kanan && s.atas >= r.atas && s.bawah <= r.bawah;

/** Sisi-sisi yang dimaksud: [sel, sisi]. mana: semua | luar | dalam | tanpa | atas | bawah | kiri | kanan. */
function sisiDipilih(kisi, rentang, mana) {
    const hasil = [];

    for (const s of kisi.sel.filter((x) => didalam(x, rentang))) {
        for (const sisi of SISI) {
            const tepi = s[sisi] === rentang[sisi];
            if (mana === 'semua' || mana === 'tanpa' || (mana === 'luar' && tepi) || (mana === 'dalam' && !tepi) || (mana === sisi && tepi)) hasil.push([s, sisi]);
        }
    }

    return hasil;
}

/**
 * Mengatur garis seperti di Word. Sisi yang dipakai bersama ditulis di kedua selnya: pada tabel yang garisnya menyatu,
 * garis sel sebelah mengalahkan "tanpa garis". Tombol satu sisi yang garisnya sudah sama berarti menghapusnya.
 */
export function aturGaris(kisi, rentang, mana, nilai) {
    const sisi = sisiDipilih(kisi, rentang, mana);
    let baru = mana === 'tanpa' ? 'none' : nilai;
    if (SISI.includes(mana) && sisi.length && sisi.every(([s, x]) => garisSisi(s, x) === baru)) baru = 'none';

    for (const [s, x] of sisi) {
        tulisGaris(s, x, baru);
        for (const t of tetangga(kisi, s, x)) {
            const muat = x === 'atas' || x === 'bawah'
                ? t.kiri >= rentang.kiri && t.kanan <= rentang.kanan
                : t.atas >= rentang.atas && t.bawah <= rentang.bawah;
            if (muat) tulisGaris(t, SEBERANG[x], baru);
        }
    }
}

const kuat = (nilai) => (nilai === 'none' ? 0 : nilai === null ? 1 : 1 + parseFloat(nilai.split(' ')[1]) + (nilai.startsWith('double') ? 0.1 : 0));

/**
 * Word hanya menulis garis bersama di salah satu selnya. Disamakan di kedua sel (yang terkuat), supaya yang tampak di
 * layar, PDF, dan Word sama. Mengembalikan ada tidaknya yang berubah.
 */
export function rapikanGaris(kisi) {
    let berubah = false;

    for (const s of kisi.sel) {
        for (const x of SISI) {
            const bersama = tetangga(kisi, s, x);
            if (!bersama.length) continue;
            const terkuat = bersama.map((t) => garisSisi(t, SEBERANG[x])).reduce((a, b) => (kuat(b) > kuat(a) ? b : a));
            if (kuat(terkuat) > kuat(garisSisi(s, x)) && bersama.every((t) => garisSisi(t, SEBERANG[x]) === terkuat)) {
                tulisGaris(s, x, terkuat);
                berubah = true;
            }
        }
    }

    return berubah;
}
