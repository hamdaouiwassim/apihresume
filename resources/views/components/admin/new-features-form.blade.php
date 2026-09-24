@props(['userName' => null, 'bulk' => false, 'sendToUser' => true])
{{-- Port of components/admin/AdminNewFeaturesEmailForm.jsx. Expects the parent Alpine scope to include newFeaturesForm(). --}}
<div {{ $attributes->class('rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50/80 to-white p-5 shadow-sm') }}>
    <div class="mb-4 flex items-center gap-2">
        <x-lucide-sparkles class="h-5 w-5 text-violet-600" />
        <h2 class="text-sm font-bold text-gray-900">New features announcement</h2>
    </div>
    @if ($userName)
        <p class="text-sm text-gray-600 mb-4" x-text="'Send a feature update to ' + {{ $userName }} + ' with custom copy and test links.'"></p>
    @else
        <p class="text-sm text-gray-600 mb-4">Custom message plus links for users to test new functionality.</p>
    @endif

    <div class="space-y-3">
        <div class="grid gap-3 sm:grid-cols-2">
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Email subject</label>
                <input type="text" x-model="nf.subject" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>
            <div>
                <label class="block text-xs font-semibold text-gray-600 mb-1">Headline (in email body)</label>
                <input type="text" x-model="nf.headline" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm" />
            </div>
        </div>

        <div>
            <label class="block text-xs font-semibold text-gray-600 mb-1">Custom message</label>
            <textarea rows="4" x-model="nf.message" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm resize-none"></textarea>
        </div>

        <div>
            <div class="flex items-center justify-between mb-2">
                <label class="text-xs font-semibold text-gray-600">Test links</label>
                <button type="button" @click="nfAddLink()" class="inline-flex items-center gap-1 text-xs font-semibold text-violet-600 hover:text-violet-800">
                    <x-lucide-plus class="h-3.5 w-3.5" />
                    Add link
                </button>
            </div>
            <div class="space-y-2">
                <template x-for="(link, index) in nf.links" :key="index">
                    <div class="flex flex-col sm:flex-row gap-2">
                        <input type="text" placeholder="Button label" x-model="link.label" class="sm:w-1/3 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                        <input type="url" placeholder="https://…" x-model="link.url" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                        <button type="button" :disabled="nf.links.length <= 1" @click="nfRemoveLink(index)" class="shrink-0 rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 disabled:opacity-30" aria-label="Remove link">
                            <x-lucide-trash-2 class="h-4 w-4" />
                        </button>
                    </div>
                </template>
            </div>
            <button type="button" @click="nfResetLinks()" class="mt-2 text-xs text-gray-500 hover:text-violet-600">Reset to default app links</button>
        </div>

        <div class="flex flex-wrap gap-2 pt-2">
            @if ($sendToUser)
                <button type="button" :disabled="nf.loading !== null" @click="nfSendUser()" class="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50">
                    <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="nf.loading === 'user'" x-cloak />
                    <x-lucide-sparkles class="h-4 w-4" x-show="nf.loading !== 'user'" />
                    @if ($userName)
                        <span x-text="'Send to ' + {{ $userName }}"></span>
                    @else
                        Send to user
                    @endif
                </button>
            @endif
            @if ($bulk)
                <select x-model="nf.filter" class="rounded-xl border border-gray-300 px-3 py-2.5 text-sm">
                    <option value="verified">Verified users</option>
                    <option value="all_users">All users</option>
                    <option value="pro">Pro users</option>
                    <option value="unverified">Unverified users</option>
                    <option value="incomplete_resume">Incomplete resumes</option>
                </select>
                <button type="button" :disabled="nf.loading !== null" @click="nfSendBulk()" class="inline-flex items-center gap-2 rounded-xl border border-violet-300 bg-white px-4 py-2.5 text-sm font-semibold text-violet-800 hover:bg-violet-50 disabled:opacity-50">
                    <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="nf.loading === 'bulk'" x-cloak />
                    Bulk queue
                </button>
            @endif
        </div>
    </div>
</div>
