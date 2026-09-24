{{-- Port of pages/admin/TemplatesManagement.jsx --}}
<x-layouts.admin title="Templates | Admin | HResume">
    <div x-data="adminTemplates">
        <div class="flex items-center justify-center min-h-[400px]" x-show="loading && items.length === 0">
            <x-lucide-loader-2 class="h-12 w-12 animate-spin text-purple-600" />
        </div>

        <div class="max-w-7xl mx-auto" x-show="!(loading && items.length === 0)" x-cloak>
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center mb-8 animate-slide-in">
                <div>
                    <h1 class="text-4xl font-bold text-gray-900 mb-2">Templates Management</h1>
                    <p class="text-gray-600">Manage all resume templates</p>
                </div>
                <button type="button" @click="openNew()" class="mt-4 sm:mt-0 inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-xl font-semibold hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                    <x-lucide-plus class="h-5 w-5 mr-2" /> New Template
                </button>
            </div>

            <div class="mb-6 flex flex-col sm:flex-row gap-4">
                <div class="relative flex-1 max-w-md">
                    <x-lucide-search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input type="text" placeholder="Search templates..." x-model.debounce.300ms="search" class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm" />
                </div>
                <div class="flex flex-wrap gap-2">
                    @foreach (['All', 'Corporate', 'Creative', 'Simple'] as $cat)
                        <button type="button" @click="category = '{{ $cat }}'" class="px-4 py-2 rounded-lg text-sm font-semibold transition-all duration-200"
                            :class="category === '{{ $cat }}' ? 'bg-gradient-to-r from-purple-500 to-pink-600 text-white shadow-lg' : 'bg-white text-gray-700 hover:bg-gray-100 shadow-sm'">{{ $cat }}</button>
                    @endforeach
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                <template x-for="template in items" :key="template.id">
                    <div class="bg-white rounded-2xl shadow-lg hover:shadow-2xl transition-all duration-300 overflow-hidden border border-gray-100 transform hover:-translate-y-2">
                        <div class="relative h-48 bg-gray-50 flex items-start justify-center p-4 overflow-hidden">
                            <template x-if="template.preview_image_url">
                                <img :src="template.preview_image_url" :alt="template.name" class="max-h-full w-auto object-contain drop-shadow-md"
                                    x-on:error="$el.src = 'https://via.placeholder.com/800x1000/667eea/ffffff?text=' + encodeURIComponent(template.name)" />
                            </template>
                            <template x-if="!template.preview_image_url">
                                <div class="w-full h-full bg-gradient-to-br from-purple-400 to-pink-500 flex items-center justify-center rounded-xl"><span class="text-white text-xl font-bold" x-text="template.name"></span></div>
                            </template>
                            <div class="absolute top-4 right-4"><span class="px-3 py-1 rounded-full text-xs font-semibold" :class="categoryColor(template.category)" x-text="template.category"></span></div>
                        </div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold text-gray-900 mb-2" x-text="template.name"></h3>
                            <p class="text-gray-600 text-sm mb-4 line-clamp-2" x-text="template.description || 'No description'"></p>
                            <div class="flex items-center space-x-2">
                                <button type="button" @click="openEdit(template)" class="flex-1 inline-flex items-center justify-center px-4 py-2 bg-gray-100 text-gray-700 rounded-lg font-semibold hover:bg-gray-200 transition-colors text-sm"><x-lucide-edit-2 class="h-4 w-4 mr-2" /> Edit</button>
                                <button type="button" @click="remove(template)" :disabled="deleting" class="inline-flex items-center justify-center px-4 py-2 bg-red-50 text-red-600 rounded-lg font-semibold hover:bg-red-100 transition-colors text-sm disabled:opacity-50 disabled:cursor-not-allowed"><x-lucide-trash-2 class="h-4 w-4" /></button>
                            </div>
                        </div>
                    </div>
                </template>
            </div>

            <div x-show="!loading && pagination.total > 0"><x-admin.pagination label="templates" class="mt-6 rounded-2xl border border-gray-100 bg-white shadow-sm" /></div>

            <div class="text-center py-12 bg-white rounded-2xl shadow-lg border border-gray-100" x-show="items.length === 0 && !loading">
                <x-lucide-layout class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                <p class="text-xl text-gray-600">No templates found</p>
            </div>
        </div>

        {{-- New / edit modal --}}
        <div class="fixed inset-0 z-50 overflow-y-auto bg-black/80 backdrop-blur-sm" x-show="modal" x-cloak @click="close()">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl my-8" @click.stop>
                    <div class="p-6 border-b border-gray-200 flex items-center justify-between">
                        <h2 class="text-2xl font-bold text-gray-900" x-text="modal === 'edit' ? 'Edit Template' : 'New Template'"></h2>
                        <button type="button" @click="close()" class="p-2 rounded-lg text-gray-400 hover:text-gray-600 hover:bg-gray-100 transition-colors"><x-lucide-x class="h-6 w-6" /></button>
                    </div>
                    <form @submit.prevent="save()" class="p-6 space-y-6">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Template Name *</label>
                            <input type="text" x-model="form.name" @input="errors.name = null" class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" :class="errors.name ? 'border-red-300 bg-red-50' : 'border-gray-300'" placeholder="e.g., Modern Professional" />
                            <p x-show="errors.name" class="mt-1 text-sm text-red-600" x-text="errors.name"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Description</label>
                            <textarea x-model="form.description" rows="3" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" placeholder="Describe the template..."></textarea>
                            <div class="mt-2 flex justify-end"><x-enhance-button value="form.description" context="template description" /></div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2">Category *</label>
                            <select x-model="form.category" @change="errors.category = null" class="w-full px-4 py-2.5 border rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" :class="errors.category ? 'border-red-300 bg-red-50' : 'border-gray-300'">
                                <option value="Corporate">Corporate</option>
                                <option value="Creative">Creative</option>
                                <option value="Simple">Simple</option>
                            </select>
                            <p x-show="errors.category" class="mt-1 text-sm text-red-600" x-text="errors.category"></p>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-2" x-text="'Preview Image ' + (modal === 'edit' ? '(optional)' : '*')"></label>
                            <div class="space-y-3">
                                <div class="relative w-full rounded-xl border-2 border-dashed border-gray-200 bg-gray-50 overflow-hidden flex items-start justify-center p-4">
                                    <img x-show="preview" :src="preview" alt="Template preview" class="max-h-56 w-auto object-contain drop-shadow-md" />
                                    <div x-show="!preview" class="h-56 flex items-center justify-center text-gray-400 text-sm">No image selected</div>
                                </div>
                                <input type="file" accept="image/*" @change="onFile($event)" class="w-full px-4 py-2.5 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                                <p x-show="errors.preview_image" class="text-sm text-red-600" x-text="errors.preview_image"></p>
                                <p class="text-xs text-gray-500">Accepted formats: JPG, PNG. Max size 2MB.</p>
                            </div>
                        </div>
                        <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-200">
                            <button type="button" @click="close()" class="px-6 py-2.5 border-2 border-gray-300 text-gray-700 rounded-lg font-semibold hover:bg-gray-50 transition-colors">Cancel</button>
                            <button type="submit" class="px-6 py-2.5 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg font-semibold hover:from-purple-600 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                <x-lucide-save class="h-4 w-4 inline mr-2" />
                                <span x-text="(modal === 'edit' ? 'Update' : 'Create') + ' Template'"></span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
