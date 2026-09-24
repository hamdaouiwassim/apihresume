{{-- Port of pages/admin/Blog.jsx --}}
@php
    $b = (array) t('admin.blog', [], []);
    $th = 'px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider';
@endphp
<x-layouts.admin title="Blog | Admin | HResume">
    <div x-data="adminBlog(@js($b))">
        <div class="max-w-7xl mx-auto">
            <div class="mb-8 animate-slide-in">
                <div class="flex justify-between items-center mb-2">
                    <div>
                        <h1 class="text-4xl font-bold text-gray-900 mb-2">{{ $b['title'] ?? 'Blog Management' }}</h1>
                        <p class="text-gray-600">{{ $b['subtitle'] ?? 'Create and manage blog posts' }}</p>
                    </div>
                    <a href="{{ route('admin.blog.new') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold hover:from-purple-700 hover:to-pink-700 transition-all duration-200 shadow-lg hover:shadow-xl transform hover:-translate-y-0.5">
                        <x-lucide-plus class="h-5 w-5 mr-2" /> {{ $b['newPost'] ?? 'New Post' }}
                    </a>
                </div>
            </div>

            <div class="mb-6">
                <div class="bg-white rounded-2xl shadow-lg p-6 flex flex-col sm:flex-row gap-4">
                    <div class="flex-1 relative">
                        <x-lucide-search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input type="text" x-model.debounce.300ms="search" placeholder="{{ $b['searchPlaceholder'] ?? 'Search posts by title or excerpt...' }}" class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm" />
                    </div>
                    <select x-model="status" class="px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm font-medium">
                        <option value="all">{{ $b['allStatus'] ?? 'All Status' }}</option>
                        <option value="published">{{ $b['published'] ?? 'Published' }}</option>
                        <option value="draft">{{ $b['draft'] ?? 'Draft' }}</option>
                    </select>
                </div>
            </div>

            <div class="flex items-center justify-center min-h-[600px]" x-show="loading">
                <x-lucide-loader-2 class="h-12 w-12 animate-spin text-purple-600" />
            </div>

            <div class="text-center py-20 bg-white rounded-2xl shadow-lg border border-gray-100" x-show="!loading && items.length === 0" x-cloak>
                <x-lucide-file-text class="h-16 w-16 text-gray-300 mx-auto mb-4" />
                <p class="text-xl text-gray-600 mb-6">{{ $b['noPosts'] ?? 'No blog posts found' }}</p>
                <a href="{{ route('admin.blog.new') }}" class="inline-flex items-center px-6 py-3 bg-gradient-to-r from-purple-600 to-pink-600 text-white rounded-xl font-semibold">
                    <x-lucide-plus class="h-5 w-5 mr-2" /> {{ $b['newPost'] ?? 'New Post' }}
                </a>
            </div>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100" x-show="!loading && items.length > 0" x-cloak>
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                            <tr>
                                <th class="{{ $th }}">{{ $b['titleColumn'] ?? $b['titleLabel'] ?? 'Title' }}</th>
                                <th class="{{ $th }}">{{ $b['author'] ?? 'Author' }}</th>
                                <th class="{{ $th }}">{{ $b['status'] ?? 'Status' }}</th>
                                <th class="{{ $th }}">{{ $b['publishedAt'] ?? 'Published At' }}</th>
                                <th class="{{ $th }}">{{ $b['views'] ?? 'Views' }}</th>
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ $b['actions'] ?? 'Actions' }}</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="post in items" :key="post.id">
                                <tr class="hover:bg-gray-50 transition-colors">
                                    <td class="px-6 py-4">
                                        <div class="text-sm font-semibold text-gray-900" x-text="post.title"></div>
                                        <div x-show="post.excerpt" class="text-sm text-gray-500 truncate max-w-md mt-1" x-text="post.excerpt"></div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="text-sm text-gray-700" x-text="post.user?.name || 'Admin'"></div></td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold" :class="post.status === 'published' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-700'"
                                            x-text="post.status === 'published' ? @js($b['published'] ?? 'Published') : @js($b['draft'] ?? 'Draft')"></span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                        <span x-show="post.published_at" x-text="shortDate(post.published_at)"></span>
                                        <span x-show="!post.published_at" class="text-gray-400">—</span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center text-sm text-gray-700"><x-lucide-eye class="h-4 w-4 mr-2 text-gray-400" /><span x-text="post.views || 0"></span></div></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a x-show="post.status === 'published'" :href="'/blog/' + post.slug" target="_blank" rel="noopener noreferrer" class="p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors" title="{{ $b['view'] ?? 'View' }}"><x-lucide-eye class="h-4 w-4" /></a>
                                            <a :href="'/admin/blog/edit/' + post.id" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="{{ $b['edit'] ?? 'Edit' }}"><x-lucide-edit-2 class="h-4 w-4" /></a>
                                            <button type="button" @click="remove(post)" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors" title="{{ $b['delete'] ?? 'Delete' }}"><x-lucide-trash-2 class="h-4 w-4" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="pagination.total > 0"><x-admin.pagination label="posts" /></div>
            </div>
        </div>
    </div>
</x-layouts.admin>
