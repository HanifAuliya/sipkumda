/*
|--------------------------------------------------------------------------
| Tema terang / gelap
|--------------------------------------------------------------------------
| Tampilan awal selalu terang, tidak mengikuti tema Windows atau HP. Tema gelap
| hanya aktif bila pengguna memilihnya lewat tombol, dan pilihan itu disimpan.
| Class "dark" dipasang lebih awal oleh script inline di <head> (lihat
| layouts/partials/head dan components/publik-layout) supaya tidak berkedip.
*/
const STORAGE_KEY = 'theme';

const readTheme = () => {
    try {
        return localStorage.getItem(STORAGE_KEY);
    } catch {
        return null;
    }
};

export const isDark = () => document.documentElement.classList.contains('dark');

// Warna bilah alamat browser di HP mengikuti tema.
const WARNA_BILAH = { dark: '#1a2233', light: '#172b4d' };

const pasangTema = (theme) => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    document.querySelector('meta[name="theme-color"]')?.setAttribute('content', WARNA_BILAH[theme === 'dark' ? 'dark' : 'light']);
};

export const setTheme = (theme) => {
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // Penyimpanan diblokir (mode privat); tema tetap berlaku untuk halaman ini.
    }

    const terapkan = () => {
        pasangTema(theme);
        window.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
    };

    // Memudar bila browser mendukung; bila tidak, langsung berganti.
    if (document.startViewTransition && !window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        document.startViewTransition(terapkan);
    } else {
        terapkan();
    }
};

window.toggleTheme = () => setTheme(isDark() ? 'light' : 'dark');

// Pasang ulang tema saat berpindah halaman tanpa muat ulang, sebelum halaman baru tampil.
const terapkanTema = () => pasangTema(readTheme());

terapkanTema();

document.addEventListener('livewire:navigating', ({ detail }) => detail.onSwap?.(terapkanTema));
document.addEventListener('livewire:navigated', terapkanTema);
