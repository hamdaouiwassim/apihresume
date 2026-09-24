{{-- Port of pages/admin/AdminEmails.jsx --}}
@php
    $tones = ['amber' => 'bg-amber-50 text-amber-700', 'emerald' => 'bg-emerald-50 text-emerald-700', 'red' => 'bg-red-50 text-red-700', 'sky' => 'bg-sky-50 text-sky-700', 'orange' => 'bg-orange-50 text-orange-700', 'violet' => 'bg-violet-50 text-violet-700'];
    $cards = [
        ['Queued', 'queued', 'clock', 'amber', null],
        ['Sent (30d)', 'sent', 'check-circle', 'emerald', null],
        ['Failed (30d)', 'failed', 'x-circle', 'red', null],
        ['Sent (24h)', 'sent_24h', 'send', 'sky', null],
        ['Stale queued', 'stale_queued', 'alert-triangle', 'orange', "'Worker may be down'"],
        ['Jobs pending', 'jobs_pending', 'inbox', 'violet', "(summary.failed_jobs ?? 0) + ' failed jobs'"],
    ];
    $bulkBtn = 'inline-flex items-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 to-pink-600 px-4 py-2 text-sm font-semibold text-white disabled:opacity-50';
@endphp
<x-layouts.admin title="Emails | Admin | HResume">
    <div class="max-w-7xl mx-auto pb-12" x-data="adminEmails">
        <div class="mb-8 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 flex items-center gap-3"><x-lucide-mail class="h-8 w-8 text-purple-600" /> Outbound emails</h1>
                <p class="text-gray-600 mt-1">Queued, sent, and failed messages with full traceability. Requires a queue worker in production.</p>
            </div>
            <button type="button" @click="refresh()" class="inline-flex items-center gap-2 rounded-xl border border-gray-200 bg-white px-4 py-2.5 text-sm font-semibold text-gray-700 hover:bg-gray-50">
                <x-lucide-refresh-cw class="h-4 w-4" /> Refresh
            </button>
        </div>

        <template x-if="summary">
            <div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
                @foreach ($cards as [$label, $key, $icon, $tone, $sub])
                    <div class="rounded-xl border border-gray-100 bg-white p-4 shadow-sm">
                        <div class="inline-flex h-9 w-9 items-center justify-center rounded-lg mb-2 {{ $tones[$tone] }}">
                            <x-dynamic-component :component="'lucide-'.$icon" class="h-4 w-4" />
                        </div>
                        <p class="text-2xl font-bold text-gray-900 tabular-nums" x-text="summary.{{ $key }} ?? 0"></p>
                        <p class="text-xs font-medium text-gray-500">{{ $label }}</p>
                        @if ($sub)
                            <p class="text-[10px] text-gray-400 mt-0.5" x-text="{{ $sub }}"></p>
                        @endif
                    </div>
                @endforeach
            </div>
        </template>

        <x-admin.new-features-form class="mb-6" :bulk="true" :send-to-user="false" />

        <div class="mb-6 rounded-2xl border border-gray-100 bg-white p-5 shadow-sm">
            <h2 class="text-sm font-bold text-gray-900 mb-3">Bulk send (templates)</h2>
            <div class="flex flex-wrap gap-2">
                @foreach ([['email_verification_reminder', 'unverified', 'Verification → unverified users'], ['resume_incomplete_reminder', 'incomplete_resume', 'Resume reminder → incomplete CVs']] as [$type, $filter, $label])
                    <button type="button" @click="bulk('{{ $type }}', '{{ $filter }}')" :disabled="bulkLoading === '{{ $type }}-{{ $filter }}'" class="{{ $bulkBtn }}">
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="bulkLoading === '{{ $type }}-{{ $filter }}'" />
                        <x-lucide-send class="h-4 w-4" x-show="bulkLoading !== '{{ $type }}-{{ $filter }}'" />
                        {{ $label }}
                    </button>
                @endforeach
            </div>
        </div>

        <div class="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
            <div class="border-b border-gray-100 p-4 flex flex-col sm:flex-row gap-3">
                <input type="search" placeholder="Search email, subject, user…" x-model.debounce.300ms="search" class="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm" />
                <select x-model="status" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All statuses</option>
                    <option value="queued">Queued</option>
                    <option value="processing">Processing</option>
                    <option value="sent">Sent</option>
                    <option value="failed">Failed</option>
                    <option value="skipped">Skipped</option>
                </select>
                <select x-model="type" class="rounded-lg border border-gray-300 px-3 py-2 text-sm">
                    <option value="">All types</option>
                    <option value="admin_custom">Custom message</option>
                    <option value="resume_incomplete_reminder">Resume reminder</option>
                    <option value="email_verification_reminder">Verification</option>
                    <option value="new_features_announcement">New features</option>
                </select>
            </div>

            <div class="flex justify-center py-16" x-show="loading">
                <x-lucide-loader-2 class="h-10 w-10 animate-spin text-purple-600" />
            </div>

            <div x-show="!loading" x-cloak>
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Recipient</th>
                                <th class="px-4 py-3">Subject</th>
                                <th class="px-4 py-3">Triggered by</th>
                                <th class="px-4 py-3">Timeline</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr x-show="items.length === 0"><td colspan="6" class="px-4 py-12 text-center text-gray-500">No emails match your filters.</td></tr>
                            <template x-for="row in items" :key="row.id">
                                <tr class="hover:bg-gray-50/80">
                                    <td class="px-4 py-3">
                                        <span class="inline-flex rounded-full px-2 py-0.5 text-xs font-semibold" :class="badge(row.status).className" x-text="badge(row.status).label"></span>
                                        <p x-show="row.error_message" class="mt-1 text-xs text-red-600 max-w-[140px] truncate" :title="row.error_message" x-text="row.error_message"></p>
                                    </td>
                                    <td class="px-4 py-3 text-gray-700" x-text="typeLabel(row.type)"></td>
                                    <td class="px-4 py-3">
                                        <template x-if="row.user"><a :href="'/admin/users/' + row.user_id" class="text-purple-600 hover:underline font-medium" x-text="row.recipient_email"></a></template>
                                        <template x-if="!row.user"><span x-text="row.recipient_email"></span></template>
                                    </td>
                                    <td class="px-4 py-3 max-w-[200px] truncate" :title="row.subject" x-text="row.subject"></td>
                                    <td class="px-4 py-3 text-gray-600" x-text="row.triggered_by?.name || '—'"></td>
                                    <td class="px-4 py-3 text-xs text-gray-500 whitespace-nowrap">
                                        <div x-text="'Q: ' + fullDate(row.queued_at)"></div>
                                        <div x-show="row.sent_at" x-text="'S: ' + fullDate(row.sent_at)"></div>
                                        <div x-show="row.failed_at" class="text-red-600" x-text="'F: ' + fullDate(row.failed_at)"></div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="pagination.total > 0"><x-admin.pagination label="emails" /></div>
            </div>
        </div>
    </div>
</x-layouts.admin>
