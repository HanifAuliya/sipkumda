/*
|--------------------------------------------------------------------------
| Komponen Alpine "chart"
|--------------------------------------------------------------------------
| <div x-data="chart(@js($config))"><canvas x-ref="canvas"></canvas></div>
|
| $config adalah konfigurasi Chart.js biasa (type, data, options). Chart.js
| dimuat saat komponen pertama kali terlihat, dan warnanya mengikuti tema.
*/
const palette = () => {
    const dark = document.documentElement.classList.contains('dark');
    return {
        text: dark ? '#94a3b8' : '#64748b',
        grid: dark ? 'rgba(148, 163, 184, 0.12)' : 'rgba(100, 116, 139, 0.12)',
    };
};

/**
 * Warna seri mengikuti tema: dataset boleh berisi warna: { terang, gelap } (dipilih dari palet yang
 * sudah divalidasi), dan celah: true untuk garis 2px sewarna permukaan kartu di antara segmen.
 */
const warnai = (config) => {
    const dark = document.documentElement.classList.contains('dark');
    const permukaan = dark ? '#1a2233' : '#ffffff';

    for (const ds of config.data?.datasets ?? []) {
        if (ds.warna) {
            ds.backgroundColor = dark ? ds.warna.gelap : ds.warna.terang;
            ds.hoverBackgroundColor = ds.backgroundColor;
            delete ds.warna;
        }
        if (ds.celah) {
            ds.borderColor = permukaan;
            ds.borderWidth = 2;
            delete ds.celah;
        }
    }

    return config;
};

export default (config) => {
    // Sengaja di luar data Alpine. Lewat proxy reaktif, destroy() tidak melepas grafik dari
    // daftar animasi Chart.js, sehingga grafik yang sudah dihancurkan tetap digambar dan memunculkan error.
    let grafik = null;

    return {
        init() {
            const observer = new IntersectionObserver(
                (entries) => {
                    if (entries.some((entry) => entry.isIntersecting)) {
                        observer.disconnect();
                        this.render();
                    }
                },
                { threshold: 0.2 },
            );
            observer.observe(this.$el);

            this.onTheme = () => this.render();
            window.addEventListener('theme-changed', this.onTheme);
        },

        async render() {
            const Chart = await window.loadChart();
            const { text, grid } = palette();

            Chart.defaults.font.family = "'Open Sans Variable', sans-serif";
            Chart.defaults.color = text;
            Chart.defaults.borderColor = grid;

            grafik?.destroy();
            grafik = new Chart(this.$refs.canvas, warnai(structuredClone(config)));
        },

        destroy() {
            window.removeEventListener('theme-changed', this.onTheme);
            grafik?.destroy();
        },
    };
};
