/**
 * Naskah seperti di Word: kertas panjang (v2/partials/isi-naskah) dipecah menjadi lembar seukuran kertasnya, dengan
 * perbesaran dan nomor halaman (v2/partials/naskah-berhalaman). Isinya tidak dipindah; di batas halaman disisipkan ruang
 * kosong, lalu lembarnya digambar di belakang. Pemenggalannya seperti Word: satu butir boleh terpenggal di tengah dan
 * judul tidak tertinggal di bawah halaman. PDF (DomPDF) memindahkan satu butir utuh, jadi halamannya bisa berbeda.
 *
 * Ukuran dalam px CSS. paraf: ruang kotak paraf di bawah halaman pertama, dipakai bila naskahnya lebih dari satu halaman.
 */
const JEDA = 28;
const UTUH = '.akhir, .tengah, .kepala-lampiran, .kop, .lampiran-berkas, .ditetapkan, .paraf, img, iframe, hr';

export default ({ lebar, tinggi, atas, bawah, kiri, paraf }) => ({
    siap: false,
    pas: true,
    skala: 1,
    halaman: 1,
    lembar: [],

    init() {
        this.$nextTick(() => this.mulai());
    },

    async mulai() {
        try {
            const zoom = Number(localStorage.getItem('v2-zoom-naskah'));
            if (zoom >= 0.4 && zoom <= 2) {
                this.pas = false;
                this.skala = zoom;
            }
        } catch (e) { /* tanpa penyimpanan: selebar layar */ }

        // Huruf dan lambang harus sudah dimuat, karena menentukan letak pergantian halaman.
        const gambar = [...this.$refs.kertas.querySelectorAll('img')].filter((g) => !g.complete)
            .map((g) => new Promise((r) => { g.onload = g.onerror = r; }));
        await Promise.race([Promise.all([document.fonts.ready, ...gambar]), new Promise((r) => setTimeout(r, 3000))]);
        if (!this.$refs.kertas?.isConnected) return;

        try {
            this.lembar = pecah(this.$refs.kertas, { tinggi, atas, bawah, kiri, paraf });
        } catch (e) {
            // Gagal memecah: tetap tampil sebagai satu lembar panjang.
            console.error(e);
            this.lembar = [{ atas: 0, tinggi: this.$refs.kertas.offsetHeight }];
        }

        gambarLembar(this.$refs.kertas, this.lembar);
        this.siap = true;
        this.ukur();

        this.pengamat = new ResizeObserver(() => this.ukur());
        this.pengamat.observe(this.$refs.meja);
        this.gulung = this.$root.closest('.overflow-y-auto');
        this.saatGulir = () => this.hitungHalaman();
        this.gulung?.addEventListener('scroll', this.saatGulir, { passive: true });
    },

    destroy() {
        this.pengamat?.disconnect();
        this.gulung?.removeEventListener('scroll', this.saatGulir);
    },

    get tinggiTotal() {
        const akhir = this.lembar[this.lembar.length - 1];

        return akhir ? akhir.atas + akhir.tinggi : 0;
    },

    ukur() {
        if (this.pas) {
            const gaya = getComputedStyle(this.$refs.meja);
            const ruang = this.$refs.meja.clientWidth - parseFloat(gaya.paddingLeft) - parseFloat(gaya.paddingRight);
            if (ruang > 0) this.skala = Math.min(1, ruang / lebar);
        }
        this.hitungHalaman();
    },

    perbesar(langkah) {
        this.pas = false;
        this.skala = Math.min(2, Math.max(0.4, Math.round((this.skala + langkah) * 10) / 10));
        this.simpan(String(this.skala));
        this.hitungHalaman();
    },

    selebarLayar() {
        this.pas = true;
        this.simpan('');
        this.ukur();
    },

    simpan(nilai) {
        try { localStorage.setItem('v2-zoom-naskah', nilai); } catch (e) { /* tidak diingat */ }
    },

    hitungHalaman() {
        if (!this.gulung) return;
        const layar = this.gulung.getBoundingClientRect();
        const y = (layar.top + layar.height * 0.4 - this.$refs.kertas.getBoundingClientRect().top) / this.skala;
        this.halaman = Math.max(1, this.lembar.filter((l) => l.atas <= y).length);
    },
});

function gambarLembar(kertas, lembar) {
    const akhir = lembar[lembar.length - 1];
    kertas.style.height = `${akhir.atas + akhir.tinggi}px`;
    kertas.prepend(...lembar.map((l) => {
        const el = document.createElement('div');
        el.className = 'lembar';
        el.style.top = `${l.atas}px`;
        el.style.height = `${l.tinggi}px`;

        return el;
    }));
}

/** Menyisipkan ruang di batas halaman; mengembalikan letak tiap lembar. Kertas harus belum diperbesar/diperkecil. */
function pecah(kertas, { tinggi, atas, bawah, kiri, paraf }) {
    const asal = () => kertas.getBoundingClientRect().top;
    const atasDari = (el) => el.getBoundingClientRect().top - asal();
    const bawahDari = (el) => el.getBoundingClientRect().bottom - asal();

    const kotakParaf = kertas.querySelector('table.paraf');
    const banyak = !!kertas.querySelector('.halaman-baru') || kertas.offsetHeight > tinggi + 1;
    const sisih = kotakParaf && banyak ? paraf : 0;

    const lembar = [];
    let mulai = 0;
    let akhir = tinggi;
    let batas = akhir - bawah - sisih;
    let awalIsi = atas;

    const muat = (el) => bawahDari(el) <= batas + 0.5;
    const diAtas = (el) => atasDari(el) <= awalIsi + 1;

    const putus = (ruang) => {
        ruang.style.height = '1px';
        lembar.push({ atas: mulai, tinggi: akhir - mulai });
        mulai = akhir + JEDA;
        akhir = mulai + tinggi;
        batas = akhir - bawah;
        awalIsi = mulai + atas;
        ruang.style.height = `${Math.max(1, awalIsi - atasDari(ruang))}px`;
    };

    /** Yang lebih tinggi dari satu halaman: halamannya dipanjangkan, daripada isinya tertutup celah. */
    const panjangkan = (dasar) => {
        batas = dasar;
        akhir = batas + bawah + (lembar.length ? 0 : sisih);
    };

    const ruangBaru = (tag = 'div') => {
        const el = document.createElement(tag);
        el.className = 'jeda-halaman';

        return el;
    };

    /** Judul rata tengah (BAB, Pasal) yang tepat di atas elemen ikut pindah bersamanya. */
    const bersamaJudul = (el) => {
        let blok = el;
        while (blok.parentElement !== kertas) blok = blok.parentElement;
        if (atasDari(el) - atasDari(blok) > 2) return el;

        let awal = null;
        for (let j = blok.previousElementSibling; j?.matches('.tengah') && !diAtas(j); j = j.previousElementSibling) awal = j;

        return awal ?? el;
    };

    /** Ruang tepat sebelum elemen; di tabel berupa baris tersendiri. */
    const sisipSebelum = (tujuan) => {
        const el = bersamaJudul(tujuan);
        const ruang = ruangBaru();

        if (el.tagName === 'TR') {
            const baris = document.createElement('tr');
            const sel = document.createElement('td');
            sel.colSpan = [...el.closest('table').rows].reduce((n, b) => Math.max(n, [...b.cells].reduce((j, s) => j + s.colSpan, 0)), 1);
            baris.className = 'jeda-baris';
            sel.append(ruang);
            baris.append(sel);
            el.before(baris);
        } else {
            el.before(ruang);
            el.style.marginTop = '0';
        }

        return ruang;
    };

    const pindah = (el) => {
        if (!diAtas(el)) putus(sisipSebelum(el));
        if (!muat(el)) panjangkan(bawahDari(el));
    };

    /** Baris pertama elemen ini tidak muat: pindah seluruhnya, supaya nomornya ikut. */
    const pindahBilaBarisPertama = (el) => {
        const tinggiBaris = parseFloat(getComputedStyle(el).lineHeight) || 20;
        if (atasDari(el) + tinggiBaris > batas + 0.5 && !diAtas(el)) putus(sisipSebelum(el));
    };

    const diLuarAlur = (el) => {
        const gaya = getComputedStyle(el);

        return gaya.display === 'none' || gaya.position === 'absolute' || el.classList.contains('jeda-halaman');
    };
    const punyaBlok = (el) => [...el.children].some((a) => !diLuarAlur(a) && !getComputedStyle(a).display.startsWith('inline'));

    const proses = (induk) => {
        for (const el of [...induk.children]) {
            if (diLuarAlur(el)) continue;
            if (el.classList.contains('halaman-baru')) {
                el.className = 'jeda-halaman';
                putus(el);
            } else if (!muat(el)) {
                tangani(el);
            }
        }
    };

    const tangani = (el) => {
        if (el.matches(UTUH)) {
            pindah(el);
        } else if (el.tagName === 'TABLE') {
            tabel(el);
        } else {
            if (el.tagName === 'LI') pindahBilaBarisPertama(el);
            if (!muat(el)) punyaBlok(el) ? proses(el) : teks(el);
        }
    };

    /** Baris yang disatukan sel gabungan (rowspan) tidak boleh terpisah. */
    const grupBaris = (tabelnya) => {
        const grup = [];

        for (const badan of tabelnya.tBodies) {
            let sampai = -1;
            [...badan.rows].forEach((b, i) => {
                if (i > sampai) grup.push([]);
                grup[grup.length - 1].push(b);
                for (const s of b.cells) sampai = Math.max(sampai, i + s.rowSpan - 1);
            });
        }

        return grup;
    };

    const tabel = (el) => {
        const bergaris = el.classList.contains('tabel-isi') || !!el.closest('.lampiran-html');

        grupBaris(el).forEach((grup, i) => {
            const akhirGrup = grup[grup.length - 1];
            if (muat(akhirGrup)) return;

            if (!bergaris) {
                isiBaris(grup[0]);
            } else if (i === 0) {
                if (!diAtas(el)) putus(sisipSebelum(el));
                if (!muat(akhirGrup)) panjangkan(bawahDari(akhirGrup));
            } else {
                putus(sisipSebelum(grup[0]));
                // Baris judul tabel diulang di halaman berikutnya, seperti cetakannya.
                if (el.tHead) grup[0].before(...[...el.tHead.rows].map((b) => b.cloneNode(true)));
                if (!muat(akhirGrup)) panjangkan(bawahDari(akhirGrup));
            }
        });
    };

    /** Baris tabel tata letak (Menimbang, diktum, ayat): yang dipenggal sel isinya, nomornya tetap di atas. */
    const isiBaris = (baris) => {
        pindahBilaBarisPertama(baris);
        if (muat(baris)) return;

        const tinggiIsi = (sel) => {
            const r = document.createRange();
            r.selectNodeContents(sel);

            return r.getBoundingClientRect().height;
        };
        const sel = [...baris.cells].reduce((a, b) => (tinggiIsi(b) > tinggiIsi(a) ? b : a));
        punyaBlok(sel) ? proses(sel) : teks(sel);
    };

    /** Huruf pertama di baris yang melewati batas halaman. */
    const titikLuber = (el, sesudah) => {
        const jalan = document.createTreeWalker(el, NodeFilter.SHOW_TEXT);
        const r = document.createRange();
        let pertama = true;

        for (let node = jalan.nextNode(); node; node = jalan.nextNode()) {
            if (sesudah && !(sesudah.compareDocumentPosition(node) & Node.DOCUMENT_POSITION_FOLLOWING)) continue;
            if (node.parentElement.closest('button')) continue;
            r.selectNodeContents(node);
            const kotak = [...r.getClientRects()];
            if (!kotak.length) continue;

            if (Math.max(...kotak.map((k) => k.bottom)) - asal() > batas + 0.5) {
                const luber = (mulaiDari) => {
                    for (let i = mulaiDari; i < node.length; i++) {
                        r.setStart(node, i);
                        r.setEnd(node, i + 1);
                        const k = r.getClientRects();
                        if (k.length) return k[k.length - 1].bottom - asal() > batas + 0.5;
                    }

                    return true;
                };
                let kecil = 0;
                let besar = node.length - 1;
                while (kecil < besar) {
                    const tengah = (kecil + besar) >> 1;
                    if (luber(tengah)) besar = tengah;
                    else kecil = tengah + 1;
                }

                return { node, letak: kecil, awal: pertama && !node.data.slice(0, kecil).trim() };
            }

            if (node.data.trim()) pertama = false;
        }

        return null;
    };

    const teks = (el) => {
        let sesudah = null;

        for (let jaga = 0; jaga < 500; jaga++) {
            const titik = titikLuber(el, sesudah);
            if (!titik) return;

            if (titik.awal) {
                const pembawa = el.tagName === 'TD' ? el.parentElement : el;
                if (sesudah || diAtas(pembawa)) return panjangkan(bawahDari(el));
                putus(sisipSebelum(pembawa));

                continue;
            }

            const r = document.createRange();
            sesudah ? r.setStartAfter(sesudah) : r.setStart(el, 0);
            r.setEnd(titik.node, titik.letak);
            const bagian = document.createElement('span');
            bagian.style.display = 'block';
            bagian.append(r.extractContents());
            // Baris terakhir sebelum pergantian halaman tetap rata kanan-kiri, kecuali memang akhir baris.
            if (getComputedStyle(el).textAlign === 'justify' && !/\n\s*$/.test(bagian.textContent)) bagian.style.textAlignLast = 'justify';

            const ruang = ruangBaru('span');
            ruang.style.display = 'block';
            r.insertNode(ruang);
            ruang.before(bagian);
            putus(ruang);
            sesudah = ruang;
        }
    };

    proses(kertas);
    lembar.push({ atas: mulai, tinggi: akhir - mulai });

    if (sisih) {
        const kotak = kotakParaf.cloneNode(true);
        kotak.classList.add('paraf-awal');
        kotak.style.top = `${lembar[0].tinggi - bawah - kotakParaf.offsetHeight}px`;
        kotak.style.left = `${kiri}px`;
        kertas.append(kotak);
    }

    return lembar;
}
