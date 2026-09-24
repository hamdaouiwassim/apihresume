{{-- Port of pages/PersonalWebsiteView.jsx (/u/{slug} and /website/{token}), rendered server-side for SEO --}}
@php
    $fr = app()->getLocale() === 'fr';
@endphp
@if (! $resume)
    <x-layouts.base title="Profile unavailable | HResume" robots="noindex, nofollow" body-class="bg-slate-50">
        <div class="min-h-screen bg-slate-50 flex items-center justify-center px-4">
            <div class="max-w-md w-full bg-white rounded-2xl shadow-lg border border-slate-200 p-6 text-center">
                <x-lucide-globe class="h-10 w-10 text-rose-500 mx-auto mb-3" />
                <h1 class="text-xl font-semibold text-slate-900 mb-2">Profile unavailable</h1>
                <p class="text-slate-600 mb-4">{{ $error ?: 'This public profile could not be loaded.' }}</p>
                <a href="{{ url('/') }}" class="inline-flex items-center px-4 py-2 rounded-lg bg-indigo-600 text-white hover:bg-indigo-700 transition-colors">
                    <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                    Back to home
                </a>
            </div>
        </div>
    </x-layouts.base>
@else
    @php
        $basic = $resume['basic_info'] ?? [];
        $experiences = $resume['experiences'] ?? [];
        $educations = $resume['educations'] ?? [];
        $skills = $resume['skills'] ?? [];
        $projects = $resume['projects'] ?? [];
        $certifications = $resume['certificates'] ?? [];
        $slug = $routeSlug;
        $canPdf = $slug && ($meta['profile_slug'] ?? null) === $slug;
        $fullName = ($basic['full_name'] ?? null) ?: ($resume['name'] ?? 'Professional Profile');
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'mainEntity' => array_filter([
                '@type' => 'Person',
                'name' => $fullName,
                'jobTitle' => $basic['job_title'] ?? null,
                'description' => $basic['professional_summary'] ?? null,
                'image' => $basic['avatar'] ?? null,
            ]),
        ];
        $state = ['slug' => $slug, 'canPdf' => (bool) $canPdf, 'filename' => (($resume['name'] ?? null) ?: ($basic['full_name'] ?? 'cv')).'.pdf'];
        $badgeOff = 'bg-slate-100 text-slate-600';
    @endphp
    <x-layouts.base
        :title="$meta['title'] ?? $fullName"
        :description="$meta['description'] ?? null"
        :robots="$meta['robots'] ?? 'noindex, nofollow'"
        :canonical="request()->path()"
        :image="$basic['avatar'] ?? null"
        og-type="profile"
        :json-ld="$jsonLd"
    >
        <div class="min-h-screen bg-slate-50" x-data="personalWebsite(@js($state))">
            <div class="bg-gradient-to-r from-indigo-700 via-purple-700 to-indigo-800 text-white" x-effect="themed($el, 'hero')">
                <div class="max-w-5xl mx-auto px-6 py-14">
                    <div class="flex items-start justify-between gap-6 flex-wrap">
                        <div class="flex items-start gap-4">
                            @if (! empty($basic['avatar']))
                                <img src="{{ $basic['avatar'] }}" alt="{{ $basic['full_name'] ?? 'Profile avatar' }}" class="h-20 w-20 rounded-full object-cover border-2 border-white/40" />
                            @endif
                            <div>
                                <h1 class="text-3xl font-bold">{{ $fullName }}</h1>
                                <p class="text-indigo-100 mt-1">{{ ($basic['job_title'] ?? null) ?: 'Career Profile' }}</p>
                            </div>
                        </div>
                        <div class="flex flex-col items-end gap-2">
                            <span class="inline-flex items-center px-3 py-1 rounded-full bg-white/15 text-sm">
                                <x-lucide-globe class="h-4 w-4 mr-2" />
                                Public profile
                            </span>
                            <button type="button" @click="downloadPdf()" :disabled="downloading || !@js((bool) $canPdf)"
                                @unless ($canPdf) title="{{ $fr ? 'Disponible pour les URLs /u/slug' : 'Available for /u/slug public profiles' }}" @endunless
                                class="inline-flex items-center gap-2 rounded-xl bg-white/20 px-4 py-2 text-sm font-semibold hover:bg-white/30 disabled:opacity-50 disabled:cursor-not-allowed">
                                <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="downloading" x-cloak />
                                <x-lucide-download class="h-4 w-4" x-show="!downloading" />
                                {{ $fr ? 'Telecharger le CV (PDF)' : 'Download CV (PDF)' }}
                            </button>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap gap-3 text-sm text-indigo-100">
                        @foreach ([['email', 'mail'], ['phone', 'phone'], ['location', 'map-pin']] as [$key, $icon])
                            @if (! empty($basic[$key]))
                                <span class="inline-flex items-center">
                                    <x-dynamic-component :component="'lucide-'.$icon" class="h-4 w-4 mr-1" />
                                    {{ $basic[$key] }}
                                </span>
                            @endif
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="max-w-5xl mx-auto px-6 py-10 space-y-8">
                <section class="bg-white border border-slate-200 rounded-2xl p-4">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-medium text-slate-700">Theme</span>
                            @foreach (['indigo' => 'Indigo', 'emerald' => 'Emerald', 'slate' => 'Slate'] as $key => $label)
                                <button type="button" @click="theme = '{{ $key }}'" class="px-3 py-1 rounded-full text-xs {{ $key === 'indigo' ? 'bg-indigo-50 text-indigo-700' : $badgeOff }}"
                                    x-effect="themed($el, 'badge', theme === '{{ $key }}')">{{ $label }}</button>
                            @endforeach
                        </div>
                        <div class="flex flex-wrap items-center gap-2">
                            @foreach (['summary' => 'Summary', 'experience' => 'Experience', 'projects' => 'Projects', 'skills' => 'Skills', 'education' => 'Education', 'certifications' => 'Certs'] as $key => $label)
                                <button type="button" @click="show.{{ $key }} = !show.{{ $key }}" class="px-3 py-1 rounded-full text-xs bg-indigo-50 text-indigo-700"
                                    x-effect="themed($el, 'badge', show.{{ $key }}, 'bg-slate-100 text-slate-500')">{{ $label }}</button>
                            @endforeach
                        </div>
                    </div>
                </section>

                @if (! empty($basic['professional_summary']))
                    <section class="bg-white border border-slate-200 rounded-2xl p-6" x-show="show.summary">
                        <h2 class="text-lg font-semibold text-slate-900 mb-3">About Me</h2>
                        <p class="text-slate-700 whitespace-pre-wrap">{{ $basic['professional_summary'] }}</p>
                    </section>
                @endif

                @if (count($experiences))
                    <section class="bg-white border border-slate-200 rounded-2xl p-6" x-show="show.experience">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4 inline-flex items-center">
                            <x-lucide-briefcase class="h-5 w-5 mr-2 text-indigo-600" />
                            Experience
                        </h2>
                        <div class="space-y-4">
                            @foreach ($experiences as $exp)
                                @php $end = $exp['endDate'] ?? $exp['end_date'] ?? null; @endphp
                                <div class="border-b border-slate-100 pb-4 last:border-b-0 last:pb-0">
                                    <p class="font-medium text-slate-900">{{ ($exp['position'] ?? null) ?: 'Role' }} {{ ! empty($exp['company']) ? '- '.$exp['company'] : '' }}</p>
                                    <p class="text-xs text-slate-500 mb-2">{{ $exp['startDate'] ?? $exp['start_date'] ?? '' }} {{ $end ? 'to '.$end : '' }}</p>
                                    @if (! empty($exp['description']))
                                        <p class="text-sm text-slate-700 whitespace-pre-wrap">{{ $exp['description'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if (count($projects))
                    <section class="bg-white border border-slate-200 rounded-2xl p-6" x-show="show.projects">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4">Projects</h2>
                        <div class="space-y-3">
                            @foreach ($projects as $project)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <p class="font-medium text-slate-900">{{ ($project['name'] ?? null) ?: 'Project' }}</p>
                                    @if (! empty($project['description']))
                                        <p class="text-sm text-slate-700 mt-2 whitespace-pre-wrap">{{ $project['description'] }}</p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                <div class="grid grid-cols-1 md:grid-cols-2 gap-8">
                    @if (count($skills))
                        <section class="bg-white border border-slate-200 rounded-2xl p-6" x-show="show.skills">
                            <h2 class="text-lg font-semibold text-slate-900 mb-4">Skills</h2>
                            <div class="flex flex-wrap gap-2">
                                @foreach ($skills as $skill)
                                    <span class="px-3 py-1 rounded-full text-sm bg-indigo-50 text-indigo-700" x-effect="themed($el, 'chip')">{{ is_array($skill) ? ($skill['name'] ?? '') : $skill }}</span>
                                @endforeach
                            </div>
                        </section>
                    @endif

                    @if (count($educations))
                        <section class="bg-white border border-slate-200 rounded-2xl p-6" x-show="show.education">
                            <h2 class="text-lg font-semibold text-slate-900 mb-4 inline-flex items-center">
                                <x-lucide-graduation-cap class="h-5 w-5 mr-2 text-indigo-600" />
                                Education
                            </h2>
                            <div class="space-y-3">
                                @foreach ($educations as $edu)
                                    <div>
                                        <p class="font-medium text-slate-900">{{ ($edu['degree'] ?? null) ?: 'Degree' }}</p>
                                        <p class="text-sm text-slate-600">{{ $edu['institution'] ?? '' }}</p>
                                    </div>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                @if (count($certifications))
                    <section class="bg-white border border-slate-200 rounded-2xl p-6" x-show="show.certifications">
                        <h2 class="text-lg font-semibold text-slate-900 mb-4 inline-flex items-center">
                            <x-lucide-award class="h-5 w-5 mr-2 text-indigo-600" />
                            Certifications
                        </h2>
                        <ul class="space-y-2">
                            @foreach ($certifications as $cert)
                                <li class="text-sm text-slate-700">{{ ($cert['name'] ?? null) ?: (($cert['title'] ?? null) ?: 'Certification') }}</li>
                            @endforeach
                        </ul>
                    </section>
                @endif
            </div>
        </div>
    </x-layouts.base>
@endif
