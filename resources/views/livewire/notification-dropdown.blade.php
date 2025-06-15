<div>
    <div>
        {{-- Ikon Notifikasi dengan Jumlah Notifikasi Belum Dibaca --}}
        <a class="nav-link position-relative" href="#" role="button" data-toggle="dropdown" aria-haspopup="true"
            aria-expanded="false">
            <i class="ni ni-bell-55"></i>
            @if ($unreadCount > 0)
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger text-white"
                    style="font-size: 0.7rem;">
                    {{ $unreadCount }}
                </span>
            @endif
        </a>

        {{-- Dropdown Notifikasi --}}
        <div class="dropdown-menu dropdown-menu-xl dropdown-menu-right py-0 overflow-hidden">
            {{-- Dropdown Header --}}
            <div class="px-3 py-3">
                <h6 class="text-sm text-muted m-0">
                    You have
                    <strong class="text-primary">{{ $unreadCount }}</strong>
                    notifications.
                </h6>
            </div>

            {{-- List Group --}}
            <div class="list-group list-group-flush" style="max-height: 400px; overflow-y: auto;">
                @forelse ($notifications as $notification)
                    <button
                        class="list-group-item list-group-item-action {{ $notification->read_at ? '' : 'font-weight-bold' }}"
                        wire:click.prevent="markAsRead('{{ $notification->id }}')">

                        <div class="d-flex align-items-center justify-content-between">
                            {{-- Konten Notifikasi --}}
                            <div>
                                {{-- Header: Judul Notifikasi --}}
                                <h4 class="mb-0 text-sm {{ $notification->read_at ? 'text-dark' : 'text-primary' }}">
                                    {{ $notification->data['title'] }}
                                </h4>

                                {{-- Pesan --}}
                                <div class="d-flex justify-content-center mr-1">
                                    <p class="text-sm mb-0 mt-2">
                                        {{ $notification->data['message'] }}
                                    </p>
                                </div>


                                {{-- Waktu --}}
                                <small class="text-muted d-block mt-1">
                                    {{ $notification->created_at->diffForHumans() }}
                                </small>
                            </div>

                            {{-- Ikon Berdasarkan Tipe Notifikasi --}}
                            <div class="ms-2">
                                @if (isset($notification->data['type']))
                                    @switch($notification->data['type'])
                                        @case('admin_persetujuan')
                                            <i class="bi bi-exclamation-circle text-danger" style="font-size: 2rem;"></i>
                                        @break

                                        @case('verifikator_detail')
                                            <i class="bi bi-info-circle text-info" style="font-size: 2rem;"></i>
                                        @break

                                        @case('persetujuan_diterima')
                                            <i class="bi bi-check2-circle text-success" style="font-size: 2rem;"></i>
                                        @break

                                        @case('persetujuan_ditolak')
                                            <i class="bi bi-x-circle text-danger" style="font-size: 2rem;"></i>
                                        @break

                                        @case('persetujuan_menunggu')
                                            <i class="bi bi-info-circle text-warning" style="font-size: 2rem;"></i>
                                        @break

                                        @case('pilih_peneliti')
                                            <i class="bi bi-person-exclamation text-danger" style="font-size: 2rem;"></i>
                                        @break

                                        @case('peneliti_dibatalkan')
                                            <i class="bi bi-person-dash text-danger" style="font-size: 2rem;"></i>
                                        @break

                                        @case('peneliti_dipilih')
                                            <i class="bi bi-person-check text-success" style="font-size: 2rem;"></i>
                                        @break

                                        @case('upload_revisi')
                                            <i class="bi bi-person-lock text-default" style="font-size: 2rem;"></i>
                                        @break

                                        @case('reset_validasi')
                                            <i class="bi bi-arrow-clockwise text-warning" style="font-size: 2rem;"></i>
                                        @break

                                        @case('validasi_revisi')
                                            <i class="bi bi-send text-warning" style="font-size: 2rem;"></i> <!-- Ganti Ikon -->
                                        @break

                                        @case('detail_validasi')
                                            <i class="bi bi-send-check text-info" style="font-size: 2rem;"></i>
                                            <!-- Ganti Ikon -->
                                        @break

                                        @case('revisi_ulang')
                                            <i class="bi bi-arrow-repeat text-info" style="font-size: 2rem;"></i>
                                            <!-- Ganti Ikon -->
                                        @break

                                        @case('rancangan_selesai')
                                            <i class="bi bi-check2-square text-success" style="font-size: 2rem;"></i>
                                        @break

                                        @case('uploadUlang_fasilitasi')
                                            <i class="bi bi-info-lg text-warning" style="font-size: 2rem;"></i>
                                        @break

                                        @case('pengajuan_fasilitasi')
                                            <i class="bi bi-file-earmark" style="font-size: 2rem;"></i>
                                        @break

                                        @case('fasilitasi_diterima')
                                            <i class="bi bi-file-earmark-check text-success" style="font-size: 2rem;"></i>
                                        @break

                                        @case('fasilitasi_ditolak')
                                            <i class="bi bi-file-x text-danger" style="font-size: 2rem;"></i>
                                        @break

                                        @case('fasilitasi_verifikasi')
                                            <i class="bi-question-square text-warning" style="font-size: 2rem;"></i>
                                        @break

                                        @default
                                            <i class="bi bi-app-indicator fs-1 text-default" style="font-size: 2rem;"></i>
                                    @endswitch
                                @endif

                            </div>

                        </div>
                    </button>

                    @empty
                        {{-- Jika Tidak Ada Notifikasi --}}
                        <div class="text-center py-3">
                            <p class="text-muted mb-0">Tidak ada notifikasi baru.</p>
                        </div>
                    @endforelse
                    </wire:poll.15000ms=>
                </div>
            </div>
        </div>
    </div>
