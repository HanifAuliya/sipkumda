/**
 * Isi PDF (hasil Word, bukan hasil pindai) menjadi HTML editor lampiran, dibaca di browser dengan PDF.js (Apache-2.0).
 * PDF hanya menyimpan letak tulisan dan garis, jadi susunannya dikira-kira: tabel dari garis-garisnya (termasuk sel
 * gabungan), paragraf dari jarak dan panjang barisnya. Hasilnya perlu dicocokkan lagi oleh pengguna.
 */

/** Jarak (pt) yang masih dianggap segaris. */
const DEKAT = 2.5;
const PT_KE_PX = 96 / 72;

export async function pdfKeHtml(isi) {
    // Tanpa Worker terpisah: PDF.js memakai modul ini di halaman yang sama, jadi tidak bergantung pada alamat server.
    globalThis.pdfjsWorker ??= await import('pdfjs-dist/legacy/build/pdf.worker.min.mjs');
    const pdfjs = await import('pdfjs-dist/legacy/build/pdf.min.mjs');

    const dok = await pdfjs.getDocument({ data: new Uint8Array(isi), isEvalSupported: false }).promise;
    const halaman = [];
    let gambar = false;

    try {
        for (let n = 1; n <= Math.min(dok.numPages, 200); n++) {
            const hal = await dok.getPage(n);
            const hasil = await bacaHalaman(hal, pdfjs);
            gambar ||= hasil.gambar;
            halaman.push(hasil);
            hal.cleanup();
        }
    } finally {
        await dok.destroy();
    }

    const huruf = halaman.reduce((n, h) => n + h.kata.reduce((j, k) => j + k.teks.trim().length, 0), 0);
    // Hasil pindai paling banyak memuat beberapa huruf, misalnya nomor halaman atau cap digital.
    if (huruf < 20 * Math.max(1, halaman.length / 2)) return { html: '', pindaian: gambar };

    return { html: susun(halaman), pindaian: false };
}

/* Membaca halaman: potongan tulisan dan ruas garis, dengan y ke bawah */

async function bacaHalaman(hal, pdfjs) {
    const tampak = hal.getViewport({ scale: 1 });
    const operasi = await hal.getOperatorList();
    const isi = await hal.getTextContent({ disableCombineTextItems: false });
    const keLayar = (x, y) => tampak.convertToViewportPoint(x, y);

    const kata = isi.items.filter((i) => i.str !== undefined && i.str !== '' && i.transform).map((i) => {
        const [x, y] = keLayar(i.transform[4], i.transform[5]);
        const ukuran = Math.hypot(i.transform[2], i.transform[3]) || i.height;
        let nama = '';
        try { nama = hal.commonObjs.get(i.fontName)?.name ?? ''; } catch { /* huruf belum dimuat */ }

        return {
            teks: i.str, x, y, lebar: i.width, ukuran,
            tebal: /bold|black|heavy|semibold/i.test(nama), miring: /italic|oblique/i.test(nama),
        };
    }).filter((k) => k.teks.trim() !== '');

    const { ruas, gambar } = bacaGaris(operasi, pdfjs, keLayar);

    return { kata, ruas, gambar, lebar: tampak.width, tinggi: tampak.height };
}

/** Ruas garis mendatar dan tegak; kotak tipis (cara Word menggambar garis tabel) dihitung sebagai garis. */
function bacaGaris(operasi, { OPS, Util }, keLayar) {
    const ruas = [];
    const tumpukan = [];
    let ctm = [1, 0, 0, 1, 0, 0];
    let gambar = false;
    let jalur = []; // ruas dan kotak yang menunggu digambar

    const titik = (x, y) => keLayar(...Util.applyTransform([x, y], ctm));
    const tambah = (x1, y1, x2, y2) => {
        if (Math.abs(y1 - y2) < 1 && Math.abs(x1 - x2) > 1) ruas.push({ arah: 'd', di: (y1 + y2) / 2, dari: Math.min(x1, x2), ke: Math.max(x1, x2) });
        else if (Math.abs(x1 - x2) < 1 && Math.abs(y1 - y2) > 1) ruas.push({ arah: 't', di: (x1 + x2) / 2, dari: Math.min(y1, y2), ke: Math.max(y1, y2) });
    };
    const gambarJalur = (isian, garisnya) => {
        for (const j of jalur) {
            if (j.kotak) {
                const [x1, y1, x2, y2] = j.kotak;
                const [l, t] = [Math.abs(x2 - x1), Math.abs(y2 - y1)];
                if (t <= 2.5 && l > 1) tambah(x1, (y1 + y2) / 2, x2, (y1 + y2) / 2);
                else if (l <= 2.5 && t > 1) tambah((x1 + x2) / 2, y1, (x1 + x2) / 2, y2);
                else if (garisnya) {
                    tambah(x1, y1, x2, y1); tambah(x1, y2, x2, y2); tambah(x1, y1, x1, y2); tambah(x2, y1, x2, y2);
                }
            } else if (garisnya) {
                tambah(...j.ruas);
            }
        }
        jalur = [];
    };

    operasi.fnArray.forEach((fn, i) => {
        const arg = operasi.argsArray[i];
        switch (fn) {
            case OPS.save: tumpukan.push(ctm); break;
            case OPS.restore: ctm = tumpukan.pop() ?? ctm; break;
            case OPS.transform: ctm = Util.transform(ctm, arg); break;
            case OPS.paintFormXObjectBegin: tumpukan.push(ctm); if (arg[0]) ctm = Util.transform(ctm, arg[0]); break;
            case OPS.paintFormXObjectEnd: ctm = tumpukan.pop() ?? ctm; break;
            case OPS.paintImageXObject: case OPS.paintInlineImageXObject: case OPS.paintImageMaskXObject: case OPS.paintImageXObjectRepeat: gambar = true; break;
            case OPS.constructPath: {
                const [ops, nilai] = arg;
                let j = 0;
                let awal = null;
                let kini = null;
                for (const op of ops) {
                    if (op === OPS.rectangle) {
                        const [x, y, l, t] = [nilai[j], nilai[j + 1], nilai[j + 2], nilai[j + 3]];
                        j += 4;
                        jalur.push({ kotak: [...titik(x, y), ...titik(x + l, y + t)] });
                    } else if (op === OPS.moveTo) {
                        awal = kini = [nilai[j], nilai[j + 1]];
                        j += 2;
                    } else if (op === OPS.lineTo) {
                        const ke = [nilai[j], nilai[j + 1]];
                        j += 2;
                        if (kini) jalur.push({ ruas: [...titik(...kini), ...titik(...ke)] });
                        kini = ke;
                    } else if (op === OPS.curveTo) {
                        kini = [nilai[j + 4], nilai[j + 5]];
                        j += 6;
                    } else if (op === OPS.curveTo2 || op === OPS.curveTo3) {
                        kini = [nilai[j + 2], nilai[j + 3]];
                        j += 4;
                    } else if (op === OPS.closePath && kini && awal) {
                        jalur.push({ ruas: [...titik(...kini), ...titik(...awal)] });
                        kini = awal;
                    }
                }
                break;
            }
            case OPS.stroke: case OPS.closeStroke: gambarJalur(false, true); break;
            case OPS.fill: case OPS.eoFill: gambarJalur(true, false); break;
            case OPS.fillStroke: case OPS.eoFillStroke: case OPS.closeFillStroke: case OPS.closeEOFillStroke: gambarJalur(true, true); break;
            case OPS.endPath: jalur = []; break;
            default:
        }
    });

    return { ruas: satukanRuas(ruas), gambar };
}

/** Ruas segaris yang bersambung atau bertumpuk menjadi satu. */
function satukanRuas(ruas) {
    const hasil = [];
    for (const arah of ['d', 't']) {
        const urut = ruas.filter((r) => r.arah === arah).sort((a, b) => a.di - b.di || a.dari - b.dari);
        const grup = [];
        for (const r of urut) {
            const g = grup.find((x) => Math.abs(x.di - r.di) <= 1.2);
            if (g) g.isi.push(r);
            else grup.push({ di: r.di, isi: [r] });
        }
        for (const g of grup) {
            g.isi.sort((a, b) => a.dari - b.dari);
            let kini = null;
            for (const r of g.isi) {
                if (kini && r.dari <= kini.ke + DEKAT) kini.ke = Math.max(kini.ke, r.ke);
                else hasil.push(kini = { arah, di: g.di, dari: r.dari, ke: r.ke });
            }
        }
    }

    return hasil;
}

/* Tabel dari garis */

/** Nilai yang berdekatan disatukan. */
function rapatkan(nilai) {
    const hasil = [];
    for (const n of [...nilai].sort((a, b) => a - b)) if (!hasil.length || n - hasil[hasil.length - 1] > DEKAT) hasil.push(n);

    return hasil;
}

function cariTabel(ruas) {
    const d = ruas.filter((r) => r.arah === 'd' && r.ke - r.dari > 8);
    const t = ruas.filter((r) => r.arah === 't' && r.ke - r.dari > 5);
    const semua = [...d, ...t];
    const induk = semua.map((_, i) => i);
    const akar = (i) => (induk[i] === i ? i : (induk[i] = akar(induk[i])));

    // Garis yang bersilangan atau bersentuhan satu tabel.
    d.forEach((a, i) => t.forEach((b, j) => {
        if (b.di >= a.dari - DEKAT && b.di <= a.ke + DEKAT && a.di >= b.dari - DEKAT && a.di <= b.ke + DEKAT) induk[akar(i)] = akar(d.length + j);
    }));

    const grup = new Map();
    semua.forEach((r, i) => grup.set(akar(i), [...(grup.get(akar(i)) ?? []), r]));

    return [...grup.values()].map((isi) => {
        const gd = isi.filter((r) => r.arah === 'd');
        const gt = isi.filter((r) => r.arah === 't');
        const x = rapatkan(gt.map((r) => r.di));
        const y = rapatkan(gd.map((r) => r.di));

        return x.length >= 2 && y.length >= 2 && x[x.length - 1] - x[0] > 20 ? { x, y, d: gd, t: gt } : null;
    }).filter(Boolean).sort((a, b) => a.y[0] - b.y[0]);
}

/** Sel tabel: kotak kisi yang tidak dipisah garis digabung (colspan, rowspan). */
function selTabel({ x, y, d, t }) {
    const nb = y.length - 1;
    const nk = x.length - 1;
    const adaTegak = (k, b) => { const m = (y[b] + y[b + 1]) / 2; return t.some((r) => Math.abs(r.di - x[k]) <= DEKAT && r.dari <= m && r.ke >= m); };
    const adaDatar = (b, k) => { const m = (x[k] + x[k + 1]) / 2; return d.some((r) => Math.abs(r.di - y[b]) <= DEKAT && r.dari <= m && r.ke >= m); };
    const milik = Array.from({ length: nb }, () => Array(nk).fill(null));
    const sel = [];

    for (let b = 0; b < nb; b++) {
        for (let k = 0; k < nk; k++) {
            if (milik[b][k]) continue;
            let kanan = k + 1;
            while (kanan < nk && !milik[b][kanan] && !adaTegak(kanan, b)) kanan++;
            let bawah = b + 1;
            const rataBawah = (r) => { for (let i = k; i < kanan; i++) if (adaDatar(r, i) || milik[r][i]) return false; return true; };
            while (bawah < nb && rataBawah(bawah)) bawah++;
            const s = { b, k, bawah, kanan, kata: [] };
            for (let r = b; r < bawah; r++) for (let i = k; i < kanan; i++) milik[r][i] = s;
            s.garis = {
                top: adaDatar(b, k), bottom: adaDatar(bawah, k), left: adaTegak(k, b), right: adaTegak(kanan, b),
            };
            sel.push(s);
        }
    }

    return { sel, milik };
}

/* Baris dan paragraf */

const aman = (teks) => teks.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');

/** Potongan tulisan menjadi baris, dari atas ke bawah. */
function jadikanBaris(kata) {
    const baris = [];
    for (const k of [...kata].sort((a, b) => a.y - b.y || a.x - b.x)) {
        const b = baris.findLast?.((r) => Math.abs(r.y - k.y) <= Math.max(2, Math.min(k.ukuran, r.ukuran) * 0.35)) ?? null;
        if (b) {
            b.kata.push(k);
            // Tulisan atas/bawah tidak menggeser letak barisnya.
            if (k.ukuran > b.ukuran) { b.y = k.y; b.ukuran = k.ukuran; }
        } else {
            baris.push({ y: k.y, ukuran: k.ukuran, kata: [k] });
        }
    }

    return baris.sort((a, b) => a.y - b.y).map((b) => {
        b.kata.sort((p, q) => p.x - q.x);
        b.kiri = b.kata[0].x;
        b.kanan = Math.max(...b.kata.map((k) => k.x + k.lebar));
        b.teks = b.kata.map((k, i) => (jarakKata(b.kata[i - 1], k) ? ' ' : '') + k.teks).join('').replace(/\s+/g, ' ').trim();

        return b;
    });
}

const jarakKata = (a, b) => a && b.x - (a.x + a.lebar) > Math.min(a.ukuran, b.ukuran) * 0.12 && !/\s$/.test(a.teks) && !/^\s/.test(b.teks);

/** Isi beberapa baris sebagai HTML, dengan tebal dan miring; jarak lebar (kolom tanpa garis) menjadi spasi tak-putus. */
function isiHtml(barisnya) {
    const potongan = [];
    barisnya.forEach((b, i) => b.kata.forEach((k, j) => {
        const sebelum = j ? b.kata[j - 1] : null;
        const lebarJarak = sebelum ? k.x - (sebelum.x + sebelum.lebar) : 0;
        let spasi = '';
        if (sebelum) spasi = lebarJarak > k.ukuran * 2 ? '    ' : jarakKata(sebelum, k) ? ' ' : '';
        else if (i) spasi = /[-‐]$/.test(barisnya[i - 1].teks) && /^\p{Ll}/u.test(k.teks) ? '' : ' ';
        const naik = b.ukuran - k.ukuran > b.ukuran * 0.2 && Math.abs(k.y - b.y) > 1.2 ? (k.y < b.y ? 'sup' : 'sub') : '';
        const gaya = `${k.tebal ? 'strong,' : ''}${k.miring ? 'em,' : ''}${naik ? `${naik},` : ''}`;
        const akhir = potongan[potongan.length - 1];
        const teks = aman(k.teks.replace(/\s+/g, ' '));
        if (akhir && akhir.gaya === gaya) akhir.teks += spasi + teks;
        else potongan.push({ gaya, teks: spasi + teks });
    }));

    return potongan.map(({ gaya, teks }) => {
        const tag = gaya.split(',').filter(Boolean);

        return tag.map((x) => `<${x}>`).join('') + teks.replace(/ {2,}/g, ' ') + tag.reverse().map((x) => `</${x}>`).join('');
    }).join('').trim();
}

const PENANDA = /^(\(?\d{1,3}[.)]|\(?[a-z][.)]|\(\d{1,3}[a-z]?\)|[ivx]{1,5}[.)]|[A-Z]\.|[-•–▪●])(\s|$)/u;

/** Baris-baris menjadi paragraf di antara tepi kiri dan kanan wadahnya. */
function jadikanParagraf(baris, kiri, kanan, diTabel) {
    const lebar = kanan - kiri;
    const tengah = (b) => {
        const [a, z] = [b.kiri - kiri, kanan - b.kanan];
        return Math.abs(a - z) < Math.max(3, lebar * 0.03) && a > Math.max(diTabel ? 3 : 12, lebar * (diTabel ? 0.06 : 0.08));
    };
    const rataKanan = (b) => kanan - b.kanan < Math.max(3, lebar * 0.02) && b.kiri - kiri > lebar * (diTabel ? 0.25 : 0.4);
    const paragraf = [];

    baris.forEach((b, i) => {
        const p = paragraf[paragraf.length - 1];
        const lalu = baris[i - 1];
        // Baris pendek yang tepinya sama dengan baris penuh di atasnya adalah akhir paragraf, walaupun kebetulan di tengah.
        const akhirParagraf = lalu && p.jenis === '' && Math.abs(b.kiri - lalu.kiri) < 3 && kanan - lalu.kanan < Math.max(lebar * 0.12, 14);
        const jenis = akhirParagraf ? '' : tengah(b) ? 'center' : rataKanan(b) ? 'right' : '';
        const jarak = lalu ? b.y - lalu.y : 0;
        const penuh = lalu && kanan - lalu.kanan < Math.max(lebar * 0.12, 14);
        const sambung = p && lalu && p.jenis === jenis && !PENANDA.test(b.teks)
            && jarak < Math.max(lalu.ukuran, b.ukuran) * 2.1
            && (p.jarak === null || jarak < p.jarak * 1.35)
            && (jenis === 'center' ? diTabel && lalu.kanan - lalu.kiri > lebar * 0.55 : penuh || (diTabel && lalu.kanan - lalu.kiri > lebar * 0.6));

        if (sambung) {
            p.baris.push(b);
            p.jarak ??= jarak;
        } else {
            paragraf.push({ jenis, baris: [b], jarak: null });
        }
    });

    return paragraf.map((p) => {
        const gaya = [];
        const utama = p.baris[0];
        if (p.jenis) gaya.push(`text-align:${p.jenis}`);
        else if (!diTabel && p.baris.length > 1 && p.baris.slice(0, -1).every((b) => kanan - b.kanan < 4)) gaya.push('text-align:justify');
        if (!p.jenis && !diTabel) {
            // Baris kedua dan seterusnya menentukan tepi kirinya; baris pertama bisa menjorok atau menggantung.
            const tepi = p.baris.length > 1 ? Math.min(...p.baris.slice(1).map((b) => b.kiri)) : utama.kiri;
            const jorok = Math.round(tepi - kiri);
            const awal = Math.round(utama.kiri - tepi);
            if (jorok > 12) gaya.push(`margin-left:${Math.min(360, jorok)}pt`);
            if (Math.abs(awal) > 6 && jorok + awal > -4) gaya.push(`text-indent:${awal}pt`);
        }
        const ukuran = Math.round(utama.ukuran * 2) / 2;
        let isi = isiHtml(p.baris);
        if (Math.abs(ukuran - 12) >= 1.5 && diTabel) isi = `<span style="font-size:${ukuran}pt">${isi}</span>`;

        return `<p${gaya.length ? ` style="${gaya.join(';')}"` : ''}>${isi}</p>`;
    }).join('');
}

/* Menyusun dokumen */

function susun(halaman) {
    const blok = []; // { html } atau { tabel }
    const siap = [];

    for (const h of halaman) {
        const tabel = cariTabel(h.ruas).map((t) => ({ ...t, ...selTabel(t) }));
        const luar = [];

        for (const k of h.kata) {
            const [cx, cy] = [k.x + k.lebar / 2, k.y - k.ukuran * 0.3];
            const t = tabel.find((s) => cx > s.x[0] - 2 && cx < s.x[s.x.length - 1] + 2 && cy > s.y[0] - 2 && cy < s.y[s.y.length - 1] + 2);
            if (!t) {
                luar.push(k);
                continue;
            }
            const kolom = Math.min(t.x.length - 2, Math.max(0, t.x.findIndex((x) => x > cx) - 1));
            const baris = Math.min(t.y.length - 2, Math.max(0, t.y.findIndex((y) => y > cy) - 1));
            t.milik[baris][kolom].kata.push(k);
        }

        // Kotak tanpa isi (bingkai, garis kop) bukan tabel.
        const berisi = tabel.filter((t) => t.sel.length > 1 && t.sel.some((s) => s.kata.length));
        for (const t of tabel.filter((s) => !berisi.includes(s))) t.sel.forEach((s) => luar.push(...s.kata));

        let baris = jadikanBaris(luar);
        // Nomor halaman di tepi atas atau bawah.
        baris = baris.filter((b) => !(/^[-–\s]*\d{1,4}[-–\s]*$/.test(b.teks) && (b.y < 80 || b.y > h.tinggi - 80)));

        siap.push({ h, baris, berisi });
    }

    // Tepi isi diambil dari seluruh halaman; tepi kanan dianggap paling sedikit seimbang dengan tepi kirinya.
    const tepiKiri = Math.min(...siap.flatMap(({ h, baris, berisi }) => [...baris.map((b) => b.kiri), ...berisi.map((t) => t.x[0] + 5), h.lebar * 0.2]));
    const tepiKanan = (h) => Math.max(h.lebar - tepiKiri, ...siap.filter((s) => s.h.lebar === h.lebar)
        .flatMap(({ baris, berisi }) => [...baris.map((b) => b.kanan), ...berisi.map((t) => t.x[t.x.length - 1] - 5)]));

    for (const { h, baris, berisi } of siap) {
        const kiri = tepiKiri;
        const kanan = tepiKanan(h);
        let i = 0;
        for (const t of berisi) {
            const atas = [];
            while (i < baris.length && baris[i].y < t.y[0] + 2) atas.push(baris[i++]);
            if (atas.length) blok.push({ html: jadikanParagraf(atas, kiri, kanan, false) });
            const lalu = blok[blok.length - 1];
            // Tabel yang berlanjut dari halaman sebelumnya: garis kolomnya sama dan tidak ada tulisan di antaranya.
            if (lalu?.tabel && lalu.halaman !== h && sama(lalu.tabel.x, t.x)) sambungTabel(lalu.tabel, t);
            else blok.push({ tabel: t });
            blok[blok.length - 1].halaman = h;
        }
        const sisa = baris.slice(i);
        if (sisa.length) blok.push({ html: jadikanParagraf(sisa, kiri, kanan, false) });
    }

    return blok.map((b) => b.html ?? tabelHtml(b.tabel)).join('');
}

const sama = (a, b) => a.length === b.length && a.every((x, i) => Math.abs(x - b[i]) < 4);

const teksBaris = (t, b) => t.sel.filter((s) => s.b === b).map((s) => jadikanBaris(s.kata).map((r) => r.teks).join(' ')).join('|');

function sambungTabel(tabel, lanjutan) {
    // Baris judul yang diulang di halaman berikutnya tidak disalin lagi.
    let lewat = 0;
    while (lewat < lanjutan.y.length - 1 && lewat < 4 && teksBaris(tabel, lewat) === teksBaris(lanjutan, lewat) && teksBaris(tabel, lewat).replace(/\|/g, '') !== '') lewat++;
    if (lewat) tabel.judul = Math.max(tabel.judul ?? 0, lewat);

    const geser = tabel.y.length - 1 - lewat;
    const dasar = tabel.y[tabel.y.length - 1];
    const awal = lanjutan.y[lewat];
    for (const s of lanjutan.sel) {
        if (s.b < lewat) continue;
        tabel.sel.push({ ...s, b: s.b + geser, bawah: s.bawah + geser });
    }
    tabel.y.push(...lanjutan.y.slice(lewat + 1).map((y) => dasar + y - awal));
}

function tabelHtml({ x, y, sel, judul = 0 }) {
    const px = x.slice(1).map((v, i) => Math.max(20, Math.round((v - x[i]) * PT_KE_PX)));
    const html = [];

    for (let b = 0; b < y.length - 1; b++) {
        const tag = b < judul ? 'th' : 'td';
        const isi = sel.filter((s) => s.b === b).sort((p, q) => p.k - q.k).map((s) => {
            const sisi = Object.entries(s.garis);
            const gaya = sisi.every(([, ada]) => !ada) ? 'border-style:none' : sisi.filter(([, ada]) => !ada).map(([n]) => `border-${n}:none`).join(';');
            const [l, r] = [x[s.k] + 5, x[s.kanan] - 5];
            const baris = jadikanBaris(s.kata);
            const tinggi = y[s.bawah] - y[s.b];
            const tegak = baris.length && baris[0].y - baris[0].ukuran - y[s.b] > Math.max(8, tinggi * 0.2) && y[s.bawah] - baris[baris.length - 1].y > 6 ? 'vertical-align:middle' : '';
            const semua = [gaya, tegak].filter(Boolean).join(';');

            return `<${tag}${s.kanan - s.k > 1 ? ` colspan="${s.kanan - s.k}"` : ''}${s.bawah - s.b > 1 ? ` rowspan="${s.bawah - s.b}"` : ''} colwidth="${px.slice(s.k, s.kanan).join(',')}"${semua ? ` style="${semua}"` : ''}>${baris.length ? jadikanParagraf(baris, l, r, true) : '<p></p>'}</${tag}>`;
        });
        html.push(`<tr>${isi.join('')}</tr>`);
    }

    return `<table><tbody>${html.join('')}</tbody></table>`;
}
