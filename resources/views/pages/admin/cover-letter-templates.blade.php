{{-- Port of pages/admin/CoverLetterTemplatesManagement.jsx --}}
@php
    $field = 'w-full px-4 py-2.5 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all';
    $th = 'px-6 py-4 text-xs font-bold text-slate-500 uppercase tracking-wider';
@endphp
<x-layouts.admin title="Cover Letter Templates | Admin | HResume">
    <div x-data="adminClTemplates">
        <div class="min-h-screen flex items-center justify-center" x-show="loading && items.length === 0">
            <x-lucide-loader-2 class="h-12 w-12 animate-spin text-purple-600" />
        </div>

        <div x-show="!(loading && items.length === 0)" x-cloak>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8">
                <div>
                    <h1 class="text-3xl font-bold text-slate-900 mb-2">Cover Letter Templates</h1>
                    <p class="text-slate-600">Manage professional templates for users</p>
                </div>
                <button type="button" @click="openNew()" class="mt-4 sm:mt-0 inline-flex items-center px-6 py-3 bg-blue-600 text-white rounded-xl font-semibold hover:bg-blue-700 transition-all shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-plus class="h-5 w-5 mr-2" /> New Template
                </button>
            </div>

            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1 max-w-md">
                    <x-lucide-search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-slate-400" />
                    <input type="text" placeholder="Search templates..." x-model.debounce.300ms="search" class="w-full pl-12 pr-4 py-3 border border-slate-200 rounded-xl focus:ring-4 focus:ring-blue-100 focus:border-blue-500 outline-none transition-all" />
                </div>
                <div class="flex gap-2">
                    @foreach (['All' => 'All Languages', 'en' => 'EN', 'fr' => 'FR'] as $lang => $label)
                        <button type="button" @click="language = '{{ $lang }}'" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all"
                            :class="language === '{{ $lang }}' ? 'bg-blue-600 text-white shadow-md' : 'bg-white text-slate-600 hover:bg-slate-50 border border-slate-200'">{{ $label }}</button>
                    @endforeach
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-sm border border-slate-200 overflow-hidden">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="bg-slate-50/50 border-b border-slate-200">
                            <th class="{{ $th }}">Template Name</th>
                            <th class="{{ $th }}">Job Type</th>
                            <th class="{{ $th }}">Language</th>
                            <th class="{{ $th }}">Status</th>
                            <th class="{{ $th }} text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100">
                        <template x-for="template in items" :key="template.id">
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-6 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="p-2 bg-blue-50 rounded-lg text-blue-600"><x-lucide-file-text class="h-5 w-5" /></div>
                                        <div>
                                            <h3 class="font-bold text-slate-900" x-text="template.name"></h3>
                                            <p class="text-xs text-slate-500 truncate max-w-xs" x-text="template.subject"></p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4"><span class="text-sm font-medium text-slate-600" x-text="template.job_type"></span></td>
                                <td class="px-6 py-4"><div class="flex items-center gap-1.5 text-sm font-medium text-slate-600 lowercase"><x-lucide-globe class="h-4 w-4" /><span x-text="template.language"></span></div></td>
                                <td class="px-6 py-4">
                                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium" :class="template.is_active ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-700'" x-text="template.is_active ? 'Active' : 'Inactive'"></span>
                                </td>
                                <td class="px-6 py-4 text-right">
                                    <div class="flex items-center justify-end gap-2 opacity-0 group-hover:opacity-100 transition-opacity">
                                        <button type="button" @click="openEdit(template)" class="p-2 text-slate-400 hover:text-blue-600 hover:bg-blue-50 rounded-lg transition-all" title="Edit Template"><x-lucide-edit-2 class="h-4 w-4" /></button>
                                        <button type="button" @click="remove(template)" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-all" title="Delete Template"><x-lucide-trash-2 class="h-4 w-4" /></button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>

                <div class="p-12 text-center" x-show="items.length === 0 && !loading">
                    <x-lucide-file-text class="h-12 w-12 text-slate-300 mx-auto mb-4" />
                    <p class="text-slate-500 font-medium">No templates found</p>
                </div>
                <div x-show="!loading && pagination.total > 0"><x-admin.pagination label="templates" /></div>
            </div>
        </div>

        <div class="fixed inset-0 z-50 overflow-y-auto bg-slate-900/50 backdrop-blur-sm flex items-center justify-center p-4" x-show="modal" x-cloak>
            <div class="bg-white rounded-3xl shadow-2xl w-full max-w-3xl overflow-hidden">
                <div class="p-6 border-b border-slate-100 flex items-center justify-between bg-slate-50/50">
                    <h2 class="text-xl font-bold text-slate-900" x-text="modal === 'edit' ? 'Edit Template' : 'New Cover Letter Template'"></h2>
                    <button type="button" @click="close()" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-white transition-all"><x-lucide-x class="h-6 w-6" /></button>
                </div>
                <form @submit.prevent="save()" class="p-6 space-y-6">
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Display Name *</label>
                            <input type="text" x-model="form.name" class="{{ $field }}" placeholder="e.g., Software Engineer (English)" required />
                            <p x-show="errors.name" class="mt-1 text-xs text-red-500" x-text="errors.name && errors.name[0]"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Job Type Identifier *</label>
                            <input type="text" x-model="form.job_type" class="{{ $field }}" placeholder="e.g., software_engineer" required />
                            <p x-show="errors.job_type" class="mt-1 text-xs text-red-500" x-text="errors.job_type && errors.job_type[0]"></p>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-6">
                        <div>
                            <label class="block text-sm font-semibold text-slate-700 mb-2">Language *</label>
                            <select x-model="form.language" class="{{ $field }} bg-white" required>
                                <option value="en">English (EN)</option>
                                <option value="fr">French (FR)</option>
                            </select>
                        </div>
                        <div class="flex items-center pt-8">
                            <label class="flex items-center gap-3 cursor-pointer group">
                                <div class="relative">
                                    <input type="checkbox" class="sr-only" x-model="form.is_active" />
                                    <div class="w-12 h-6 rounded-full transition-colors" :class="form.is_active ? 'bg-blue-600' : 'bg-slate-200'"></div>
                                    <div class="absolute top-1 left-1 bg-white w-4 h-4 rounded-full transition-transform" :class="form.is_active ? 'translate-x-6' : ''"></div>
                                </div>
                                <span class="text-sm font-semibold text-slate-700">Active Template</span>
                            </label>
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Email Subject Line *</label>
                        <input type="text" x-model="form.subject" class="{{ $field }}" placeholder="e.g., Application for [Position] - [Your Name]" required />
                        <p x-show="errors.subject" class="mt-1 text-xs text-red-500" x-text="errors.subject && errors.subject[0]"></p>
                    </div>
                    <div>
                        <label class="block text-sm font-semibold text-slate-700 mb-2">Template Content *</label>
                        <textarea x-model="form.content" rows="10" class="{{ $field }} resize-none font-mono text-sm" placeholder="Enter the template body text here..." required></textarea>
                        <div class="mt-2 flex justify-end"><x-enhance-button value="form.content" context="cover letter template content" /></div>
                        <p x-show="errors.content" class="mt-1 text-xs text-red-500" x-text="errors.content && errors.content[0]"></p>
                        <p class="mt-2 text-xs text-slate-400">Use placeholders like [Position], [Company Name], [Your Name], etc.</p>
                    </div>
                    <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-100 bg-slate-50/50 p-6 -mx-6 -mb-6">
                        <button type="button" @click="close()" class="px-6 py-2.5 font-semibold text-slate-600 hover:bg-white rounded-xl transition-all">Cancel</button>
                        <button type="submit" :disabled="saving" class="px-8 py-2.5 bg-blue-600 text-white rounded-xl font-bold hover:bg-blue-700 transition-all shadow-lg hover:shadow-blue-200 disabled:opacity-50 flex items-center gap-2">
                            <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="saving" />
                            <x-lucide-save class="h-4 w-4" x-show="!saving" />
                            <span x-text="modal === 'edit' ? 'Update Template' : 'Create Template'"></span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-layouts.admin>
