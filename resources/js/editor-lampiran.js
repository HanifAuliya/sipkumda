/**
 * Editor lampiran seperti Word (Tiptap, lisensi MIT). Dimuat hanya saat ada lampiran yang disunting.
 * Isinya HTML; server membersihkannya lagi (App\Support\HtmlLampiran), jadi fitur di sini hanya yang ikut
 * tersimpan: rata, tebal/miring/garis bawah/coret, ukuran huruf, jorok, daftar, dan tabel (./tabel: gabung dan bagi
 * sel, lebar kolom, tinggi baris, garis per sisi, arsiran, letak).
 */
import { Editor, Extension, createDocument } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { OrderedList } from '@tiptap/extension-list';
import { Table, TableCell, TableHeader, TableRow } from '@tiptap/extension-table';
import { Fragment, Slice } from '@tiptap/pm/model';
import { Plugin, Selection, TextSelection } from '@tiptap/pm/state';
import { CellSelection, TableMap, cellAround } from '@tiptap/pm/tables';
import TextAlign from '@tiptap/extension-text-align';
import { FontSize, TextStyle } from '@tiptap/extension-text-style';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
import { bacaKisi, rapikanGaris, SISI, tulisKisi } from './tabel/kisi';
import * as tabel from './tabel/perintah';
import { seretGaris, TampilanTabel } from './tabel/tampilan';
import { bersihkanTempelan, kePt } from './tempel-word';

/** Satu langkah jorok = 1,25 cm, sama dengan bawaan Word di Indonesia. */
const LANGKAH = 36;
const BATAS_JOROK = 360;

const JENIS_NOMOR = { a: 'lower-alpha', A: 'upper-alpha', i: 'lower-roman', I: 'upper-roman' };

/** Jenis nomor ditulis sebagai gaya, karena CSS aplikasi dan PhpWord tidak membaca atribut type. */
const DaftarBernomor = OrderedList.extend({
    addAttributes() {
        const bawaan = this.parent?.();
        return {
            ...bawaan,
            type: {
                ...bawaan.type,
                renderHTML: ({ type }) => (JENIS_NOMOR[type] ? { style: `list-style-type: ${JENIS_NOMOR[type]}` } : {}),
            },
        };
    },
});

/** Jorok kiri dan baris pertama paragraf (margin-left dan text-indent), termasuk yang terbawa dari Word. */
const Jorok = Extension.create({
    name: 'jorok',
    priority: 50, // Tab di daftar dan tabel ditangani ekstensinya sendiri lebih dulu.

    addGlobalAttributes() {
        return [{
            types: ['paragraph', 'heading'],
            attributes: {
                kiri: {
                    default: 0,
                    parseHTML: (el) => Math.max(0, kePt(el.style.marginLeft)),
                    renderHTML: ({ kiri }) => (kiri ? { style: `margin-left: ${kiri}pt` } : {}),
                },
                awal: {
                    default: 0,
                    parseHTML: (el) => kePt(el.style.textIndent),
                    renderHTML: ({ awal }) => (awal ? { style: `text-indent: ${awal}pt` } : {}),
                },
            },
        }];
    },

    addCommands() {
        const geser = (arah) => ({ state, tr, dispatch }) => {
            let berubah = false;
            state.doc.nodesBetween(state.selection.from, state.selection.to, (node, pos) => {
                if (!['paragraph', 'heading'].includes(node.type.name)) return;
                const kiri = Math.min(BATAS_JOROK, Math.max(0, node.attrs.kiri + arah * LANGKAH));
                if (kiri !== node.attrs.kiri) {
                    tr.setNodeAttribute(pos, 'kiri', kiri);
                    berubah = true;
                }
            });
            if (berubah) dispatch?.(tr);
            return berubah;
        };
        return { tambahJorok: () => geser(1), kurangiJorok: () => geser(-1) };
    },

    addKeyboardShortcuts() {
        return {
            // Tab selalu ditelan supaya kursor tidak lompat keluar editor.
            Tab: () => this.editor.commands.tambahJorok() || true,
            'Shift-Tab': () => this.editor.commands.kurangiJorok() || true,
        };
    },
});

const CSS_SISI = { atas: 'Top', kanan: 'Right', bawah: 'Bottom', kiri: 'Left' };

/** Garis per sisi dari gaya sel: null garis biasa, 'none', atau jenis dan tebalnya ("solid 1.5"). */
function bacaGaris(el) {
    const garis = {};

    for (const sisi of SISI) {
        const jenis = el.style[`border${CSS_SISI[sisi]}Style`];
        if (!jenis || jenis === 'initial') continue;
        if (['none', 'hidden'].includes(jenis)) {
            garis[sisi] = 'none';
            continue;
        }
        const ukuran = el.style[`border${CSS_SISI[sisi]}Width`];
        const tebal = parseFloat(ukuran) * (/px$/.test(ukuran) ? 0.75 : 1) || 0.5;
        const dikenal = ['double', 'dashed', 'dotted'].includes(jenis) ? jenis : 'solid';
        // Garis tunggal sampai 1 pt adalah garis biasa.
        if (dikenal !== 'solid' || tebal > 1) garis[sisi] = `${dikenal} ${Math.round(tebal * 100) / 100}`;
    }

    return Object.keys(garis).length ? garis : null;
}

function gayaGaris(garis) {
    if (SISI.every((sisi) => garis[sisi] === 'none')) return 'border-style: none';

    return SISI.filter((sisi) => garis[sisi]).map((sisi) => {
        const [jenis, tebal] = garis[sisi].split(' ');

        return `border-${CSS_SISI[sisi].toLowerCase()}: ${jenis === 'none' ? 'none' : `${tebal}pt ${jenis}`}`;
    }).join('; ');
}

/** Warna latar sebagai heksa; putih dan bening berarti tanpa arsiran. */
function keHeksa(warna) {
    const m = /^rgba?\((\d+),\s*(\d+),\s*(\d+)(?:,\s*([\d.]+))?\)$/.exec(warna ?? '');
    if (!m || m[4] === '0') return null;
    const heksa = `#${[m[1], m[2], m[3]].map((n) => Number(n).toString(16).padStart(2, '0')).join('')}`;

    return heksa === '#ffffff' ? null : heksa;
}

const atributSel = (bawaan) => ({
    ...bawaan,
    garis: {
        default: null,
        parseHTML: bacaGaris,
        // data-tg: sisi tanpa garis, supaya di editor tetap tampak garis bantunya; tidak ikut disimpan server.
        renderHTML: ({ garis }) => {
            const tanpa = garis ? SISI.filter((sisi) => garis[sisi] === 'none').join(' ') : '';

            return garis ? { style: gayaGaris(garis), ...(tanpa && { 'data-tg': tanpa }) } : {};
        },
    },
    latar: {
        default: null,
        parseHTML: (el) => keHeksa(el.style.backgroundColor),
        renderHTML: ({ latar }) => (latar ? { style: `background-color: ${latar}` } : {}),
    },
    tegak: {
        default: null,
        parseHTML: (el) => [el.style.verticalAlign, el.getAttribute('valign')].find((t) => ['middle', 'bottom'].includes(t)) ?? null,
        renderHTML: ({ tegak }) => (tegak ? { style: `vertical-align: ${tegak}` } : {}),
    },
});

const Sel = TableCell.extend({ addAttributes() { return atributSel(this.parent?.()); } });
const SelJudul = TableHeader.extend({ addAttributes() { return atributSel(this.parent?.()); } });

const Baris = TableRow.extend({
    addAttributes() {
        return {
            ...this.parent?.(),
            tinggi: {
                default: null,
                parseHTML: (el) => {
                    const pt = kePt(el.style.height);

                    return pt > 0 ? Math.min(pt, 800) : null;
                },
                renderHTML: ({ tinggi }) => (tinggi ? { style: `height: ${tinggi}pt` } : {}),
            },
        };
    },
});

/** Klik di ruang kosong sel (di bawah tulisannya) menaruh kursor di sel itu, bukan di sel berikutnya. */
function kursorDiSel(view, $awal, $akhir) {
    const diSel = ($p) => ['tableCell', 'tableHeader'].includes($p.parent.type.name);
    if (!diSel($awal) && !diSel($akhir)) return null;

    const masuk = ($p) => {
        if (!diSel($p)) return $p;
        const mundur = Selection.findFrom($p, -1, true);

        return (mundur && mundur.from >= $p.start() ? mundur : Selection.near($p, 1)).$head;
    };

    return TextSelection.between(masuk($awal), masuk($akhir));
}

const Tabel = Table.extend({
    addOptions() {
        return { ...this.parent?.(), lebarKertas: 627 };
    },

    addAttributes() {
        return {
            ...this.parent?.(),
            letak: {
                default: null,
                parseHTML: (el) => {
                    if (el.style.marginLeft === 'auto') return el.style.marginRight === 'auto' ? 'center' : 'right';

                    return ['center', 'right'].includes(el.getAttribute('align')) ? el.getAttribute('align') : null;
                },
                renderHTML: ({ letak }) => (letak ? { style: `margin-left: auto; margin-right: ${letak === 'center' ? 'auto' : '0px'}` } : {}),
            },
        };
    },

    addNodeView() {
        return ({ node, view, HTMLAttributes }) => new TampilanTabel(node, tabel.KOLOM_MIN, view, HTMLAttributes, this.options.lebarKertas);
    },

    addProseMirrorPlugins() {
        return [new Plugin({ props: { handleDOMEvents: seretGaris, createSelectionBetween: kursorDiSel } }), ...this.parent()];
    },

    addKeyboardShortcuts() {
        const bawaan = this.parent();
        /** Seperti di Word: Backspace pada baris atau kolom yang dipilih utuh menghapusnya; Delete hanya isinya. */
        const hapus = () => {
            const { selection } = this.editor.state;
            if (selection instanceof CellSelection && selection.isRowSelection()) return tabel.hapusBaris(this.editor.view);
            if (selection instanceof CellSelection && selection.isColSelection()) return tabel.hapusKolom(this.editor.view);

            return bawaan.Backspace({ editor: this.editor });
        };
        const pindah = (arah) => () => this.editor.isActive('table') && tabel.pindahBaris(this.editor.view, arah);

        return { ...bawaan, Backspace: hapus, 'Alt-Shift-ArrowUp': pindah(-1), 'Alt-Shift-ArrowDown': pindah(1) };
    },
});

/**
 * Tabel tidak pernah melebihi lebar isi kertas: bila kolomnya digeser atau ditempel terlalu lebar, semua kolom yang
 * lebarnya sudah diatur diciutkan sebanding. Sama dengan yang dilakukan cetakan (HtmlLampiran::untukTampil).
 */
const MuatDiKertas = Extension.create({
    name: 'muatDiKertas',
    addOptions: () => ({ lebar: 627, kolomMin: 25, kolomBebas: 60 }),
    addProseMirrorPlugins() {
        const { lebar, kolomMin, kolomBebas } = this.options;

        return [new Plugin({
            appendTransaction: (transaksi, _, state) => {
                if (!transaksi.some((t) => t.docChanged)) return null;
                const tr = state.tr;

                state.doc.descendants((tabel, posTabel) => {
                    if (tabel.type.name !== 'table') return true;

                    const peta = TableMap.get(tabel);
                    const kolom = Array(peta.width).fill(0);
                    const sel = new Map();
                    peta.map.forEach((pos, i) => {
                        const k = i % peta.width;
                        if (!sel.has(pos)) sel.set(pos, k);
                        const px = tabel.nodeAt(pos).attrs.colwidth?.[k - sel.get(pos)];
                        if (px && !kolom[k]) kolom[k] = px;
                    });

                    const total = kolom.reduce((a, b) => a + b, 0);
                    const batas = lebar - kolom.filter((px) => !px).length * kolomBebas;
                    if (total <= batas + 1) return false;

                    const baru = kolom.map((px) => px && Math.max(kolomMin, Math.floor(px * batas / total)));
                    sel.forEach((k, pos) => {
                        const { attrs } = tabel.nodeAt(pos);
                        if (!attrs.colwidth) return;
                        tr.setNodeAttribute(posTabel + 1 + pos, 'colwidth', attrs.colwidth.map((px, i) => (px ? baru[k + i] : 0)));
                    });

                    return false;
                });

                return tr.docChanged ? tr : null;
            },
        })];
    },
});

/** Garis bersama disamakan di semua tabel (lihat rapikanGaris). Dipakai untuk isi dari luar: tempelan dan file. */
function rapikanTabel(simpul) {
    if (simpul.type.name === 'table') {
        try {
            const kisi = bacaKisi(simpul);
            if (rapikanGaris(kisi)) simpul = tulisKisi(kisi);
        } catch (e) {
            console.warn('Tabel tidak dirapikan.', e);
        }
    }

    return simpul.isTextblock || simpul.isLeaf ? simpul : simpul.copy(rapikanIsi(simpul.content));
}

function rapikanIsi(isi) {
    const hasil = [];
    isi.forEach((simpul) => hasil.push(rapikanTabel(simpul)));

    return Fragment.from(hasil);
}

/** Mengganti seluruh isi editor dengan HTML dari file; bisa diurungkan dengan Ctrl+Z. */
export function gantiIsi(editor, html) {
    const dok = createDocument(html, editor.schema, { preserveWhitespace: true });
    editor.chain().setContent(rapikanTabel(dok).toJSON(), { emitUpdate: true }).focus('start').run();
}

/** Klik kanan di sel: pilihan sel yang sudah ada dipertahankan, selain itu kursor pindah ke sel itu. */
export function kursorKe(editor, e) {
    const { state, view } = editor;
    const pos = view.posAtCoords({ left: e.clientX, top: e.clientY });
    if (!pos) return;

    const { selection } = state;
    let sudah = !selection.empty && pos.pos >= selection.from && pos.pos <= selection.to;
    if (selection instanceof CellSelection) {
        const $sel = cellAround(state.doc.resolve(pos.pos));
        sudah = false;
        selection.forEachCell((simpul, p) => { sudah ||= p === $sel?.pos; });
    }
    if (!sudah) view.dispatch(state.tr.setSelection(TextSelection.near(state.doc.resolve(pos.pos))));
}

export { tabel };

/** HTML editor. Bukan editor.isEmpty: tabel formulir yang selnya masih kosong semua tetap isi. */
export function isiEditor(editor) {
    const html = editor.getHTML();
    return html === '<p></p>' ? '' : html;
}

/** Keadaan tombol untuk bilah alat. */
export function keadaan(editor) {
    const rata = ['center', 'right', 'justify'].find((r) => editor.isActive({ textAlign: r })) ?? 'left';

    return {
        tebal: editor.isActive('bold'),
        miring: editor.isActive('italic'),
        garisBawah: editor.isActive('underline'),
        coret: editor.isActive('strike'),
        atas: editor.isActive('superscript'),
        bawah: editor.isActive('subscript'),
        rata,
        nomor: editor.isActive('orderedList'),
        jenisNomor: editor.getAttributes('orderedList').type ?? '1',
        poin: editor.isActive('bulletList'),
        judul: [2, 3].find((level) => editor.isActive('heading', { level })) ?? 0,
        ukuran: editor.getAttributes('textStyle').fontSize ?? '',
        tabel: tabel.keadaanTabel(editor.view),
        bisaUrung: editor.can().undo(),
        bisaUlang: editor.can().redo(),
    };
}

export function buatEditorLampiran(elemen, isi, { saatUbah, saatStatus, label, lebar }) {
    return new Editor({
        element: elemen,
        content: isi ?? '',
        // Spasi ganda yang diketik untuk merapikan isian tidak hilang saat dibuka lagi.
        parseOptions: { preserveWhitespace: true },
        extensions: [
            StarterKit.configure({ blockquote: false, code: false, codeBlock: false, link: false, orderedList: false, heading: { levels: [2, 3] } }),
            DaftarBernomor,
            TextAlign.configure({ types: ['heading', 'paragraph'] }),
            TextStyle, FontSize, Subscript, Superscript, Jorok,
            Tabel.configure({ resizable: false, cellMinWidth: tabel.KOLOM_MIN, ...(lebar && { lebarKertas: lebar }) }),
            Baris, Sel, SelJudul,
            MuatDiKertas.configure(lebar ? { lebar } : {}),
        ],
        editorProps: {
            attributes: { class: 'lampiran-html', role: 'textbox', 'aria-multiline': 'true', 'aria-label': label ?? 'Lampiran' },
            transformPastedHTML: bersihkanTempelan,
            // Di baris kosong, paragraf pertama yang ditempel membawa ratanya sendiri, bukan melebur ke baris itu.
            transformPasted: (potongan, view) => {
                const induk = view.state.selection.$from.parent;
                const rapi = new Slice(rapikanIsi(potongan.content), potongan.openStart, potongan.openEnd);

                return induk.isTextblock && induk.content.size === 0 && rapi.openStart > 0 ? new Slice(rapi.content, 0, 0) : rapi;
            },
        },
        onUpdate: ({ editor }) => saatUbah(isiEditor(editor)),
        onTransaction: ({ editor }) => saatStatus(keadaan(editor)),
    });
}
