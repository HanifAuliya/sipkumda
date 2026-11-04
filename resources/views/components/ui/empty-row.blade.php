{{-- Baris "tidak ada data" untuk <x-ui.table>. --}}
@props(['colspan' => 1, 'message' => 'Tidak ada data.', 'icon' => 'inbox'])

<tr class="hover:bg-transparent!">
    <td colspan="{{ $colspan }}" class="py-12! text-center">
        <x-ui.empty-state :icon="$icon" :title="$message" />
    </td>
</tr>
