@props(['label' => 'items', 'perPageSelector' => true])
{{-- Port of components/admin/AdminListPagination.jsx; reads the parent adminList() Alpine state --}}
<div {{ $attributes->class('flex flex-col gap-3 border-t border-gray-200 bg-gray-50/50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between sm:px-6') }}>
    <div class="text-sm text-gray-700">
        <template x-if="pagination.total > 0">
            <span>Showing <span class="font-medium" x-text="fromRow"></span> to <span class="font-medium" x-text="toRow"></span> of <span class="font-medium" x-text="pagination.total"></span> {{ $label }}</span>
        </template>
        <template x-if="pagination.total === 0">
            <span class="text-gray-500">No {{ $label }}</span>
        </template>
    </div>

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:gap-4">
        @if ($perPageSelector)
            <div class="flex items-center gap-2">
                <span class="text-xs font-semibold uppercase tracking-wide text-gray-500 whitespace-nowrap">Rows</span>
                <div class="inline-flex rounded-lg border border-gray-200 bg-white p-0.5 shadow-sm">
                    @foreach ([10, 25, 50, 100] as $size)
                        <button type="button" @click="setPerPage({{ $size }})"
                            class="min-w-[2.5rem] rounded-md px-2.5 py-1.5 text-sm font-semibold transition-colors"
                            :class="perPage === {{ $size }} ? 'bg-purple-600 text-white shadow-sm' : 'text-gray-600 hover:bg-gray-100'"
                            :aria-pressed="perPage === {{ $size }}" aria-label="Show {{ $size }} rows per page">{{ $size }}</button>
                    @endforeach
                </div>
            </div>
        @endif

        <div class="flex items-center gap-2" x-show="pagination.last_page > 1">
            <button type="button" @click="load(pagination.current_page - 1)" :disabled="pagination.current_page <= 1"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">Previous</button>
            <span class="text-sm text-gray-600 tabular-nums whitespace-nowrap" x-text="'Page ' + pagination.current_page + ' / ' + pagination.last_page"></span>
            <button type="button" @click="load(pagination.current_page + 1)" :disabled="pagination.current_page >= pagination.last_page"
                class="rounded-lg border border-gray-300 bg-white px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50 transition-colors">Next</button>
        </div>
    </div>
</div>
