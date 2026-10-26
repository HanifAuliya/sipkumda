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

export default (config) => ({
    chart: null,

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

        this.chart?.destroy();
        this.chart = new Chart(this.$refs.canvas, structuredClone(config));
    },

    destroy() {
        window.removeEventListener('theme-changed', this.onTheme);
        this.chart?.destroy();
    },
});
