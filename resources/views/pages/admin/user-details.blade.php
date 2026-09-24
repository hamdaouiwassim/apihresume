{{-- Port of pages/admin/UserDetails.jsx (recruiter sections removed) --}}
@php $input = 'w-full rounded-xl border border-slate-200 bg-white px-4 py-2.5 text-sm focus:border-blue-500 focus:ring-4 focus:ring-blue-100 transition-all'; @endphp
<x-layouts.admin title="User details | Admin | HResume" :islands="true">
    <div x-data="adminUserDetails({{ (int) $id }})">
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12" x-show="loading">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-center min-h-[600px]">
                    <div class="text-center">
                        <x-lucide-loader-2 class="h-12 w-12 animate-spin text-purple-600 mx-auto mb-4" />
                        <p class="text-gray-600">Loading user details...</p>
                    </div>
                </div>
            </div>
        </div>

        <template x-if="!loading && user">
            <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="mb-8">
                        <a href="{{ route('admin.users') }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4 transition-colors">
                            <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                            Back to Users
                        </a>
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-4xl font-bold text-gray-900 mb-2">User Details</h1>
                                <p class="text-gray-600">View comprehensive information about this user</p>
                            </div>
                        </div>
                    </div>

                    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                        <div class="lg:col-span-2 space-y-6">
                            {{-- Profile card --}}
                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <div class="flex items-start space-x-6">
                                    <img :src="user.avatar || defaultAvatar(user.email)" :alt="user.name" class="h-24 w-24 rounded-full border-4 border-purple-200" />
                                    <div class="flex-1">
                                        <div class="flex items-center justify-between mb-2">
                                            <h2 class="text-2xl font-bold text-gray-900" x-text="user.name"></h2>
                                            <div class="flex items-center gap-2">
                                                <span x-show="user.is_pro" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800"><x-lucide-crown class="h-3 w-3 mr-1" /> Pro</span>
                                                <span x-show="user.email_verified_at" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700"><x-lucide-check-circle class="h-3 w-3 mr-1" /> Verified</span>
                                                <span x-show="!user.email_verified_at" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700"><x-lucide-x-circle class="h-3 w-3 mr-1" /> Unverified</span>
                                            </div>
                                        </div>
                                        <div class="space-y-2">
                                            <div class="flex items-center text-gray-600"><x-lucide-mail class="h-4 w-4 mr-2" /><span x-text="user.email"></span></div>
                                            <div class="flex items-center text-gray-600"><x-lucide-calendar class="h-4 w-4 mr-2" /><span x-text="'Joined: ' + longDate(user.created_at)"></span></div>
                                            <template x-if="user.last_activity">
                                                <div class="flex items-center text-gray-600"><x-lucide-clock class="h-4 w-4 mr-2" /><span x-text="'Last active: ' + timeAgo(user.last_activity)"></span></div>
                                            </template>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            {{-- Roles & status --}}
                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <h3 class="text-xl font-bold text-gray-900 mb-4">Roles & Status</h3>
                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div class="p-4 bg-gray-50 rounded-lg">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-600">Admin</span>
                                            <span x-show="user.is_admin" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700"><x-lucide-shield class="h-3 w-3 mr-1" /> Yes</span>
                                            <span x-show="!user.is_admin" class="text-xs text-gray-400">No</span>
                                        </div>
                                    </div>
                                    <div class="p-4 bg-gray-50 rounded-lg flex flex-col">
                                        <div class="flex items-center justify-between mb-2">
                                            <span class="text-sm font-medium text-gray-600">Pro</span>
                                            <span x-show="user.is_pro" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800"><x-lucide-crown class="h-3 w-3 mr-1" /> Active</span>
                                            <span x-show="!user.is_pro" class="text-xs text-gray-400">Free</span>
                                        </div>
                                        <p class="text-xs text-gray-500 mb-3 flex-1">
                                            Pro: 50,000 AI tokens/month plus higher feature quotas.
                                            <span x-show="!user.email_verified_at && !user.is_pro" class="block mt-1 text-amber-700">Verify this user's email before granting Pro.</span>
                                        </p>
                                        <button type="button" @click="togglePro()" :disabled="!user.is_pro && !user.email_verified_at"
                                            class="inline-flex items-center justify-center gap-2 rounded-lg px-3 py-2 text-sm font-semibold transition-colors disabled:cursor-not-allowed disabled:opacity-50"
                                            :class="user.is_pro ? 'bg-amber-100 text-amber-900 hover:bg-amber-200' : 'bg-gradient-to-r from-amber-500 to-orange-500 text-white hover:from-amber-600 hover:to-orange-600'">
                                            <x-lucide-crown class="h-4 w-4" />
                                            <span x-text="user.is_pro ? 'Remove Pro' : 'Grant Pro'"></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            {{-- AI consumption --}}
                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2"><x-lucide-sparkles class="h-5 w-5 text-purple-600" /> AI consumption</h3>
                                    <a href="{{ route('admin.ai-usage') }}" class="text-sm font-medium text-purple-600 hover:text-purple-800">Global AI usage →</a>
                                </div>
                                <template x-if="user.ai_tokens">
                                    <div class="mb-6 rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50 to-fuchsia-50/60 p-4">
                                        <div class="flex flex-wrap items-end justify-between gap-3 mb-3">
                                            <div>
                                                <p class="text-xs font-semibold uppercase tracking-wide text-gray-500" x-text="'Monthly token budget' + (user.ai_tokens.is_unlimited ? ' (admin)' : (user.is_pro ? ' (Pro)' : ' (free)'))"></p>
                                                <p x-show="user.ai_tokens.is_unlimited" class="text-lg font-bold text-emerald-700">Unlimited</p>
                                                <p x-show="!user.ai_tokens.is_unlimited" class="text-2xl font-bold text-gray-900 tabular-nums">
                                                    <span x-text="num(user.ai_tokens.credits_remaining ?? user.ai_tokens.tokens_remaining ?? 0)"></span>
                                                    <span class="text-base font-medium text-gray-500" x-text="' / ' + num(user.ai_tokens.credits_total ?? user.ai_tokens.token_limit ?? 0) + ' left'"></span>
                                                </p>
                                            </div>
                                            <p x-show="!user.ai_tokens.is_unlimited" class="text-sm text-gray-600 tabular-nums"
                                                x-text="'Used: ' + num(user.ai_tokens.credits_used ?? user.ai_tokens.tokens_used ?? 0) + (user.ai_tokens.percent_used != null ? ' (' + user.ai_tokens.percent_used + '%)' : '')"></p>
                                        </div>
                                        <template x-if="!user.ai_tokens.is_unlimited && user.ai_tokens.token_limit > 0">
                                            <div class="h-2.5 w-full overflow-hidden rounded-full bg-white ring-1 ring-violet-100">
                                                <div class="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500 transition-all" :style="'width: ' + Math.min(100, user.ai_tokens.percent_used ?? 0) + '%'"></div>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="user.ai_usage?.totals">
                                    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                                        <template x-for="[label, key] in [['Calls (month)', 'calls'], ['Total tokens', 'total_tokens'], ['Prompt', 'prompt_tokens'], ['Completion', 'completion_tokens']]" :key="key">
                                            <div class="rounded-lg bg-gray-50 p-3 text-center">
                                                <p class="text-[10px] font-semibold uppercase text-gray-500" x-text="label"></p>
                                                <p class="text-lg font-bold text-gray-900 tabular-nums" x-text="key === 'calls' ? (user.ai_usage.totals.calls ?? 0) : num(user.ai_usage.totals[key])"></p>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                                <template x-if="user.ai_usage?.by_kind?.length > 0">
                                    <div class="mb-4 overflow-x-auto">
                                        <table class="w-full text-sm">
                                            <thead><tr class="border-b border-gray-200 text-left text-xs uppercase text-gray-500"><th class="py-2 pr-4">Tool</th><th class="py-2 pr-4">Calls</th><th class="py-2">Tokens</th></tr></thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <template x-for="row in user.ai_usage.by_kind" :key="row.kind">
                                                    <tr>
                                                        <td class="py-2 pr-4 font-medium text-gray-900" x-text="AI_KIND_LABELS[row.kind] || row.kind"></td>
                                                        <td class="py-2 pr-4 tabular-nums" x-text="row.calls"></td>
                                                        <td class="py-2 tabular-nums" x-text="num(row.total_tokens)"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                                <template x-if="user.recent_ai_logs?.length > 0">
                                    <div>
                                        <p class="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Recent activity</p>
                                        <div class="max-h-48 overflow-y-auto rounded-lg border border-gray-100 divide-y divide-gray-100">
                                            <template x-for="log in user.recent_ai_logs" :key="log.id">
                                                <div class="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-xs">
                                                    <span class="font-medium text-gray-800" x-text="AI_KIND_LABELS[log.kind] || log.kind"></span>
                                                    <span class="text-gray-500 tabular-nums" x-text="log.total_tokens + ' tokens · ' + longDate(log.created_at)"></span>
                                                </div>
                                            </template>
                                        </div>
                                    </div>
                                </template>
                                <p x-show="!(user.recent_ai_logs?.length > 0)" class="text-sm text-gray-500">No AI usage recorded yet.</p>
                            </div>

                            {{-- Created CVs --}}
                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <div class="flex items-center justify-between mb-4">
                                    <h3 class="text-xl font-bold text-gray-900 flex items-center gap-2"><x-lucide-file-text class="h-5 w-5 text-purple-600" /> Created CVs</h3>
                                    <span class="text-sm text-gray-600" x-text="'Total: ' + (user.resumes_count || 0)"></span>
                                </div>
                                <template x-if="user.resumes && user.resumes.length > 0">
                                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50 border-b border-gray-200">
                                                <tr>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Name</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Template</th>
                                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Updated</th>
                                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <template x-for="resume in user.resumes" :key="resume.id">
                                                    <tr class="hover:bg-gray-50/80">
                                                        <td class="px-4 py-3"><p class="font-semibold text-gray-900" x-text="resume.name"></p><p class="text-xs text-gray-500" x-text="'#' + resume.id"></p></td>
                                                        <td class="px-4 py-3 text-gray-600" x-text="resume.template?.name || '—'"></td>
                                                        <td class="px-4 py-3 text-gray-600 whitespace-nowrap" x-text="longDate(resume.updated_at)"></td>
                                                        <td class="px-4 py-3">
                                                            <div class="flex items-center justify-end gap-1">
                                                                <button type="button" @click="showResume(resume.id)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors disabled:opacity-50" title="Show"><x-lucide-eye class="h-4 w-4" /></button>
                                                                <a :href="'/resume/edit/' + resume.id" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Open editor"><x-lucide-edit class="h-4 w-4" /></a>
                                                                <button type="button" @click="deleteResume(resume)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="Delete"><x-lucide-trash-2 class="h-4 w-4" /></button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </template>
                                <p x-show="!(user.resumes && user.resumes.length > 0)" class="text-gray-500 text-center py-8">No resumes created yet</p>
                            </div>
                        </div>

                        {{-- Sidebar --}}
                        <div class="space-y-6">
                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Profile Photo</h3>
                                <div class="flex flex-col items-center text-center">
                                    <div class="relative w-full">
                                        <div class="mx-auto h-56 w-56 rounded-[2.25rem] overflow-hidden border-4 border-purple-100 shadow-2xl bg-gradient-to-br from-purple-50 to-pink-50">
                                            <img :src="avatar" :alt="user.name" class="h-full w-full object-cover object-center" />
                                        </div>
                                        <span class="absolute -bottom-3 right-6 px-3 py-1 text-xs font-semibold rounded-full bg-white shadow text-gray-600 border border-gray-100" x-text="'ID #' + user.id"></span>
                                    </div>
                                    <p class="mt-4 text-sm text-gray-600" x-text="user.avatar ? 'Uploaded avatar' : 'Auto-generated fallback'"></p>
                                    <div class="mt-4 w-full space-y-2 text-sm">
                                        <div class="flex items-center justify-between text-gray-500"><span>Last update</span><span class="font-semibold text-gray-900" x-text="longDate(user.updated_at)"></span></div>
                                        <div class="flex items-center justify-between text-gray-500"><span>Status</span><span class="font-semibold text-gray-900" x-text="user.email_verified_at ? 'Verified' : 'Pending'"></span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Quick Stats</h3>
                                <div class="space-y-4">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center text-gray-600"><x-lucide-file-text class="h-5 w-5 mr-2" /><span>Resumes</span></div>
                                        <span class="text-2xl font-bold text-gray-900" x-text="user.resumes_count || 0"></span>
                                    </div>
                                    <template x-if="user.resumes_count > 0">
                                        <a :href="'/admin/users/' + user.id + '/cvs'" class="block w-full mt-4 px-4 py-2 bg-gradient-to-r from-purple-500 to-pink-600 text-white rounded-lg hover:from-purple-600 hover:to-pink-700 transition-all duration-200 text-sm font-semibold text-center">View All CVs</a>
                                    </template>
                                </div>
                            </div>

                            {{-- Ban panel --}}
                            <template x-if="!user.is_admin">
                                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                    <div class="flex items-center gap-2 mb-2">
                                        <x-lucide-ban class="h-5 w-5" ::class="user.ban?.is_banned ? 'text-red-600' : 'text-gray-500'" />
                                        <h3 class="text-lg font-bold text-gray-900">Account ban</h3>
                                    </div>
                                    <template x-if="user.ban?.is_banned">
                                        <div class="space-y-3">
                                            <div class="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
                                                <p class="font-semibold" x-text="user.ban.banned_permanently ? 'Permanently banned' : 'Temporarily banned'"></p>
                                                <p x-show="!user.ban.banned_permanently && user.ban.banned_until" class="mt-1 text-red-800" x-text="'Until ' + (user.ban.banned_until ? new Date(user.ban.banned_until).toLocaleString() : '')"></p>
                                                <p x-show="user.ban.ban_reason" class="mt-2 text-red-700"><span class="font-medium">Reason:</span> <span x-text="user.ban.ban_reason"></span></p>
                                                <p x-show="user.ban.banned_by?.name" class="mt-1 text-xs text-red-600" x-text="'By ' + (user.ban.banned_by?.name || '')"></p>
                                            </div>
                                            <button type="button" @click="unban()" :disabled="banLoading !== null" class="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-900 hover:bg-emerald-100 disabled:opacity-50">
                                                <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="banLoading === 'unban'" />
                                                <x-lucide-shield-off class="h-4 w-4" x-show="banLoading !== 'unban'" />
                                                Lift ban
                                            </button>
                                        </div>
                                    </template>
                                    <template x-if="!user.ban?.is_banned">
                                        <div class="space-y-3">
                                            <p class="text-sm text-gray-600 mb-3">Suspend sign-in and API access. Tokens are revoked immediately.</p>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">Duration</label>
                                                <select x-model="banDuration" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm">
                                                    <option value="3_days">3 days</option>
                                                    <option value="7_days">7 days</option>
                                                    <option value="15_days">15 days</option>
                                                    <option value="1_month">1 month</option>
                                                    <option value="permanent">Permanent ban</option>
                                                </select>
                                            </div>
                                            <div>
                                                <label class="block text-xs font-semibold text-gray-600 mb-1">Reason (optional)</label>
                                                <textarea rows="2" x-model="banReason" placeholder="Internal note shown in admin only" class="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm resize-none"></textarea>
                                            </div>
                                            <button type="button" @click="ban()" :disabled="banLoading !== null" class="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50">
                                                <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="banLoading === 'ban'" />
                                                <x-lucide-ban class="h-4 w-4" x-show="banLoading !== 'ban'" />
                                                Apply ban
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>

                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <h3 class="text-lg font-bold text-gray-900 mb-2">Email templates</h3>
                                <p class="text-sm text-gray-600 mb-4">Queue automated reminders. Delivery requires the queue worker.</p>
                                <div class="flex flex-col gap-2">
                                    <button type="button" :disabled="reminderLoading === 'resume'" @click="reminder('resume')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-violet-200 bg-violet-50 px-4 py-2.5 text-sm font-semibold text-violet-900 hover:bg-violet-100 disabled:opacity-50">
                                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="reminderLoading === 'resume'" />
                                        <x-lucide-file-warning class="h-4 w-4" x-show="reminderLoading !== 'resume'" />
                                        Resume incomplete reminder
                                    </button>
                                    <button type="button" :disabled="reminderLoading === 'verify' || Boolean(user.email_verified_at)" @click="reminder('verify')" class="inline-flex items-center justify-center gap-2 rounded-xl border border-blue-200 bg-blue-50 px-4 py-2.5 text-sm font-semibold text-blue-900 hover:bg-blue-100 disabled:opacity-50">
                                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="reminderLoading === 'verify'" />
                                        <x-lucide-mail class="h-4 w-4" x-show="reminderLoading !== 'verify'" />
                                        Email verification reminder
                                    </button>
                                </div>
                                <a href="{{ route('admin.emails') }}" class="mt-3 inline-block text-sm font-medium text-purple-600 hover:text-purple-800">View all outbound logs →</a>
                            </div>

                            <x-admin.new-features-form user-name="user.name" />

                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Send Message</h3>
                                <p class="text-sm text-gray-600 mb-4" x-text="'Reach out to ' + user.name + ' directly. This will send an email from your admin account.'"></p>
                                <form @submit.prevent="sendMessage()" class="space-y-4">
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" for="email-subject">Subject</label>
                                        <input id="email-subject" type="text" class="{{ $input }}" x-model="emailForm.subject" placeholder="Subject line" required />
                                    </div>
                                    <div>
                                        <label class="block text-sm font-medium text-gray-700 mb-1" for="email-body">Message</label>
                                        <textarea id="email-body" rows="5" class="{{ $input }} resize-none" x-model="emailForm.message" placeholder="Write your message..." required></textarea>
                                        <div class="mt-2 flex justify-end">
                                            <x-enhance-button value="emailForm.message" context="admin user email" />
                                        </div>
                                    </div>
                                    <button type="submit" :disabled="sendingEmail" class="w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-white font-semibold transition"
                                        :class="sendingEmail ? 'bg-slate-400 cursor-not-allowed' : 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg shadow-blue-500/30'">
                                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="sendingEmail" />
                                        <span x-text="sendingEmail ? 'Sending...' : 'Send Email'"></span>
                                    </button>
                                </form>
                            </div>

                            <template x-if="(user.recent_outbound_emails?.length ?? 0) > 0">
                                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                    <h3 class="text-lg font-bold text-gray-900 mb-4">Email history</h3>
                                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                                        <table class="w-full text-sm">
                                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500"><tr><th class="px-3 py-2">Status</th><th class="px-3 py-2">Type</th><th class="px-3 py-2">Subject</th><th class="px-3 py-2">When</th></tr></thead>
                                            <tbody class="divide-y divide-gray-100">
                                                <template x-for="row in user.recent_outbound_emails" :key="row.id">
                                                    <tr>
                                                        <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold" :class="statusBadge(row.status).className" x-text="statusBadge(row.status).label"></span></td>
                                                        <td class="px-3 py-2 text-gray-600" x-text="typeLabel(row.type)"></td>
                                                        <td class="px-3 py-2 text-gray-700 max-w-[12rem] truncate" :title="row.subject" x-text="row.subject || '—'"></td>
                                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500" x-text="longDate(row.created_at)"></td>
                                                    </tr>
                                                </template>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </template>

                            <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
                                <h3 class="text-lg font-bold text-gray-900 mb-4">Account Information</h3>
                                <div class="space-y-3 text-sm">
                                    <div><span class="text-gray-600">User ID:</span><p class="text-gray-900 font-semibold" x-text="'#' + user.id"></p></div>
                                    <div><span class="text-gray-600">Email Verified:</span><p class="text-gray-900 font-semibold" x-text="user.email_verified_at ? longDate(user.email_verified_at) : 'Not verified'"></p></div>
                                    <div><span class="text-gray-600">Created:</span><p class="text-gray-900 font-semibold" x-text="longDate(user.created_at)"></p></div>
                                    <div><span class="text-gray-600">Last Updated:</span><p class="text-gray-900 font-semibold" x-text="longDate(user.updated_at)"></p></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>
    </div>

    <div data-island="admin-resume-preview"></div>
</x-layouts.admin>
