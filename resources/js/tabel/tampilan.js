/**
 * Tampilan tabel di editor lampiran, seperti di Word:
 * - seret garis kolom: kolom kiri dan kanannya bertukar lebar, tabel tetap (dengan Shift: tabel ikut melebar);
 *   seret garis baris: tinggi baris; garis kanan atau sudut kanan bawah: lebar tabel;
 * - tepi atas dan kiri memilih kolom dan baris, kotak di sudut memilih seluruh tabel;
 * - tombol + di antara kolom atau baris untuk menyisipkan.
 */
import { TableView, updateColumns } from '@tiptap/extension-table';
import { TableMap, cellAround } from '@tiptap/pm/tables';
import { bacaKisi } from './kisi';
import { KOLOM_MIN, pastikanLebar, pilihDeret, sisipDi, skala, ukur } from './perintah';

const DEKAT_KOLOM = 5;
const DEKAT_BARIS = 4;

const buat = (tag, kelas, judul) => {
    const el = document.createElement(tag);
    el.className = kelas;
    el.contentEditable = 'false';
    if (tag === 'button') el.type = 'button';
    if (judul) el.title = judul;

    return el;
};

/** Menyeret: `gerak(dx, dy)` dalam px kertas, lalu `selesai(dx, dy)`. Escape membatalkan. */
function seret(e, s, gerak, selesai) {
    e.preventDefault();
    const [x, y] = [e.clientX, e.clientY];
    let akhir = [0, 0];
    const pindah = (ev) => {
        akhir = [(ev.clientX - x) / s, (ev.clientY - y) / s];
        gerak(...akhir, ev);
    };
    const henti = (simpan) => {
        window.removeEventListener('mousemove', pindah);
        window.removeEventListener('mouseup', lepas);
        window.removeEventListener('keydown', batal, true);
        selesai(simpan, ...akhir);
    };
    const lepas = (ev) => { pindah(ev); henti(true); };
    const batal = (ev) => { if (ev.key === 'Escape') { ev.preventDefault(); ev.stopPropagation(); henti(false); } };
    window.addEventListener('mousemove', pindah);
    window.addEventListener('mouseup', lepas);
    window.addEventListener('keydown', batal, true);
}

/** Lebar kolom baru ditulis ke tiap sel; kursor tidak berpindah. */
function terapkanLebar(view, awal, kolom) {
    const tabel = view.state.doc.nodeAt(awal - 1);
    const tr = view.state.tr;
    for (const s of bacaKisi(tabel).sel) {
        const lebar = kolom.slice(s.kiri, s.kanan).map(Math.round);
        if (String(lebar) !== String(s.attrs.colwidth ?? '')) tr.setNodeAttribute(awal + s.pos, 'colwidth', lebar);
    }
    if (tr.docChanged) view.dispatch(tr);
}

export class TampilanTabel extends TableView {
    constructor(node, lebarMin, view, atribut, lebarKertas) {
        super(node, lebarMin, view, atribut);
        this.view = view;
        this.lebarKertas = lebarKertas;
        this.dom.tampilan = this;
        this.table.dataset.letak = node.attrs.letak ?? '';

        this.alat = buat('div', 'tabel-alat');
        this.pegangan = buat('button', 'tabel-pegangan', 'Pilih seluruh tabel');
        this.stripKolom = buat('div', 'tabel-strip-kolom', 'Pilih kolom');
        this.stripBaris = buat('div', 'tabel-strip-baris', 'Pilih baris');
        this.tambah = buat('button', 'tabel-tambah');
        this.sudut = buat('div', 'tabel-sudut', 'Seret untuk mengubah lebar tabel');
        this.garis = buat('div', 'tabel-garis');
        this.tambah.hidden = this.garis.hidden = true;
        this.pegangan.innerHTML = '<i class="bi bi-arrows-move" aria-hidden="true"></i>';
        this.tambah.innerHTML = '<i class="bi bi-plus-lg" aria-hidden="true"></i>';
        this.pegangan.setAttribute('aria-label', 'Pilih seluruh tabel');
        for (const el of [this.pegangan, this.tambah]) el.tabIndex = -1;
        this.alat.append(this.stripKolom, this.stripBaris, this.pegangan, this.sudut, this.tambah, this.garis);
        this.dom.append(this.alat);

        // Letak alat mengikuti tabelnya, yang bisa lebih sempit dari kertas dan berada di tengah atau kanan.
        this.pengamat = new ResizeObserver(() => this.letakkan());
        this.pengamat.observe(this.table);

        this.pegangan.addEventListener('mousedown', (e) => { e.preventDefault(); pilihDeret(view, this.awal(), 'tabel'); });
        this.stripKolom.addEventListener('mousedown', (e) => this.pilih(e, 'kolom'));
        this.stripBaris.addEventListener('mousedown', (e) => this.pilih(e, 'baris'));
        this.stripKolom.addEventListener('mousemove', (e) => this.tawarkan(e, 'kolom'));
        this.stripBaris.addEventListener('mousemove', (e) => this.tawarkan(e, 'baris'));
        this.dom.addEventListener('mouseleave', () => { this.tambah.hidden = true; });
        this.tambah.addEventListener('mousedown', (e) => {
            e.preventDefault();
            const { arah, di } = this.tambah.dataset;
            this.tambah.hidden = true;
            sisipDi(view, this.awal(), arah, Number(di));
        });
        this.sudut.addEventListener('mousedown', (e) => this.seretLebarTabel(e));
    }

    /** Posisi tepat di dalam tabel (tableStart). */
    awal() {
        return this.view.posAtDOM(this.contentDOM, 0);
    }

    ukur() {
        const awal = this.awal();
        const kisi = bacaKisi(this.view.state.doc.nodeAt(awal - 1));

        return { awal, kisi, ...ukur(this.view, awal, kisi) };
    }

    letakkan() {
        Object.assign(this.alat.style, {
            left: `${this.table.offsetLeft}px`, top: `${this.table.offsetTop}px`, width: `${this.table.offsetWidth}px`, height: `${this.table.offsetHeight}px`,
        });
    }

    /** Letak kursor di tabel, dalam px kertas. */
    titik(e, s) {
        const kotak = this.table.getBoundingClientRect();

        return [(e.clientX - kotak.left) / s, (e.clientY - kotak.top) / s];
    }

    pilih(e, arah) {
        e.preventDefault();
        const cari = (ev) => {
            const { awal, x, y, s } = this.ukur();
            const [px, py] = this.titik(ev, s);
            const [batas, nilai] = arah === 'kolom' ? [x, px] : [y, py];
            let i = batas.findIndex((b, n) => n > 0 && nilai < b) - 1;
            if (i < 0) i = nilai < 0 ? 0 : batas.length - 2;

            return { awal, i };
        };
        const { awal, i: dari } = cari(e);
        pilihDeret(this.view, awal, arah, dari, dari);
        seret(e, 1, (dx, dy, ev) => { const { i } = cari(ev); pilihDeret(this.view, awal, arah, dari, i); }, () => {});
    }

    /** Tombol + muncul di garis kolom atau baris terdekat. */
    tawarkan(e, arah) {
        const { x, y, s } = this.ukur();
        const [px, py] = this.titik(e, s);
        const [batas, nilai] = arah === 'kolom' ? [x, px] : [y, py];
        const di = batas.findIndex((b) => Math.abs(b - nilai) < 9);

        this.tambah.hidden = di < 0;
        if (di < 0) return;
        Object.assign(this.tambah.dataset, { arah, di });
        this.tambah.title = arah === 'kolom' ? 'Sisipkan kolom di sini' : 'Sisipkan baris di sini';
        Object.assign(this.tambah.style, arah === 'kolom' ? { left: `${batas[di]}px`, top: '-19px' } : { left: '-21px', top: `${batas[di]}px` });
    }

    tampilGaris(arah, letak) {
        this.garis.hidden = false;
        this.garis.className = `tabel-garis tabel-garis-${arah}`;
        this.garis.style.left = arah === 'kolom' ? `${letak}px` : '0';
        this.garis.style.top = arah === 'kolom' ? '0' : `${letak}px`;
    }

    seretKolom(e, batas) {
        const { awal, kisi, x, s } = this.ukur();
        pastikanLebar(this.view, awal, kisi);
        const kolom = kisi.kolom;
        const total = kolom.reduce((a, b) => a + b, 0);
        const ujung = batas === kolom.length;

        const hitung = (dx, sendiri) => {
            const min = KOLOM_MIN - kolom[batas - 1];
            const maks = sendiri || ujung ? Math.max(0, this.lebarKertas - total) : kolom[batas] - KOLOM_MIN;

            return Math.min(Math.max(dx, min), maks);
        };

        let sendiri = e.shiftKey;
        seret(e, s, (dx, dy, ev) => {
            sendiri = ev.shiftKey;
            this.tampilGaris('kolom', x[batas] + hitung(dx, sendiri));
        }, (simpan, dx) => {
            this.garis.hidden = true;
            const d = Math.round(hitung(dx, sendiri));
            if (!simpan || !d) return;
            kolom[batas - 1] += d;
            if (!ujung && !sendiri) kolom[batas] -= d;
            terapkanLebar(this.view, awal, kolom);
        });
    }

    seretBaris(e, batas) {
        const { awal, kisi, y, s } = this.ukur();
        const tinggi = y[batas] - y[batas - 1];
        const hitung = (dy) => Math.max(8, tinggi + dy);

        seret(e, s, (dx, dy) => this.tampilGaris('baris', y[batas - 1] + hitung(dy)), (simpan, dx, dy) => {
            this.garis.hidden = true;
            if (!simpan || Math.abs(dy) < 1) return;
            const { state } = this.view;
            let pos = awal;
            for (let i = 0; i < batas - 1; i++) pos += kisi.tabel.child(i).nodeSize;
            this.view.dispatch(state.tr.setNodeAttribute(pos, 'tinggi', Math.min(800, Math.round(hitung(dy) * 7.5) / 10)));
        });
    }

    seretLebarTabel(e) {
        const { awal, kisi, s } = this.ukur();
        pastikanLebar(this.view, awal, kisi);
        const total = kisi.kolom.reduce((a, b) => a + b, 0);
        const hitung = (dx) => Math.min(this.lebarKertas, Math.max(KOLOM_MIN * kisi.kolom.length, total + dx));

        seret(e, s, (dx) => this.tampilGaris('kolom', hitung(dx)), (simpan, dx) => {
            this.garis.hidden = true;
            if (!simpan || Math.abs(dx) < 1) return;
            terapkanLebar(this.view, awal, kisi.kolom.map((px) => Math.max(KOLOM_MIN, Math.floor(px * hitung(dx) / total))));
        });
    }

    update(node) {
        if (!node.sameMarkup(this.node)) return false;
        this.node = node;
        updateColumns(node, this.colgroup, this.table, this.cellMinWidth);

        return true;
    }

    stopEvent(e) {
        return this.alat.contains(e.target);
    }

    destroy() {
        this.pengamat.disconnect();
    }
}

/** Garis yang dekat dengan kursor: { arah, batas, tampilan } atau null. */
function garisDekat(view, e) {
    const sel = e.target.closest?.('td, th');
    if (!sel || !view.dom.contains(sel)) return null;

    const kotak = sel.getBoundingClientRect();
    const tampilan = sel.closest('.tableWrapper')?.tampilan;
    if (!tampilan) return null;

    const kanan = kotak.right - e.clientX;
    const kiri = e.clientX - kotak.left;
    const bawah = kotak.bottom - e.clientY;
    const atas = e.clientY - kotak.top;
    if (kanan >= DEKAT_KOLOM && kiri >= DEKAT_KOLOM && bawah >= DEKAT_BARIS && atas >= DEKAT_BARIS) return null;

    const $sel = cellAround(view.state.doc.resolve(view.posAtDOM(sel, 0)));
    if (!$sel) return null;
    const r = TableMap.get($sel.node(-1)).findCell($sel.pos - $sel.start(-1));

    if (kanan < DEKAT_KOLOM) return { arah: 'kolom', batas: r.right, tampilan };
    if (kiri < DEKAT_KOLOM && r.left > 0) return { arah: 'kolom', batas: r.left, tampilan };
    if (bawah < DEKAT_BARIS) return { arah: 'baris', batas: r.bottom, tampilan };
    if (atas < DEKAT_BARIS && r.top > 0) return { arah: 'baris', batas: r.top, tampilan };

    return null;
}

/** Penangan kursor di garis tabel, untuk handleDOMEvents. */
export const seretGaris = {
    mousemove(view, e) {
        const dekat = view.editable && !e.buttons ? garisDekat(view, e) : null;
        view.dom.classList.toggle('geser-kolom', dekat?.arah === 'kolom');
        view.dom.classList.toggle('geser-baris', dekat?.arah === 'baris');

        return false;
    },
    mousedown(view, e) {
        if (e.button !== 0 || !view.editable) return false;
        const dekat = garisDekat(view, e);
        if (!dekat) return false;
        dekat.arah === 'kolom' ? dekat.tampilan.seretKolom(e, dekat.batas) : dekat.tampilan.seretBaris(e, dekat.batas);

        return true;
    },
};

export { skala };
