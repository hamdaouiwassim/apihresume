{{-- Port of pages/admin/Dashboard.jsx (server-rendered from Admin\DashboardController@index) --}}
@php
    use App\Support\OutboundEmailLabels;
    $s = $stats;
    $user = auth()->user();
    $hour = (int) now()->format('G');
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');
    $firstName = $user?->name ? explode(' ', $user->name)[0] : null;
    $date = fn ($d) => $d ? \Illuminate\Support\Carbon::parse($d)->format('M j, Y') : 'N/A';
    $activityMax = max($s['total_users'] ?? 1, $s['active_users_7d'] ?? 0, $s['active_users_24h'] ?? 0, 1);
    $aiTokens = $s['ai_usage_30d']['total_tokens'] ?? null;
    $statCards = [
        ['label' => 'Total users', 'value' => $s['total_users'] ?? 0, 'href' => '/admin/users', 'link' => 'Manage users', 'icon' => 'users', 'gradient' => 'bg-blue-400', 'iconBg' => 'bg-blue-50', 'iconColor' => 'text-blue-600'],
        ['label' => 'Admins', 'value' => $s['total_admins'] ?? 0, 'href' => '/admin/users?role=admin', 'link' => 'View admins', 'icon' => 'shield', 'gradient' => 'bg-violet-400', 'iconBg' => 'bg-violet-50', 'iconColor' => 'text-violet-600'],
        ['label' => 'AI usage (30d)', 'value' => $s['ai_usage_30d']['calls'] ?? 0, 'subtext' => $aiTokens !== null ? number_format($aiTokens).' tokens' : null, 'href' => '/admin/ai-usage', 'link' => 'Usage & logs', 'icon' => 'sparkles', 'gradient' => 'bg-fuchsia-400', 'iconBg' => 'bg-fuchsia-50', 'iconColor' => 'text-fuchsia-600'],
        ['label' => 'Templates', 'value' => $s['total_templates'] ?? 0, 'href' => '/admin/templates', 'link' => 'Manage templates', 'icon' => 'layout', 'gradient' => 'bg-pink-400', 'iconBg' => 'bg-pink-50', 'iconColor' => 'text-pink-600'],
        ['label' => 'Resumes', 'value' => $s['total_resumes'] ?? 0, 'subtext' => 'Total created', 'icon' => 'file-text', 'gradient' => 'bg-emerald-400', 'iconBg' => 'bg-emerald-50', 'iconColor' => 'text-emerald-600'],
        ['label' => 'Cover letters', 'value' => $s['total_cover_letters'] ?? 0, 'href' => '/admin/cover-letters', 'link' => 'View generated', 'icon' => 'mail', 'gradient' => 'bg-amber-400', 'iconBg' => 'bg-amber-50', 'iconColor' => 'text-amber-600'],
        ['label' => 'Work certificates', 'value' => $s['total_work_certificates'] ?? 0, 'href' => '/admin/work-certificates', 'link' => 'Manage certificates', 'icon' => 'scroll-text', 'gradient' => 'bg-indigo-400', 'iconBg' => 'bg-indigo-50', 'iconColor' => 'text-indigo-600'],
    ];
    $meters = [
        ['Active users (24h)', $s['active_users_24h'] ?? 0, $activityMax, 'bg-gradient-to-r from-purple-500 to-violet-500'],
        ['Active users (7d)', $s['active_users_7d'] ?? 0, $activityMax, 'bg-gradient-to-r from-pink-500 to-rose-500'],
        ['Cover letters this month', $s['cover_letters_this_month'] ?? 0, max($s['total_cover_letters'] ?: 1, $s['cover_letters_this_month'] ?? 0), 'bg-gradient-to-r from-amber-400 to-orange-500'],
        ['Work certificates this month', $s['work_certificates_this_month'] ?? 0, max($s['total_work_certificates'] ?: 1, $s['work_certificates_this_month'] ?? 0), 'bg-gradient-to-r from-indigo-500 to-blue-500'],
    ];
    $quick = [
        ['/admin/emails', 'send', 'Outbound emails', 'Queue, sent & failed mail logs', 'soft'],
        ['/admin/ai-usage', 'sparkles', 'AI usage & tokens', 'Per-user LLM calls and token totals', 'soft'],
        ['/admin/users', 'users', 'Manage users', 'Accounts and roles', 'primary'],
        ['/admin/templates', 'layout', 'Resume templates', 'Edit and publish CV layouts', 'default'],
        ['/admin/blog', 'pen-line', 'Blog posts', 'Create and publish articles', 'default'],
        ['/admin/cover-letters', 'mail', 'Generated cover letters', 'Browse user-generated letters', 'default'],
        ['/admin/work-certificates', 'scroll-text', 'Work certificates', 'Review employment certificates', 'soft'],
    ];
    $variants = [
        'primary' => 'border-purple-200/80 bg-gradient-to-br from-purple-600 to-pink-600 text-white shadow-md shadow-purple-200/50 hover:shadow-lg hover:shadow-purple-300/40',
        'default' => 'border-gray-100 bg-white text-gray-900 hover:border-purple-100 hover:bg-purple-50/50',
        'soft' => 'border-indigo-100 bg-indigo-50/80 text-indigo-950 hover:bg-indigo-100',
    ];
@endphp
<x-layouts.admin title="Admin Dashboard | HResume">
    <div class="space-y-8 pb-4" x-data="{ refreshing: false }">
        <section class="relative overflow-hidden rounded-3xl border border-purple-100/80 bg-gradient-to-br from-purple-600 via-violet-600 to-pink-600 p-6 text-white shadow-xl shadow-purple-200/40 md:p-8">
            <div class="pointer-events-none absolute -right-20 -top-20 h-56 w-56 rounded-full bg-white/10 blur-3xl" aria-hidden="true"></div>
            <div class="pointer-events-none absolute -bottom-16 left-1/3 h-40 w-40 rounded-full bg-pink-400/30 blur-3xl" aria-hidden="true"></div>
            <div class="relative flex flex-col gap-6 lg:flex-row lg:items-center lg:justify-between">
                <div>
                    <div class="mb-3 inline-flex items-center gap-2 rounded-full bg-white/15 px-3 py-1 text-xs font-semibold backdrop-blur-sm">
                        <x-lucide-sparkles class="h-3.5 w-3.5" />
                        Admin overview
                    </div>
                    <h1 class="text-2xl font-bold tracking-tight md:text-3xl">{{ $greeting }}{{ $firstName ? ', '.$firstName : '' }}</h1>
                    <p class="mt-2 max-w-xl text-sm text-white/85 md:text-base">Monitor users, content, and platform activity from one place.</p>
                </div>
                <div class="flex flex-wrap items-center gap-3">
                    <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-xs font-medium text-white/70">Active (7 days)</p>
                        <p class="text-2xl font-bold tabular-nums">{{ $s['active_users_7d'] ?? 0 }}</p>
                    </div>
                    <div class="rounded-2xl bg-white/10 px-4 py-3 backdrop-blur-sm">
                        <p class="text-xs font-medium text-white/70">This month</p>
                        <p class="text-2xl font-bold tabular-nums">{{ ($s['cover_letters_this_month'] ?? 0) + ($s['work_certificates_this_month'] ?? 0) }}</p>
                        <p class="text-[10px] text-white/60">CL + certificates</p>
                    </div>
                    <button type="button" @click="refreshing = true; window.location.reload()" :disabled="refreshing"
                        class="inline-flex items-center gap-2 rounded-xl bg-white px-4 py-2.5 text-sm font-semibold text-purple-700 shadow-lg transition hover:bg-purple-50 disabled:opacity-70">
                        <x-lucide-refresh-cw class="h-4 w-4" ::class="refreshing ? 'animate-spin' : ''" />
                        Refresh
                    </button>
                </div>
            </div>
        </section>

        <section>
            <div class="mb-4 flex items-center gap-2">
                <x-lucide-bar-chart-3 class="h-5 w-5 text-purple-600" />
                <h2 class="text-lg font-bold text-gray-900">Key metrics</h2>
            </div>
            <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-3 2xl:grid-cols-4">
                @foreach ($statCards as $i => $card)
                    @php $tag = isset($card['href']) ? 'a' : 'div'; @endphp
                    <{{ $tag }} @isset($card['href']) href="{{ url($card['href']) }}" @endisset class="block">
                        <article class="group relative overflow-hidden rounded-2xl border border-white/60 bg-white p-5 shadow-sm ring-1 ring-gray-100/80 transition-all duration-300 hover:-translate-y-0.5 hover:shadow-lg hover:ring-purple-100 {{ isset($card['href']) ? 'cursor-pointer' : '' }}" style="animation-delay: {{ $i * 40 }}ms">
                            <div class="pointer-events-none absolute -right-6 -top-6 h-28 w-28 rounded-full opacity-40 blur-2xl transition-opacity group-hover:opacity-60 {{ $card['gradient'] }}" aria-hidden="true"></div>
                            <div class="relative flex items-start justify-between gap-3">
                                <div class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl shadow-sm {{ $card['iconBg'] }}">
                                    <x-dynamic-component :component="'lucide-'.$card['icon']" class="h-6 w-6 {{ $card['iconColor'] }}" stroke-width="2" />
                                </div>
                                <span class="text-right text-xs font-semibold uppercase tracking-wide text-gray-400">{{ $card['label'] }}</span>
                            </div>
                            <p class="relative mt-4 text-3xl font-bold tracking-tight text-gray-900 tabular-nums">{{ $card['value'] }}</p>
                            @if (! empty($card['subtext']))
                                <p class="relative mt-1 text-sm text-gray-500">{{ $card['subtext'] }}</p>
                            @endif
                            @isset($card['href'])
                                <span class="relative mt-4 inline-flex items-center gap-1 text-sm font-semibold text-purple-600 transition-colors group-hover:text-purple-700">
                                    {{ $card['link'] }}
                                    <x-lucide-arrow-up-right class="h-4 w-4 transition-transform group-hover:translate-x-0.5 group-hover:-translate-y-0.5" />
                                </span>
                            @endisset
                        </article>
                    </{{ $tag }}>
                @endforeach
            </div>
        </section>

        <div class="grid grid-cols-1 gap-6 lg:grid-cols-5">
            <section class="lg:col-span-3 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-100/80">
                <div class="mb-6 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-purple-50">
                        <x-lucide-activity class="h-5 w-5 text-purple-600" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Engagement</h2>
                        <p class="text-sm text-gray-500">Activity relative to your user base</p>
                    </div>
                </div>
                <div class="space-y-5">
                    @foreach ($meters as [$label, $value, $max, $accent])
                        @php $pct = $max > 0 ? min(100, (int) round($value / $max * 100)) : 0; @endphp
                        <div class="space-y-2">
                            <div class="flex items-center justify-between gap-2 text-sm">
                                <span class="font-medium text-gray-600">{{ $label }}</span>
                                <span class="font-bold tabular-nums text-gray-900">{{ $value }}</span>
                            </div>
                            <div class="h-2 overflow-hidden rounded-full bg-gray-100">
                                <div class="h-full rounded-full transition-all duration-700 {{ $accent }}" style="width: {{ $pct }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <div class="mt-6 flex flex-wrap gap-4 rounded-xl bg-gray-50 p-4 text-sm">
                    <div class="flex items-center gap-2 text-gray-600">
                        <x-lucide-clock class="h-4 w-4 text-gray-400" />
                        <span><strong class="text-gray-900">{{ $s['total_users'] ?? 0 }}</strong> registered users</span>
                    </div>
                    <div class="flex items-center gap-2 text-gray-600">
                        <x-lucide-trending-up class="h-4 w-4 text-emerald-500" />
                        <span><strong class="text-gray-900">{{ $s['total_resumes'] ?? 0 }}</strong> resumes built</span>
                    </div>
                </div>
            </section>

            <section class="lg:col-span-2 rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-100/80">
                <div class="mb-5 flex items-center gap-3">
                    <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-pink-50">
                        <x-lucide-sparkles class="h-5 w-5 text-pink-600" />
                    </div>
                    <div>
                        <h2 class="text-lg font-bold text-gray-900">Quick actions</h2>
                        <p class="text-sm text-gray-500">Jump to common admin tasks</p>
                    </div>
                </div>
                <div class="grid gap-3 sm:grid-cols-1">
                    @php
                        $quickCard = function ($to, $icon, $title, $desc, $variant) use ($variants) {
                            return view('components.admin.quick-action', compact('to', 'icon', 'title', 'desc', 'variant') + ['classes' => $variants[$variant]])->render();
                        };
                    @endphp
                    @foreach ($quick as $q)
                        {!! $quickCard(...$q) !!}
                    @endforeach
                    <div class="grid grid-cols-2 gap-3">
                        {!! $quickCard('/admin/reviews', 'message-square', 'Reviews', 'Moderate feedback', 'default') !!}
                        {!! $quickCard('/admin/fonts', 'type', 'Fonts', 'PDF typography', 'default') !!}
                    </div>
                </div>
            </section>
        </div>

        @if (! empty($s['outbound_emails']))
            @php $oe = $s['outbound_emails']; @endphp
            <section class="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm ring-1 ring-gray-100/80">
                <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <div class="flex items-center gap-3">
                        <div class="flex h-10 w-10 items-center justify-center rounded-xl bg-violet-50">
                            <x-lucide-send class="h-5 w-5 text-violet-600" />
                        </div>
                        <div>
                            <h2 class="text-lg font-bold text-gray-900">Outbound emails</h2>
                            <p class="text-sm text-gray-500">Last 30 days · queue worker required</p>
                        </div>
                    </div>
                    <a href="{{ route('admin.emails') }}" class="text-sm font-semibold text-purple-600 hover:text-purple-800">View all logs →</a>
                </div>
                <div class="mb-4 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
                    @foreach ([['Queued', 'queued', false], ['Sent', 'sent', false], ['Failed', 'failed', false], ['Sent 24h', 'sent_24h', false], ['Stale', 'stale_queued', true], ['Jobs', 'jobs_pending', false]] as [$label, $key, $warn])
                        @php $v = $oe[$key] ?? 0; $hot = $warn && (int) $v > 0; @endphp
                        <div class="rounded-xl border px-3 py-2 {{ $hot ? 'border-amber-200 bg-amber-50' : 'border-gray-100 bg-gray-50' }}">
                            <p class="text-xs text-gray-500">{{ $label }}</p>
                            <p class="text-lg font-bold tabular-nums {{ $hot ? 'text-amber-800' : 'text-gray-900' }}">{{ $v }}</p>
                        </div>
                    @endforeach
                </div>
                @if (count($s['recent_outbound_emails'] ?? []) > 0)
                    <div class="overflow-x-auto rounded-xl border border-gray-100">
                        <table class="w-full text-sm">
                            <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                                <tr>
                                    <th class="px-3 py-2">Status</th>
                                    <th class="px-3 py-2">Type</th>
                                    <th class="px-3 py-2">To</th>
                                    <th class="px-3 py-2">When</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-100">
                                @foreach ($s['recent_outbound_emails'] as $row)
                                    @php $b = OutboundEmailLabels::status($row['status'] ?? null); @endphp
                                    <tr>
                                        <td class="px-3 py-2"><span class="rounded-full px-2 py-0.5 text-xs font-semibold {{ $b['className'] }}">{{ $b['label'] }}</span></td>
                                        <td class="px-3 py-2 text-gray-600">{{ OutboundEmailLabels::type($row['type'] ?? null) }}</td>
                                        <td class="px-3 py-2"><a href="{{ url('/admin/users/'.$row['user_id']) }}" class="text-purple-600 hover:underline">{{ $row['recipient_email'] ?? '' }}</a></td>
                                        <td class="px-3 py-2 whitespace-nowrap text-xs text-gray-500">{{ $date($row['created_at'] ?? null) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <p class="py-4 text-center text-sm text-gray-500">No outbound emails yet.</p>
                @endif
            </section>
        @endif

        <section>
            <div class="mb-4 flex items-center justify-between gap-4">
                <div class="flex items-center gap-2">
                    <x-lucide-clock class="h-5 w-5 text-gray-500" />
                    <h2 class="text-lg font-bold text-gray-900">Recent activity</h2>
                </div>
                <p class="text-sm text-gray-500">Latest signups and content</p>
            </div>
            <div class="grid grid-cols-1 gap-6 md:grid-cols-2 xl:grid-cols-4">
                <x-admin.recent-section title="Users" icon="users" icon-class="bg-blue-50 text-blue-600" href="/admin/users" href-class="text-blue-600" empty="No recent users" :count="count($s['recent_users'] ?? [])">
                    @foreach ($s['recent_users'] ?? [] as $u)
                        <li>
                            <a href="{{ url('/admin/users') }}" class="flex items-center gap-3 rounded-xl border border-transparent p-3 transition-colors hover:border-purple-100 hover:bg-purple-50/40">
                                <img src="{{ ($u['avatar'] ?? null) ?: default_avatar() }}" alt="" class="h-10 w-10 rounded-full border-2 border-white shadow ring-1 ring-purple-100" />
                                <div class="min-w-0 flex-1">
                                    <p class="truncate font-semibold text-gray-900">{{ $u['name'] }}</p>
                                    <p class="truncate text-xs text-gray-500">{{ $u['email'] }}</p>
                                </div>
                                <time class="shrink-0 text-xs font-medium text-gray-400">{{ $date($u['created_at'] ?? null) }}</time>
                            </a>
                        </li>
                    @endforeach
                </x-admin.recent-section>

                <x-admin.recent-section title="Templates" icon="layout" icon-class="bg-pink-50 text-pink-600" href="/admin/templates" href-class="text-pink-600" empty="No recent templates" :count="count($s['recent_templates'] ?? [])">
                    @foreach ($s['recent_templates'] ?? [] as $tpl)
                        <li>
                            <div class="rounded-xl border border-transparent p-3 transition-colors hover:border-pink-100 hover:bg-pink-50/40">
                                <p class="font-semibold text-gray-900">{{ $tpl['name'] }}</p>
                                <div class="mt-1 flex items-center justify-between gap-2">
                                    <span class="rounded-md bg-gray-100 px-2 py-0.5 text-xs font-medium text-gray-600">{{ ($tpl['category'] ?? null) ?: 'General' }}</span>
                                    <time class="text-xs text-gray-400">{{ $date($tpl['created_at'] ?? null) }}</time>
                                </div>
                            </div>
                        </li>
                    @endforeach
                </x-admin.recent-section>

                <x-admin.recent-section title="Cover letters" icon="mail" icon-class="bg-amber-50 text-amber-600" href="/admin/cover-letters" href-class="text-amber-600" empty="No generated cover letters" :count="count($s['recent_cover_letters'] ?? [])">
                    @foreach ($s['recent_cover_letters'] ?? [] as $letter)
                        <li>
                            <div class="rounded-xl border border-transparent p-3 transition-colors hover:border-amber-100 hover:bg-amber-50/40">
                                <p class="font-semibold text-gray-900 line-clamp-1">{{ $letter['title'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $letter['user']['name'] ?? 'Unknown' }} · {{ ($letter['style'] ?? null) ?: 'classic' }}</p>
                                <time class="mt-1 block text-xs text-gray-400">{{ $date($letter['created_at'] ?? null) }}</time>
                            </div>
                        </li>
                    @endforeach
                </x-admin.recent-section>

                <x-admin.recent-section title="Work certificates" icon="scroll-text" icon-class="bg-indigo-50 text-indigo-600" href="/admin/work-certificates" href-class="text-indigo-600" empty="No work certificates yet" :count="count($s['recent_work_certificates'] ?? [])">
                    @foreach ($s['recent_work_certificates'] ?? [] as $cert)
                        <li>
                            <div class="rounded-xl border border-transparent p-3 transition-colors hover:border-indigo-100 hover:bg-indigo-50/40">
                                <p class="font-semibold text-gray-900 line-clamp-1">{{ $cert['title'] }}</p>
                                <p class="mt-1 text-xs text-gray-500">{{ $cert['user']['name'] ?? 'Unknown' }} · {{ $cert['company_name'] ?? '' }}</p>
                                <time class="mt-1 block text-xs text-gray-400">{{ $date($cert['created_at'] ?? null) }}</time>
                            </div>
                        </li>
                    @endforeach
                </x-admin.recent-section>
            </div>
        </section>
    </div>
</x-layouts.admin>
