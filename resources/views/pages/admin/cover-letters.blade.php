{{-- Port of pages/admin/CoverLettersManagement.jsx --}}
@php
    $config = ['endpoint' => 'admin/cover-letters', 'messages' => [
        'loadError' => 'Failed to load cover letters', 'viewError' => 'Failed to load cover letter details',
        'confirmTitle' => 'Delete Cover Letter', 'confirmMessage' => 'Are you sure you want to delete this cover letter?',
        'deleted' => 'Cover letter deleted', 'deleteError' => 'Failed to delete cover letter',
    ]];
@endphp
<x-layouts.admin title="Generated Cover Letters | Admin | HResume">
    <div class="space-y-6" x-data="adminDocuments(@js($config))">
        <div>
            <h1 class="text-3xl font-bold text-gray-900">Generated Cover Letters</h1>
            <p class="text-gray-600 mt-1">Manage all generated cover letters.</p>
        </div>

        <div class="bg-white p-4 rounded-xl border border-gray-100 shadow-sm">
            <div class="flex gap-3">
                <div class="flex-1 relative">
                    <x-lucide-search class="h-4 w-4 text-gray-400 absolute left-3 top-1/2 -translate-y-1/2" />
                    <input x-model="search" placeholder="Search by title, recipient, user..." class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-lg" />
                </div>
                <button type="button" @click="load(1)" class="px-4 py-2.5 rounded-lg bg-purple-600 text-white font-medium">Search</button>
            </div>
        </div>

        <div class="h-48 flex items-center justify-center" x-show="loading">
            <x-lucide-loader-2 class="h-8 w-8 animate-spin text-purple-600" />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-2 gap-6" x-show="!loading" x-cloak>
            <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="px-4 py-3 border-b border-gray-100 font-semibold text-gray-900" x-text="'Cover Letters (' + (pagination.total || items.length) + ')'"></div>
                <div class="divide-y divide-gray-100 max-h-[540px] overflow-y-auto">
                    <template x-for="item in items" :key="item.id">
                        <div class="px-4 py-3 flex items-center justify-between">
                            <div>
                                <p class="font-semibold text-gray-900" x-text="item.title"></p>
                                <p class="text-sm text-gray-500" x-text="(item.user?.name || 'Unknown') + ' • ' + (item.recipient_company || 'No company')"></p>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" @click="view(item.id)" class="p-2 rounded bg-gray-100 text-gray-700"><x-lucide-eye class="h-4 w-4" /></button>
                                <button type="button" @click="remove(item)" class="p-2 rounded bg-red-50 text-red-600"><x-lucide-trash-2 class="h-4 w-4" /></button>
                            </div>
                        </div>
                    </template>
                    <p x-show="items.length === 0" class="p-6 text-center text-gray-500">No cover letters found.</p>
                </div>
                <div x-show="pagination.total > 0"><x-admin.pagination label="cover letters" /></div>
            </div>

            <div class="bg-white rounded-xl border border-gray-100 shadow-sm p-5">
                <template x-if="selected">
                    <div>
                        <h2 class="text-xl font-bold text-gray-900" x-text="selected.title"></h2>
                        <p class="text-sm text-gray-500 mt-1" x-text="(selected.user?.name || 'Unknown user') + ' • ' + (selected.recipient_name || 'No recipient')"></p>
                        <div class="mt-4 space-y-2 text-sm text-gray-700">
                            <p><span class="font-medium">Company:</span> <span x-text="selected.recipient_company || 'N/A'"></span></p>
                            <p><span class="font-medium">Email:</span> <span x-text="selected.recipient_email || 'N/A'"></span></p>
                            <p><span class="font-medium">Style:</span> <span x-text="selected.style || 'classic'"></span></p>
                        </div>
                        <div class="mt-5 p-4 bg-gray-50 rounded-lg border border-gray-100 max-h-[350px] overflow-y-auto whitespace-pre-wrap text-sm text-gray-800" x-text="selected.content || 'No content'"></div>
                    </div>
                </template>
                <p x-show="!selected" class="text-gray-500">Select a cover letter to view details.</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
