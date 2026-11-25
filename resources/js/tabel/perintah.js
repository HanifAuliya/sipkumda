/**
 * Perintah tabel lampiran, seperti tab Tata Letak dan Desain tabel di Word. Semuanya menerima EditorView dan bekerja
 * pada sel yang dipilih, atau sel tempat kursor berada. Ukuran di kertas: 96 px = 2,54 cm.
 */
import {
    CellSelection, TableMap, deleteCellSelection, deleteColumn, deleteRow, deleteTable, isInTable, mergeCells, moveTableColumn,
    moveTableRow, selectedRect, splitCell, toggleHeaderRow,
} from '@tiptap/pm/tables';
import { Selection, TextSelection } from '@tiptap/pm/state';
import { aturGaris, bacaKisi, bagiSel, sisipBaris as sisipBarisKisi, sisipKolom as sisipKolomKisi, tulisKisi } from './kisi';

export const KOLOM_MIN = 25;
const PX_PER_CM = 96 / 2.54;
const PT_PER_CM = 72 / 2.54;

/** Lebar isi kertas dalam px, dipasang editor-lampiran saat dibuat. */
const lebarKertas = (view) => Number(view.dom.closest('[data-wadah]')?.dataset.wadah) || 627;

/** Kertas diperkecil dengan zoom: px di layar dibagi skala menjadi px di kertas. */
export const skala = (el) => {
    const kotak = el.getBoundingClientRect().width;

    return el.offsetWidth > 0 && kotak > 0 ? kotak / el.offsetWidth : 1;
};

/** Letak garis kolom dan baris di layar, dalam px kertas terhadap sudut kiri atas tabel. */
export function ukur(view, awal, kisi) {
    const el = view.nodeDOM(awal - 1)?.querySelector?.('table');
    if (!el) return null;

    const s = skala(el);
    const tabel = el.getBoundingClientRect();
    const x = Array(kisi.kolom.length + 1).fill(null);
    const y = Array(kisi.baris.length + 1).fill(null);

    for (const sel of kisi.sel) {
        const kotak = view.nodeDOM(awal + sel.pos)?.getBoundingClientRect?.();
        if (!kotak) continue;
        x[sel.kiri] = (kotak.left - tabel.left) / s;
        x[sel.kanan] = (kotak.right - tabel.left) / s;
        y[sel.atas] = (kotak.top - tabel.top) / s;
        y[sel.bawah] = (kotak.bottom - tabel.top) / s;
    }

    // Garis yang seluruhnya tertutup sel gabungan: dibagi rata di antara garis yang diketahui.
    const isi = (a, ujung) => {
        a[0] ??= 0;
        a[a.length - 1] ??= ujung;
        for (let i = 1; i < a.length - 1; i++) {
            if (a[i] !== null) continue;
            let j = i;
            while (a[j] === null) j++;
            a[i] = a[i - 1] + (a[j] - a[i - 1]) / (j - i + 1);
        }
    };
    isi(x, tabel.width / s);
    isi(y, tabel.height / s);

    return { el, s, x, y };
}

/** Semua kolom diberi lebar seperti yang tampak, supaya mengubah satu kolom tidak menggeser kolom lain. */
export function pastikanLebar(view, awal, kisi) {
    if (kisi.kolom.every(Boolean)) return;
    const u = ukur(view, awal, kisi);
    if (u) kisi.kolom = kisi.kolom.map((px, i) => px || Math.max(KOLOM_MIN, Math.round(u.x[i + 1] - u.x[i])));
}

/** Diciutkan sebanding bila melebihi `batas`. */
function muatkan(kolom, batas) {
    const total = kolom.reduce((a, b) => a + b, 0);
    return total > batas + 1 ? kolom.map((px) => Math.max(KOLOM_MIN, Math.floor(px * batas / total))) : kolom;
}

const rentang = (r) => ({ kiri: r.left, atas: r.top, kanan: r.right, bawah: r.bottom });

/** Sel (anchor dan head) untuk memilih rentang kisi; tanpa rentang, seluruh tabel. */
function pilihan(doc, awal, peta, r) {
    const p = r ?? { kiri: 0, atas: 0, kanan: peta.width, bawah: peta.height };

    return CellSelection.create(doc, awal + peta.map[p.atas * peta.width + p.kiri], awal + peta.map[(p.bawah - 1) * peta.width + p.kanan - 1]);
}

/**
 * Menjalankan perubahan kisi. `kerja(kisi, r)` boleh mengembalikan rentang yang dipilih sesudahnya, atau false untuk
 * membatalkan. Tanpa rentang, kursor kembali ke tempatnya di sel yang sama.
 */
function ubah(view, kerja) {
    const { state } = view;
    if (!isInTable(state)) return false;

    const r = selectedRect(state);
    const kisi = bacaKisi(r.table);
    const hasil = kerja(kisi, rentang(r), r);
    if (hasil === false) return false;

    const baru = tulisKisi(kisi);
    const blok = state.selection instanceof CellSelection;
    const geser = blok ? 0 : state.selection.head - (r.tableStart + r.map.map[r.top * r.map.width + r.left]);
    const tr = state.tr.replaceWith(r.tableStart - 1, r.tableStart - 1 + r.table.nodeSize, baru);
    const peta = TableMap.get(baru);
    const tujuan = hasil && typeof hasil === 'object' ? hasil : rentang(r);

    if (blok || (hasil && tujuan.banyak)) {
        tr.setSelection(pilihan(tr.doc, r.tableStart, peta, tujuan));
    } else {
        const sel = r.tableStart + peta.map[tujuan.atas * peta.width + tujuan.kiri];
        const di = hasil && typeof hasil === 'object' ? sel + 2 : Math.min(sel + geser, sel + tr.doc.nodeAt(sel).nodeSize - 2);
        tr.setSelection(TextSelection.near(tr.doc.resolve(di)));
    }

    view.dispatch(tr.scrollIntoView());

    return true;
}

/** Mengubah atribut semua sel yang dipilih. */
function aturSel(view, attrs) {
    return ubah(view, (kisi, r) => {
        for (const s of kisi.sel) {
            if (s.kiri >= r.kiri && s.kanan <= r.kanan && s.atas >= r.atas && s.bawah <= r.bawah) Object.assign(s.attrs, attrs);
        }
    });
}

export function sisipBaris(view, arah) {
    return ubah(view, (kisi, r) => {
        const jumlah = r.bawah - r.atas;
        const di = arah < 0 ? r.atas : r.bawah;
        sisipBarisKisi(kisi, di, jumlah, arah < 0 ? r.atas : r.bawah - 1);

        return { ...r, atas: di, bawah: di + jumlah, banyak: jumlah > 1 };
    });
}

export function sisipKolom(view, arah) {
    return ubah(view, (kisi, r, { tableStart }) => {
        const jumlah = r.kanan - r.kiri;
        const di = arah < 0 ? r.kiri : r.kanan;
        pastikanLebar(view, tableStart, kisi);
        sisipKolomKisi(kisi, di, jumlah, arah < 0 ? r.kiri : r.kanan - 1);
        kisi.kolom = muatkan(kisi.kolom, lebarKertas(view));

        return { ...r, kiri: di, kanan: di + jumlah, banyak: jumlah > 1 };
    });
}

/** Tombol + di tepi tabel: menyisip di satu batas tertentu, di mana pun kursornya. */
export function sisipDi(view, awal, arah, di) {
    const { state } = view;
    const tabel = state.doc.nodeAt(awal - 1);
    const kisi = bacaKisi(tabel);

    if (arah === 'baris') {
        sisipBarisKisi(kisi, di, 1, Math.max(0, di - 1));
    } else {
        pastikanLebar(view, awal, kisi);
        sisipKolomKisi(kisi, di, 1, Math.max(0, di - 1));
        kisi.kolom = muatkan(kisi.kolom, lebarKertas(view));
    }

    const baru = tulisKisi(kisi);
    const peta = TableMap.get(baru);
    const tr = state.tr.replaceWith(awal - 1, awal - 1 + tabel.nodeSize, baru);
    const sel = awal + peta.map[arah === 'baris' ? di * peta.width : di];
    view.dispatch(tr.setSelection(TextSelection.near(tr.doc.resolve(sel + 2))));
    view.focus();
}

/** Perintah bawaan prosemirror-tables, dengan kursor dijaga tetap di tabel yang sama. */
function bawaan(view, perintah) {
    const awal = selectedRect(view.state).tableStart;

    return perintah(view.state, (tr) => {
        const tabel = tr.doc.nodeAt(awal - 1);
        if (tabel?.type.name === 'table' && tr.selection.head > awal + tabel.nodeSize) tr.setSelection(Selection.near(tr.doc.resolve(awal + 2)));
        view.dispatch(tr);
    });
}

export const hapusBaris = (view) => bawaan(view, deleteRow) || hapusTabel(view);
export const hapusKolom = (view) => bawaan(view, deleteColumn) || hapusTabel(view);
export const hapusTabel = (view) => deleteTable(view.state, view.dispatch);
export const gabung = (view) => mergeCells(view.state, view.dispatch);

export function hapusIsi(view) {
    if (!(view.state.selection instanceof CellSelection)) pilih(view, 'sel');

    return deleteCellSelection(view.state, view.dispatch);
}

/** Memecah sel gabungan menjadi sel-sel asalnya. */
export const pisah = (view) => splitCell(view.state, view.dispatch);

/** Membagi sel seperti "Split Cells" di Word; beberapa sel yang dipilih digabung dulu. */
export function bagi(view, kolom, baris) {
    const n = (nilai) => Math.min(20, Math.max(1, Math.round(Number(nilai) || 1)));
    const [nKolom, nBaris] = [n(kolom), n(baris)];
    if (view.state.selection instanceof CellSelection && mergeCells(view.state)) mergeCells(view.state, view.dispatch);

    return ubah(view, (kisi, r, { tableStart }) => {
        const sel = kisi.sel.find((s) => s.kiri === r.kiri && s.atas === r.atas);
        if (!sel || (nKolom === 1 && nBaris === 1)) return false;
        pastikanLebar(view, tableStart, kisi);
        bagiSel(kisi, sel, nKolom, nBaris);
        kisi.kolom = muatkan(kisi.kolom.map((px) => Math.max(KOLOM_MIN, px)), lebarKertas(view));

        return { kiri: sel.kiri, atas: sel.atas, kanan: sel.kanan, bawah: sel.bawah };
    });
}

/** Kolom yang dipilih, atau semua kolom bila hanya satu, sama lebarnya. */
export function samaKolom(view) {
    return ubah(view, (kisi, r, { tableStart }) => {
        pastikanLebar(view, tableStart, kisi);
        const [a, b] = r.kanan - r.kiri > 1 ? [r.kiri, r.kanan] : [0, kisi.kolom.length];
        const rata = Math.floor(kisi.kolom.slice(a, b).reduce((x, y) => x + y, 0) / (b - a));
        kisi.kolom = kisi.kolom.map((px, i) => (i >= a && i < b ? rata : px));
    });
}

export function samaBaris(view) {
    return ubah(view, (kisi, r, { tableStart }) => {
        const u = ukur(view, tableStart, kisi);
        if (!u) return false;
        const [a, b] = r.bawah - r.atas > 1 ? [r.atas, r.bawah] : [0, kisi.baris.length];
        const rata = Math.ceil((u.y[b] - u.y[a]) / (b - a) * 0.75);
        for (let i = a; i < b; i++) kisi.baris[i].tinggi = rata;
    });
}

export function selebarKertas(view) {
    return ubah(view, (kisi, r, { tableStart }) => {
        pastikanLebar(view, tableStart, kisi);
        const total = kisi.kolom.reduce((a, b) => a + b, 0);
        kisi.kolom = kisi.kolom.map((px) => Math.max(KOLOM_MIN, Math.floor(px * lebarKertas(view) / total)));
        kisi.tabel = kisi.tabel.type.create({ ...kisi.tabel.attrs, letak: null }, kisi.tabel.content);
    });
}

/** Tiap kolom selebar isinya, diukur dari salinan tabel tanpa lebar tetap. */
export function sesuaiIsi(view) {
    return ubah(view, (kisi, r, { tableStart }) => {
        const asli = view.nodeDOM(tableStart - 1)?.querySelector('table');
        if (!asli) return false;

        const salinan = asli.cloneNode(true);
        salinan.querySelector('colgroup')?.remove();
        salinan.style.cssText = `position:absolute;visibility:hidden;table-layout:auto;width:auto;min-width:0;max-width:${lebarKertas(view)}px`;
        asli.parentElement.append(salinan);
        const x = Array(kisi.kolom.length + 1).fill(null);
        const s = skala(salinan);
        const awal = salinan.getBoundingClientRect().left;
        let b = 0;
        for (const baris of salinan.rows) {
            for (const sel of kisi.sel.filter((c) => c.atas === b).sort((c, d) => c.kiri - d.kiri).entries()) {
                const kotak = baris.cells[sel[0]]?.getBoundingClientRect();
                if (!kotak) continue;
                x[sel[1].kiri] = (kotak.left - awal) / s;
                x[sel[1].kanan] = (kotak.right - awal) / s;
            }
            b++;
        }
        salinan.remove();
        if (x.some((nilai) => nilai === null)) return false;
        kisi.kolom = muatkan(kisi.kolom.map((_, i) => Math.max(KOLOM_MIN, Math.ceil(x[i + 1] - x[i]) + 1)), lebarKertas(view));
    });
}

export function lebarKolom(view, cm) {
    const px = Math.max(KOLOM_MIN, Math.round(Number(cm) * PX_PER_CM));
    if (!(px > 0)) return false;

    return ubah(view, (kisi, r, { tableStart }) => {
        pastikanLebar(view, tableStart, kisi);
        const lain = kisi.kolom.reduce((a, w, i) => a + (i >= r.kiri && i < r.kanan ? 0 : w), 0);
        // Yang dipilih mengikuti isian; bila tidak muat di kertas, kolom lain yang mengalah.
        const pilih = Math.min(px, Math.floor((lebarKertas(view) - (kisi.kolom.length - (r.kanan - r.kiri)) * KOLOM_MIN) / (r.kanan - r.kiri)));
        const sisa = lebarKertas(view) - pilih * (r.kanan - r.kiri);
        const ciut = lain > sisa ? sisa / lain : 1;
        kisi.kolom = kisi.kolom.map((w, i) => (i >= r.kiri && i < r.kanan ? pilih : Math.max(KOLOM_MIN, Math.floor(w * ciut))));
    });
}

export function tinggiBaris(view, cm) {
    const pt = Math.round(Number(cm) * PT_PER_CM * 10) / 10;

    return ubah(view, (kisi, r) => {
        for (let i = r.atas; i < r.bawah; i++) kisi.baris[i].tinggi = pt > 0 ? Math.min(pt, 800) : null;
    });
}

export const garis = (view, mana, jenis, tebal) => ubah(view, (kisi, r) => {
    const biasa = jenis === 'solid' && Number(tebal) <= 0.5;
    aturGaris(kisi, r, mana, biasa ? null : `${jenis} ${Number(tebal)}`);
});

export const latar = (view, warna) => aturSel(view, { latar: warna || null });
export const tegak = (view, letak) => aturSel(view, { tegak: letak === 'top' ? null : letak });
export const barisJudul = (view) => toggleHeaderRow(view.state, view.dispatch);

/** Letak tabel di kertas: kiri, tengah, atau kanan (terasa bila tabelnya lebih sempit dari kertas). */
export function letak(view, nilai) {
    const { state } = view;
    if (!isInTable(state)) return false;
    view.dispatch(state.tr.setNodeAttribute(selectedRect(state).tableStart - 1, 'letak', nilai || null));

    return true;
}

export function pilih(view, apa) {
    const { state } = view;
    if (!isInTable(state)) return false;

    const { tableStart, map, ...r } = selectedRect(state);
    const p = rentang(r);
    if (apa === 'baris' || apa === 'tabel') Object.assign(p, { kiri: 0, kanan: map.width });
    if (apa === 'kolom' || apa === 'tabel') Object.assign(p, { atas: 0, bawah: map.height });
    view.dispatch(state.tr.setSelection(pilihan(state.doc, tableStart, map, p)));

    return true;
}

/** Dari pegangan di tepi tabel: memilih kolom atau baris `dari` sampai `ke`, atau seluruh tabel. */
export function pilihDeret(view, awal, arah, dari, ke) {
    const tabel = view.state.doc.nodeAt(awal - 1);
    const peta = TableMap.get(tabel);
    const [a, b] = [Math.min(dari, ke), Math.max(dari, ke) + 1];
    const r = arah === 'tabel' ? null
        : arah === 'kolom' ? { kiri: a, kanan: b, atas: 0, bawah: peta.height }
            : { kiri: 0, kanan: peta.width, atas: a, bawah: b };
    view.dispatch(view.state.tr.setSelection(pilihan(view.state.doc, awal, peta, r)));
    view.focus();
}

export function pindahBaris(view, arah) {
    const r = selectedRect(view.state);
    const [dari, ke] = arah < 0 ? [r.top, r.top - 1] : [r.bottom - 1, r.bottom];
    if (ke < 0 || ke >= r.map.height) return false;

    return moveTableRow({ from: dari, to: ke })(view.state, view.dispatch);
}

export function pindahKolom(view, arah) {
    const r = selectedRect(view.state);
    const [dari, ke] = arah < 0 ? [r.left, r.left - 1] : [r.right - 1, r.right];
    if (ke < 0 || ke >= r.map.width) return false;

    return moveTableColumn({ from: dari, to: ke })(view.state, view.dispatch);
}

/** Tabel dipisah menjadi dua di atas baris yang dipilih. */
export function pisahTabel(view) {
    const { state } = view;
    if (!isInTable(state)) return false;

    const r = selectedRect(state);
    const kisi = bacaKisi(r.table);
    if (r.top === 0 || kisi.sel.some((s) => s.atas < r.top && r.top < s.bawah)) return false;

    const baris = [];
    r.table.forEach((b) => baris.push(b));
    const bagian = [baris.slice(0, r.top), baris.slice(r.top)].map((isi) => r.table.type.create(r.table.attrs, isi));
    // Lebar kolom tabel kedua diambil dari baris pertamanya; dipastikan ada di semua sel.
    pastikanLebar(view, r.tableStart, kisi);
    const lebar = bagian.map((t) => tulisKisi({ ...bacaKisi(t), kolom: kisi.kolom }));
    const antara = state.schema.nodes.paragraph.create();
    const tr = state.tr.replaceWith(r.tableStart - 1, r.tableStart - 1 + r.table.nodeSize, [lebar[0], antara, lebar[1]]);
    view.dispatch(tr.setSelection(TextSelection.near(tr.doc.resolve(r.tableStart - 1 + lebar[0].nodeSize + 1))).scrollIntoView());

    return true;
}

/** Keadaan tabel untuk bilah alat; null bila kursor di luar tabel. */
export function keadaanTabel(view) {
    const { state } = view;
    if (!view.docView || !isInTable(state)) return null;

    const r = selectedRect(state);
    const sel = r.table.nodeAt(r.map.map[r.top * r.map.width + r.left]);
    const kisi = bacaKisi(r.table);
    const lebar = kisi.kolom.slice(r.left, r.right);
    let px = lebar.every(Boolean) ? lebar[0] : 0;
    let pt = kisi.baris[r.top].tinggi ?? 0;

    if (!px || !pt) {
        const u = ukur(view, r.tableStart, kisi);
        px ||= u ? u.x[r.left + 1] - u.x[r.left] : 0;
        pt ||= u ? (u.y[r.top + 1] - u.y[r.top]) * 0.75 : 0;
    }

    const bulat = (n) => Math.round(n * 100) / 100;

    return {
        lebar: bulat(px / PX_PER_CM),
        tinggi: bulat(pt / PT_PER_CM),
        baris: r.bottom - r.top,
        kolom: r.right - r.left,
        tegak: sel.attrs.tegak ?? 'top',
        latar: sel.attrs.latar ?? '',
        letak: r.table.attrs.letak ?? '',
        judul: kisi.sel.filter((s) => s.atas === 0).every((s) => s.node.type.name === 'tableHeader'),
        bisaGabung: mergeCells(state),
        bisaPisah: splitCell(state),
        bisaPisahTabel: r.top > 0,
    };
}
