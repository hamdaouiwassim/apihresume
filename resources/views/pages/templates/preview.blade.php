{{-- Port of pages/TemplatePreviewPage.jsx: Blade chrome + React island for the live preview --}}
<x-layouts.guest
    :title="$template ? $template->name.' Resume Template Preview | HResume' : 'Template not found | HResume'"
    :description="$template?->description ?: 'Preview this resume template with sample data.'"
    :robots="$template ? 'index, follow' : 'noindex, follow'"
    :islands="(bool) $template"
>
    <div class="min-h-screen bg-gray-50 py-8">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="mb-6">
                <a href="{{ route('templates.public') }}" class="inline-flex items-center text-blue-600 hover:text-blue-700">
                    <x-lucide-arrow-left class="h-4 w-4 mr-2" />
                    Back to Templates
                </a>
            </div>

            @if (! $template)
                <div class="bg-white rounded-xl p-8 text-center shadow-sm border border-gray-100">
                    <h1 class="text-2xl font-bold text-gray-900 mb-2">Template not found</h1>
                    <p class="text-gray-600">The requested template preview does not exist.</p>
                </div>
            @else
                <div class="bg-white rounded-xl p-6 mb-6 shadow-sm border border-gray-100">
                    <h1 class="text-3xl font-bold text-gray-900">{{ $template->name }} Preview</h1>
                    <p class="text-gray-600 mt-2">{{ $template->description ?: 'Preview with sample data.' }}</p>
                </div>
                <div class="bg-white rounded-xl p-4 md:p-8 shadow-sm border border-gray-100">
                    <div data-island="template-preview" data-props="{{ json_encode(['template' => $template]) }}">
                        <div class="flex items-center justify-center min-h-[400px]">
                            <x-lucide-loader-2 class="h-10 w-10 animate-spin text-blue-600" />
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </div>
</x-layouts.guest>
