/*
|--------------------------------------------------------------------------
| Komponen Alpine "padTtd": kotak untuk menggambar tanda tangan langsung
|--------------------------------------------------------------------------
| Dipakai lewat <x-ttd.pad model="ttdLangsung" />. Bisa dengan mouse, jari, atau stylus (Pointer
| Events). Setiap goresan selesai, isi kanvas dikirim ke properti Livewire `model` sebagai data URL
| PNG berlatar transparan, tanpa permintaan ke server (dikirim bersama aksi berikutnya). Server
| memeriksa dan merapikannya (App\Support\TandaTanganLangsung).
|
| Bila properti itu dikosongkan dari server (mis. setelah tersimpan), kanvas ikut dibersihkan.
*/
const TINTA = '#14206b';

export default (model) => ({
    kosong: true,
    menggambar: false,
    titik: [],
    kanvas: null,
    ctx: null,

    init() {
        // Kotak bisa muncul lewat pembaruan Livewire; kanvasnya dicari setelah elemen lengkap terpasang.
        this.$nextTick(() => {
            this.kanvas = this.$el.querySelector('canvas');
            if (!this.kanvas) return;
            this.siapkan();

            // Lebar kotak berubah (layar diputar, panel dibuka): ukur ulang tanpa kehilangan goresan.
            new ResizeObserver(() => this.siapkan(true)).observe(this.kanvas);
        });

        // Dikosongkan dari server: bersihkan juga kanvasnya.
        this.$watch(`$wire.${model}`, (nilai) => {
            if (!nilai && !this.kosong) this.bersihkan(false);
        });
    },

    siapkan(pertahankan = false) {
        const rasio = window.devicePixelRatio || 1;
        const lebar = Math.round(this.kanvas.clientWidth * rasio);
        const tinggi = Math.round(this.kanvas.clientHeight * rasio);

        if (!lebar || !tinggi || (lebar === this.kanvas.width && tinggi === this.kanvas.height)) return;

        const lama = pertahankan && !this.kosong ? this.kanvas.toDataURL('image/png') : null;

        this.kanvas.width = lebar;
        this.kanvas.height = tinggi;
        this.ctx = this.kanvas.getContext('2d');
        this.ctx.scale(rasio, rasio);
        this.ctx.lineCap = 'round';
        this.ctx.lineJoin = 'round';
        this.ctx.strokeStyle = TINTA;
        this.ctx.fillStyle = TINTA;

        if (lama) {
            const img = new Image();
            img.onload = () => this.ctx.drawImage(img, 0, 0, this.kanvas.clientWidth, this.kanvas.clientHeight);
            img.src = lama;
        }
    },

    posisi(e) {
        const kotak = this.kanvas.getBoundingClientRect();
        return { x: e.clientX - kotak.left, y: e.clientY - kotak.top, tekanan: e.pressure || 0.5 };
    },

    tebal(p) {
        // Stylus memberi tekanan; mouse dan jari memakai tebal sedang.
        return 1.6 + 2.2 * (p.tekanan ?? 0.5);
    },

    mulai(e) {
        if (!this.ctx || (e.button !== undefined && e.button !== 0)) return;
        e.preventDefault();
        // Goresan tetap tercatat walau jari keluar dari kotak; tidak semua peramban mendukungnya.
        try {
            this.kanvas.setPointerCapture(e.pointerId);
        } catch {
            // abaikan
        }
        this.menggambar = true;
        const p = this.posisi(e);
        this.titik = [p];

        // Titik tunggal (ketukan) tetap terlihat.
        this.ctx.beginPath();
        this.ctx.arc(p.x, p.y, this.tebal(p) / 2, 0, Math.PI * 2);
        this.ctx.fill();
        this.kosong = false;
    },

    gerak(e) {
        if (!this.menggambar) return;
        e.preventDefault();

        for (const ev of e.getCoalescedEvents?.() ?? [e]) {
            const p = this.posisi(ev);
            const t = this.titik;
            t.push(p);

            if (t.length < 3) continue;

            // Kurva melalui titik tengah antar-titik supaya goresan halus, tidak patah-patah.
            const [a, b, c] = t.slice(-3);
            const m1 = { x: (a.x + b.x) / 2, y: (a.y + b.y) / 2 };
            const m2 = { x: (b.x + c.x) / 2, y: (b.y + c.y) / 2 };
            this.ctx.lineWidth = this.tebal(b);
            this.ctx.beginPath();
            this.ctx.moveTo(m1.x, m1.y);
            this.ctx.quadraticCurveTo(b.x, b.y, m2.x, m2.y);
            this.ctx.stroke();
        }
    },

    selesai() {
        if (!this.menggambar) return;
        this.menggambar = false;
        this.titik = [];
        this.$wire.set(model, this.kanvas.toDataURL('image/png'), false);
    },

    bersihkan(kirim = true) {
        this.ctx?.clearRect(0, 0, this.kanvas.width, this.kanvas.height);
        this.kosong = true;
        if (kirim) this.$wire.set(model, '', false);
    },
});
