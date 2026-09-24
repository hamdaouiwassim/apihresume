{{-- Port of pages/admin/ReviewsManagement.jsx --}}
@php $th = 'px-6 py-4 text-xs font-semibold text-gray-500 uppercase tracking-wider'; @endphp
<x-layouts.admin title="Reviews | Admin | HResume">
    <div class="max-w-7xl mx-auto" x-data="adminReviews">
        <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-8">
            <div>
                <h1 class="text-3xl font-bold text-gray-900 mb-2">Reviews Management</h1>
                <p class="text-gray-600">Manage customer feedback and success stories</p>
            </div>
            <div class="flex items-center gap-3">
                <div class="relative">
                    <x-lucide-search class="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                    <input type="text" placeholder="Search reviews..." x-model.debounce.300ms="search" class="pl-10 pr-4 py-2 border border-gray-200 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent w-full md:w-64 outline-none transition-all" />
                </div>
            </div>
        </div>

        <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
            <div class="flex flex-col items-center justify-center p-20 text-gray-500" x-show="loading">
                <x-lucide-loader-2 class="h-10 w-10 animate-spin text-purple-600 mb-4" />
                <p class="font-medium">Loading reviews...</p>
            </div>

            <div class="overflow-x-auto" x-show="!loading && items.length > 0" x-cloak>
                <table class="w-full text-left">
                    <thead class="bg-gray-50 border-b border-gray-100">
                        <tr>
                            <th class="{{ $th }}">User</th>
                            <th class="{{ $th }}">Review</th>
                            <th class="{{ $th }} text-center">Rating</th>
                            <th class="{{ $th }}">Status</th>
                            <th class="{{ $th }}">Date</th>
                            <th class="{{ $th }} text-right">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <template x-for="review in items" :key="review.id">
                            <tr class="hover:bg-gray-50/50 transition-colors">
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <div class="flex items-center space-x-3">
                                        <img :src="review.user?.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=' + review.user?.name" alt="" class="h-9 w-9 rounded-full ring-2 ring-white" />
                                        <div>
                                            <div class="text-sm font-semibold text-gray-900" x-text="review.user?.name"></div>
                                            <div class="text-xs text-gray-500" x-text="review.user?.email"></div>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-6 py-4">
                                    <div class="max-w-xs xl:max-w-md">
                                        <div class="text-sm font-bold text-gray-900 mb-1" x-text="review.title"></div>
                                        <div class="text-sm text-gray-600 line-clamp-2" x-text="review.comment"></div>
                                    </div>
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <div class="inline-flex items-center px-2.5 py-1 rounded-full bg-yellow-50 text-yellow-700">
                                        <x-lucide-star class="h-3.5 w-3.5 fill-current mr-1" />
                                        <span class="text-sm font-bold" x-text="review.rating"></span>
                                    </div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    <button type="button" @click="toggle(review)" :disabled="togglingId === review.id"
                                        class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold shadow-sm transition-all"
                                        :class="[review.is_public ? 'bg-green-100 text-green-700 hover:bg-green-200' : 'bg-gray-100 text-gray-600 hover:bg-gray-200', togglingId === review.id ? 'opacity-50 cursor-not-allowed' : '']">
                                        <x-lucide-loader-2 class="h-3 w-3 animate-spin mr-1.5" x-show="togglingId === review.id" />
                                        <x-lucide-check-circle-2 class="h-3 w-3 mr-1.5" x-show="togglingId !== review.id && review.is_public" />
                                        <x-lucide-x-circle class="h-3 w-3 mr-1.5" x-show="togglingId !== review.id && !review.is_public" />
                                        <span x-text="review.is_public ? 'Public' : 'Hidden'"></span>
                                    </button>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                                    <div class="flex items-center"><x-lucide-calendar class="h-3.5 w-3.5 mr-1.5 text-gray-400" /><span x-text="shortDate(review.created_at)"></span></div>
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                    <div class="flex items-center justify-end space-x-2">
                                        <button type="button" @click="remove(review)" :disabled="deletingId === review.id" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50" title="Delete review">
                                            <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="deletingId === review.id" />
                                            <x-lucide-trash-2 class="h-4 w-4" x-show="deletingId !== review.id" />
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>

            <div class="flex flex-col items-center justify-center p-20 text-gray-500 bg-gray-50/30" x-show="!loading && items.length === 0" x-cloak>
                <x-lucide-message-square class="h-16 w-16 text-gray-200 mb-4" />
                <h3 class="text-lg font-semibold text-gray-900 mb-1">No reviews found</h3>
                <p class="max-w-xs text-center">Try adjusting your search criteria or check back later for new reviews.</p>
            </div>

            <div x-show="!loading && pagination.total > 0"><x-admin.pagination label="reviews" /></div>
        </div>
    </div>
</x-layouts.admin>
