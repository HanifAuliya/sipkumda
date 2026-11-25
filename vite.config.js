import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    // Paket yang baru diimpor saat dipakai (editor lampiran, gabung PDF). Disiapkan sejak server dev menyala,
    // supaya tidak dijawab 504 atau memuat ulang halaman ketika editornya pertama dibuka.
    optimizeDeps: {
        include: [
            '@tiptap/core', '@tiptap/starter-kit', '@tiptap/extension-list', '@tiptap/extension-table',
            '@tiptap/extension-text-align', '@tiptap/extension-text-style', '@tiptap/extension-subscript',
            '@tiptap/extension-superscript', '@tiptap/pm/model', '@tiptap/pm/tables', 'pdf-lib',
            'pdfjs-dist/legacy/build/pdf.min.mjs', 'pdfjs-dist/legacy/build/pdf.worker.min.mjs',
        ],
    },
});
