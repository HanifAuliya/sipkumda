/**
 * Mengubah tabel dari Word atau Excel menjadi baris "| a | b |", bentuk yang dikenali TataLetakNaskah.
 * Menghasilkan null bila yang ditempel bukan tabel, supaya browser menempelkannya seperti biasa.
 */
const bersih = (teks) => teks.replace(/\s+/g, ' ').replace(/\|/g, '/').trim();

const keBaris = (sel) => `| ${sel.join(' | ')} |`;

/** Sel gabungan (colspan/rowspan) diisi kosong supaya kolomnya tetap lurus. */
function isiTabel(tabel) {
    const grid = [];
    const jumlahBaris = tabel.rows.length;

    [...tabel.rows].forEach((tr, r) => {
        grid[r] ??= [];
        let c = 0;

        for (const td of tr.cells) {
            while (grid[r][c] !== undefined) c++;

            for (let i = 0; i < td.rowSpan && r + i < jumlahBaris; i++) {
                grid[r + i] ??= [];
                for (let j = 0; j < td.colSpan; j++) {
                    grid[r + i][c + j] = i === 0 && j === 0 ? bersih(td.textContent) : '';
                }
            }

            c += td.colSpan;
        }
    });

    return grid
        .map((baris) => Array.from(baris, (isi) => isi ?? ''))
        .filter((baris) => baris.some((isi) => isi !== ''));
}

/** Tabel ditulis sebagai baris tabel, teks di sekitarnya tetap teks biasa. */
function kumpulkan(induk, keluaran) {
    let adaTabel = false;

    for (const simpul of induk.childNodes) {
        if (simpul.nodeType === Node.TEXT_NODE) {
            const teks = bersih(simpul.textContent);
            if (teks) keluaran.push(teks);
        } else if (simpul.nodeType !== Node.ELEMENT_NODE || ['STYLE', 'SCRIPT', 'META', 'LINK', 'TITLE'].includes(simpul.tagName)) {
            continue;
        } else if (simpul.tagName === 'TABLE') {
            const isi = isiTabel(simpul);

            // Tabel satu kolom hanyalah daftar teks.
            if (Math.max(0, ...isi.map((baris) => baris.length)) >= 2) {
                adaTabel = true;
                keluaran.push(...isi.map(keBaris));
            } else {
                keluaran.push(...isi.map((baris) => baris[0]));
            }
        } else if (simpul.querySelector('table')) {
            adaTabel = kumpulkan(simpul, keluaran) || adaTabel;
        } else {
            const teks = bersih(simpul.textContent);
            if (teks) keluaran.push(teks);
        }
    }

    return adaTabel;
}

/** Salinan tanpa HTML: tiap baris harus punya jumlah tab yang sama, supaya butir "a.<tab>isi" tidak dikira tabel. */
function dariTeks(teks) {
    const baris = teks.split(/\r?\n/).filter((isi) => isi.trim() !== '');
    const kolom = baris.map((isi) => isi.split('\t').length);

    if (baris.length < 2 || kolom[0] < 2 || kolom.some((jumlah) => jumlah !== kolom[0])) {
        return null;
    }

    return baris.map((isi) => keBaris(isi.split('\t').map(bersih))).join('\n');
}

export default function tabelDariClipboard(clipboard) {
    if (!clipboard) return null;

    const html = clipboard.getData('text/html');

    if (html) {
        if (!/<table/i.test(html)) return null;

        const keluaran = [];
        const dokumen = new DOMParser().parseFromString(html, 'text/html');

        return kumpulkan(dokumen.body, keluaran) ? keluaran.join('\n') : null;
    }

    return dariTeks(clipboard.getData('text/plain'));
}
