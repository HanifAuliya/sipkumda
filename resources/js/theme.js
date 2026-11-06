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

export const setTheme = (theme) => {
    document.documentElement.classList.toggle('dark', theme === 'dark');
    try {
        localStorage.setItem(STORAGE_KEY, theme);
    } catch {
        // Penyimpanan diblokir (mode privat); tema tetap berlaku untuk halaman ini.
    }
    window.dispatchEvent(new CustomEvent('theme-changed', { detail: theme }));
};

window.toggleTheme = () => setTheme(isDark() ? 'light' : 'dark');

// Pasang ulang tema saat berpindah halaman tanpa muat ulang, sebelum halaman baru tampil.
const terapkanTema = () => document.documentElement.classList.toggle('dark', readTheme() === 'dark');

document.addEventListener('livewire:navigating', ({ detail }) => detail.onSwap?.(terapkanTema));
document.addEventListener('livewire:navigated', terapkanTema);
