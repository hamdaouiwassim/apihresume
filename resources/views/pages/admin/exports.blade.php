<x-layouts.admin title="Database & Assets Export | Admin | HResume">
    <div class="space-y-8 pb-10" x-data="adminExports">
        {{-- Header Banner --}}
        <div class="relative overflow-hidden rounded-3xl border border-purple-100/80 bg-gradient-to-br from-indigo-700 via-purple-700 to-pink-600 p-6 text-white shadow-xl shadow-purple-200/40 md:p-8">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-pink-400/30 blur-3xl" aria-hidden="true"></div>

            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-sm">
                        <x-lucide-hard-drive class="h-3.5 w-3.5" />
                        System Administration
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">Database & Assets Export</h1>
                    <p class="mt-2 max-w-2xl text-sm text-white/85 md:text-base">
                        Export your full database to SQL/JSON and archive storage assets into downloadable files. Create instant downloads or manage server backups.
                    </p>
                </div>

                <div class="flex flex-wrap items-center gap-3">
                    <button type="button" @click="fetchStatus()" :disabled="loading" class="inline-flex items-center gap-2 rounded-xl bg-white/15 px-4 py-2.5 text-sm font-semibold backdrop-blur-sm transition-all hover:bg-white/25 active:scale-95 disabled:opacity-50">
                        <x-lucide-refresh-cw class="h-4 w-4" ::class="loading ? 'animate-spin' : ''" />
                        Refresh Status
                    </button>
                    <button type="button" @click="exportFull(true)" :disabled="exportingFull" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-2.5 text-sm font-bold text-purple-900 shadow-lg shadow-purple-950/20 transition-all hover:bg-purple-50 active:scale-95 disabled:opacity-50">
                        <x-lucide-download class="h-4 w-4 text-purple-700" x-show="!exportingFull" />
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin text-purple-700" x-show="exportingFull" />
                        <span x-text="exportingFull ? 'Generating...' : '1-Click Full Backup'">1-Click Full Backup</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- System Metrics Overview Cards --}}
        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
            {{-- Database Metric --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-purple-50 text-purple-600">
                            <x-lucide-database class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Database Engine</p>
                            <h3 class="text-lg font-bold text-gray-900 capitalize" x-text="stats.database?.driver || 'sqlite'">Database</h3>
                        </div>
                    </div>
                    <span class="rounded-full bg-purple-100 px-3 py-1 text-xs font-bold text-purple-700 uppercase" x-text="stats.database?.connection || 'sqlite'"></span>
                </div>
                <div class="mt-5 grid grid-cols-3 gap-2 border-t border-gray-100 pt-4 text-center">
                    <div>
                        <p class="text-xs text-gray-400">Tables</p>
                        <p class="text-lg font-bold text-gray-800" x-text="stats.database?.total_tables || 0">0</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Records</p>
                        <p class="text-lg font-bold text-gray-800" x-text="(stats.database?.total_records || 0).toLocaleString()">0</p>
                    </div>
                    <div>
                        <p class="text-xs text-gray-400">Est. Size</p>
                        <p class="text-lg font-bold text-gray-800" x-text="stats.database?.size_formatted || '0 B'">0 B</p>
                    </div>
                </div>
            </div>

            {{-- Assets Storage Metric --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all hover:shadow-md">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-pink-50 text-pink-600">
                            <x-lucide-folder-archive class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Assets Storage</p>
                            <h3 class="text-lg font-bold text-gray-900">Uploaded Media</h3>
                        </div>
                    </div>
                    <span class="rounded-full bg-pink-100 px-3 py-1 text-xs font-bold text-pink-700" x-text="stats.assets?.total_formatted || '0 B'">0 B</span>
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                    <div>
                        <p class="text-xs text-gray-400">Total Uploaded Files</p>
                        <p class="text-lg font-bold text-gray-800" x-text="(stats.assets?.total_files || 0).toLocaleString() + ' files'">0 files</p>
                    </div>
                    <div class="flex flex-wrap gap-1 text-[11px] text-gray-500">
                        <span class="rounded bg-gray-100 px-2 py-0.5">Avatars</span>
                        <span class="rounded bg-gray-100 px-2 py-0.5">Blog</span>
                        <span class="rounded bg-gray-100 px-2 py-0.5">Fonts</span>
                    </div>
                </div>
            </div>

            {{-- Backups Repository Metric --}}
            <div class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm transition-all hover:shadow-md sm:col-span-2 lg:col-span-1">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-indigo-50 text-indigo-600">
                            <x-lucide-archive class="h-6 w-6" />
                        </div>
                        <div>
                            <p class="text-xs font-semibold uppercase tracking-wider text-gray-400">Server Backups</p>
                            <h3 class="text-lg font-bold text-gray-900">Stored Archives</h3>
                        </div>
                    </div>
                    <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-bold text-indigo-700" x-text="(stats.backups?.length || 0) + ' saved'">0 saved</span>
                </div>
                <div class="mt-5 flex items-center justify-between border-t border-gray-100 pt-4">
                    <div>
                        <p class="text-xs text-gray-400">Location on Server</p>
                        <p class="text-xs font-mono text-gray-600 truncate max-w-[200px]" title="storage/app/backups">storage/app/backups</p>
                    </div>
                    <a href="#backups-list" class="text-xs font-semibold text-indigo-600 hover:text-indigo-800">
                        View files &darr;
                    </a>
                </div>
            </div>
        </div>

        {{-- Export Tools Section --}}
        <div class="grid grid-cols-1 gap-8 lg:grid-cols-2">
            {{-- 1. Database Export Card --}}
            <div class="flex flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
                <div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-100 text-purple-700">
                                <x-lucide-database class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Export Database to File</h2>
                                <p class="text-xs text-gray-500">Generate clean SQL dump or structured data</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-purple-50 px-3 py-1 text-xs font-semibold text-purple-700">
                            <span x-text="selectedTables.length">0</span> / <span x-text="stats.database?.total_tables || 0">0</span> tables
                        </span>
                    </div>

                    {{-- Format Selection --}}
                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Export Format</label>
                            <div class="grid grid-cols-3 gap-3">
                                <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                    :class="dbFormat === 'sql' ? 'border-purple-600 bg-purple-50/50 text-purple-900 ring-2 ring-purple-500/20' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="dbFormat" value="sql" x-model="dbFormat" class="sr-only" />
                                        <x-lucide-file-code class="h-4 w-4" ::class="dbFormat === 'sql' ? 'text-purple-600' : 'text-gray-400'" />
                                        <span class="text-xs font-bold">SQL Dump</span>
                                    </div>
                                    <span class="text-[10px] text-gray-400">.sql</span>
                                </label>

                                <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                    :class="dbFormat === 'json' ? 'border-purple-600 bg-purple-50/50 text-purple-900 ring-2 ring-purple-500/20' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center gap-2">
                                        <input type="radio" name="dbFormat" value="json" x-model="dbFormat" class="sr-only" />
                                        <x-lucide-braces class="h-4 w-4" ::class="dbFormat === 'json' ? 'text-purple-600' : 'text-gray-400'" />
                                        <span class="text-xs font-bold">JSON Data</span>
                                    </div>
                                    <span class="text-[10px] text-gray-400">.json</span>
                                </label>

                                <template x-if="stats.database?.driver === 'sqlite'">
                                    <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                        :class="dbFormat === 'sqlite' ? 'border-purple-600 bg-purple-50/50 text-purple-900 ring-2 ring-purple-500/20' : 'border-gray-200 hover:border-gray-300'">
                                        <div class="flex items-center gap-2">
                                            <input type="radio" name="dbFormat" value="sqlite" x-model="dbFormat" class="sr-only" />
                                            <x-lucide-database class="h-4 w-4" ::class="dbFormat === 'sqlite' ? 'text-purple-600' : 'text-gray-400'" />
                                            <span class="text-xs font-bold">Raw SQLite</span>
                                        </div>
                                        <span class="text-[10px] text-gray-400">.sqlite</span>
                                    </label>
                                </template>
                            </div>
                        </div>

                        {{-- Compression Toggle --}}
                        <div class="flex items-center justify-between rounded-xl bg-gray-50 p-3">
                            <div class="flex items-center gap-2.5">
                                <x-lucide-archive class="h-4 w-4 text-purple-600" />
                                <div>
                                    <p class="text-xs font-semibold text-gray-800">Compress with ZIP</p>
                                    <p class="text-[11px] text-gray-400">Reduces downloaded file size</p>
                                </div>
                            </div>
                            <label class="relative inline-flex cursor-pointer items-center">
                                <input type="checkbox" x-model="dbCompress" class="peer sr-only" />
                                <div class="h-5 w-9 rounded-full bg-gray-200 peer-checked:bg-purple-600 peer-focus:outline-none after:absolute after:left-[2px] after:top-[2px] after:h-4 after:w-4 after:rounded-full after:bg-white after:transition-all after:content-[''] peer-checked:after:translate-x-full"></div>
                            </label>
                        </div>

                        {{-- Tables Picker --}}
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold uppercase tracking-wider text-gray-500">Select Tables</label>
                                    <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-[10px] font-semibold text-emerald-700">App-Only Filter</span>
                                </div>
                                <div class="flex items-center gap-2 text-xs">
                                    <button type="button" @click="selectAppTablesOnly()" class="font-semibold text-purple-600 hover:text-purple-800" title="Select all core application tables, excluding temporary cache and sessions">
                                        App Data
                                    </button>
                                    <span class="text-gray-300">|</span>
                                    <button type="button" @click="toggleAllTables()" class="font-semibold text-gray-500 hover:text-gray-700">
                                        <span x-text="selectAllTables ? 'Deselect All' : 'Select All'">Select All</span>
                                    </button>
                                </div>
                            </div>

                            <p class="mb-2 text-[11px] text-gray-500">
                                Strictly filters to your app's tables. Foreign or unrelated tables sharing your MySQL database are automatically excluded.
                            </p>

                            <div class="flex items-center gap-2 mb-2">
                                <div class="relative flex-1">
                                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
                                    <input type="text" x-model="tableSearch" placeholder="Search tables..." class="w-full rounded-xl border border-gray-200 bg-white py-1.5 pl-9 pr-3 text-xs placeholder-gray-400 focus:border-purple-500 focus:outline-none focus:ring-1 focus:ring-purple-500" />
                                </div>
                                <div class="flex rounded-xl bg-gray-100 p-0.5 text-[11px]">
                                    <button type="button" @click="tableCategoryFilter = 'all'" class="rounded-lg px-2 py-1 font-medium transition-colors" :class="tableCategoryFilter === 'all' ? 'bg-white text-gray-900 shadow-xs' : 'text-gray-500 hover:text-gray-900'">All</button>
                                    <button type="button" @click="tableCategoryFilter = 'app'" class="rounded-lg px-2 py-1 font-medium transition-colors" :class="tableCategoryFilter === 'app' ? 'bg-white text-purple-700 shadow-xs' : 'text-gray-500 hover:text-gray-900'">App</button>
                                    <button type="button" @click="tableCategoryFilter = 'system'" class="rounded-lg px-2 py-1 font-medium transition-colors" :class="tableCategoryFilter === 'system' ? 'bg-white text-gray-700 shadow-xs' : 'text-gray-500 hover:text-gray-900'">Cache</button>
                                </div>
                            </div>

                            <div class="max-h-48 overflow-y-auto rounded-xl border border-gray-200 bg-gray-50/50 p-2 divide-y divide-gray-100 custom-scrollbar">
                                <template x-for="t in filteredTables" :key="t.name">
                                    <label class="flex cursor-pointer items-center justify-between py-1.5 px-2 rounded-lg hover:bg-white transition-colors">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <input type="checkbox" :checked="selectedTables.includes(t.name)" @change="toggleTable(t.name)" class="rounded border-gray-300 text-purple-600 focus:ring-purple-500" />
                                            <span class="text-xs font-medium text-gray-800 truncate" x-text="t.name"></span>
                                            <span class="text-[9px] font-semibold uppercase px-1.5 py-0.2 rounded"
                                                :class="t.is_core ? 'bg-purple-100 text-purple-700' : 'bg-gray-200 text-gray-600'"
                                                x-text="t.category">
                                            </span>
                                        </div>
                                        <span class="text-[11px] font-mono text-gray-400 flex-shrink-0 ml-2" x-text="(t.rows || 0) + ' rows'"></span>
                                    </label>
                                </template>
                                <div x-show="filteredTables.length === 0" class="py-4 text-center text-xs text-gray-400">
                                    No tables match your search or filter
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 pt-5 border-t border-gray-100 flex flex-wrap gap-3">
                    <button type="button" @click="exportDatabase(true)" :disabled="exportingDb || selectedTables.length === 0"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-purple-200 hover:from-purple-700 hover:to-indigo-700 active:scale-95 disabled:opacity-50">
                        <x-lucide-download class="h-4 w-4" />
                        <span>Download SQL to File</span>
                    </button>
                    <button type="button" @click="exportDatabase(false)" :disabled="exportingDb || selectedTables.length === 0"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-purple-200 bg-purple-50/80 px-4 py-3 text-sm font-semibold text-purple-800 hover:bg-purple-100 active:scale-95 disabled:opacity-50">
                        <x-lucide-save class="h-4 w-4 text-purple-600" x-show="!exportingDb" />
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin text-purple-600" x-show="exportingDb" />
                        <span x-text="exportingDb ? 'Generating...' : 'Save to Backups'">Save to Backups</span>
                    </button>
                </div>
            </div>

            {{-- 2. Assets Export Card --}}
            <div class="flex flex-col justify-between rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
                <div>
                    <div class="flex items-center justify-between border-b border-gray-100 pb-5">
                        <div class="flex items-center gap-3">
                            <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-100 text-pink-700">
                                <x-lucide-folder-archive class="h-5 w-5" />
                            </div>
                            <div>
                                <h2 class="text-lg font-bold text-gray-900">Export Assets to File</h2>
                                <p class="text-xs text-gray-500">Archive uploaded media, images, and fonts as ZIP</p>
                            </div>
                        </div>
                        <span class="rounded-full bg-pink-50 px-3 py-1 text-xs font-semibold text-pink-700" x-text="stats.assets?.total_formatted || '0 B'">0 B</span>
                    </div>

                    {{-- Scope Selection --}}
                    <div class="mt-6 space-y-4">
                        <div>
                            <label class="block text-xs font-bold uppercase tracking-wider text-gray-500 mb-2">Select Asset Scope</label>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                                <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                    :class="selectedScopes.includes('all') ? 'border-pink-600 bg-pink-50/50 text-pink-900 ring-2 ring-pink-500/20' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="checkbox" value="all" @change="toggleScope('all')" :checked="selectedScopes.includes('all')" class="sr-only" />
                                        <x-lucide-layers class="h-4 w-4 text-pink-600" />
                                        <div>
                                            <p class="text-xs font-bold">All Storage Assets</p>
                                            <p class="text-[10px] text-gray-400">Everything in storage/app</p>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-mono text-pink-700" x-text="stats.assets?.total_files || 0"></span>
                                </label>

                                <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                    :class="selectedScopes.includes('avatars') ? 'border-pink-600 bg-pink-50/50 text-pink-900 ring-2 ring-pink-500/20' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="checkbox" value="avatars" @change="toggleScope('avatars')" :checked="selectedScopes.includes('avatars')" class="sr-only" />
                                        <x-lucide-user class="h-4 w-4 text-pink-600" />
                                        <div>
                                            <p class="text-xs font-bold">Avatars & Photos</p>
                                            <p class="text-[10px] text-gray-400">User & brand profile pictures</p>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-mono text-gray-400" x-text="(stats.assets?.categories?.find(c => c.key === 'avatars')?.files_count || 0)"></span>
                                </label>

                                <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                    :class="selectedScopes.includes('blog') ? 'border-pink-600 bg-pink-50/50 text-pink-900 ring-2 ring-pink-500/20' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="checkbox" value="blog" @change="toggleScope('blog')" :checked="selectedScopes.includes('blog')" class="sr-only" />
                                        <x-lucide-image class="h-4 w-4 text-pink-600" />
                                        <div>
                                            <p class="text-xs font-bold">Blog Media</p>
                                            <p class="text-[10px] text-gray-400">Article images & WebP</p>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-mono text-gray-400" x-text="(stats.assets?.categories?.find(c => c.key === 'blog')?.files_count || 0)"></span>
                                </label>

                                <label class="flex cursor-pointer items-center justify-between rounded-xl border p-3 transition-all"
                                    :class="selectedScopes.includes('fonts') ? 'border-pink-600 bg-pink-50/50 text-pink-900 ring-2 ring-pink-500/20' : 'border-gray-200 hover:border-gray-300'">
                                    <div class="flex items-center gap-2.5">
                                        <input type="checkbox" value="fonts" @change="toggleScope('fonts')" :checked="selectedScopes.includes('fonts')" class="sr-only" />
                                        <x-lucide-type class="h-4 w-4 text-pink-600" />
                                        <div>
                                            <p class="text-xs font-bold">Custom Fonts</p>
                                            <p class="text-[10px] text-gray-400">PDF TTF/OTF font files</p>
                                        </div>
                                    </div>
                                    <span class="text-[11px] font-mono text-gray-400" x-text="(stats.assets?.categories?.find(c => c.key === 'fonts')?.files_count || 0)"></span>
                                </label>
                            </div>
                        </div>

                        {{-- Details Breakdown --}}
                        <div class="rounded-2xl border border-gray-100 bg-gray-50/60 p-4">
                            <h4 class="text-xs font-bold uppercase tracking-wider text-gray-600 mb-2">Category Storage Breakdown</h4>
                            <div class="space-y-2">
                                <template x-for="cat in stats.assets?.categories || []" :key="cat.key">
                                    <div class="flex items-center justify-between text-xs py-1">
                                        <span class="text-gray-700" x-text="cat.name"></span>
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-gray-500" x-text="cat.files_count + ' files'"></span>
                                            <span class="rounded bg-white px-2 py-0.5 font-mono font-semibold text-gray-700 shadow-xs" x-text="cat.size_formatted"></span>
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Action Buttons --}}
                <div class="mt-6 pt-5 border-t border-gray-100 flex flex-wrap gap-3">
                    <button type="button" @click="exportAssets(true)" :disabled="exportingAssets"
                        class="flex-1 inline-flex items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-pink-600 to-rose-600 px-4 py-3 text-sm font-bold text-white shadow-md shadow-pink-200 hover:from-pink-700 hover:to-rose-700 active:scale-95 disabled:opacity-50">
                        <x-lucide-download class="h-4 w-4" />
                        <span>Download Assets Zip</span>
                    </button>
                    <button type="button" @click="exportAssets(false)" :disabled="exportingAssets"
                        class="inline-flex items-center justify-center gap-2 rounded-xl border border-pink-200 bg-pink-50/80 px-4 py-3 text-sm font-semibold text-pink-800 hover:bg-pink-100 active:scale-95 disabled:opacity-50">
                        <x-lucide-save class="h-4 w-4 text-pink-600" x-show="!exportingAssets" />
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin text-pink-600" x-show="exportingAssets" />
                        <span x-text="exportingAssets ? 'Generating...' : 'Save to Backups'">Save to Backups</span>
                    </button>
                </div>
            </div>
        </div>

        {{-- 3. Stored Backups & Download History Table --}}
        <div id="backups-list" class="rounded-3xl border border-gray-100 bg-white p-6 shadow-sm md:p-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-b border-gray-100 pb-5">
                <div class="flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-indigo-100 text-indigo-700">
                        <x-lucide-archive class="h-5 w-5" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Stored Backups on Server</h2>
                        <p class="text-xs text-gray-500">Download previously generated database dumps and asset archives</p>
                    </div>
                </div>

                <div class="flex items-center gap-3">
                    <div class="relative w-full sm:w-64">
                        <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 h-3.5 w-3.5 text-gray-400" />
                        <input type="text" x-model="backupsSearch" placeholder="Search backups..." class="w-full rounded-xl border border-gray-200 bg-white py-2 pl-9 pr-3 text-xs placeholder-gray-400 focus:border-indigo-500 focus:outline-none focus:ring-1 focus:ring-indigo-500" />
                    </div>
                </div>
            </div>

            {{-- Table --}}
            <div class="mt-6 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead>
                        <tr class="border-b border-gray-100 text-xs font-semibold uppercase tracking-wider text-gray-400">
                            <th class="py-3 px-4">Backup File</th>
                            <th class="py-3 px-4">Type</th>
                            <th class="py-3 px-4">Format</th>
                            <th class="py-3 px-4">File Size</th>
                            <th class="py-3 px-4">Created At</th>
                            <th class="py-3 px-4 text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="b in filteredBackups" :key="b.filename">
                            <tr class="hover:bg-gray-50/80 transition-colors">
                                <td class="py-3.5 px-4">
                                    <div class="flex items-center gap-2">
                                        <x-lucide-file-archive class="h-4 w-4 text-indigo-600 flex-shrink-0" x-show="b.format === 'zip'" />
                                        <x-lucide-file-code class="h-4 w-4 text-purple-600 flex-shrink-0" x-show="b.format === 'sql'" />
                                        <x-lucide-database class="h-4 w-4 text-blue-600 flex-shrink-0" x-show="b.format === 'sqlite'" />
                                        <x-lucide-braces class="h-4 w-4 text-amber-600 flex-shrink-0" x-show="b.format === 'json'" />
                                        <span class="font-mono text-xs font-medium text-gray-900" x-text="b.filename"></span>
                                        <button type="button" @click="copyFilename(b.filename)" class="text-gray-400 hover:text-gray-600" title="Copy filename">
                                            <x-lucide-copy class="h-3.5 w-3.5" />
                                        </button>
                                    </div>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-0.5 text-[11px] font-semibold capitalize"
                                        :class="{
                                            'bg-purple-100 text-purple-700': b.type === 'database',
                                            'bg-pink-100 text-pink-700': b.type === 'assets',
                                            'bg-indigo-100 text-indigo-700': b.type === 'full'
                                        }" x-text="b.type === 'full' ? 'Full Backup' : b.type">
                                    </span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-mono text-xs font-bold uppercase text-gray-600" x-text="b.format"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <span class="font-mono text-xs font-medium text-gray-700" x-text="b.size_formatted"></span>
                                </td>
                                <td class="py-3.5 px-4">
                                    <p class="text-xs text-gray-900" x-text="b.created_at_formatted"></p>
                                    <p class="text-[11px] text-gray-400" x-text="b.age"></p>
                                </td>
                                <td class="py-3.5 px-4 text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <a :href="b.download_url" class="inline-flex items-center gap-1.5 rounded-lg bg-indigo-50 px-3 py-1.5 text-xs font-semibold text-indigo-700 hover:bg-indigo-100 transition-colors" title="Download backup">
                                            <x-lucide-download class="h-3.5 w-3.5" />
                                            Download
                                        </a>
                                        <button type="button" @click="deleteBackup(b)" class="rounded-lg p-1.5 text-gray-400 hover:bg-red-50 hover:text-red-600 transition-colors" title="Delete backup">
                                            <x-lucide-trash-2 class="h-4 w-4" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>

                        <tr x-show="filteredBackups.length === 0">
                            <td colspan="6" class="py-12 text-center text-gray-400">
                                <x-lucide-archive class="mx-auto h-10 w-10 text-gray-300 mb-2" />
                                <p class="text-sm font-medium text-gray-600">No backup files stored yet</p>
                                <p class="text-xs text-gray-400 mt-1">Use the export tools above to generate database and assets backups</p>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</x-layouts.admin>
