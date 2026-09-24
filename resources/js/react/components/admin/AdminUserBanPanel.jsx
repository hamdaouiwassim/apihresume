import { useState } from "react";
import { Ban, Loader2, ShieldOff } from "lucide-react";
import { toast } from "sonner";
import { banAdminUser, unbanAdminUser } from "../../services/adminService";

const DURATIONS = [
  { value: "3_days", label: "3 days" },
  { value: "7_days", label: "7 days" },
  { value: "15_days", label: "15 days" },
  { value: "1_month", label: "1 month" },
  { value: "permanent", label: "Permanent ban" },
];

function formatBanEnd(iso) {
  if (!iso) return null;
  try {
    return new Date(iso).toLocaleString();
  } catch {
    return iso;
  }
}

export default function AdminUserBanPanel({ user, onUpdated }) {
  const [duration, setDuration] = useState("7_days");
  const [reason, setReason] = useState("");
  const [loading, setLoading] = useState(null);

  if (!user || user.is_admin) {
    return null;
  }

  const banned = user.ban?.is_banned;

  const handleBan = async () => {
    if (duration === "permanent" && !window.confirm("Permanently ban this user until an admin lifts the ban?")) {
      return;
    }
    try {
      setLoading("ban");
      const res = await banAdminUser(user.id, { duration, reason: reason.trim() || undefined });
      if (res.data?.status) {
        toast.success(res.data.message || "User banned");
        onUpdated?.();
      }
    } catch (e) {
      toast.error(e.response?.data?.message || "Failed to ban user");
    } finally {
      setLoading(null);
    }
  };

  const handleUnban = async () => {
    try {
      setLoading("unban");
      const res = await unbanAdminUser(user.id);
      if (res.data?.status) {
        toast.success(res.data.message || "Ban lifted");
        onUpdated?.();
      }
    } catch (e) {
      toast.error(e.response?.data?.message || "Failed to lift ban");
    } finally {
      setLoading(null);
    }
  };

  return (
    <div className="bg-white rounded-2xl shadow-lg p-6 border border-gray-100">
      <div className="flex items-center gap-2 mb-2">
        <Ban className={`h-5 w-5 ${banned ? "text-red-600" : "text-gray-500"}`} />
        <h3 className="text-lg font-bold text-gray-900">Account ban</h3>
      </div>

      {banned ? (
        <div className="space-y-3">
          <div className="rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-900">
            <p className="font-semibold">
              {user.ban.banned_permanently ? "Permanently banned" : "Temporarily banned"}
            </p>
            {!user.ban.banned_permanently && user.ban.banned_until && (
              <p className="mt-1 text-red-800">Until {formatBanEnd(user.ban.banned_until)}</p>
            )}
            {user.ban.ban_reason && (
              <p className="mt-2 text-red-700">
                <span className="font-medium">Reason:</span> {user.ban.ban_reason}
              </p>
            )}
            {user.ban.banned_by?.name && (
              <p className="mt-1 text-xs text-red-600">By {user.ban.banned_by.name}</p>
            )}
          </div>
          <button
            type="button"
            onClick={handleUnban}
            disabled={loading !== null}
            className="inline-flex w-full items-center justify-center gap-2 rounded-xl border border-emerald-300 bg-emerald-50 px-4 py-2.5 text-sm font-semibold text-emerald-900 hover:bg-emerald-100 disabled:opacity-50"
          >
            {loading === "unban" ? <Loader2 className="h-4 w-4 animate-spin" /> : <ShieldOff className="h-4 w-4" />}
            Lift ban
          </button>
        </div>
      ) : (
        <div className="space-y-3">
          <p className="text-sm text-gray-600 mb-3">
            Suspend sign-in and API access. Tokens are revoked immediately.
          </p>
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Duration</label>
            <select
              value={duration}
              onChange={(e) => setDuration(e.target.value)}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            >
              {DURATIONS.map((d) => (
                <option key={d.value} value={d.value}>
                  {d.label}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Reason (optional)</label>
            <textarea
              rows={2}
              value={reason}
              onChange={(e) => setReason(e.target.value)}
              placeholder="Internal note shown in admin only"
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm resize-none"
            />
          </div>
          <button
            type="button"
            onClick={handleBan}
            disabled={loading !== null}
            className="inline-flex w-full items-center justify-center gap-2 rounded-xl bg-red-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-red-700 disabled:opacity-50"
          >
            {loading === "ban" ? <Loader2 className="h-4 w-4 animate-spin" /> : <Ban className="h-4 w-4" />}
            Apply ban
          </button>
        </div>
      )}
    </div>
  );
}
