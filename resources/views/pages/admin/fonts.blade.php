{{-- Port of pages/admin/FontManagement.jsx --}}
<x-layouts.admin title="Fonts | Admin | HResume">
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12" x-data="adminFonts">
        <div class="max-w-5xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between mb-8">
                <div class="flex items-center gap-3">
                    <div class="p-3 bg-gradient-to-br from-purple-500 to-pink-600 rounded-xl shadow-lg"><x-lucide-type class="h-6 w-6 text-white" /></div>
                    <div>
                        <h1 class="text-2xl font-bold text-gray-900">PDF Font Management</h1>
                        <p class="text-sm text-gray-500">Upload custom fonts for PDF resume generation</p>
                    </div>
                </div>
                <button type="button" @click="showForm = !showForm" class="inline-flex items-center gap-2 px-4 py-2.5 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200">
                    <x-lucide-upload class="h-4 w-4" />
                    <span x-text="showForm ? 'Cancel' : 'Upload Font'">Upload Font</span>
                </button>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 p-6 mb-8" x-show="showForm" x-cloak>
                <h2 class="text-lg font-semibold text-gray-900 mb-4 flex items-center gap-2"><x-lucide-upload class="h-5 w-5 text-purple-600" /> Upload New Font</h2>
                <div class="bg-amber-50 border border-amber-200 rounded-xl p-4 mb-6">
                    <div class="flex items-start gap-2">
                        <x-lucide-alert-circle class="h-5 w-5 text-amber-600 flex-shrink-0 mt-0.5" />
                        <div class="text-sm text-amber-800">
                            <p class="font-medium mb-1">Font file requirements:</p>
                            <ul class="list-disc list-inside space-y-1 text-amber-700">
                                <li>Accepted formats: <strong>.ttf</strong> and <strong>.otf</strong></li>
                                <li><strong>Regular</strong> variant is required</li>
                                <li>Bold, Italic, and Bold Italic are optional but recommended for proper rendering</li>
                                <li>You can download free fonts from <a href="https://fonts.google.com" target="_blank" rel="noopener noreferrer" class="underline font-medium">Google Fonts</a></li>
                            </ul>
                        </div>
                    </div>
                </div>
                <form @submit.prevent="upload()" class="space-y-5">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1.5">Font Family Name <span class="text-red-500">*</span></label>
                        <input type="text" x-model="familyName" placeholder="e.g. Roboto, Open Sans, Poppins" class="w-full rounded-xl border border-gray-300 px-4 py-2.5 text-sm focus:border-purple-500 focus:ring-1 focus:ring-purple-500" required />
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        @foreach ([['regular', 'Regular', true], ['bold', 'Bold', false], ['italic', 'Italic', false], ['bold_italic', 'Bold Italic', false]] as [$key, $label, $required])
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1.5">{{ $label }} @if ($required)<span class="text-red-500">*</span>@else<span class="text-gray-400">(optional)</span>@endif</label>
                                <div class="relative">
                                    <input type="file" accept=".ttf,.otf" @change="onFile('{{ $key }}', $event)" @if ($required) required @endif
                                        class="w-full text-sm text-gray-600 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-purple-50 file:text-purple-700 hover:file:bg-purple-100 cursor-pointer border border-gray-300 rounded-xl py-1.5 px-2" />
                                    <x-lucide-check class="absolute right-3 top-1/2 -translate-y-1/2 h-4 w-4 text-green-500" x-show="files.{{ $key }}" x-cloak />
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <div class="flex justify-end">
                        <button type="submit" :disabled="uploading" class="inline-flex items-center gap-2 px-6 py-2.5 bg-gradient-to-r from-purple-500 to-pink-600 text-white font-semibold rounded-xl shadow-lg hover:shadow-xl transition-all duration-200" :class="uploading ? 'opacity-50 cursor-not-allowed' : ''">
                            <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="uploading" />
                            <x-lucide-upload class="h-4 w-4" x-show="!uploading" />
                            <span x-text="uploading ? 'Uploading...' : 'Upload Font'"></span>
                        </button>
                    </div>
                </form>
            </div>

            <div class="bg-white rounded-2xl shadow-lg border border-gray-100 overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-100">
                    <h2 class="text-lg font-semibold text-gray-900" x-text="'Uploaded Fonts (' + fonts.length + ')'">Uploaded Fonts (0)</h2>
                </div>
                <div class="flex items-center justify-center py-16" x-show="loading">
                    <x-lucide-loader-2 class="h-6 w-6 animate-spin text-purple-600" />
                    <span class="ml-3 text-gray-600">Loading fonts...</span>
                </div>
                <div class="text-center py-16" x-show="!loading && fonts.length === 0" x-cloak>
                    <x-lucide-type class="h-12 w-12 text-gray-300 mx-auto mb-4" />
                    <p class="text-gray-500 text-lg">No custom fonts uploaded yet</p>
                    <p class="text-gray-400 text-sm mt-1">Click "Upload Font" to add TTF/OTF fonts for PDF generation</p>
                </div>
                <div class="divide-y divide-gray-100" x-show="!loading && fonts.length > 0" x-cloak>
                    <template x-for="font in fonts" :key="font.id">
                        <div class="px-6 py-4 flex items-center justify-between hover:bg-gray-50 transition-colors">
                            <div class="flex items-center gap-4">
                                <div class="p-2.5 rounded-xl" :class="font.is_active ? 'bg-purple-100' : 'bg-gray-100'">
                                    <x-lucide-type class="h-5 w-5" ::class="font.is_active ? 'text-purple-600' : 'text-gray-400'" />
                                </div>
                                <div>
                                    <h3 class="font-semibold" :class="font.is_active ? 'text-gray-900' : 'text-gray-400'" x-text="font.family_name"></h3>
                                    <div class="flex items-center gap-2 mt-1">
                                        <template x-for="variant in variants" :key="variant">
                                            <span class="inline-flex items-center gap-1 text-xs px-2 py-0.5 rounded-full" :class="font[variant] ? 'bg-green-50 text-green-700' : 'bg-gray-100 text-gray-400'">
                                                <x-lucide-check class="h-3 w-3" x-show="font[variant]" />
                                                <x-lucide-file-text class="h-3 w-3" x-show="!font[variant]" />
                                                <span x-text="variantLabel(variant)"></span>
                                            </span>
                                        </template>
                                    </div>
                                </div>
                            </div>
                            <div class="flex items-center gap-3">
                                <button type="button" @click="toggle(font)" class="p-2 rounded-lg transition-colors" :class="font.is_active ? 'text-green-600 hover:bg-green-50' : 'text-gray-400 hover:bg-gray-100'" :title="font.is_active ? 'Deactivate' : 'Activate'">
                                    <x-lucide-toggle-right class="h-6 w-6" x-show="font.is_active" />
                                    <x-lucide-toggle-left class="h-6 w-6" x-show="!font.is_active" />
                                </button>
                                <button type="button" @click="remove(font)" class="p-2 text-red-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete font"><x-lucide-trash-2 class="h-5 w-5" /></button>
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="mt-6 bg-white/60 backdrop-blur-sm rounded-2xl border border-gray-100 p-6">
                <h3 class="text-sm font-semibold text-gray-700 mb-3">Built-in Fonts (always available)</h3>
                <div class="flex flex-wrap gap-2">
                    @foreach (['Helvetica (Sans-Serif)', 'Times (Serif)', 'Courier (Monospace)', 'DejaVu Sans', 'DejaVu Serif', 'DejaVu Sans Mono'] as $name)
                        <span class="text-xs px-3 py-1.5 bg-gray-100 text-gray-600 rounded-full">{{ $name }}</span>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
