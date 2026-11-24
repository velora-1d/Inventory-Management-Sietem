<?php

namespace App\Livewire\AuditLogs;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use PowerComponents\LivewirePowerGrid\Column;
use PowerComponents\LivewirePowerGrid\Facades\PowerGrid;
use PowerComponents\LivewirePowerGrid\PowerGridComponent;
use PowerComponents\LivewirePowerGrid\PowerGridFields;

final class AuditLogTable extends PowerGridComponent
{
    public string $tableName = 'audit-log-table';
    public string $sortField = 'created_at';
    public string $sortDirection = 'desc';

    public function setUp(): array
    {
        return [
            PowerGrid::header()
                ->showSearchInput(),

            PowerGrid::footer()
                ->showPerPage(perPage: 15, perPageValues: [15, 25, 50, 100])
                ->showRecordCount(),
        ];
    }

    public function datasource(): Builder
    {
        return AuditLog::query()->with('actor:id,name');
    }

    public function fields(): PowerGridFields
    {
        return PowerGrid::fields()
            ->add('id')
            ->add('created_at_formatted', fn (AuditLog $model) => Carbon::parse($model->created_at)->format('d/m/Y H:i:s'))
            ->add('actor_name', fn (AuditLog $model) => $model->actor?->name ?? 'System')
            ->add('action_badge', fn (AuditLog $model) => new HtmlString(
                '<span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">'
                . e($model->action) . '</span>'
            ))
            ->add('entity_display', fn (AuditLog $model) => $model->entity_type
                ? class_basename($model->entity_type) . ($model->entity_id ? " #{$model->entity_id}" : '')
                : '-')
            ->add('reason', fn (AuditLog $model) => Str::limit((string) ($model->reason ?: '-'), 60))
            ->add('ip_address', fn (AuditLog $model) => $model->ip_address ?: '-');
    }

    public function columns(): array
    {
        return [
            Column::make('No.', 'id')->index(),
            Column::make('Waktu', 'created_at_formatted', 'created_at')->sortable(),
            Column::make('Aktor', 'actor_name'),
            Column::make('Aksi', 'action_badge', 'action')->searchable()->sortable(),
            Column::make('Entitas', 'entity_display', 'entity_type')->searchable(),
            Column::make('Alasan / Catatan', 'reason')->searchable(),
            Column::make('IP', 'ip_address'),
        ];
    }
}
