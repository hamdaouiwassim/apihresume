{{-- Port of components/CollaborationNotifications.jsx --}}
<div class="relative" x-data="collabNotifications">
    <button
        type="button"
        @click="open = !open"
        class="relative p-2 text-gray-700 hover:text-blue-600 hover:bg-gray-100 rounded-lg transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2"
        title="Collaboration invitations"
    >
        <x-lucide-bell class="h-5 w-5" />
        <span
            x-show="count > 0"
            x-cloak
            x-text="count > 9 ? '9+' : count"
            class="absolute top-0 right-0 flex items-center justify-center h-5 w-5 bg-red-500 text-white text-xs font-bold rounded-full transform translate-x-1/2 -translate-y-1/2"
        ></span>
    </button>

    <template x-if="open">
        <div>
            <div class="fixed inset-0 z-10" @click="open = false"></div>
            <div class="absolute right-0 mt-2 w-96 bg-white rounded-lg shadow-xl border border-gray-200 z-20 max-h-[600px] overflow-hidden flex flex-col">
                <div class="px-4 py-3 border-b border-gray-200 bg-gradient-to-r from-blue-50 to-purple-50">
                    <div class="flex items-center justify-between">
                        <h3 class="text-lg font-semibold text-gray-900">Collaboration Invitations</h3>
                        <span x-show="count > 0" x-text="count + ' new'" class="px-2 py-1 bg-red-500 text-white text-xs font-bold rounded-full"></span>
                    </div>
                </div>

                <div class="overflow-y-auto flex-1">
                    <template x-if="loading && invitations.length === 0">
                        <div class="flex items-center justify-center py-12">
                            <x-lucide-loader-2 class="h-6 w-6 animate-spin text-blue-600" />
                        </div>
                    </template>
                    <template x-if="!loading && invitations.length === 0">
                        <div class="px-4 py-12 text-center">
                            <x-lucide-bell class="h-12 w-12 text-gray-300 mx-auto mb-3" />
                            <p class="text-gray-500 text-sm">No pending invitations</p>
                        </div>
                    </template>
                    <div class="divide-y divide-gray-100" x-show="invitations.length > 0">
                        <template x-for="invitation in invitations" :key="invitation.id">
                            <div class="p-4 hover:bg-gray-50 transition-colors duration-150">
                                <div class="flex items-start space-x-3">
                                    <div class="flex-shrink-0">
                                        <div class="h-10 w-10 rounded-full bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                                            <x-lucide-file-text class="h-5 w-5 text-white" />
                                        </div>
                                    </div>
                                    <div class="flex-1 min-w-0">
                                        <p class="text-sm font-semibold text-gray-900 truncate" x-text="invitation.resume?.name || 'Untitled Resume'"></p>
                                        <div class="flex items-center mt-1 text-xs text-gray-500">
                                            <x-lucide-user class="h-3 w-3 mr-1" />
                                            <span class="truncate" x-text="invitation.resume?.owner?.name || invitation.resume?.owner?.email || 'Unknown'"></span>
                                        </div>
                                        <p class="text-xs text-gray-400 mt-1" x-text="formatDate(invitation.invited_at)"></p>
                                        <template x-if="invitation.allowed_sections && invitation.allowed_sections.length > 0">
                                            <p class="text-xs text-blue-600 mt-1" x-text="'Can edit: ' + invitation.allowed_sections.length + ' section(s)'"></p>
                                        </template>
                                        <div class="flex items-center gap-2 mt-3">
                                            <button
                                                type="button"
                                                @click="accept(invitation.id)"
                                                :disabled="isProcessing(invitation.id)"
                                                class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="isProcessing(invitation.id)" />
                                                <span class="inline-flex items-center" x-show="!isProcessing(invitation.id)"><x-lucide-check class="h-4 w-4 mr-1" /> Accept</span>
                                            </button>
                                            <button
                                                type="button"
                                                @click="refuse(invitation.id)"
                                                :disabled="isProcessing(invitation.id)"
                                                class="flex-1 inline-flex items-center justify-center px-3 py-1.5 bg-gray-100 text-gray-700 text-sm font-medium rounded-lg hover:bg-gray-200 transition-colors duration-200 disabled:opacity-50 disabled:cursor-not-allowed"
                                            >
                                                <x-lucide-loader-2 class="h-4 w-4 animate-spin" x-show="isProcessing(invitation.id)" />
                                                <span class="inline-flex items-center" x-show="!isProcessing(invitation.id)"><x-lucide-x class="h-4 w-4 mr-1" /> Decline</span>
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
