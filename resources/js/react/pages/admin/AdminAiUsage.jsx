import { useState, useEffect, useCallback } from "react";
import { Link } from "react-router-dom";
import AdminLayout from "../../Layouts/AdminLayout";
import {
  Sparkles,
  Loader2,
  RefreshCw,
  ChevronLeft,
  ChevronRight,
  BarChart3,
  Users,
  List,
  Save,
  Infinity,
  Search,
} from "lucide-react";
import {
  getAdminAiUsageLogs,
  getAdminAiUsageSummary,
  getAdminAiUserLimits,
  updateAdminUserTokenLimit,
} from "../../services/adminService";
import {
  DailyTokensChart,
  KindDonutChart,
  HorizontalBarChart,
} from "../../components/admin/AiUsageCharts";
import { toast } from "sonner";
import AdminListPagination from "../../components/admin/AdminListPagination";
import { DEFAULT_ADMIN_PER_PAGE } from "../../constants/adminPagination";

const KINDS = [
  { value: "", label: "All kinds" },
  { value: "enhance_text", label: "Enhance text" },
  { value: "tailor_resume", label: "Tailor resume" },
  { value: "ats_score", label: "ATS score" },
];

const TABS = [
  { id: "overview", label: "Overview", icon: BarChart3 },
  { id: "limits", label: "User limits", icon: Users },
  { id: "logs", label: "Call log", icon: List },
];

function defaultDateRange() {
  const to = new Date();
  const from = new Date();
  from.setDate(from.getDate() - 30);
  return {
    from: from.toISOString().slice(0, 10),
    to: to.toISOString().slice(0, 10),
  };
}

export default function AdminAiUsage() {
  const initialRange = defaultDateRange();
  const [tab, setTab] = useState("overview");
  const [summary, setSummary] = useState(null);
  const [summaryLoading, setSummaryLoading] = useState(true);
  const [logs, setLogs] = useState(null);
  const [logsLoading, setLogsLoading] = useState(false);
  const [limits, setLimits] = useState(null);
  const [limitsLoading, setLimitsLoading] = useState(false);
  const [userId, setUserId] = useState("");
  const [kind, setKind] = useState("");
  const [from, setFrom] = useState(initialRange.from);
  const [to, setTo] = useState(initialRange.to);
  const [page, setPage] = useState(1);
  const [logsPerPage, setLogsPerPage] = useState(DEFAULT_ADMIN_PER_PAGE);
  const [limitsSearch, setLimitsSearch] = useState("");
  const [limitsPage, setLimitsPage] = useState(1);
  const [limitsPerPage, setLimitsPerPage] = useState(DEFAULT_ADMIN_PER_PAGE);
  const [editingLimits, setEditingLimits] = useState({});
  const [savingUserId, setSavingUserId] = useState(null);

  const loadSummary = useCallback(async () => {
    try {
      setSummaryLoading(true);
      const params = {};
      if (from) params.from = from;
      if (to) params.to = to;
      const res = await getAdminAiUsageSummary(params);
      if (res.data?.status) setSummary(res.data.data);
    } catch {
      toast.error("Failed to load AI usage summary");
    } finally {
      setSummaryLoading(false);
    }
  }, [from, to]);

  const loadLogs = useCallback(async () => {
    try {
      setLogsLoading(true);
      const params = { page, per_page: logsPerPage };
      if (userId.trim()) params.user_id = userId.trim();
      if (kind) params.kind = kind;
      if (from) params.from = from;
      if (to) params.to = to;
      const res = await getAdminAiUsageLogs(params);
      if (res.data?.status) setLogs(res.data.data);
    } catch {
      toast.error("Failed to load logs");
    } finally {
      setLogsLoading(false);
    }
  }, [page, logsPerPage, userId, kind, from, to]);

  const loadLimits = useCallback(async () => {
    try {
      setLimitsLoading(true);
      const params = { page: limitsPage, per_page: limitsPerPage };
      if (limitsSearch.trim()) params.search = limitsSearch.trim();
      const res = await getAdminAiUserLimits(params);
      if (res.data?.status) {
        setLimits(res.data.data);
        const draft = {};
        (res.data.data.data || []).forEach((u) => {
          draft[u.id] =
            u.ai_monthly_token_limit === null || u.ai_monthly_token_limit === undefined
              ? ""
              : String(u.ai_monthly_token_limit);
        });
        setEditingLimits(draft);
      }
    } catch {
      toast.error("Failed to load user limits");
    } finally {
      setLimitsLoading(false);
    }
  }, [limitsPage, limitsPerPage, limitsSearch]);

  useEffect(() => {
    loadSummary();
  }, [loadSummary]);

  useEffect(() => {
    if (tab === "logs") loadLogs();
  }, [tab, loadLogs]);

  useEffect(() => {
    if (tab === "limits") loadLimits();
  }, [tab, loadLimits]);

  const refreshAll = () => {
    loadSummary();
    if (tab === "logs") loadLogs();
    if (tab === "limits") loadLimits();
  };

  const saveUserLimit = async (user) => {
    const raw = editingLimits[user.id];
    let value = null;
    if (raw !== "" && raw !== undefined) {
      const n = parseInt(raw, 10);
      if (Number.isNaN(n) || n < 0) {
        toast.error("Enter a valid non-negative number or leave empty for default");
        return;
      }
      value = n;
    }
    setSavingUserId(user.id);
    try {
      const res = await updateAdminUserTokenLimit(user.id, value);
      if (res.data?.status) {
        toast.success(`Limit updated for ${user.name || user.email}`);
        loadLimits();
        loadSummary();
      }
    } catch (e) {
      toast.error(e.response?.data?.message || "Failed to update limit");
    } finally {
      setSavingUserId(null);
    }
  };

  const fmt = (n) => (n == null ? "—" : Number(n).toLocaleString());
  const rows = logs?.data ?? [];
  const lastPage = logs?.last_page ?? 1;
  const logsTotal = logs?.total ?? rows.length;
  const logsCurrentPage = logs?.current_page ?? page;
  const limitRows = limits?.data ?? [];
  const limitsLastPage = limits?.last_page ?? 1;
  const limitsTotal = limits?.total ?? limitRows.length;
  const limitsCurrentPage = limits?.current_page ?? limitsPage;
  const defaultLimit = summary?.default_monthly_token_limit ?? 1000;
  const proDefaultLimit = summary?.pro_monthly_token_limit ?? 50000;

  return (
    <AdminLayout>
      <div className="max-w-7xl mx-auto animate-in fade-in duration-500 pb-12">
        {/* Header */}
        <div className="mb-6 flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
          <div>
            <h1 className="text-3xl sm:text-4xl font-bold text-gray-900 mb-2 flex items-center gap-3">
              <span className="flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-fuchsia-500 to-purple-600 shadow-lg shadow-purple-200">
                <Sparkles className="h-6 w-6 text-white" />
              </span>
              AI usage & limits
            </h1>
            <p className="text-gray-600 max-w-xl">
              Monitor token consumption, charts, and per-user monthly caps. Free: 1,000/mo · Pro: 50,000/mo · Admin: unlimited unless capped.
            </p>
          </div>
          <button
            type="button"
            onClick={refreshAll}
            className="inline-flex items-center gap-2 rounded-xl bg-white border border-gray-200 px-4 py-2.5 text-sm font-semibold text-gray-700 shadow-sm hover:bg-gray-50"
          >
            <RefreshCw className="h-4 w-4" />
            Refresh
          </button>
        </div>

        {/* Date range */}
        <div className="mb-6 rounded-2xl border border-purple-100 bg-gradient-to-r from-purple-50/80 to-fuchsia-50/50 p-4 sm:p-5">
          <p className="text-xs font-semibold uppercase tracking-wide text-purple-600 mb-3">Report period</p>
          <div className="flex flex-wrap gap-3 items-end">
            <label className="block">
              <span className="text-xs text-gray-500">From</span>
              <input
                type="date"
                value={from}
                onChange={(e) => {
                  setFrom(e.target.value);
                  setPage(1);
                }}
                className="mt-1 block rounded-xl border border-gray-300 px-3 py-2 text-sm bg-white"
              />
            </label>
            <label className="block">
              <span className="text-xs text-gray-500">To</span>
              <input
                type="date"
                value={to}
                onChange={(e) => {
                  setTo(e.target.value);
                  setPage(1);
                }}
                className="mt-1 block rounded-xl border border-gray-300 px-3 py-2 text-sm bg-white"
              />
            </label>
            <p className="text-sm text-gray-600 pb-2">
              Defaults: free <strong className="tabular-nums">{fmt(defaultLimit)}</strong> · Pro{" "}
              <strong className="tabular-nums">{fmt(proDefaultLimit)}</strong> tokens / month
            </p>
          </div>
        </div>

        {/* Tabs */}
        <div className="mb-6 flex gap-2 overflow-x-auto pb-1">
          {TABS.map(({ id, label, icon: Icon }) => (
            <button
              key={id}
              type="button"
              onClick={() => setTab(id)}
              className={`inline-flex items-center gap-2 rounded-xl px-4 py-2.5 text-sm font-semibold whitespace-nowrap transition-all ${
                tab === id
                  ? "bg-gradient-to-r from-purple-600 to-fuchsia-600 text-white shadow-md"
                  : "bg-white text-gray-600 border border-gray-200 hover:bg-gray-50"
              }`}
            >
              <Icon className="h-4 w-4" />
              {label}
            </button>
          ))}
        </div>

        {/* Overview tab */}
        {tab === "overview" && (
          <div className="space-y-6">
            {summaryLoading ? (
              <div className="flex justify-center py-20">
                <Loader2 className="h-10 w-10 animate-spin text-purple-600" />
              </div>
            ) : (
              <>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                  {[
                    { label: "API calls", value: summary?.totals?.calls, sub: "In period" },
                    { label: "Total tokens", value: summary?.totals?.total_tokens, sub: "Prompt + completion" },
                    { label: "Prompt tokens", value: summary?.totals?.prompt_tokens },
                    { label: "Completion tokens", value: summary?.totals?.completion_tokens },
                  ].map((card) => (
                    <div
                      key={card.label}
                      className="rounded-2xl border border-white/60 bg-white p-5 shadow-sm ring-1 ring-gray-100"
                    >
                      <p className="text-xs font-semibold uppercase tracking-wide text-gray-400">{card.label}</p>
                      <p className="mt-2 text-3xl font-bold text-gray-900 tabular-nums">{fmt(card.value)}</p>
                      {card.sub && <p className="mt-1 text-xs text-gray-500">{card.sub}</p>}
                    </div>
                  ))}
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                  <section className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-1 text-lg font-bold text-gray-900">Daily tokens</h2>
                    <p className="mb-4 text-sm text-gray-500">Usage over the selected date range</p>
                    <DailyTokensChart byDay={summary?.by_day} />
                  </section>
                  <section className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-1 text-lg font-bold text-gray-900">By tool</h2>
                    <p className="mb-4 text-sm text-gray-500">Share of tokens per feature</p>
                    <KindDonutChart byKind={summary?.by_kind} />
                  </section>
                </div>

                <div className="grid gap-6 lg:grid-cols-2">
                  <section className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-bold text-gray-900">Top users</h2>
                    <HorizontalBarChart
                      items={summary?.top_users}
                      valueKey="total_tokens"
                      labelKey="name"
                    />
                  </section>
                  <section className="rounded-2xl border border-gray-100 bg-white p-6 shadow-sm">
                    <h2 className="mb-4 text-lg font-bold text-gray-900">Calls by tool</h2>
                    <HorizontalBarChart items={summary?.by_kind} valueKey="calls" labelKey="kind" />
                  </section>
                </div>
              </>
            )}
          </div>
        )}

        {/* User limits tab */}
        {tab === "limits" && (
          <div className="space-y-4">
            <div className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm flex flex-col sm:flex-row gap-3">
              <div className="relative flex-1">
                <Search className="absolute left-3 top-1/2 -translate-y-1/2 h-4 w-4 text-gray-400" />
                <input
                  type="text"
                  value={limitsSearch}
                  onChange={(e) => {
                    setLimitsSearch(e.target.value);
                    setLimitsPage(1);
                  }}
                  placeholder="Search name, email, or user ID…"
                  className="w-full pl-10 pr-4 py-2.5 rounded-xl border border-gray-300 text-sm"
                />
              </div>
            </div>

            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
              <div className="border-b border-gray-100 px-6 py-4 bg-gray-50/80">
                <h2 className="text-lg font-bold text-gray-900">Per-user token limits</h2>
                <p className="text-sm text-gray-500 mt-1">
                  Empty = role default (free {fmt(defaultLimit)}, Pro {fmt(proDefaultLimit)}); admin unlimited. <strong>0</strong> = block AI.
                </p>
              </div>
              {limitsLoading ? (
                <div className="flex justify-center py-16">
                  <Loader2 className="h-10 w-10 animate-spin text-purple-600" />
                </div>
              ) : (
                <>
                  <div className="overflow-x-auto">
                    <table className="min-w-full text-sm">
                      <thead className="bg-gray-50 text-gray-600">
                        <tr>
                          <th className="px-4 py-3 text-left font-semibold">User</th>
                          <th className="px-4 py-3 text-left font-semibold">Plan</th>
                          <th className="px-4 py-3 text-right font-semibold">Used (month)</th>
                          <th className="px-4 py-3 text-right font-semibold">Limit</th>
                          <th className="px-4 py-3 text-left font-semibold w-48">Usage</th>
                          <th className="px-4 py-3 text-right font-semibold">Custom cap</th>
                          <th className="px-4 py-3" />
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {limitRows.length === 0 ? (
                          <tr>
                            <td colSpan={7} className="px-4 py-12 text-center text-gray-500">
                              No users found.
                            </td>
                          </tr>
                        ) : (
                          limitRows.map((u) => {
                            const pct = u.is_unlimited ? null : u.percent_used ?? 0;
                            const barPct = u.is_unlimited ? 0 : Math.min(100, pct);
                            return (
                              <tr key={u.id} className="hover:bg-gray-50/80">
                                <td className="px-4 py-3">
                                  <div className="font-medium text-gray-900">{u.name}</div>
                                  <div className="text-xs text-gray-500">{u.email}</div>
                                  <Link to={`/admin/users/${u.id}`} className="text-xs font-semibold text-purple-600">
                                    #{u.id}
                                  </Link>
                                </td>
                                <td className="px-4 py-3">
                                  {u.is_admin ? (
                                    <span className="rounded-full bg-violet-100 px-2 py-0.5 text-xs font-semibold text-violet-700">Admin</span>
                                  ) : u.is_pro ? (
                                    <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-semibold text-amber-800">Pro</span>
                                  ) : (
                                    <span className="rounded-full bg-gray-100 px-2 py-0.5 text-xs font-semibold text-gray-600">Free</span>
                                  )}
                                </td>
                                <td className="px-4 py-3 text-right tabular-nums">{fmt(u.tokens_used)}</td>
                                <td className="px-4 py-3 text-right tabular-nums">
                                  {u.is_unlimited ? (
                                    <span className="inline-flex items-center gap-1 text-emerald-600 font-medium">
                                      <Infinity className="h-4 w-4" /> Unlimited
                                    </span>
                                  ) : (
                                    fmt(u.token_limit)
                                  )}
                                </td>
                                <td className="px-4 py-3">
                                  {u.is_unlimited ? (
                                    <span className="text-xs text-gray-400">—</span>
                                  ) : (
                                    <div className="h-2 rounded-full bg-gray-100 overflow-hidden">
                                      <div
                                        className={`h-full rounded-full ${
                                          barPct >= 90 ? "bg-red-500" : barPct >= 70 ? "bg-amber-500" : "bg-purple-500"
                                        }`}
                                        style={{ width: `${barPct}%` }}
                                      />
                                    </div>
                                  )}
                                </td>
                                <td className="px-4 py-3">
                                  <input
                                    type="number"
                                    min="0"
                                    placeholder={`Default (${defaultLimit})`}
                                    value={editingLimits[u.id] ?? ""}
                                    onChange={(e) =>
                                      setEditingLimits((prev) => ({ ...prev, [u.id]: e.target.value }))
                                    }
                                    className="w-36 rounded-lg border border-gray-300 px-2 py-1.5 text-sm text-right tabular-nums"
                                  />
                                </td>
                                <td className="px-4 py-3 text-right">
                                  <button
                                    type="button"
                                    disabled={savingUserId === u.id}
                                    onClick={() => saveUserLimit(u)}
                                    className="inline-flex items-center gap-1 rounded-lg bg-purple-600 px-3 py-1.5 text-xs font-semibold text-white hover:bg-purple-700 disabled:opacity-50"
                                  >
                                    {savingUserId === u.id ? (
                                      <Loader2 className="h-3.5 w-3.5 animate-spin" />
                                    ) : (
                                      <Save className="h-3.5 w-3.5" />
                                    )}
                                    Save
                                  </button>
                                </td>
                              </tr>
                            );
                          })
                        )}
                      </tbody>
                    </table>
                  </div>
                  {!limitsLoading && limitsTotal > 0 && (
                    <AdminListPagination
                      currentPage={limitsCurrentPage}
                      lastPage={limitsLastPage}
                      perPage={limitsPerPage}
                      total={limitsTotal}
                      onPageChange={setLimitsPage}
                      onPerPageChange={(size) => {
                        setLimitsPerPage(size);
                        setLimitsPage(1);
                      }}
                      itemLabel="users"
                    />
                  )}
                </>
              )}
            </div>
          </div>
        )}

        {/* Logs tab */}
        {tab === "logs" && (
          <div className="space-y-4">
            <div className="rounded-2xl border border-gray-100 bg-white p-4 shadow-sm grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
              <div>
                <label className="text-xs font-semibold text-gray-500">User ID</label>
                <input
                  type="text"
                  value={userId}
                  onChange={(e) => {
                    setUserId(e.target.value);
                    setPage(1);
                  }}
                  placeholder="e.g. 42"
                  className="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm"
                />
              </div>
              <div>
                <label className="text-xs font-semibold text-gray-500">Kind</label>
                <select
                  value={kind}
                  onChange={(e) => {
                    setKind(e.target.value);
                    setPage(1);
                  }}
                  className="mt-1 w-full rounded-xl border border-gray-300 px-3 py-2 text-sm"
                >
                  {KINDS.map((k) => (
                    <option key={k.value || "all"} value={k.value}>
                      {k.label}
                    </option>
                  ))}
                </select>
              </div>
            </div>

            <div className="rounded-2xl border border-gray-100 bg-white shadow-sm overflow-hidden">
              {logsLoading ? (
                <div className="flex justify-center py-16">
                  <Loader2 className="h-10 w-10 animate-spin text-purple-600" />
                </div>
              ) : (
                <>
                  <div className="overflow-x-auto">
                    <table className="min-w-full text-left text-sm">
                      <thead className="bg-gray-50 text-gray-600">
                        <tr>
                          <th className="px-4 py-3 font-semibold">Time</th>
                          <th className="px-4 py-3 font-semibold">User</th>
                          <th className="px-4 py-3 font-semibold">Kind</th>
                          <th className="px-4 py-3 font-semibold">Resume</th>
                          <th className="px-4 py-3 font-semibold">Model</th>
                          <th className="px-4 py-3 font-semibold text-right">Total</th>
                        </tr>
                      </thead>
                      <tbody className="divide-y divide-gray-100">
                        {rows.length === 0 ? (
                          <tr>
                            <td colSpan={6} className="px-4 py-12 text-center text-gray-500">
                              No records match your filters.
                            </td>
                          </tr>
                        ) : (
                          rows.map((row) => (
                            <tr key={row.id} className="hover:bg-gray-50/80">
                              <td className="px-4 py-3 whitespace-nowrap text-gray-600">
                                {row.created_at ? new Date(row.created_at).toLocaleString() : "—"}
                              </td>
                              <td className="px-4 py-3">
                                <div className="font-medium">{row.user?.name}</div>
                                <Link to={`/admin/users/${row.user_id}`} className="text-xs text-purple-600">
                                  #{row.user_id}
                                </Link>
                              </td>
                              <td className="px-4 py-3 font-mono text-xs">{row.kind}</td>
                              <td className="px-4 py-3">{row.resume_id ?? "—"}</td>
                              <td className="px-4 py-3 text-xs">
                                <span className="rounded bg-gray-100 px-1">{row.provider}</span> {row.model}
                              </td>
                              <td className="px-4 py-3 text-right font-semibold tabular-nums">
                                {fmt(row.total_tokens)}
                              </td>
                            </tr>
                          ))
                        )}
                      </tbody>
                    </table>
                  </div>
                  {!logsLoading && logsTotal > 0 && (
                    <AdminListPagination
                      currentPage={logsCurrentPage}
                      lastPage={lastPage}
                      perPage={logsPerPage}
                      total={logsTotal}
                      onPageChange={setPage}
                      onPerPageChange={(size) => {
                        setLogsPerPage(size);
                        setPage(1);
                      }}
                      itemLabel="log entries"
                    />
                  )}
                </>
              )}
            </div>
          </div>
        )}
      </div>
    </AdminLayout>
  );
}
