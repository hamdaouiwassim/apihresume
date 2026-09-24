{{-- Port of pages/admin/UsersList.jsx (recruiter role removed) --}}
<x-layouts.admin title="Users | Admin | HResume">
    <div x-data="adminUsers">
        <div class="flex items-center justify-center min-h-[400px]" x-show="loading && items.length === 0">
            <x-lucide-loader-2 class="h-12 w-12 animate-spin text-purple-600" />
        </div>

        <div class="max-w-7xl mx-auto" x-show="!(loading && items.length === 0)" x-cloak>
            <div class="mb-8 animate-slide-in">
                <h1 class="text-4xl font-bold text-gray-900 mb-2">Users Management</h1>
                <p class="text-gray-600">Manage all users and their activity</p>
            </div>

            <div class="mb-6 flex flex-col gap-4">
                <div class="relative max-w-md">
                    <x-lucide-search class="absolute left-4 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                    <input type="text" placeholder="Search users by name or email..." x-model.debounce.300ms="search"
                        class="w-full pl-12 pr-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 shadow-sm" />
                </div>

                <div class="flex flex-wrap items-center gap-4">
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Role:</label>
                        <select x-model="role" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm">
                            <option value="">All Roles</option>
                            <option value="candidate">Candidate</option>
                            <option value="admin">Admin</option>
                            <option value="pro">Pro</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Verification:</label>
                        <select x-model="verification" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm">
                            <option value="">All</option>
                            <option value="verified">Verified</option>
                            <option value="unverified">Unverified</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <label class="text-sm font-medium text-gray-700">Trash:</label>
                        <select x-model="trashed" class="px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent transition-all duration-200 text-sm">
                            <option value="">Active only</option>
                            <option value="only">Deleted only</option>
                            <option value="with">All (incl. deleted)</option>
                        </select>
                    </div>
                    <button type="button" x-show="hasFilters" @click="clearFilters()" class="px-4 py-2 text-sm text-gray-700 hover:text-gray-900 hover:bg-gray-100 rounded-lg transition-colors">Clear Filters</button>
                </div>

                <div class="flex items-center gap-2 flex-wrap text-xs" x-show="role || verification">
                    <span class="font-semibold text-gray-600">Active filters:</span>
                    <span x-show="role" class="px-2 py-1 rounded-full bg-purple-50 text-purple-700 capitalize" x-text="'Role: ' + role"></span>
                    <span x-show="verification" class="px-2 py-1 rounded-full bg-blue-50 text-blue-700 capitalize" x-text="'Verification: ' + verification"></span>
                </div>
            </div>

            <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100">
                <div class="overflow-x-auto">
                    <table class="w-full">
                        <thead class="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                            <tr>
                                @foreach (['User', 'Status', 'Role', 'Resumes', 'Last Activity', 'Joined'] as $th)
                                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">{{ $th }}</th>
                                @endforeach
                                <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-200">
                            <template x-for="user in items" :key="user.id">
                                <tr class="hover:bg-gray-50 transition-colors" :class="user.deleted_at ? 'bg-red-50/40' : ''">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <img :src="user.avatar || 'https://api.dicebear.com/7.x/avataaars/svg?seed=default'" :alt="user.name" class="h-10 w-10 rounded-full border-2 border-purple-200 mr-3" />
                                            <div>
                                                <div class="text-sm font-semibold text-gray-900" x-text="user.name"></div>
                                                <div class="text-sm text-gray-500 flex items-center">
                                                    <x-lucide-mail class="h-3 w-3 mr-1" />
                                                    <span x-text="user.email"></span>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span x-show="user.email_verified_at" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-emerald-100 text-emerald-700">
                                            <x-lucide-check-circle class="h-3 w-3 mr-1" /> Verified
                                        </span>
                                        <span x-show="!user.email_verified_at" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-700">
                                            <x-lucide-x-circle class="h-3 w-3 mr-1" /> Unverified
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex flex-wrap items-center gap-2">
                                            <span x-show="user.is_admin" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-purple-100 text-purple-700"><x-lucide-shield class="h-3 w-3 mr-1" /> Admin</span>
                                            <span x-show="user.is_pro" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-amber-100 text-amber-800"><x-lucide-crown class="h-3 w-3 mr-1" /> Pro</span>
                                            <span x-show="!user.is_admin && !user.is_pro" class="inline-flex items-center px-3 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">User</span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center text-sm text-gray-900">
                                            <x-lucide-file-text class="h-4 w-4 mr-2 text-gray-400" />
                                            <span x-text="user.resumes_count || 0"></span>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-900" x-text="timeAgo(user.last_activity)"></div>
                                        <template x-if="user.last_activity">
                                            <div class="text-xs text-gray-500 flex items-center mt-1">
                                                <x-lucide-calendar class="h-3 w-3 mr-1" />
                                                <span x-text="adminDate(user.last_activity, true)"></span>
                                            </div>
                                        </template>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500" x-text="adminDate(user.created_at, true)"></td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <a :href="'/admin/users/' + user.id" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="View user details"><x-lucide-eye class="h-4 w-4" /></a>
                                            <button type="button" @click="toggleAdmin(user)" class="p-2 rounded-lg transition-colors"
                                                :class="user.is_admin ? 'text-purple-600 hover:bg-purple-50' : 'text-gray-600 hover:bg-gray-100'"
                                                :title="user.is_admin ? 'Remove admin' : 'Make admin'"><x-lucide-shield class="h-4 w-4" /></button>
                                            <button type="button" @click="togglePro(user)" :disabled="!user.is_pro && !user.email_verified_at"
                                                class="p-2 rounded-lg transition-colors disabled:cursor-not-allowed disabled:opacity-40"
                                                :class="user.is_pro ? 'text-amber-600 hover:bg-amber-50' : 'text-gray-600 hover:bg-gray-100'"
                                                :title="user.is_pro ? 'Remove Pro' : (user.email_verified_at ? 'Grant Pro' : 'Verify email before granting Pro')"><x-lucide-crown class="h-4 w-4" /></button>
                                            <button type="button" x-show="user.deleted_at" @click="restore(user)" class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors" title="Restore user"><x-lucide-rotate-ccw class="h-4 w-4" /></button>
                                            <button type="button" x-show="!user.deleted_at" @click="remove(user)" :disabled="deleting" class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors disabled:opacity-50 disabled:cursor-not-allowed" title="Move to trash"><x-lucide-trash-2 class="h-4 w-4" /></button>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                <div x-show="!loading && pagination.total > 0"><x-admin.pagination label="users" /></div>
            </div>

            <div class="text-center py-12 bg-white rounded-2xl shadow-lg border border-gray-100" x-show="items.length === 0 && !loading">
                <x-lucide-users class="h-16 w-16 text-gray-400 mx-auto mb-4" />
                <p class="text-xl text-gray-600">No users found</p>
            </div>
        </div>
    </div>
</x-layouts.admin>
