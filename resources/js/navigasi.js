/*
|--------------------------------------------------------------------------
| Perpindahan menu dan tab (v2)
|--------------------------------------------------------------------------
| Semuanya berjalan di browser dan tidak menambah permintaan ke server:
| penanda menu meluncur saat diklik, isi halaman lama meredup, dan kerangka
| halaman (skeleton) hanya muncul bila server lambat menjawab.
*/
import { Livewire } from '../../vendor/livewire/livewire/dist/livewire.esm';

const menuSamping = () => document.querySelector('[data-menu-samping]');
const isiHalaman = () => document.querySelector('[data-isi-halaman]');

/** Perpindahan yang lebih cepat dari ini tidak memunculkan kerangka, supaya tidak berkedip. */
const JEDA_KERANGKA = 500;

/** Bila perpindahan belum selesai sampai batas ini, browser membuka halamannya secara biasa. */
const BATAS_PINDAH = 6000;

let jedaKerangka = null;
let batasPindah = null;

const hentikanPenunggu = () => {
    clearTimeout(jedaKerangka);
    clearTimeout(batasPindah);
};

const tampilkanKerangka = () => {
    const isi = isiHalaman();
    const kerangka = document.getElementById('kerangka-halaman');

    if (isi && kerangka && !isi.classList.contains('hidden')) {
        isi.classList.add('hidden');
        isi.after(kerangka.content.cloneNode(true));
    }
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
    const url = new URL(detail.url, location.origin);
    const item = menu && [...menu.querySelectorAll('a[href]')].find((a) => new URL(a.href).pathname === url.pathname);

    if (item) {
        menu.querySelectorAll('a[aria-current]').forEach((a) => a.removeAttribute('aria-current'));
        item.setAttribute('aria-current', 'page');
        tempatkanPenanda(true);
    }

    isiHalaman()?.classList.add('sedang-pindah');

    hentikanPenunggu();
    jedaKerangka = setTimeout(tampilkanKerangka, JEDA_KERANGKA);
    // Respons yang tidak kunjung datang tidak boleh meninggalkan pengguna di kerangka kosong.
    batasPindah = setTimeout(() => location.assign(url.href), BATAS_PINDAH);
});

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

        hentikanPenunggu();
        pasangMenuCiut();
        if (menu) menu.scrollTop = posisi;
        tempatkanPenanda(false);
    });
});

document.addEventListener('livewire:navigated', () => {
    hentikanPenunggu();
    tempatkanPenanda(false);
    amatiMenu();
});

// Huruf yang baru termuat bisa menggeser tinggi item menu.
document.fonts?.ready.then(() => tempatkanPenanda(false));

/*
 * Garis muat di atas layar (seperti GitHub) saat pindah menu dan ganti tab. Bar bawaan Livewire
 * tidak dipakai: hanya untuk wire:navigate dan baru muncul setelah 150 ms, sehingga di server yang
 * cepat nyaris tidak terlihat. Garis ini muncul langsung, lalu selesai dan memudar (minimal ~0,4 detik
 * terlihat). Hanya hiasan di browser: tidak menambah permintaan ke server.
 */
const garisMuat = (() => {
    let el = null;
    let nilai = 0;
    let rayap = null;
    let jumlah = 0; // permintaan yang belum selesai

    const geser = (n) => {
        nilai = n;
        el.firstChild.style.transform = `translate3d(${(n - 1) * 100}%, 0, 0)`;
    };

    const mulai = () => {
        jumlah++;
        if (el) return;

        el = document.createElement('div');
        el.className = 'garis-muat';
        el.setAttribute('aria-hidden', 'true');
        el.innerHTML = '<div></div>';
        // Di <html>, bukan <body>: body diganti saat pindah menu.
        document.documentElement.appendChild(el);
        geser(0.08);
        requestAnimationFrame(() => el && geser(0.35));
        rayap = setInterval(() => geser(nilai + (0.9 - nilai) * 0.08), 200);
    };

    const selesai = (semua = false) => {
        jumlah = semua ? 0 : Math.max(0, jumlah - 1);
        if (!el || jumlah > 0) return;

        clearInterval(rayap);
        const lama = el;
        el = null;
        lama.firstChild.style.transform = 'translate3d(0, 0, 0)';
        setTimeout(() => {
            lama.style.opacity = '0';
            setTimeout(() => lama.remove(), 250);
        }, 200);
    };

    return { mulai, selesai };
})();

// Pindah menu.
document.addEventListener('livewire:navigate', () => garisMuat.mulai());
document.addEventListener('livewire:navigated', () => garisMuat.selesai(true));

// Ganti tab. Galat di sini dianggap Livewire sebagai permintaan gagal, jadi garis (hiasan saja)
// tidak boleh pernah melempar galat.
Livewire.interceptMessage(({ message, onSend, onFinish }) => {
    let dariTab = false;

    onSend(() => {
        try {
            // actions berupa Set, bukan array.
            dariTab = [...message.actions].some((a) => a.origin?.el?.closest?.('[role="tab"]'));
            if (dariTab) garisMuat.mulai();
        } catch {
            dariTab = false;
        }
    });
    onFinish(() => {
        try {
            if (dariTab) garisMuat.selesai();
        } catch {
            // abaikan
        }
    });
});

// Tab yang diklik langsung tampak aktif; jawaban server tetap menjadi penentu akhirnya.
document.addEventListener('click', (event) => {
    const tab = event.target.closest?.('[role="tab"][wire\\:click]');

    if (!tab) return;

    tab.closest('[role="tablist"]')
        ?.querySelectorAll('[role="tab"]')
        .forEach((t) => t.setAttribute('aria-selected', t === tab ? 'true' : 'false'));
});
