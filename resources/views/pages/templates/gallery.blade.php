{{-- Port of pages/ResumeTemplates.jsx (/templates/public) --}}
@php $tp = (array) t('resumeTemplates', [], []); @endphp
<x-layouts.guest
    :title="($tp['title'] ?? 'Resume Templates').' | HResume'"
    :description="$tp['subtitle'] ?? 'Choose from our collection of professional, ATS-friendly resume templates'"
    canonical="/templates/public"
>
    <div class="min-h-screen bg-gradient-to-br from-gray-50 via-blue-50 to-purple-50 py-12">
        <div class="max-w-6xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12 animate-slide-in">
                <div class="flex items-center justify-center gap-2 mb-4">
                    <x-lucide-sparkles class="h-6 w-6 text-blue-600 animate-pulse-slow" />
                    <span class="text-blue-600 font-semibold">{{ $tp['badge'] ?? 'Professional Templates' }}</span>
                </div>
                <h1 class="text-5xl md:text-6xl font-bold mb-4 bg-gradient-to-r from-blue-600 via-purple-600 to-pink-600 bg-clip-text text-transparent">{{ $tp['title'] ?? 'Resume Templates' }}</h1>
                <p class="text-xl text-gray-600 max-w-2xl mx-auto">{{ $tp['subtitle'] ?? 'Choose from our collection of professional, ATS-friendly resume templates' }}</p>
            </div>

            <div class="bg-gradient-to-r from-blue-50 to-purple-50 border border-blue-200 rounded-2xl p-6 md:p-8 mb-12 shadow-lg">
                <div class="flex items-start gap-4">
                    <div class="flex-shrink-0 w-12 h-12 rounded-full bg-blue-100 flex items-center justify-center">
                        <x-lucide-file-text class="h-6 w-6 text-blue-600" />
                    </div>
                    <div class="flex-1">
                        <h2 class="text-2xl font-bold text-gray-900 mb-3">{{ $tp['currentStatus']['title'] ?? 'Current Template Availability' }}</h2>
                        <p class="text-gray-700 leading-relaxed mb-4">{{ $tp['currentStatus']['message'] ?? "At the moment, we offer 1 ATS-friendly template designed to help your resume pass through Applicant Tracking Systems. We're working hard to expand our collection with more professional templates in the near future." }}</p>
                        <div class="flex items-center gap-2 text-blue-600 font-semibold">
                            <x-lucide-zap class="h-5 w-5" />
                            <span>{{ $tp['currentStatus']['comingSoon'] ?? 'More templates coming soon!' }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-12">
                @foreach ([
                    ['shield', 'bg-blue-100', 'text-blue-600', 'ats', 'ATS-Friendly', 'Optimized to pass through Applicant Tracking Systems used by most employers'],
                    ['file-text', 'bg-purple-100', 'text-purple-600', 'professional', 'Professional Design', 'Clean, modern layouts that make a great first impression'],
                    ['check-circle', 'bg-green-100', 'text-green-600', 'customizable', 'Fully Customizable', 'Easily customize colors, fonts, and layout to match your style'],
                ] as [$icon, $bg, $fg, $key, $title, $desc])
                    <div class="bg-white rounded-xl p-6 shadow-lg border border-gray-100">
                        <div class="w-12 h-12 rounded-lg {{ $bg }} flex items-center justify-center mb-4">
                            <x-dynamic-component :component="'lucide-'.$icon" class="h-6 w-6 {{ $fg }}" />
                        </div>
                        <h3 class="text-xl font-bold text-gray-900 mb-2">{{ $tp['features'][$key]['title'] ?? $title }}</h3>
                        <p class="text-gray-600">{{ $tp['features'][$key]['description'] ?? $desc }}</p>
                    </div>
                @endforeach
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 mb-12">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $tp['preview']['title'] ?? 'Our Current Template' }}</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto">{{ $tp['preview']['description'] ?? 'Our ATS-friendly template is designed to help your resume stand out while ensuring it can be properly parsed by automated systems.' }}</p>
                </div>
                <div class="bg-gradient-to-br from-gray-50 to-gray-100 rounded-xl p-8 border-2 border-dashed border-gray-300">
                    <div class="text-center py-12">
                        <x-lucide-file-text class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                        <h3 class="text-2xl font-bold text-gray-700 mb-2">{{ $tp['preview']['templateName'] ?? 'ATS-Friendly Professional Template' }}</h3>
                        <p class="text-gray-600 mb-6">{{ $tp['preview']['templateDescription'] ?? 'A clean, professional template optimized for both human readers and ATS systems' }}</p>
                        <div class="flex flex-wrap justify-center gap-3 mb-8">
                            <span class="px-4 py-2 bg-blue-100 text-blue-700 rounded-full text-sm font-semibold">{{ $tp['preview']['tags']['ats'] ?? 'ATS-Friendly' }}</span>
                            <span class="px-4 py-2 bg-purple-100 text-purple-700 rounded-full text-sm font-semibold">{{ $tp['preview']['tags']['professional'] ?? 'Professional' }}</span>
                            <span class="px-4 py-2 bg-green-100 text-green-700 rounded-full text-sm font-semibold">{{ $tp['preview']['tags']['modern'] ?? 'Modern' }}</span>
                        </div>
                        <a href="{{ route('register') }}" class="inline-flex items-center px-8 py-4 bg-gradient-to-r from-blue-500 to-purple-600 text-white rounded-xl font-semibold hover:from-blue-600 hover:to-purple-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:scale-105">
                            {{ $tp['preview']['cta'] ?? 'Get Started Free' }}
                            <x-lucide-arrow-right class="h-5 w-5 ml-2" />
                        </a>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12 mb-12">
                <div class="text-center mb-8">
                    <h2 class="text-3xl font-bold text-gray-900 mb-3">Template Preview Pages</h2>
                    <p class="text-gray-600">Open each CV template with sample data to see the full layout.</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <a href="{{ route('templates.public.preview', 1) }}" class="block rounded-xl border border-gray-200 p-5 hover:border-blue-300 hover:bg-blue-50 transition-colors">
                        <p class="font-semibold text-gray-900">Classic</p>
                        <p class="text-sm text-gray-600 mt-1">Traditional and ATS-friendly.</p>
                    </a>
                    <a href="{{ route('templates.public.preview', 2) }}" class="block rounded-xl border border-gray-200 p-5 hover:border-purple-300 hover:bg-purple-50 transition-colors">
                        <p class="font-semibold text-gray-900">Executive Split</p>
                        <p class="text-sm text-gray-600 mt-1">Premium split-column structure.</p>
                    </a>
                    <a href="{{ route('templates.public.preview', 3) }}" class="block rounded-xl border border-gray-200 p-5 hover:border-green-300 hover:bg-green-50 transition-colors">
                        <p class="font-semibold text-gray-900">Modern Professional</p>
                        <p class="text-sm text-gray-600 mt-1">Balanced modern two-column design.</p>
                    </a>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-xl p-8 md:p-12">
                <div class="text-center mb-8">
                    <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 mb-6">
                        <x-lucide-sparkles class="h-8 w-8 text-white" />
                    </div>
                    <h2 class="text-3xl font-bold text-gray-900 mb-4">{{ $tp['future']['title'] ?? 'More Templates Coming Soon' }}</h2>
                    <p class="text-gray-600 max-w-2xl mx-auto mb-8">{{ $tp['future']['description'] ?? "We're continuously working on expanding our template collection. In the future, you'll be able to choose from a variety of styles including:" }}</p>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    @foreach ([
                        ['from-blue-50 to-blue-100 border-blue-200', 'corporate', 'Corporate', 'Traditional and professional designs for corporate environments'],
                        ['from-purple-50 to-purple-100 border-purple-200', 'creative', 'Creative', 'Bold and unique designs for creative professionals'],
                        ['from-green-50 to-green-100 border-green-200', 'minimal', 'Minimal', 'Clean and simple designs that focus on content'],
                    ] as [$cls, $key, $title, $desc])
                        <div class="bg-gradient-to-br {{ $cls }} rounded-xl p-6 border">
                            <h3 class="text-lg font-bold text-gray-900 mb-2">{{ $tp['future']['types'][$key] ?? $title }}</h3>
                            <p class="text-gray-600 text-sm">{{ $tp['future']['types'][$key.'Desc'] ?? $desc }}</p>
                        </div>
                    @endforeach
                </div>
                <div class="mt-8 text-center">
                    <p class="text-gray-600 mb-4">{{ $tp['future']['footer'] ?? "Stay tuned for updates! We'll notify you when new templates are available." }}</p>
                    <a href="{{ route('register') }}" class="inline-flex items-center text-blue-600 font-semibold hover:text-blue-700 transition-colors">
                        {{ $tp['future']['cta'] ?? 'Create your free account to be notified' }}
                        <x-lucide-arrow-right class="h-5 w-5 ml-2" />
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-layouts.guest>
