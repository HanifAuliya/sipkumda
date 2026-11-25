/**
 * Editor lampiran seperti Word (Tiptap, lisensi MIT). Dimuat hanya saat ada lampiran yang disunting.
 * Isinya HTML; server membersihkannya lagi (App\Support\HtmlLampiran), jadi fitur di sini hanya yang ikut
 * tersimpan: rata, tebal/miring/garis bawah/coret, ukuran huruf, jorok, daftar, dan tabel (gabung sel,
 * lebar kolom, garis).
 */
import { Editor, Extension } from '@tiptap/core';
import StarterKit from '@tiptap/starter-kit';
import { OrderedList } from '@tiptap/extension-list';
import { Table, TableCell, TableHeader, TableRow } from '@tiptap/extension-table';
import { Slice } from '@tiptap/pm/model';
import { CellSelection } from '@tiptap/pm/tables';
import TextAlign from '@tiptap/extension-text-align';
import { FontSize, TextStyle } from '@tiptap/extension-text-style';
import Subscript from '@tiptap/extension-subscript';
import Superscript from '@tiptap/extension-superscript';
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

const atributSel = (bawaan) => ({
    ...bawaan,
    /** Sel tanpa garis: untuk merapikan formulir (Nama : ....) tanpa kotak. */
    tanpaGaris: {
        default: false,
        parseHTML: (el) => /border(-style)?\s*:\s*none/i.test(el.getAttribute('style') ?? ''),
        // Bukan "border: none": browser menulisnya ulang menjadi beberapa properti.
        renderHTML: ({ tanpaGaris }) => (tanpaGaris ? { style: 'border-style: none' } : {}),
    },
    tegak: {
        default: null,
        parseHTML: (el) => (['middle', 'bottom'].includes(el.style.verticalAlign) ? el.style.verticalAlign : null),
        renderHTML: ({ tegak }) => (tegak ? { style: `vertical-align: ${tegak}` } : {}),
    },
});

const Sel = TableCell.extend({ addAttributes() { return atributSel(this.parent?.()); } });
const SelJudul = TableHeader.extend({ addAttributes() { return atributSel(this.parent?.()); } });

/** Menghidup-matikan garis: sel yang dipilih, atau seluruh tabel bila hanya ada kursor. */
export function ubahGaris(editor) {
    const { state, view } = editor;
    const { selection, tr } = state;
    const sel = [];

    if (selection instanceof CellSelection) {
        selection.forEachCell((node, pos) => sel.push([node, pos]));
    } else {
        for (let d = selection.$from.depth; d > 0; d--) {
            if (selection.$from.node(d).type.name !== 'table') continue;
            const awal = selection.$from.start(d);
            selection.$from.node(d).descendants((node, pos) => {
                if (['tableCell', 'tableHeader'].includes(node.type.name)) sel.push([node, awal + pos]);
            });
            break;
        }
    }

    const sembunyikan = sel.some(([node]) => !node.attrs.tanpaGaris);
    sel.forEach(([, pos]) => tr.setNodeAttribute(pos, 'tanpaGaris', sembunyikan));
    if (sel.length) view.dispatch(tr);
    view.focus();
}

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
        tabel: editor.isActive('table'),
        bisaGabung: editor.can().mergeCells(),
        bisaPisah: editor.can().splitCell(),
        bisaUrung: editor.can().undo(),
        bisaUlang: editor.can().redo(),
    };
}

export function buatEditorLampiran(elemen, isi, { saatUbah, saatStatus, label }) {
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
            Table.configure({ resizable: true, lastColumnResizable: true, cellMinWidth: 25 }),
            TableRow, Sel, SelJudul,
        ],
        editorProps: {
            attributes: { class: 'lampiran-html', role: 'textbox', 'aria-multiline': 'true', 'aria-label': label ?? 'Lampiran' },
            transformPastedHTML: bersihkanTempelan,
            // Di baris kosong, paragraf pertama yang ditempel membawa ratanya sendiri, bukan melebur ke baris itu.
            transformPasted: (potongan, view) => {
                const induk = view.state.selection.$from.parent;
                return induk.isTextblock && induk.content.size === 0 && potongan.openStart > 0 ? new Slice(potongan.content, 0, 0) : potongan;
            },
        },
        onUpdate: ({ editor }) => saatUbah(isiEditor(editor)),
        onTransaction: ({ editor }) => saatStatus(keadaan(editor)),
    });
}
