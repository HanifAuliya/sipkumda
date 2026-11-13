/*
|--------------------------------------------------------------------------
| Komponen Alpine "pilihan": tampilan dropdown untuk <x-form.select>
|--------------------------------------------------------------------------
| <select> asli tetap ada (disembunyikan) dan tetap memegang nilai, jadi wire:model, name, dan
| validasi bekerja seperti biasa. Komponen ini membaca <option>/<optgroup> dari select itu,
| menampilkan panel yang seragam di semua peramban, lalu menulis pilihan kembali ke select dan
| memicu event "input" dan "change".
|
| Daftar yang panjang (lebih dari BATAS_CARI pilihan, mis. Perangkat Daerah) mendapat kotak cari.
*/
const BATAS_CARI = 20;

export default () => ({
    buka: false,
    opsi: [], // { jenis: 'grup', label } | { jenis: 'opsi', value, label, disabled }
    placeholder: '',
    label: '',
    cari: '',
    aktif: -1,
    nonaktif: false,
    ketikan: '',
    ketikanTimer: null,

    init() {
        this.baca();
        // wire:model baru memasang nilai ke <select> setelah komponen ini dimuat; baca lagi sesudahnya.
        this.$nextTick(() => this.baca());
        setTimeout(() => this.baca(), 0);

        const select = this.$refs.select;
        select.addEventListener('change', () => this.baca());

        // Livewire mengganti isi atau nilai select tanpa event (mis. reset form, daftar yang berubah).
        new MutationObserver(() => this.baca()).observe(select, { childList: true, subtree: true, attributes: true, characterData: true });
        if (window.Livewire?.hook) {
            this.lepasHook = window.Livewire.hook('commit', ({ succeed }) => succeed(() => queueMicrotask(() => this.baca())));
        }
    },

    destroy() {
        this.lepasHook?.();
    },

    baca() {
        const select = this.$refs.select;
        const opsi = [];
        let placeholder = '';

        const tambah = (o) => {
            if (o.value === '' && o.hidden) {
                placeholder = o.textContent.trim();

                return;
            }
            if (o.hidden) return;
            opsi.push({ jenis: 'opsi', value: o.value, label: o.textContent.trim(), disabled: o.disabled });
        };

        for (const el of select.children) {
            if (el.tagName === 'OPTGROUP') {
                opsi.push({ jenis: 'grup', label: el.label });
                [...el.children].forEach(tambah);
            } else if (el.tagName === 'OPTION') {
                tambah(el);
            }
        }

        this.opsi = opsi;
        this.placeholder = placeholder;
        this.nonaktif = select.disabled;
        const terpilih = select.selectedOptions[0];
        this.label = terpilih && !(terpilih.value === '' && terpilih.hidden) ? terpilih.textContent.trim() : '';
    },

    get nilai() {
        return this.$refs.select.value;
    },

    get bisaCari() {
        return this.opsi.filter((o) => o.jenis === 'opsi').length > BATAS_CARI;
    },

    /** Pilihan yang tampil: tersaring kotak cari; judul grup hanya bila grupnya punya isi. */
    get tampil() {
        const q = this.cari.trim().toLowerCase();
        const hasil = [];
        let grup = null;

        for (const o of this.opsi) {
            if (o.jenis === 'grup') {
                grup = o;
                continue;
            }
            if (q && !o.label.toLowerCase().includes(q)) continue;
            if (grup) {
                hasil.push(grup);
                grup = null;
            }
            hasil.push(o);
        }

        return hasil;
    },

    get bisaDipilih() {
        return this.tampil.filter((o) => o.jenis === 'opsi' && !o.disabled);
    },

    bukaPanel() {
        if (this.nonaktif || this.buka) return;
        this.baca();
        this.cari = '';
        this.buka = true;
        this.aktif = Math.max(0, this.bisaDipilih.findIndex((o) => o.value === this.nilai));

        this.$nextTick(() => {
            this.$refs.panel.style.minWidth = `${this.$refs.tombol.offsetWidth}px`;
            (this.bisaCari ? this.$refs.kotakCari : this.$refs.daftar)?.focus({ preventScroll: true });
            this.gulirKeAktif();
        });
    },

    tutup(kembaliKeTombol = true) {
        if (!this.buka) return;
        this.buka = false;
        if (kembaliKeTombol) this.$refs.tombol.focus({ preventScroll: true });
    },

    pilih(o) {
        if (!o || o.disabled) return;
        const select = this.$refs.select;

        if (select.value !== o.value) {
            select.value = o.value;
            select.dispatchEvent(new Event('input', { bubbles: true }));
            select.dispatchEvent(new Event('change', { bubbles: true }));
        }
        this.baca();
        this.tutup();
    },

    geser(langkah) {
        const n = this.bisaDipilih.length;
        if (!n) return;
        this.aktif = (this.aktif + langkah + n) % n;
        this.gulirKeAktif();
    },

    gulirKeAktif() {
        this.$nextTick(() => this.$refs.daftar?.querySelector('[data-aktif="true"]')?.scrollIntoView({ block: 'nearest' }));
    },

    /** Tombol tertutup: panah/Enter/Spasi membuka, huruf melompat ke pilihan yang diawali huruf itu. */
    tombolPemicu(e) {
        if (['ArrowDown', 'ArrowUp', 'Enter', ' '].includes(e.key)) {
            e.preventDefault();
            this.bukaPanel();
        } else if (e.key.length === 1 && /\S/.test(e.key) && !e.ctrlKey && !e.metaKey && !e.altKey) {
            this.lompatKetik(e.key, true);
        }
    },

    /** Di dalam panel. */
    tombolPanel(e) {
        const peta = {
            ArrowDown: () => this.geser(1),
            ArrowUp: () => this.geser(-1),
            Home: () => { this.aktif = 0; this.gulirKeAktif(); },
            End: () => { this.aktif = this.bisaDipilih.length - 1; this.gulirKeAktif(); },
            Enter: () => this.pilih(this.bisaDipilih[this.aktif]),
            Escape: () => this.tutup(),
            Tab: () => this.tutup(false),
        };

        if (peta[e.key]) {
            if (e.key !== 'Tab') e.preventDefault();
            peta[e.key]();
        } else if (!this.bisaCari && e.key.length === 1 && /\S/.test(e.key)) {
            this.lompatKetik(e.key, false);
        }
    },

    lompatKetik(huruf, langsungPilih) {
        clearTimeout(this.ketikanTimer);
        this.ketikan += huruf.toLowerCase();
        this.ketikanTimer = setTimeout(() => (this.ketikan = ''), 600);

        const daftar = this.opsi.filter((o) => o.jenis === 'opsi' && !o.disabled);
        const ketemu = daftar.find((o) => o.label.toLowerCase().startsWith(this.ketikan));
        if (!ketemu) return;

        if (langsungPilih) {
            this.pilih(ketemu);
        } else {
            this.aktif = this.bisaDipilih.findIndex((o) => o.value === ketemu.value);
            this.gulirKeAktif();
        }
    },
});
