<div>
    @includeIf(data_get($setUp, 'header.includeViewOnTop'))

    <div class="mb-3 flex flex-col md:flex-row w-full gap-3 justify-between items-center">
        <!-- Left Side: Actions & Export -->
        <div class="flex flex-row items-center gap-2 w-full md:w-auto">
            <div x-data="pgRenderActions">
                <span class="pg-actions" x-html="toHtml"></span>
            </div>
            <div class="flex flex-row items-center text-sm flex-wrap gap-2">
                @if (data_get($setUp, 'exportable'))
                    <div id="pg-header-export">
                        @include(data_get($theme, 'root') . '.header.export')
                    </div>
                @endif
                @includeIf(data_get($theme, 'root') . '.header.toggle-columns')
                @includeIf(data_get($theme, 'root') . '.header.soft-deletes')
            </div>
            @includeWhen(boolval(data_get($setUp, 'header.wireLoading')),
                data_get($theme, 'root') . '.header.loading')
        </div>

        <!-- Right Side: Filters & Search Aligned -->
        <div class="flex flex-row items-end gap-3 w-full md:w-auto justify-end flex-nowrap shrink-0">
            @if (config('livewire-powergrid.filter') === 'outside')
                @php
                    $filtersFromColumns = $columns->filter(fn($column) => filled(data_get($column, 'filters')));
                @endphp
                @if ($filtersFromColumns->count() > 0)
                    <div class="flex flex-row items-end gap-2 flex-nowrap shrink-0">
                        @php
                            $componentFilters = collect($this->filters());
                            $filterOrderMap = $componentFilters->pluck('field')->flip();
                            $sortedFilters = $filtersFromColumns->sortBy(function ($column) use ($filterOrderMap) {
                                $fieldName = data_get($column, 'filters.field');
                                return $filterOrderMap->get($fieldName, 999);
                            });
                        @endphp
                        @foreach ($sortedFilters as $column)
                            @php
                                $filter = data_get($column, 'filters');
                                $title = data_get($column, 'title');
                                $className = str(data_get($filter, 'className'));
                            @endphp
                            <div class="w-36 text-xs shrink-0 pg-filter-wrapper">
                                @if ($className->contains('FilterMultiSelect'))
                                    <x-livewire-powergrid::inputs.select
                                        :inline="false"
                                        :theme="$theme"
                                        :table-name="$tableName"
                                        :filter="$filter"
                                        :title="$title"
                                        :initial-values="data_get(data_get($filter, 'multi_select'), data_get($filter, 'field'), [])"
                                    />
                                @elseif ($className->contains(['FilterDateTimePicker', 'FilterDatePicker']))
                                    @includeIf(theme_style($theme, 'filterDatePicker.view'), [
                                        'filter' => $filter,
                                        'tableName' => $tableName,
                                        'classAttr' => 'w-full',
                                        'type' => $className->contains('FilterDateTimePicker') ? 'datetime' : 'date',
                                    ])
                                @elseif ($className->contains(['FilterSelect', 'FilterEnumSelect']))
                                    @includeIf(theme_style($theme, 'filterSelect.view'), [
                                        'filter' => $filter,
                                    ])
                                @elseif ($className->contains('FilterNumber'))
                                    @includeIf(theme_style($theme, 'filterNumber.view'), [
                                        'filter' => $filter,
                                    ])
                                @elseif ($className->contains('FilterInputText'))
                                    @includeIf(theme_style($theme, 'filterInputText.view'), [
                                        'filter' => $filter,
                                    ])
                                @elseif ($className->contains('FilterBoolean'))
                                    @includeIf(theme_style($theme, 'filterBoolean.view'), [
                                        'filter' => $filter,
                                    ])
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endif
            @endif

            <!-- Search Input -->
            <div class="min-w-[180px] md:w-64 shrink-0">
                @include(data_get($theme, 'root') . '.header.search')
            </div>
        </div>
    </div>

    @includeIf(data_get($theme, 'root') . '.header.enabled-filters')

    @includeWhen(data_get($setUp, 'exportable.batchExport.queues', 0), data_get($theme, 'root') . '.header.batch-exporting')
    @includeWhen($multiSort, data_get($theme, 'root') . '.header.multi-sort')
    @includeIf(data_get($setUp, 'header.includeViewOnBottom'))
    @includeIf(data_get($theme, 'root') . '.header.message-soft-deletes')
</div>
