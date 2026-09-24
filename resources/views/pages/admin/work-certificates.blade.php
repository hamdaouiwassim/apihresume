{{-- Port of pages/admin/WorkCertificatesManagement.jsx --}}
@php
    $config = ['endpoint' => 'admin/work-certificates', 'messages' => [
        'loadError' => 'Failed to load work certificates', 'viewError' => 'Failed to load certificate details',
        'confirmTitle' => 'Delete work certificate', 'confirmMessage' => 'This permanently removes the certificate for the user. Continue?',
        'deleted' => 'Work certificate deleted', 'deleteError' => 'Failed to delete work certificate',
    ]];
@endphp
<x-layouts.admin title="Work certificates | Admin | HResume">
    <div class="space-y-6" x-data="adminDocuments(@js($config))">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div>
                <div class="flex items-center gap-2 text-indigo-600 font-semibold text-sm mb-1">
                    <x-lucide-scroll-text class="h-5 w-5 shrink-0" />
                    Work certifications
                </div>
                <h1 class="text-3xl font-bold text-gray-900">Work certificates</h1>
                <p class="text-gray-600 mt-1">View and remove user-generated employment certificates.</p>
            </div>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex flex-col sm:flex-row gap-3">
                <div class="flex-1 relative min-w-0">
                    <x-lucide-search class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                    <input x-model="search" @keydown.enter="load(1)" placeholder="Search by title, company, employee, user…" class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg text-sm" />
                </div>
                <button type="button" @click="load(1)" class="inline-flex items-center justify-center px-4 py-2.5 rounded-lg bg-indigo-600 text-white font-medium text-sm shrink-0">Search</button>
            </div>
        </div>

        <div class="h-48 flex items-center justify-center" x-show="loading">
            <x-lucide-loader-2 class="h-8 w-8 animate-spin text-indigo-600" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-show="!loading" x-cloak>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-900" x-text="'Certificates (' + (pagination.total || items.length) + ')'"></div>
                <div class="divide-y divide-gray-100 max-h-[540px] overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <div class="px-4 py-3 flex items-center justify-between gap-3">
                            <div class="min-w-0">
                                <p class="font-semibold text-gray-900 truncate" x-text="item.title"></p>
                                <p class="text-sm text-gray-500 truncate" x-text="(item.user?.name || 'Unknown') + ' • ' + item.company_name"></p>
                            </div>
                            <div class="flex shrink-0 items-center gap-0.5">
                                <button type="button" @click="view(item.id)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-gray-100 text-gray-700 hover:bg-gray-200" aria-label="View details"><x-lucide-eye class="h-4 w-4 shrink-0" /></button>
                                <button type="button" @click="remove(item)" class="inline-flex h-9 w-9 items-center justify-center rounded-lg bg-red-50 text-red-600 hover:bg-red-100" aria-label="Delete"><x-lucide-trash-2 class="h-4 w-4 shrink-0" /></button>
                            </div>
                        </div>
                    </template>
                    <p x-show="items.length === 0" class="p-6 text-center text-gray-500">No work certificates found.</p>
                </div>
                <div x-show="pagination.total > 0"><x-admin.pagination label="certificates" /></div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5 min-h-[200px]">
                <template x-if="selected">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900" x-text="selected.title"></h2>
                        <p class="text-sm text-gray-500 mt-1" x-text="(selected.user?.name || 'Unknown user') + (selected.user?.email ? ' • ' + selected.user.email : '')"></p>
                        <div class="mt-4 space-y-2 text-sm text-gray-700">
                            <p><span class="font-medium text-gray-900">Employee:</span> <span x-text="selected.employee_name"></span></p>
                            <p x-show="selected.employee_job_title"><span class="font-medium text-gray-900">Role:</span> <span x-text="selected.employee_job_title"></span></p>
                            <p><span class="font-medium text-gray-900">Company:</span> <span x-text="selected.company_name"></span></p>
                            <p><span class="font-medium text-gray-900">Period:</span> <span x-text="shortDate(selected.employment_start) + (selected.is_current_employment ? ' — present' : (selected.employment_end ? ' — ' + shortDate(selected.employment_end) : ''))"></span></p>
                            <p><span class="font-medium text-gray-900">Locale:</span> <span x-text="selected.locale || 'en'"></span></p>
                        </div>
                        <div x-show="selected.company_address" class="mt-4 p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-700 whitespace-pre-wrap max-h-32 overflow-y-auto" x-text="selected.company_address"></div>
                        <div x-show="selected.duties_summary" class="mt-4">
                            <p class="text-xs font-semibold text-gray-500 uppercase tracking-wide mb-1">Duties</p>
                            <div class="p-3 bg-gray-50 rounded-lg border border-gray-100 text-sm text-gray-800 whitespace-pre-wrap max-h-40 overflow-y-auto" x-text="selected.duties_summary"></div>
                        </div>
                    </div>
                </template>
                <p x-show="!selected" class="text-gray-500">Select a certificate to view details.</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
