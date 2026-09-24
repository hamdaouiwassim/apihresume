{{-- Public, indexable resume template page (/templates/{slug}): server-rendered text + image, live preview island below. --}}
@php
    $tp = (array) t('templatePage', [], []);
    $tr = fn (string $key, array $replace = []) => t('templatePage.'.$key, $replace);

    if ($template) {
        $name = $template->name;
        $description = trim((string) $template->description);
        $image = $template->preview_image_url;
        $imageSize = $template->previewImageSize();
        $pageUrl = localized_route('templates.show', $template->slug);
        $metaDescription = \Illuminate\Support\Str::limit(trim($tr('metaDescription', ['name' => $name, 'description' => $description])), 158, '…');

        $jsonLd = \App\Support\SchemaOrg::graph(
            [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    ['@type' => 'ListItem', 'position' => 1, 'name' => $tr('breadcrumbHome'), 'item' => localized_url('/')],
                    ['@type' => 'ListItem', 'position' => 2, 'name' => $tr('breadcrumbTemplates'), 'item' => localized_route('templates.public')],
                    ['@type' => 'ListItem', 'position' => 3, 'name' => $name, 'item' => $pageUrl],
                ],
            ],
            array_filter([
                '@type' => 'CreativeWork',
                'name' => $tr('h1', ['name' => $name]),
                'description' => $description ?: null,
                'url' => $pageUrl,
                'image' => $image,
                'genre' => $template->category,
                'inLanguage' => app()->getLocale(),
                'isAccessibleForFree' => true,
                'publisher' => ['@id' => \App\Support\SchemaOrg::organizationId()],
            ]),
        );
    }
@endphp
<x-layouts.guest
    :title="$template ? $tr('metaTitle', ['name' => $name]) : $tr('notFoundTitle').' | HResume'"
    :description="$template ? $metaDescription : $tr('notFoundText')"
    :canonical="$template ? '/templates/'.$template->slug : null"
    :image="$template ? $image : null"
    :image-alt="$template ? $tr('imageAlt', ['name' => $name]) : null"
    :image-width="$imageSize['width'] ?? null"
    :image-height="$imageSize['height'] ?? null"
    :robots="$template ? 'index, follow' : 'noindex, follow'"
    :json-ld="$jsonLd ?? null"
    :islands="(bool) $template"
>
    @if ($template && $image)
        <x-slot:head>
            <link rel="preload" as="image" href="{{ $image }}" fetchpriority="high">
        </x-slot:head>
    @endif

    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <nav aria-label="Breadcrumb" class="mb-6 text-sm text-gray-500">
                <ol class="flex flex-wrap items-center gap-1.5">
                    <li><a href="{{ localized_url('/') }}" class="hover:text-blue-600">{{ $tr('breadcrumbHome') }}</a></li>
                    <li aria-hidden="true"><x-lucide-chevron-right class="h-3.5 w-3.5" /></li>
                    <li><a href="{{ localized_route('templates.public') }}" class="hover:text-blue-600">{{ $tr('breadcrumbTemplates') }}</a></li>
                    @if ($template)
                        <li aria-hidden="true"><x-lucide-chevron-right class="h-3.5 w-3.5" /></li>
                        <li class="text-gray-700 font-medium" aria-current="page">{{ $name }}</li>
                    @endif
                </ol>
            </nav>

            @if (! $template)
                <div class="bg-white rounded-xl p-8 text-center shadow-sm border border-gray-100">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">{{ $tr('notFoundTitle') }}</h1>
                    <p class="text-gray-600 mb-6">{{ $tr('notFoundText') }}</p>
                    <a href="{{ localized_route('templates.public') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700 font-semibold">
                        <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                        {{ $tr('browseAll') }}
                    </a>
                </div>
            @else
                <div class="bg-white rounded-2xl shadow-xl border border-gray-100 overflow-hidden mb-8">
                    <div class="grid grid-cols-1 lg:grid-cols-2">
                        <div class="bg-gradient-to-br from-blue-50 via-white to-purple-50 p-6 sm:p-10 flex items-center justify-center">
                            @if ($image)
                                <img
                                    src="{{ $image }}"
                                    alt="{{ $tr('imageAlt', ['name' => $name]) }}"
                                    @if ($imageSize) width="{{ $imageSize['width'] }}" height="{{ $imageSize['height'] }}" @endif
                                    fetchpriority="high"
                                    decoding="async"
                                    class="w-full max-w-sm h-auto rounded-lg shadow-2xl ring-1 ring-black/5"
                                >
                            @else
                                <x-lucide-file-text class="h-24 w-24 text-gray-300" />
                            @endif
                        </div>

                        <div class="p-6 sm:p-10 flex flex-col">
                            <div class="flex flex-wrap gap-2 mb-4">
                                <span class="px-3 py-1 bg-blue-100 text-blue-700 rounded-full text-xs font-semibold">{{ $template->category ?: 'Professional' }}</span>
                                <span class="px-3 py-1 bg-green-100 text-green-700 rounded-full text-xs font-semibold">{{ $tp['tags']['ats'] ?? 'ATS-friendly' }}</span>
                                <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-xs font-semibold">{{ $tp['tags']['free'] ?? 'Free' }}</span>
                                <span class="px-3 py-1 bg-gray-100 text-gray-700 rounded-full text-xs font-semibold">{{ $tp['tags']['pdf'] ?? 'PDF export' }}</span>
                            </div>

                            <h1 class="text-3xl sm:text-4xl font-bold text-gray-900 leading-tight">{{ $tr('h1', ['name' => $name]) }}</h1>
                            @if ($description)
                                <p class="text-gray-600 mt-4 text-lg leading-relaxed">{{ $description }}</p>
                            @endif

                            <h2 class="text-lg font-bold text-gray-900 mt-8 mb-3">{{ $tr('whyTitle', ['name' => $name]) }}</h2>
                            <ul class="space-y-2.5">
                                @foreach ((array) ($tp['features'] ?? []) as $feature)
                                    <li class="flex items-start gap-2.5 text-gray-700">
                                        <x-lucide-check-circle class="h-5 w-5 text-green-500 shrink-0 mt-0.5" />
                                        <span>{{ $feature }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <div class="mt-8 pt-6 border-t border-gray-100 flex flex-col sm:flex-row sm:items-center gap-4">
                                <a href="{{ route('resume.start', ['template' => $template->id]) }}" class="inline-flex items-center justify-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl">
                                    {{ $tr('cta') }}
                                    <x-lucide-arrow-right class="h-5 w-5 ml-2" />
                                </a>
                                <a href="{{ localized_route('templates.public') }}" class="text-blue-600 hover:text-blue-700 font-semibold">{{ $tr('browseAll') }}</a>
                            </div>
                            <p class="text-sm text-gray-500 mt-3">{{ $tr('ctaNote') }}</p>
                        </div>
                    </div>
                </div>

                <section class="bg-white rounded-xl p-4 md:p-8 shadow-sm border border-gray-100 mb-8">
                    <div class="mb-6">
                        <h2 class="text-2xl font-bold text-gray-900">{{ $tr('previewTitle') }}</h2>
                        <p class="text-gray-600 mt-1">{{ $tr('previewSubtitle', ['name' => $name]) }}</p>
                    </div>
                    <div data-island="template-preview" data-props="{{ json_encode(['template' => $template]) }}">
                        <div class="flex items-center justify-center min-h-[400px]">
                            <x-lucide-loader-2 class="h-10 w-10 animate-spin text-blue-600" />
                        </div>
                    </div>
                </section>

                @if ($others->isNotEmpty())
                    <section>
                        <h2 class="text-2xl font-bold text-gray-900 mb-4">{{ $tr('othersTitle') }}</h2>
                        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                            @foreach ($others as $other)
                                <a href="{{ localized_route('templates.show', $other->slug) }}" class="block rounded-xl border border-gray-200 bg-white p-5 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                                    <p class="font-semibold text-gray-900">{{ $tr('h1', ['name' => $other->name]) }}</p>
                                    @if ($other->description)
                                        <p class="text-sm text-gray-600 mt-1">{{ \Illuminate\Support\Str::limit($other->description, 90) }}</p>
                                    @endif
                                    <span class="inline-flex items-center text-sm text-blue-600 font-medium mt-3">
                                        {{ $tr('viewTemplate') }}
                                        <x-lucide-arrow-right class="h-4 w-4 ml-1" />
                                    </span>
                                </a>
                            @endforeach
                        </div>
                    </section>
                @endif
            @endif
        </div>
    </div>
</x-layouts.guest>
