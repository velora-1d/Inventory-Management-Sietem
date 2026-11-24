<?php

namespace App\Livewire\Concerns;

use Livewire\Attributes\On;
use PowerComponents\LivewirePowerGrid\Button;

/**
 * Provides bulk-delete header button, DeleteModal confirmation trigger,
 * and batch deletion handling for PowerGrid tables with checkboxes.
 */
trait HandlesBulkDelete
{
    protected function bulkDeleteButton(): Button
    {
        return Button::add('bulk-delete')
            ->slot('<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:14px;height:14px;display:inline;vertical-align:middle;margin-right:4px;"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg> Hapus Terpilih')
            ->class('bg-red-600 hover:bg-red-700 text-white px-3 py-2 rounded-md font-medium text-xs inline-flex items-center shadow-sm transition-colors')
            ->dispatch('request-bulk-delete', []);
    }

    public function header(): array
    {
        return [
            $this->bulkDeleteButton(),
        ];
    }

    #[On('request-bulk-delete')]
    public function requestBulkDelete(): void
    {
        $ids = array_values(array_filter($this->checkboxValues ?? []));

        if (empty($ids)) {
            $this->dispatch('toast', message: 'Pilih minimal 1 data terlebih dahulu.', type: 'warning');
            return;
        }

        $count = count($ids);

        $this->dispatch(
            'open-delete-modal',
            component: $this->getName(),
            method: 'confirm-bulk-delete',
            params: ['ids' => $ids],
            title: "Hapus {$count} Data Terpilih?",
            description: "Apakah Anda yakin ingin menghapus {$count} data yang dipilih? Tindakan ini tidak dapat dibatalkan.",
            confirmButtonText: 'Ya, Hapus Semua'
        );
    }

    #[On('confirm-bulk-delete')]
    public function confirmBulkDelete(array $ids = []): void
    {
        $deleted = 0;
        $failed = 0;
        $lastError = '';

        foreach ($ids as $id) {
            try {
                if ($this->performBulkDeleteItem($id)) {
                    $deleted++;
                }
            } catch (\Throwable $e) {
                $failed++;
                $lastError = $e->getMessage();
            }
        }

        $this->checkboxValues = [];
        $this->dispatch('pg:eventRefresh-' . $this->tableName);

        if ($deleted > 0 && $failed === 0) {
            $this->dispatch('toast', message: "{$deleted} data berhasil dihapus.", type: 'success');
        } elseif ($deleted > 0 && $failed > 0) {
            $this->dispatch('toast', message: "{$deleted} data berhasil dihapus, {$failed} gagal ({$lastError}).", type: 'warning');
        } elseif ($failed > 0) {
            $this->dispatch('toast', message: "Gagal menghapus data: {$lastError}", type: 'error');
        }
    }

    abstract protected function performBulkDeleteItem(int|string $id): bool;
}
