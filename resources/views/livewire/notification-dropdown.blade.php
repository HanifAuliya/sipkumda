@php
    // Ikon & warna per jenis notifikasi.
    $icons = [
        'admin_persetujuan' => ['exclamation-circle', 'text-rose-500 bg-rose-50'],
        'verifikator_detail' => ['info-circle', 'text-cyan-500 bg-cyan-50'],
        'persetujuan_diterima' => ['check2-circle', 'text-emerald-500 bg-emerald-50'],
        'persetujuan_ditolak' => ['x-circle', 'text-rose-500 bg-rose-50'],
        'persetujuan_menunggu' => ['hourglass-split', 'text-orange-500 bg-orange-50'],
        'pilih_peneliti' => ['person-exclamation', 'text-rose-500 bg-rose-50'],
        'peneliti_dibatalkan' => ['person-dash', 'text-rose-500 bg-rose-50'],
        'peneliti_dipilih' => ['person-check', 'text-emerald-500 bg-emerald-50'],
        'upload_revisi' => ['person-lock', 'text-navy-700 bg-navy-50'],
        'reset_validasi' => ['arrow-clockwise', 'text-orange-500 bg-orange-50'],
        'validasi_revisi' => ['send', 'text-orange-500 bg-orange-50'],
        'detail_validasi' => ['send-check', 'text-cyan-500 bg-cyan-50'],
        'revisi_ulang' => ['arrow-repeat', 'text-cyan-500 bg-cyan-50'],
        'rancangan_selesai' => ['check2-square', 'text-emerald-500 bg-emerald-50'],
        'uploadUlang_fasilitasi' => ['info-lg', 'text-orange-500 bg-orange-50'],
        'pengajuan_fasilitasi' => ['file-earmark', 'text-primary-500 bg-primary-50'],
        'fasilitasi_diterima' => ['file-earmark-check', 'text-emerald-500 bg-emerald-50'],
        'fasilitasi_ditolak' => ['file-x', 'text-rose-500 bg-rose-50'],
        'fasilitasi_verifikasi' => ['question-square', 'text-orange-500 bg-orange-50'],
    ];
    $fallback = ['bell', 'text-slate-500 bg-slate-100'];
@endphp

<div wire:poll.30s>
    <x-ui.dropdown width="w-[22rem] max-w-[calc(100vw-2rem)]">
        <x-slot:trigger>
            <button type="button" class="relative rounded-lg p-2 text-white/80 transition hover:bg-white/10 hover:text-white"
                aria-label="Notifikasi ({{ $unreadCount }} belum dibaca)">
                <i class="bi bi-bell text-lg leading-none"></i>
                @if ($unreadCount > 0)
                    <span class="absolute -top-0.5 -right-0.5 grid min-w-5 place-items-center rounded-full bg-rose-500 px-1 text-[0.65rem] leading-5 font-bold text-white ring-2 ring-navy-900">
                        {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                    </span>
                @endif
            </button>
        </x-slot:trigger>

        <div class="flex items-center justify-between px-3 pt-1.5 pb-2.5">
            <p class="text-sm font-semibold text-navy-900 dark:text-white">Notifikasi</p>
            <x-ui.badge :color="$unreadCount ? 'primary' : 'slate'">{{ $unreadCount }} belum dibaca</x-ui.badge>
        </div>

        <div class="-mx-1.5 max-h-[26rem] overflow-y-auto border-t border-slate-100 dark:border-navy-700">
            @forelse ($notifications as $notification)
                @php [$icon, $color] = $icons[$notification->data['type']] ?? $fallback; @endphp
                <button type="button" wire:key="notif-{{ $notification->id }}"
                    wire:click="markAsRead('{{ $notification->id }}')"
                    @class([
                        'flex w-full items-start gap-3 px-4 py-3 text-left transition hover:bg-slate-50 dark:hover:bg-navy-700/50',
                        'bg-primary-50/50 dark:bg-primary-500/5' => ! $notification->read_at,
                    ])>
                    <span class="grid size-9 shrink-0 place-items-center rounded-full {{ $color }} dark:bg-white/5">
                        <i class="bi bi-{{ $icon }}"></i>
                    </span>
                    <span class="min-w-0 flex-1">
                        <span @class([
                            'block text-sm',
                            'font-semibold text-navy-900 dark:text-white' => ! $notification->read_at,
                            'font-medium text-slate-700 dark:text-slate-300' => $notification->read_at,
                        ])>{{ $notification->data['title'] ?? 'Notifikasi' }}</span>
                        <span class="mt-0.5 block text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $notification->data['message'] ?? '' }}</span>
                        <span class="mt-1 block text-[0.7rem] text-slate-400">{{ $notification->created_at->diffForHumans() }}</span>
                    </span>
                    @unless ($notification->read_at)
                        <span class="mt-1.5 size-2 shrink-0 rounded-full bg-primary-500"></span>
                    @endunless
                </button>
            @empty
                <x-ui.empty-state icon="bell-slash" title="Tidak ada notifikasi." class="py-8" />
            @endforelse
        </div>
    </x-ui.dropdown>
</div>
