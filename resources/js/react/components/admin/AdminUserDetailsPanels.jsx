import { Link } from 'react-router-dom';
import { Sparkles, Eye, Edit, Trash2, FileText } from 'lucide-react';

const AI_KIND_LABELS = {
    enhance_text: 'Enhance text',
    tailor_resume: 'Job targeting',
    ats_score: 'ATS score',
};

export function AdminUserAiConsumption({ user, formatDate }) {
    return (
        <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <Sparkles className="h-5 w-5 text-purple-600" />
                    AI consumption
                </h3>
                <Link
                    to="/admin/ai-usage"
                    className="text-sm font-medium text-purple-600 hover:text-purple-800"
                >
                    Global AI usage →
                </Link>
            </div>

            {user.ai_tokens && (
                <div className="mb-6 rounded-xl border border-violet-100 bg-gradient-to-br from-violet-50 to-fuchsia-50/60 p-4">
                    <div className="flex flex-wrap items-end justify-between gap-3 mb-3">
                        <div>
                            <p className="text-xs font-semibold uppercase tracking-wide text-gray-500">
                                Monthly token budget
                                {user.ai_tokens.is_unlimited ? ' (admin)' : user.is_pro ? ' (Pro)' : ' (free)'}
                            </p>
                            {user.ai_tokens.is_unlimited ? (
                                <p className="text-lg font-bold text-emerald-700">Unlimited</p>
                            ) : (
                                <p className="text-2xl font-bold text-gray-900 tabular-nums">
                                    {(user.ai_tokens.credits_remaining ?? user.ai_tokens.tokens_remaining ?? 0).toLocaleString()}
                                    <span className="text-base font-medium text-gray-500">
                                        {' '}/ {(user.ai_tokens.credits_total ?? user.ai_tokens.token_limit ?? 0).toLocaleString()} left
                                    </span>
                                </p>
                            )}
                        </div>
                        {!user.ai_tokens.is_unlimited && (
                            <p className="text-sm text-gray-600 tabular-nums">
                                Used: {(user.ai_tokens.credits_used ?? user.ai_tokens.tokens_used ?? 0).toLocaleString()}
                                {user.ai_tokens.percent_used != null && ` (${user.ai_tokens.percent_used}%)`}
                            </p>
                        )}
                    </div>
                    {!user.ai_tokens.is_unlimited && user.ai_tokens.token_limit > 0 && (
                        <div className="h-2.5 w-full overflow-hidden rounded-full bg-white ring-1 ring-violet-100">
                            <div
                                className="h-full rounded-full bg-gradient-to-r from-violet-500 to-fuchsia-500 transition-all"
                                style={{
                                    width: `${Math.min(100, user.ai_tokens.percent_used ?? 0)}%`,
                                }}
                            />
                        </div>
                    )}
                </div>
            )}

            {user.ai_usage?.totals && (
                <div className="grid grid-cols-2 sm:grid-cols-4 gap-3 mb-4">
                    <div className="rounded-lg bg-gray-50 p-3 text-center">
                        <p className="text-[10px] font-semibold uppercase text-gray-500">Calls (month)</p>
                        <p className="text-lg font-bold text-gray-900">{user.ai_usage.totals.calls ?? 0}</p>
                    </div>
                    <div className="rounded-lg bg-gray-50 p-3 text-center">
                        <p className="text-[10px] font-semibold uppercase text-gray-500">Total tokens</p>
                        <p className="text-lg font-bold text-gray-900 tabular-nums">
                            {(user.ai_usage.totals.total_tokens ?? 0).toLocaleString()}
                        </p>
                    </div>
                    <div className="rounded-lg bg-gray-50 p-3 text-center">
                        <p className="text-[10px] font-semibold uppercase text-gray-500">Prompt</p>
                        <p className="text-lg font-bold text-gray-900 tabular-nums">
                            {(user.ai_usage.totals.prompt_tokens ?? 0).toLocaleString()}
                        </p>
                    </div>
                    <div className="rounded-lg bg-gray-50 p-3 text-center">
                        <p className="text-[10px] font-semibold uppercase text-gray-500">Completion</p>
                        <p className="text-lg font-bold text-gray-900 tabular-nums">
                            {(user.ai_usage.totals.completion_tokens ?? 0).toLocaleString()}
                        </p>
                    </div>
                </div>
            )}

            {user.ai_usage?.by_kind?.length > 0 && (
                <div className="mb-4 overflow-x-auto">
                    <table className="w-full text-sm">
                        <thead>
                            <tr className="border-b border-gray-200 text-left text-xs uppercase text-gray-500">
                                <th className="py-2 pr-4">Tool</th>
                                <th className="py-2 pr-4">Calls</th>
                                <th className="py-2">Tokens</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {user.ai_usage.by_kind.map((row) => (
                                <tr key={row.kind}>
                                    <td className="py-2 pr-4 font-medium text-gray-900">
                                        {AI_KIND_LABELS[row.kind] || row.kind}
                                    </td>
                                    <td className="py-2 pr-4 tabular-nums">{row.calls}</td>
                                    <td className="py-2 tabular-nums">{Number(row.total_tokens).toLocaleString()}</td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            )}

            {user.recent_ai_logs?.length > 0 ? (
                <div>
                    <p className="text-xs font-semibold uppercase tracking-wide text-gray-500 mb-2">Recent activity</p>
                    <div className="max-h-48 overflow-y-auto rounded-lg border border-gray-100 divide-y divide-gray-100">
                        {user.recent_ai_logs.map((log) => (
                            <div key={log.id} className="flex flex-wrap items-center justify-between gap-2 px-3 py-2 text-xs">
                                <span className="font-medium text-gray-800">
                                    {AI_KIND_LABELS[log.kind] || log.kind}
                                </span>
                                <span className="text-gray-500 tabular-nums">
                                    {log.total_tokens} tokens · {formatDate(log.created_at)}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            ) : (
                <p className="text-sm text-gray-500">No AI usage recorded yet.</p>
            )}
        </div>
    );
}

export function AdminUserResumesList({
    user,
    formatDate,
    isLoadingResume,
    onShowResume,
    onDeleteResume,
}) {
    return (
        <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
            <div className="flex items-center justify-between mb-4">
                <h3 className="text-xl font-bold text-gray-900 flex items-center gap-2">
                    <FileText className="h-5 w-5 text-purple-600" />
                    Created CVs
                </h3>
                <span className="text-sm text-gray-600">Total: {user.resumes_count || 0}</span>
            </div>
            {user.resumes && user.resumes.length > 0 ? (
                <div className="overflow-x-auto rounded-xl border border-gray-100">
                    <table className="w-full text-sm">
                        <thead className="bg-gray-50 border-b border-gray-200">
                            <tr>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Name</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Template</th>
                                <th className="px-4 py-3 text-left text-xs font-semibold uppercase text-gray-600">Updated</th>
                                <th className="px-4 py-3 text-right text-xs font-semibold uppercase text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y divide-gray-100">
                            {user.resumes.map((resume) => (
                                <tr key={resume.id} className="hover:bg-gray-50/80">
                                    <td className="px-4 py-3">
                                        <p className="font-semibold text-gray-900">{resume.name}</p>
                                        <p className="text-xs text-gray-500">#{resume.id}</p>
                                    </td>
                                    <td className="px-4 py-3 text-gray-600">
                                        {resume.template?.name || '—'}
                                    </td>
                                    <td className="px-4 py-3 text-gray-600 whitespace-nowrap">
                                        {formatDate(resume.updated_at)}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center justify-end gap-1">
                                            <button
                                                type="button"
                                                onClick={() => onShowResume(resume.id)}
                                                disabled={isLoadingResume}
                                                className="p-2 text-purple-600 hover:bg-purple-50 rounded-lg transition-colors disabled:opacity-50"
                                                title="Show"
                                            >
                                                <Eye className="h-4 w-4" />
                                            </button>
                                            <Link
                                                to={`/resume/edit/${resume.id}`}
                                                className="p-2 text-gray-600 hover:bg-gray-100 rounded-lg transition-colors"
                                                title="Open editor"
                                            >
                                                <Edit className="h-4 w-4" />
                                            </Link>
                                            <button
                                                type="button"
                                                onClick={() => onDeleteResume(resume)}
                                                className="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                                                title="Delete"
                                            >
                                                <Trash2 className="h-4 w-4" />
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            ) : (
                <p className="text-gray-500 text-center py-8">No resumes created yet</p>
            )}
        </div>
    );
}
