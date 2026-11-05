/*
|--------------------------------------------------------------------------
| Umpan balik saat berpindah menu dan tab (v2)
|--------------------------------------------------------------------------
| Semuanya berjalan di browser dan tidak menambah permintaan ke server:
| penanda menu meluncur seketika saat diklik, isi halaman meredup sampai
| halaman baru datang, dan posisi gulir menu tidak melompat.
*/
const menuSamping = () => document.querySelector('[data-menu-samping]');
const isiHalaman = () => document.querySelector('[data-isi-halaman]');

/** Bila perpindahan belum selesai sampai batas ini, browser membuka halamannya secara biasa. */
const BATAS_PINDAH = 6000;

/*
| Garis kemajuan di atas layar. Menempel pada <html>, bukan <body>, supaya tidak
| ikut terhapus saat halaman ditukar dan sempat terlihat selesai walau servernya cepat.
*/
let garis = null;
let batasGaris = null;

const selesaiMuat = () => {
    clearTimeout(batasGaris);

    if (garis?.hasAttribute('data-berjalan')) {
        garis.removeAttribute('data-berjalan');
        garis.setAttribute('data-selesai', '');
    }
};

const mulaiMuat = (tujuan = null) => {
    if (!garis) {
        garis = document.createElement('div');
        garis.className = 'garis-muat';
        garis.setAttribute('aria-hidden', 'true');
        document.documentElement.append(garis);
    }

    clearTimeout(batasGaris);
    garis.removeAttribute('data-selesai');
    garis.removeAttribute('data-berjalan');
    void garis.offsetWidth; // Paksa kembali ke nol sebelum animasi berikutnya.
    garis.setAttribute('data-berjalan', '');
    // Galat sesaat di server tidak boleh meninggalkan pengguna dengan indikator yang terus berputar.
    batasGaris = setTimeout(() => (tujuan ? location.assign(tujuan) : selesaiMuat()), BATAS_PINDAH);
};

/** Pemberitahuan di tengah area konten; terhapus sendiri saat halaman baru menggantikannya. */
const pasangPemuat = (isi) => {
    const pemuat = document.createElement('div');

    pemuat.className = 'pemuat-halaman';
    pemuat.setAttribute('role', 'status');
    pemuat.innerHTML = '<span class="pemuat-isi"><span class="pemuat-putar"></span>Memuat halaman...</span>';
    isi.after(pemuat);

    return pemuat;
};

const tempatkanPenanda = (animasi = true) => {
    const menu = menuSamping();
    const penanda = menu?.querySelector('[data-penanda-menu]');

    if (!penanda) return;

    const aktif = menu.querySelector('a[aria-current="page"]');

    if (!aktif) {
        penanda.style.opacity = '0';
        return;
    }

    const kotakMenu = menu.getBoundingClientRect();
    const kotakAktif = aktif.getBoundingClientRect();

    penanda.style.transitionDuration = animasi ? '' : '0s';
    penanda.style.height = `${kotakAktif.height}px`;
    penanda.style.transform = `translateY(${kotakAktif.top - kotakMenu.top + menu.scrollTop}px)`;
    penanda.style.opacity = '1';
    menu.setAttribute('data-penanda-siap', '');
};

/** Lebar menu berubah saat diciutkan, jadi penanda perlu mengikuti posisi item. */
const amatiMenu = () => {
    const menu = menuSamping();

    if (menu && !menu.dataset.diamati) {
        menu.dataset.diamati = '1';
        new ResizeObserver(() => tempatkanPenanda(false)).observe(menu);
    }
};

document.addEventListener('livewire:navigate', ({ detail }) => {
    const menu = menuSamping();
    const tujuan = new URL(detail.url, location.origin).pathname;
    const item = menu && [...menu.querySelectorAll('a[href]')].find((a) => new URL(a.href).pathname === tujuan);

    if (item) {
        menu.querySelectorAll('a[aria-current]').forEach((a) => a.removeAttribute('aria-current'));
        item.setAttribute('aria-current', 'page');
        item.setAttribute('data-memuat', '');
        tempatkanPenanda(true);
    }

    mulaiMuat(new URL(detail.url, location.origin).href);

    const isi = isiHalaman();

    if (isi && !isi.classList.contains('sedang-pindah')) {
        pasangPemuat(isi);
        isi.classList.add('sedang-pindah');
    }
});

// Tautan yang memuat ulang penuh, misalnya ke layar kerja naskah, juga menampilkan garis kemajuan.
document.addEventListener('click', (event) => {
    if (event.target.closest?.('a[data-muat-halaman]') && !event.ctrlKey && !event.metaKey && !event.shiftKey) {
        mulaiMuat();
    }
});

// Kembali lewat tombol browser bisa memulihkan halaman beserta garis yang masih berjalan.
window.addEventListener('pageshow', selesaiMuat);

/** Dipanggil saat tombol ciut ditekan; bila keadaannya sudah sama, tidak ada animasi. */
window.gantiMenuCiut = (ciut) => {
    const html = document.documentElement;

    if (html.hasAttribute('data-menu-ciut') === ciut) return;

    html.setAttribute('data-menu-gerak', '');
    html.toggleAttribute('data-menu-ciut', ciut);
    setTimeout(() => html.removeAttribute('data-menu-gerak'), 300);
};

const pasangMenuCiut = () => {
    try {
        document.documentElement.toggleAttribute('data-menu-ciut', localStorage.getItem('sidebar-collapsed') === 'true');
    } catch {
        // Penyimpanan diblokir: menu memakai keadaan terakhir.
    }
};

// Menu dibuat baru setelah berpindah, jadi keadaan dan posisi gulirnya dipulihkan sebelum tampil.
document.addEventListener('livewire:navigating', ({ detail }) => {
    const posisi = menuSamping()?.scrollTop ?? 0;

    detail.onSwap?.(() => {
        const menu = menuSamping();

        pasangMenuCiut();
        if (menu) menu.scrollTop = posisi;
        tempatkanPenanda(false);
    });
});

document.addEventListener('livewire:navigated', () => {
    selesaiMuat();
    tempatkanPenanda(false);
    amatiMenu();
});

// Huruf yang baru termuat bisa menggeser tinggi item menu.
document.fonts?.ready.then(() => tempatkanPenanda(false));

// Tab yang diklik langsung tampak aktif; jawaban server tetap menjadi penentu akhirnya.
document.addEventListener('click', (event) => {
    const tab = event.target.closest?.('[role="tab"][wire\\:click]');

    if (!tab) return;

    tab.closest('[role="tablist"]')
        ?.querySelectorAll('[role="tab"]')
        .forEach((t) => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
});
