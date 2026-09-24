{{-- Port of pages/admin/UserCVs.jsx --}}
@php $th = 'px-6 py-4 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider'; @endphp
<x-layouts.admin title="User CVs | Admin | HResume">
    <div x-data="adminUserCvs({{ (int) $id }})">
        {{-- Detail view --}}
        <template x-if="selected">
            <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12">
                <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                    <div class="mb-8">
                        <button type="button" @click="selected = null" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4 transition-colors">
                            <x-lucide-x class="h-4 w-4 mr-2" /> Back to List
                        </button>
                        <h1 class="text-4xl font-bold text-gray-900 mb-2">Resume Details</h1>
                    </div>
                    <div class="bg-white rounded-2xl shadow-lg p-8 border border-gray-100">
                        <div class="mb-6">
                            <h2 class="text-2xl font-bold text-gray-900 mb-2" x-text="selected.name"></h2>
                            <div class="flex items-center gap-4 text-sm text-gray-600">
                                <template x-if="selected.user"><div class="flex items-center"><x-lucide-user class="h-4 w-4 mr-2" /><span x-text="selected.user.name + ' (' + selected.user.email + ')'"></span></div></template>
                                <template x-if="selected.template"><div class="flex items-center"><x-lucide-file-text class="h-4 w-4 mr-2" /><span x-text="'Template: ' + selected.template.name"></span></div></template>
                                <div class="flex items-center"><x-lucide-calendar class="h-4 w-4 mr-2" /><span x-text="adminDate(selected.updated_at, true)"></span></div>
                            </div>
                        </div>
                        <div class="space-y-6">
                            <template x-if="selected.basicInfo">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-2">Basic Information</h3>
                                    <div class="bg-gray-50 p-4 rounded-lg"><pre class="text-sm text-gray-700 whitespace-pre-wrap" x-text="JSON.stringify(selected.basicInfo, null, 2)"></pre></div>
                                </div>
                            </template>
                            <template x-if="selected.experiences?.length > 0">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="'Experiences (' + selected.experiences.length + ')'"></h3>
                                    <div class="space-y-2">
                                        <template x-for="(exp, idx) in selected.experiences" :key="idx">
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <p class="font-semibold text-gray-900" x-text="exp.title || 'N/A'"></p>
                                                <p class="text-sm text-gray-600" x-text="exp.company || 'N/A'"></p>
                                                <p x-show="exp.start_date" class="text-xs text-gray-500" x-text="exp.start_date + ' - ' + (exp.end_date || 'Present')"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selected.educations?.length > 0">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="'Education (' + selected.educations.length + ')'"></h3>
                                    <div class="space-y-2">
                                        <template x-for="(edu, idx) in selected.educations" :key="idx">
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <p class="font-semibold text-gray-900" x-text="edu.degree || 'N/A'"></p>
                                                <p class="text-sm text-gray-600" x-text="edu.institution || 'N/A'"></p>
                                                <p x-show="edu.start_date" class="text-xs text-gray-500" x-text="edu.start_date + ' - ' + (edu.end_date || 'Present')"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selected.skills?.length > 0">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="'Skills (' + selected.skills.length + ')'"></h3>
                                    <div class="flex flex-wrap gap-2">
                                        <template x-for="(skill, idx) in selected.skills" :key="idx">
                                            <span class="px-3 py-1 bg-purple-100 text-purple-700 rounded-full text-sm" x-text="skill.name || skill"></span>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <template x-if="selected.languages?.length > 0">
                                <div>
                                    <h3 class="text-lg font-bold text-gray-900 mb-2" x-text="'Languages (' + selected.languages.length + ')'"></h3>
                                    <div class="space-y-2">
                                        <template x-for="(lang, idx) in selected.languages" :key="idx">
                                            <div class="bg-gray-50 p-4 rounded-lg">
                                                <p class="font-semibold text-gray-900" x-text="lang.language || 'N/A'"></p>
                                                <p class="text-sm text-gray-600" x-text="'Proficiency: ' + (lang.proficiency || 'N/A')"></p>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </template>
                            <div class="flex gap-4 pt-4 border-t border-gray-200">
                                <a :href="'/resume/edit/' + selected.id" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition-colors flex items-center"><x-lucide-edit class="h-4 w-4 mr-2" /> Edit Resume</a>
                                <a :href="'/admin/users/' + selected.user_id" class="px-4 py-2 bg-gray-100 text-gray-700 rounded-lg hover:bg-gray-200 transition-colors flex items-center"><x-lucide-user class="h-4 w-4 mr-2" /> View User</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </template>

        {{-- List view --}}
        <div class="min-h-screen bg-gradient-to-br from-gray-50 via-purple-50 to-pink-50 py-12" x-show="!selected">
            <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
                <div class="mb-8">
                    <a href="{{ url('/admin/users/'.$id) }}" class="inline-flex items-center text-sm text-gray-600 hover:text-gray-900 mb-4 transition-colors">
                        <x-lucide-arrow-left class="h-4 w-4 mr-2" /> Back to User Details
                    </a>
                    <div class="flex items-center justify-between">
                        <div>
                            <h1 class="text-4xl font-bold text-gray-900 mb-2">User's Generated CVs</h1>
                            <template x-if="userInfo">
                                <p class="text-gray-600">All resumes created by <span class="font-semibold" x-text="userInfo.name"></span> (<span x-text="userInfo.email"></span>)</p>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-6 border border-gray-100 mb-6">
                    <div class="relative">
                        <x-lucide-search class="absolute left-3 top-1/2 transform -translate-y-1/2 h-5 w-5 text-gray-400" />
                        <input type="text" placeholder="Search by resume name..." x-model.debounce.300ms="search" class="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-purple-500 focus:border-transparent" />
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg p-12 border border-gray-100" x-show="loading">
                    <div class="flex items-center justify-center">
                        <x-lucide-loader-2 class="h-8 w-8 animate-spin text-purple-600" />
                        <span class="ml-3 text-gray-600">Loading CVs...</span>
                    </div>
                </div>

                <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-gray-100" x-show="!loading" x-cloak>
                    <div class="overflow-x-auto">
                        <table class="w-full">
                            <thead class="bg-gradient-to-r from-purple-50 to-pink-50 border-b border-gray-200">
                                <tr>
                                    <th class="{{ $th }}">Resume Name</th>
                                    <th class="{{ $th }}">Template</th>
                                    <th class="{{ $th }}">Created</th>
                                    <th class="{{ $th }}">Last Updated</th>
                                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">Actions</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200">
                                <template x-for="resume in items" :key="resume.id">
                                    <tr class="hover:bg-gray-50 transition-colors">
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <div class="flex items-center">
                                                <x-lucide-file-text class="h-5 w-5 text-purple-600 mr-3" />
                                                <div>
                                                    <div class="text-sm font-semibold text-gray-900" x-text="resume.name"></div>
                                                    <div class="text-xs text-gray-500" x-text="'ID: #' + resume.id"></div>
                                                </div>
                                            </div>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <span class="text-sm" :class="resume.template ? 'text-gray-900' : 'text-gray-400'" x-text="resume.template ? resume.template.name : 'N/A'"></span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center text-sm text-gray-900"><x-lucide-calendar class="h-4 w-4 mr-2 text-gray-400" /><span x-text="adminDate(resume.created_at, true)"></span></div></td>
                                        <td class="px-6 py-4 whitespace-nowrap"><div class="flex items-center text-sm text-gray-900"><x-lucide-calendar class="h-4 w-4 mr-2 text-gray-400" /><span x-text="adminDate(resume.updated_at, true)"></span></div></td>
                                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                            <div class="flex items-center justify-end space-x-2">
                                                <button type="button" @click="view(resume.id)" class="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors" title="View Details"><x-lucide-eye class="h-4 w-4" /></button>
                                                <a :href="'/resume/edit/' + resume.id" class="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors" title="Edit Resume"><x-lucide-edit class="h-4 w-4" /></a>
                                            </div>
                                        </td>
                                    </tr>
                                </template>
                                <tr x-show="items.length === 0">
                                    <td colspan="5" class="px-6 py-12 text-center">
                                        <x-lucide-file-text class="h-12 w-12 text-gray-400 mx-auto mb-4" />
                                        <p class="text-gray-500" x-text="search ? 'No resumes found matching your search' : 'This user has not created any resumes yet'"></p>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div x-show="pagination.total > 0"><x-admin.pagination label="resumes" /></div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.admin>
