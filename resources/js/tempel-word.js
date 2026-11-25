/**
 * Merapikan HTML yang ditempel dari Word atau Excel sebelum masuk editor lampiran:
 * - daftar bernomor Word (paragraf ber-mso-list) menjadi daftar sungguhan; nomor berbentuk lain, misalnya "(1)"
 *   atau "a)", dibiarkan sebagai teks supaya bentuknya tidak berubah;
 * - lebar kolom, rata, dan tabel tanpa garis dipertahankan;
 * - ukuran 12pt dilepas, karena itu ukuran bawaan naskah.
 */

const KE_PT = { pt: 1, px: 0.75, cm: 28.35, mm: 2.835, in: 72, em: 12 };

/** "36.0pt", "1.27cm" menjadi angka pt; 0 bila tidak dikenali. */
export function kePt(nilai) {
    const m = /^(-?\d*\.?\d+)(pt|px|cm|mm|in|em)$/.exec((nilai ?? '').trim());
    return m ? Math.round(parseFloat(m[1]) * KE_PT[m[2]]) : 0;
}

const gaya = (el) => el.getAttribute('style') ?? '';

function romawi(teks) {
    const nilai = { i: 1, v: 5, x: 10, l: 50, c: 100 };
    const angka = [...teks.toLowerCase()].map((h) => nilai[h]);
    return angka.reduce((n, a, i) => n + (a < (angka[i + 1] ?? 0) ? -a : a), 0);
}

/** Penanda Word menjadi jenis daftar, atau null bila bukan "1." / "a." / "i." / poin. */
function jenisPenanda(penanda, sebelumnya) {
    if (/^[·•o§▪◦●\-–—Øüv]$/u.test(penanda)) return { tag: 'ul' };

    const m = /^([0-9]+|[a-z]+|[A-Z]+)\.$/.exec(penanda);
    if (!m) return null;
    if (/^\d+$/.test(m[1])) return { tag: 'ol', type: null, nilai: Number(m[1]) };

    const besar = m[1] === m[1].toUpperCase();
    const kecil = m[1].toLowerCase();
    // "i." membuka daftar romawi; "i." sesudah "h." adalah huruf kesembilan.
    const huruf = kecil.length === 1 ? kecil.charCodeAt(0) - 96 : 0;
    const lanjutanHuruf = sebelumnya && /^a$/i.test(sebelumnya.type ?? '') && sebelumnya.nilai === huruf - 1;

    if (/^[ivxlc]+$/.test(kecil) && !lanjutanHuruf && (kecil === 'i' || kecil.length > 1 || /^i$/i.test(sebelumnya?.type ?? ''))) {
        return { tag: 'ol', type: besar ? 'I' : 'i', nilai: romawi(kecil) };
    }

    return huruf ? { tag: 'ol', type: besar ? 'A' : 'a', nilai: huruf } : null;
}

function butirWord(p) {
    const tingkat = /mso-list\s*:[^;"']*\blevel(\d+)/i.exec(gaya(p));
    if (!tingkat) return null;

    const span = [...p.querySelectorAll('span')].find((s) => /mso-list\s*:\s*Ignore/i.test(gaya(s)));
    if (!span) return null;

    return { tingkat: Number(tingkat[1]), span, penanda: span.textContent.replace(/[\s ]+/g, '') };
}

function susunDaftar(dok) {
    // Tiap rangkaian paragraf daftar yang berurutan menjadi satu daftar bertingkat.
    for (const awal of [...dok.querySelectorAll('p, h1, h2, h3, h4, h5, h6')]) {
        if (!awal.isConnected || !butirWord(awal)) continue;

        const tumpukan = []; // { tingkat, el, jenis, li }
        let p = awal;

        while (p) {
            const berikut = p.nextElementSibling;
            const b = butirWord(p);
            if (!b) break;

            while (tumpukan.length && tumpukan.at(-1).tingkat > b.tingkat) tumpukan.pop();
            const atas = tumpukan.at(-1)?.tingkat === b.tingkat ? tumpukan.at(-1) : null;
            const jenis = jenisPenanda(b.penanda, atas?.jenis);

            if (!jenis) {
                // Penanda "(1)" atau "a)": tetap teks, berjorok seperti di Word.
                b.span.replaceWith(dok.createTextNode(`${b.penanda} `));
                p.style.removeProperty('mso-list');
                p.setAttribute('style', gaya(p).replace(/mso-list\s*:[^;]*;?/gi, ''));
                break;
            }

            b.span.remove();
            const li = dok.createElement('li');
            const isi = dok.createElement('p');
            if (p.style.textAlign) isi.style.textAlign = p.style.textAlign;
            isi.append(...p.childNodes);
            li.append(isi);

            if (atas && atas.jenis.tag === jenis.tag && (atas.jenis.type ?? null) === (jenis.type ?? null)) {
                atas.el.append(li);
                Object.assign(atas, { jenis, li });
                p.remove();
            } else {
                if (atas) tumpukan.pop();
                const el = dok.createElement(jenis.tag);
                if (jenis.type) el.setAttribute('type', jenis.type);
                if (jenis.nilai > 1) el.setAttribute('start', jenis.nilai);
                el.append(li);

                const induk = tumpukan.at(-1);
                if (induk) {
                    induk.li.append(el);
                    p.remove();
                } else {
                    p.replaceWith(el);
                }
                tumpukan.push({ tingkat: b.tingkat, el, jenis, li });
            }

            p = berikut;
        }
    }
}

export function bersihkanTempelan(html) {
    if (!/mso-|urn:schemas-microsoft-com/i.test(html)) return html;

    const excel = /office:excel/i.test(html);
    const dok = new DOMParser().parseFromString(html, 'text/html');

    dok.querySelectorAll('style, xml, meta, link, title, script').forEach((el) => el.remove());
    dok.querySelectorAll('o\\:p').forEach((el) => el.replaceWith(...el.childNodes));

    const komentar = dok.createTreeWalker(dok.body, NodeFilter.SHOW_COMMENT);
    const buang = [];
    while (komentar.nextNode()) buang.push(komentar.currentNode);
    buang.forEach((k) => k.remove());

    dok.querySelectorAll('[align]').forEach((el) => {
        if (!el.style.textAlign && /^(left|right|center|justify)$/i.test(el.getAttribute('align')) && el.tagName !== 'TABLE') {
            el.style.textAlign = el.getAttribute('align').toLowerCase();
        }
    });

    susunDaftar(dok);

    dok.querySelectorAll('td, th').forEach((sel) => {
        const asli = gaya(sel);
        const lebar = kePt(sel.style.width) / 0.75 || parseInt(sel.getAttribute('width'), 10) || 0;
        const rentang = Number(sel.getAttribute('colspan') ?? 1);

        if (lebar > 0 && !/%$/.test(sel.getAttribute('width') ?? '')) {
            sel.setAttribute('colwidth', Array(rentang).fill(Math.max(25, Math.round(lebar / rentang))).join(','));
        }
        // Word menulis garis di tiap sel; sel tanpa gaya garis berarti memang tidak bergaris. Excel memakai kelas.
        if (!excel && !/solid|double|dashed|dotted/i.test(asli)) sel.setAttribute('style', `${asli};border-style:none`);
    });

    // Dari span hanya ukuran huruf yang dipakai; 12pt dan span tanpa gaya dilepas.
    dok.querySelectorAll('span').forEach((el) => {
        const ukuran = el.style.fontSize;
        if (ukuran && kePt(ukuran) !== 12) el.setAttribute('style', `font-size: ${ukuran}`);
        else el.replaceWith(...el.childNodes);
    });
    dok.body.normalize();

    return dok.body.innerHTML;
}
