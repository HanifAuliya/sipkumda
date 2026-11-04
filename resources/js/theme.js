/*
|--------------------------------------------------------------------------
| Tema terang / gelap
|--------------------------------------------------------------------------
| Class "dark" sudah dipasang lebih awal oleh script inline di <head>
| (lihat components/layouts/theme-script) supaya tidak berkedip. File ini
| hanya menyediakan fungsi toggle untuk tombol di topbar.
*/
const STORAGE_KEY = 'theme';

const prefersDark = () => window.matchMedia('(prefers-color-scheme: dark)').matches;

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

// Ikuti preferensi sistem selama pengguna belum memilih sendiri.
window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
    if (!readTheme()) {
        document.documentElement.classList.toggle('dark', prefersDark());
    }
});
