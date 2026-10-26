import { Livewire, Alpine } from '../../vendor/livewire/livewire/dist/livewire.esm';
import Swal from 'sweetalert2';

import './theme';
import chart from './alpine/chart';

Alpine.data('chart', chart);

window.Swal = Swal;

/** Chart.js hanya dimuat di halaman yang membutuhkannya: const Chart = await loadChart(); */
window.loadChart = () => import('chart.js/auto').then((module) => module.default);

/*
|--------------------------------------------------------------------------
| Payload event Livewire
|--------------------------------------------------------------------------
| $this->dispatch('x', ['a' => 1]) menghasilkan detail [{a: 1}], sedangkan
| $this->dispatch('x', a: 1) menghasilkan {a: 1}. Normalisasi keduanya.
*/
const payload = (detail) => (Array.isArray(detail) ? detail[0] ?? {} : detail ?? {});

/*
|--------------------------------------------------------------------------
| Modal
|--------------------------------------------------------------------------
| Komponen <x-ui.modal id="..."> mendengarkan event "modal-open" dan
| "modal-close". Dari PHP cukup: $this->dispatch('openModal', 'idModal').
*/
const modalId = (detail) => {
    const data = payload(detail);
    return typeof data === 'string' ? data : data.id ?? data.modalId;
};

window.openModal = (id) => window.dispatchEvent(new CustomEvent('modal-open', { detail: id }));
window.closeModal = (id) => window.dispatchEvent(new CustomEvent('modal-close', { detail: id }));

window.addEventListener('openModal', (e) => window.openModal(modalId(e.detail)));
window.addEventListener('closeModal', (e) => window.closeModal(modalId(e.detail)));

/*
|--------------------------------------------------------------------------
| SweetAlert
|--------------------------------------------------------------------------
| Semua notifikasi dari server bermuara ke dua bentuk: dialog atau toast.
| Nama event lama tetap didukung agar komponen PHP tidak perlu diubah.
*/
const toast = Swal.mixin({
    toast: true,
    position: 'top-end',
    showConfirmButton: false,
    timer: 6000,
    timerProgressBar: true,
});

window.notify = {
    dialog: ({ type = 'info', title = 'Informasi', message = '' } = {}) =>
        Swal.fire({ icon: type, title, text: message, confirmButtonText: 'OK' }),
    toast: ({ type = 'success', title = '', message = '' } = {}) =>
        toast.fire({ icon: type, title, text: message }),
};

['swal:modal', 'swal:error', 'swal:reset', 'swal:ttd', 'swal:notif', 'swal:denied', 'swal:user'].forEach((name) =>
    window.addEventListener(name, (e) => window.notify.dialog(payload(e.detail))),
);

['swal:toast', 'swal:delete', 'swal:modalPersetujuan'].forEach((name) =>
    window.addEventListener(name, (e) => window.notify.toast(payload(e.detail))),
);

window.addEventListener('hideLoadingSwal', () => Swal.close());

/** Tampilkan dialog loading (mis. saat membuat PDF); ditutup oleh event hideLoadingSwal. */
window.showLoading = (title = 'Memproses...', text = 'Mohon tunggu sebentar.') =>
    Swal.fire({ title, text, allowOutsideClick: false, didOpen: () => Swal.showLoading() });

/*
|--------------------------------------------------------------------------
| Konfirmasi
|--------------------------------------------------------------------------
| Pemakaian di Blade:
|   x-on:click="$confirm({ text: 'Hapus data ini?' }).then(ok => ok && $wire.hapus(1))"
*/
window.confirmAction = ({
    title = 'Apakah Anda yakin?',
    text = 'Data yang dihapus tidak dapat dikembalikan!',
    confirmText = 'Ya, lanjutkan',
    cancelText = 'Batal',
    icon = 'warning',
    danger = true,
} = {}) =>
    Swal.fire({
        title,
        text,
        icon,
        showCancelButton: true,
        reverseButtons: true,
        confirmButtonText: confirmText,
        cancelButtonText: cancelText,
        confirmButtonColor: danger ? '#f5365c' : '#5e72e4',
    }).then((result) => result.isConfirmed);

Alpine.magic('confirm', () => window.confirmAction);

Livewire.start();
