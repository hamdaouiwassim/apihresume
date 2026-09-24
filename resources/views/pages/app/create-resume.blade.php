{{-- Port of pages/createResume.jsx (guest: /resume/start with GuestLayout, signed in: /resume/create with AuthLayout) --}}
@php
    $cr = (array) t('createResume', [], []);
    $state = [
        'templates' => $templates->map(fn ($tpl) => [
            'id' => $tpl->id, 'name' => $tpl->name, 'description' => $tpl->description,
            'category' => $tpl->category, 'preview_image_url' => $tpl->preview_image_url,
        ])->values(),
        'allowGuest' => $allowGuest,
        'canCreate' => $canCreate,
        'initialTemplate' => $initialTemplate,
        'strings' => [
            'validationError' => $cr['validationError'] ?? 'Please choose a template and enter a resume name.',
            'guestDraftSavedToast' => $cr['guestDraftSavedToast'] ?? 'Your choices are saved. Create an account to open your resume.',
            'creationError' => $cr['creationError'] ?? 'Something went wrong. Please try again.',
            'limitReached' => $cr['limitReached'] ?? 'Free plan includes one resume. Upgrade to Pro to create more.',
        ],
    ];
    $layout = $allowGuest ? 'layouts.guest' : 'layouts.app';
@endphp
<x-dynamic-component :component="$layout" :title="($cr['title'] ?? 'Create your resume').' | HResume'" :description="$cr['pageSubtitle'] ?? 'Pick a name and template to get started.'">
    <div class="relative min-h-[calc(100dvh-4rem)] overflow-hidden bg-gradient-to-br from-slate-50 via-blue-50/90 to-purple-100/80" x-data="createResume(@js($state))">
        <div aria-hidden="true" class="pointer-events-none absolute -right-24 top-0 h-72 w-72 rounded-full bg-blue-400/20 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute -left-20 top-1/3 h-64 w-64 rounded-full bg-purple-400/15 blur-3xl"></div>
        <div aria-hidden="true" class="pointer-events-none absolute bottom-32 right-1/4 h-48 w-48 rounded-full bg-indigo-300/20 blur-3xl"></div>

        <form @submit.prevent="submit" class="relative z-10 max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-8 pb-28 space-y-8">
            @if ($allowGuest)
                <p class="text-sm text-slate-600 rounded-xl border border-white/60 bg-white/70 backdrop-blur-sm shadow-sm px-4 py-3">{{ $cr['guestBanner'] ?? '' }}</p>
            @endif

            <header class="space-y-4">
                <div>
                    <h1 class="text-2xl sm:text-3xl font-bold bg-gradient-to-r from-slate-900 via-blue-800 to-purple-800 bg-clip-text text-transparent">{{ $cr['title'] ?? 'Create your resume' }}</h1>
                    <p class="text-slate-600 mt-1 text-sm">{{ $cr['pageSubtitle'] ?? 'Pick a name and template to get started.' }}</p>
                </div>
                <div class="max-w-md space-y-1.5">
                    <label for="resumeName" class="text-sm font-medium text-slate-700">{{ $cr['nameLabel'] ?? 'Resume name' }}</label>
                    <input x-ref="name" type="text" id="resumeName" x-model="name" placeholder="{{ $cr['namePlaceholder'] ?? 'e.g. Product Designer 2026' }}" autocomplete="off"
                        class="w-full px-4 py-2.5 rounded-lg border border-white/80 bg-white/80 backdrop-blur-sm shadow-sm focus:border-blue-500 focus:ring-2 focus:ring-blue-100 outline-none" />
                </div>
            </header>

            <section class="space-y-4 rounded-2xl border border-white/50 bg-white/40 backdrop-blur-sm p-4 sm:p-5 shadow-sm">
                <div class="flex flex-col sm:flex-row sm:items-center gap-3 sm:justify-between">
                    <h2 class="text-lg font-semibold text-slate-900">{{ $cr['templateLabel'] ?? 'Template' }}</h2>
                    <div class="flex flex-col sm:flex-row gap-2 sm:items-center">
                        <div class="relative w-full sm:w-64">
                            <x-lucide-search class="h-4 w-4 absolute left-3 top-1/2 -translate-y-1/2 text-slate-400" />
                            <input type="search" placeholder="{{ $cr['searchPlaceholder'] ?? 'Search templates' }}" x-model="search"
                                class="pl-9 pr-3 py-2 rounded-lg border border-white/80 bg-white/80 backdrop-blur-sm shadow-sm focus:ring-2 focus:ring-blue-500 focus:outline-none w-full text-sm" />
                        </div>
                        <div class="flex flex-wrap gap-1.5">
                            <template x-for="cat in categories" :key="cat">
                                <button type="button" @click="category = cat"
                                    class="px-2.5 py-1.5 rounded-lg text-xs font-medium border transition"
                                    :class="category === cat ? 'bg-gradient-to-r from-blue-600 to-purple-600 text-white border-transparent shadow-sm' : 'border-white/80 bg-white/70 text-slate-600 hover:border-blue-200'"
                                    x-text="cat === 'all' ? @js(t('common.all', [], 'All')) : cat"></button>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                    <template x-if="filtered.length === 0">
                        <div class="col-span-full border border-dashed border-slate-200 rounded-xl p-8 text-center text-slate-500">
                            <x-lucide-palette class="h-8 w-8 mx-auto mb-2 opacity-50" />
                            <p class="font-medium text-slate-700">{{ $cr['noTemplatesTitle'] ?? 'No templates found' }}</p>
                        </div>
                    </template>
                    <template x-for="tpl in filtered" :key="tpl.id">
                        <button type="button" @click="selected = tpl.id"
                            class="text-left border rounded-xl p-3 bg-white/90 backdrop-blur-sm shadow-sm transition focus:outline-none"
                            :class="selected == tpl.id ? 'border-blue-500 ring-2 ring-blue-200 shadow-md shadow-blue-100/50' : 'border-white/80 hover:border-blue-200 hover:shadow-md'">
                            <div class="relative mb-2">
                                <div class="h-40 bg-slate-50 border border-slate-100 rounded-lg flex items-center justify-center overflow-hidden">
                                    <template x-if="tpl.preview_image_url">
                                        <img :src="tpl.preview_image_url" :alt="tpl.name" class="max-h-full w-auto object-contain"
                                            x-on:error="$el.src = placeholderImage(400, 520, 'e2e8f0', '64748b', tpl.name)" />
                                    </template>
                                    <template x-if="!tpl.preview_image_url">
                                        <span class="text-sm font-medium text-slate-500" x-text="tpl.name"></span>
                                    </template>
                                </div>
                                <span x-show="selected == tpl.id" class="absolute top-2 right-2 px-2 py-0.5 text-xs font-medium rounded-full bg-blue-600 text-white">{{ t('common.selected', [], 'Selected') }}</span>
                            </div>
                            <p class="font-medium text-slate-900 text-sm" x-text="tpl.name"></p>
                        </button>
                    </template>
                </div>
            </section>

            <div class="fixed bottom-0 inset-x-0 z-30 border-t border-white/60 bg-white/80 backdrop-blur-md shadow-[0_-4px_24px_rgba(59,130,246,0.08)]">
                <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 py-3 flex flex-col sm:flex-row sm:items-center sm:justify-end gap-2">
                    <button type="submit" :disabled="!canSubmit || submitting"
                        class="w-full sm:w-auto px-6 py-2.5 rounded-lg font-semibold text-white text-sm flex items-center justify-center gap-2 transition bg-slate-400 cursor-not-allowed"
                        x-effect="$swap($el, !canSubmit || submitting, 'bg-slate-400 cursor-not-allowed', 'bg-gradient-to-r from-blue-600 to-purple-600 hover:from-blue-700 hover:to-purple-700 shadow-lg shadow-blue-500/25')">
                        <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="submitting" x-cloak />
                        {{ $allowGuest ? ($cr['guestFinalButton'] ?? 'Continue to sign up') : t('common.startEditingResume', [], 'Start editing') }}
                    </button>
                </div>
                @if ($allowGuest)
                    <p class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8 pb-3 text-xs text-slate-500 text-center sm:text-right">
                        {{ $cr['guestHasAccount'] ?? '' }}
                        <a href="{{ route('login') }}?next={{ rawurlencode('/resume/claim-draft') }}" class="text-blue-600 hover:underline">{{ $cr['guestLoginLink'] ?? '' }}</a>
                    </p>
                @endif
            </div>
        </form>
    </div>
</x-dynamic-component>
