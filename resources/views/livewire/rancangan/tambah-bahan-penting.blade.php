@section('title', 'Template Bahan Penting')

<div>
    <x-ui.page-header title="Template Bahan Penting" icon="list-task"
        description="Template yang dapat diunduh perangkat daerah dari menu Rancanganku" />

    <div class="grid gap-6 lg:grid-cols-5">
        <x-ui.card title="Daftar Bahan Penting" description="Tampil di tombol “Bahan Penting” untuk Perangkat Daerah." class="lg:col-span-3">
            <x-ui.table>
                <x-slot:head>
                    <th class="w-12">No</th>
                    <th>Nama Tombol</th>
                    <th class="text-right">Aksi</th>
                </x-slot:head>

                @forelse ($materials as $material)
                    <tr wire:key="material-{{ $material->id }}">
                        <td class="text-slate-400">{{ $loop->iteration }}</td>
                        <td>
                            <span class="flex items-center gap-2.5 font-medium text-navy-900 dark:text-white">
                                <i class="{{ $this->getFileIcon($material->file_path) }} text-lg"></i>
                                {{ $material->button_name }}
                            </span>
                        </td>
                        <td>
                            <div class="flex justify-end gap-1.5">
                                <x-ui.button variant="soft-primary" size="sm" icon="download" wire:click="download({{ $material->id }})">Unduh</x-ui.button>
                                <x-ui.button variant="soft-danger" size="sm" square icon="trash" title="Hapus"
                                    x-on:click="$confirm({ text: {{ \Illuminate\Support\Js::from('Template “' . $material->button_name . '” akan dihapus.') }}, confirmText: 'Ya, hapus' })
                                        .then(ok => ok && $wire.deleteMaterial({{ $material->id }}))" />
                            </div>
                        </td>
                    </tr>
                @empty
                    <x-ui.empty-row colspan="3" icon="file-earmark" message="Belum ada bahan penting." />
                @endforelse
            </x-ui.table>
        </x-ui.card>

        <x-ui.card title="Tambah Bahan Penting" class="lg:col-span-2 self-start">
            <form wire:submit="addMaterial" class="space-y-5">
                <x-form.field label="Nama Tombol" for="buttonName" error="buttonName" required>
                    <x-form.input id="buttonName" wire:model="buttonName" placeholder="Contoh: Template Matrik" />
                </x-form.field>

                <x-form.upload model="file" :file="$file" label="File" accept=".doc,.docx,.pdf" hint="Word atau PDF, maks. 20MB" required />

                <div class="flex justify-end">
                    <x-ui.button type="submit" variant="navy" icon="plus-lg" loading="addMaterial">Tambah</x-ui.button>
                </div>
            </form>
        </x-ui.card>
    </div>
</div>
