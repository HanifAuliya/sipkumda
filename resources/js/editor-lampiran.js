/**
 * Editor lampiran seperti Word (CKEditor 5, lisensi GPL untuk dipakai sendiri). Dimuat hanya saat ada baris
 * lampiran yang dibuka, karena ukurannya besar. Isinya HTML; server membersihkannya lagi (App\Support\HtmlLampiran),
 * jadi pilihan di sini hanya fitur yang memang ikut tersimpan: rata, tebal/miring/garis bawah, ukuran huruf,
 * daftar bernomor, jorok, dan tabel (gabung sel, lebar kolom, garis). Tempel dari Word mempertahankan formatnya.
 */
import {
    Alignment, Autoformat, Bold, ClassicEditor, Essentials, FontSize, Heading, HorizontalLine, Indent, IndentBlock,
    Italic, List, ListProperties, Paragraph, PasteFromOffice, RemoveFormat, Strikethrough, Subscript, Superscript,
    Table, TableCellProperties, TableColumnResize, TableProperties, TableToolbar, Underline,
} from 'ckeditor5';
import translations from 'ckeditor5/translations/id.js';
import 'ckeditor5/ckeditor5.css';

/** Warna disimpan sebagai heksadesimal, satu-satunya bentuk yang diterima pembersih di server. */
const WARNA = { colorPicker: { format: 'hex' } };

export async function buatEditorLampiran(elemen, isi) {
    return ClassicEditor.create(elemen, {
        licenseKey: 'GPL',
        language: 'id',
        translations: [translations],
        initialData: isi ?? '',
        plugins: [
            Essentials, Paragraph, Heading, Autoformat, Bold, Italic, Underline, Strikethrough, Subscript, Superscript,
            Alignment, FontSize, RemoveFormat, List, ListProperties, Indent, IndentBlock, HorizontalLine,
            Table, TableToolbar, TableProperties, TableCellProperties, TableColumnResize, PasteFromOffice,
        ],
        toolbar: {
            items: [
                'undo', 'redo', '|', 'heading', 'fontSize', '|', 'bold', 'italic', 'underline', 'strikethrough',
                'subscript', 'superscript', 'removeFormat', '|', 'alignment', '|', 'numberedList', 'bulletedList',
                'outdent', 'indent', '|', 'insertTable', 'horizontalLine',
            ],
            shouldNotGroupWhenFull: false,
        },
        heading: {
            options: [
                { model: 'paragraph', title: 'Paragraf', class: 'ck-heading_paragraph' },
                { model: 'heading2', view: 'h2', title: 'Judul', class: 'ck-heading_heading2' },
                { model: 'heading3', view: 'h3', title: 'Subjudul', class: 'ck-heading_heading3' },
            ],
        },
        fontSize: { options: ['10px', '11px', 'default', '14px', '16px', '18px'] },
        list: { properties: { styles: true, startIndex: true, reversed: false } },
        table: {
            contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells', 'tableProperties', 'tableCellProperties'],
            tableProperties: WARNA,
            tableCellProperties: WARNA,
        },
    });
}
