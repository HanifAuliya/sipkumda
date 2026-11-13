/**
 * Tur pengenalan: menyorot bagian layar (atribut data-tur) satu per satu dengan kartu penjelasan.
 *
 *   <div x-data="turPengenalan(@js($langkah), '{{ route('tur.selesai') }}')">...</div>
 *
 * Langkah yang targetnya tidak ada atau tidak terlihat (mis. menu samping di HP) ditampilkan di
 * tengah layar. Selesai atau Lewati dicatat ke server, jadi tur tidak muncul lagi.
 */
export default (langkah, urlSelesai) => ({
    langkah,
    i: 0,
    buka: false,
    sorot: null, // { top, left, width, height } atau null bila tanpa target
    kartu: { top: 0, left: 0 },
    hp: false,

    init() {
        const mulai = () => {
            this.buka = true;
            this.$nextTick(() => this.tampilkan());
        };

        // Tunggu layar pembuka (splash) selesai supaya tur tidak tertutup.
        const tunggu = () => (document.getElementById('layar-masuk') ? setTimeout(tunggu, 200) : setTimeout(mulai, 350));
        tunggu();

        const ulang = () => this.buka && this.tampilkan(false);
        window.addEventListener('resize', ulang);
        window.addEventListener('scroll', ulang, { passive: true });
    },

    get sekarang() {
        return this.langkah[this.i];
    },

    /** Menu samping tersembunyi di HP: beri tahu letak tombolnya. */
    get menuTersembunyi() {
        return !this.sorot && (this.sekarang?.target ?? '').startsWith('menu-');
    },

    /** Kalimat langkah ini; memakai isi_kosong bila bagian yang diperiksa bertanda data-kosong. */
    get isi() {
        const s = this.sekarang;
        const cek = s?.cek ?? s?.target;
        const kosong = s?.isi_kosong && cek && document.querySelector(`[data-tur="${cek}"]`)?.hasAttribute('data-kosong');

        return kosong ? s.isi_kosong : s?.isi;
    },

    get terakhir() {
        return this.i === this.langkah.length - 1;
    },

    /** Elemen target bila ada dan terlihat di layar (bukan di laci menu yang tertutup). */
    target() {
        const nama = this.sekarang?.target;
        if (!nama) return null;

        const el = document.querySelector(`[data-tur="${nama}"]`);
        if (!el) return null;

        const r = el.getBoundingClientRect();
        const terlihat = r.width > 0 && r.height > 0 && r.right > 0 && r.left < window.innerWidth;

        return terlihat ? el : null;
    },

    tampilkan(gulir = true) {
        this.hp = window.innerWidth < 640;
        const el = this.target();

        if (!el) {
            this.sorot = null;
            return;
        }

        if (gulir) {
            const r = el.getBoundingClientRect();
            if (r.top < 80 || r.bottom > window.innerHeight - 40) {
                el.scrollIntoView({ block: 'center', behavior: 'smooth' });
                setTimeout(() => this.tampilkan(false), 380);
            }
        }

        const r = el.getBoundingClientRect();
        const jarak = 6;
        this.sorot = { top: r.top - jarak, left: r.left - jarak, width: r.width + jarak * 2, height: r.height + jarak * 2 };

        if (this.hp) return; // Di HP kartu menempel di bawah layar.

        // Kartu di kanan target bila muat (menu samping), selain itu di bawah atau di atasnya.
        const lebar = 360;
        const tinggi = this.$refs.kartu?.offsetHeight || 200;
        let top;
        let left;

        if (r.right + 16 + lebar < window.innerWidth && r.width < 320) {
            left = r.right + 16;
            top = r.top + r.height / 2 - tinggi / 2;
        } else {
            left = r.left + r.width / 2 - lebar / 2;
            top = r.bottom + 16 + tinggi < window.innerHeight ? r.bottom + 16 : r.top - 16 - tinggi;
        }

        this.kartu = {
            top: Math.min(Math.max(top, 16), window.innerHeight - tinggi - 16),
            left: Math.min(Math.max(left, 16), window.innerWidth - lebar - 16),
        };
    },

    ke(i) {
        if (i < 0 || i >= this.langkah.length) return;
        this.i = i;
        this.$nextTick(() => this.tampilkan());
    },

    lanjut() {
        this.terakhir ? this.selesai() : this.ke(this.i + 1);
    },

    selesai() {
        this.buka = false;

        // Membuang ?tur=1 dari alamat supaya memuat ulang tidak memunculkan tur lagi.
        const url = new URL(window.location.href);
        if (url.searchParams.has('tur')) {
            url.searchParams.delete('tur');
            history.replaceState(history.state, '', url);
        }

        fetch(urlSelesai, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
                Accept: 'application/json',
            },
        }).catch(() => {});
    },

    tombol(e) {
        if (!this.buka) return;
        if (e.key === 'ArrowRight') this.lanjut();
        if (e.key === 'ArrowLeft') this.ke(this.i - 1);
        if (e.key === 'Escape') this.selesai();
    },
});
