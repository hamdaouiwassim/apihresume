import { useState } from "react";
import { Loader2, Plus, Trash2, Sparkles } from "lucide-react";
import { toast } from "sonner";
import { defaultNewFeatureLinks } from "../../utils/defaultNewFeatureLinks";

const emptyLink = () => ({ label: "", url: "" });

export default function AdminNewFeaturesEmailForm({
  onSendToUser,
  onSendBulk,
  userName,
  showBulk = true,
  className = "",
}) {
  const [subject, setSubject] = useState("New features on the app — try them out");
  const [headline, setHeadline] = useState("What's new");
  const [message, setMessage] = useState(
    "We've added improvements you'll want to try. Use the links below to test the latest features and let us know if anything feels off."
  );
  const [links, setLinks] = useState(() => defaultNewFeatureLinks());
  const [filter, setFilter] = useState("verified");
  const [loading, setLoading] = useState(null);

  const updateLink = (index, field, value) => {
    setLinks((prev) => prev.map((row, i) => (i === index ? { ...row, [field]: value } : row)));
  };

  const payload = () => ({
    subject: subject.trim(),
    headline: headline.trim() || subject.trim(),
    message: message.trim(),
    links: links
      .map((l) => ({ label: l.label.trim(), url: l.url.trim() }))
      .filter((l) => l.label && l.url),
  });

  const validate = () => {
    const p = payload();
    if (!p.subject || !p.message) {
      return "Subject and message are required.";
    }
    if (p.links.length === 0) {
      return "Add at least one link with label and URL.";
    }
    return null;
  };

  const handleUser = async () => {
    const err = validate();
    if (err) return err;
    setLoading("user");
    try {
      await onSendToUser(payload());
      return null;
    } catch (e) {
      return e.response?.data?.message || "Failed to queue email.";
    } finally {
      setLoading(null);
    }
  };

  const handleBulk = async () => {
    const err = validate();
    if (err) return err;
    if (!window.confirm(`Queue new-features email for "${filter.replace("_", " ")}" users?`)) {
      return null;
    }
    setLoading("bulk");
    try {
      await onSendBulk({ ...payload(), filter });
      return null;
    } catch (e) {
      return e.response?.data?.message || "Failed to queue bulk emails.";
    } finally {
      setLoading(null);
    }
  };

  return (
    <div className={`rounded-2xl border border-violet-100 bg-gradient-to-br from-violet-50/80 to-white p-5 shadow-sm ${className}`}>
      <div className="mb-4 flex items-center gap-2">
        <Sparkles className="h-5 w-5 text-violet-600" />
        <h2 className="text-sm font-bold text-gray-900">New features announcement</h2>
      </div>
      <p className="text-sm text-gray-600 mb-4">
        {userName
          ? `Send a feature update to ${userName} with custom copy and test links.`
          : "Custom message plus links for users to test new functionality."}
      </p>

      <div className="space-y-3">
        <div className="grid gap-3 sm:grid-cols-2">
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Email subject</label>
            <input
              type="text"
              value={subject}
              onChange={(e) => setSubject(e.target.value)}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </div>
          <div>
            <label className="block text-xs font-semibold text-gray-600 mb-1">Headline (in email body)</label>
            <input
              type="text"
              value={headline}
              onChange={(e) => setHeadline(e.target.value)}
              className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-semibold text-gray-600 mb-1">Custom message</label>
          <textarea
            rows={4}
            value={message}
            onChange={(e) => setMessage(e.target.value)}
            className="w-full rounded-lg border border-gray-300 px-3 py-2 text-sm resize-none"
          />
        </div>

        <div>
          <div className="flex items-center justify-between mb-2">
            <label className="text-xs font-semibold text-gray-600">Test links</label>
            <button
              type="button"
              onClick={() => setLinks((prev) => [...prev, emptyLink()])}
              className="inline-flex items-center gap-1 text-xs font-semibold text-violet-600 hover:text-violet-800"
            >
              <Plus className="h-3.5 w-3.5" />
              Add link
            </button>
          </div>
          <div className="space-y-2">
            {links.map((link, index) => (
              <div key={index} className="flex flex-col sm:flex-row gap-2">
                <input
                  type="text"
                  placeholder="Button label"
                  value={link.label}
                  onChange={(e) => updateLink(index, "label", e.target.value)}
                  className="sm:w-1/3 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                />
                <input
                  type="url"
                  placeholder="https://…"
                  value={link.url}
                  onChange={(e) => updateLink(index, "url", e.target.value)}
                  className="flex-1 rounded-lg border border-gray-300 px-3 py-2 text-sm"
                />
                <button
                  type="button"
                  disabled={links.length <= 1}
                  onClick={() => setLinks((prev) => prev.filter((_, i) => i !== index))}
                  className="shrink-0 rounded-lg border border-gray-200 p-2 text-gray-500 hover:bg-red-50 hover:text-red-600 disabled:opacity-30"
                  aria-label="Remove link"
                >
                  <Trash2 className="h-4 w-4" />
                </button>
              </div>
            ))}
          </div>
          <button
            type="button"
            onClick={() => setLinks(defaultNewFeatureLinks())}
            className="mt-2 text-xs text-gray-500 hover:text-violet-600"
          >
            Reset to default app links
          </button>
        </div>

        <div className="flex flex-wrap gap-2 pt-2">
          {onSendToUser && (
            <button
              type="button"
              disabled={loading !== null}
              onClick={async () => {
                const err = await handleUser();
                if (err) toast.error(err);
              }}
              className="inline-flex items-center gap-2 rounded-xl bg-violet-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-violet-700 disabled:opacity-50"
            >
              {loading === "user" ? <Loader2 className="h-4 w-4 animate-spin" /> : <Sparkles className="h-4 w-4" />}
              {userName ? `Send to ${userName}` : "Send to user"}
            </button>
          )}
          {showBulk && onSendBulk && (
            <>
              <select
                value={filter}
                onChange={(e) => setFilter(e.target.value)}
                className="rounded-xl border border-gray-300 px-3 py-2.5 text-sm"
              >
                <option value="verified">Verified users</option>
                <option value="all_users">All users</option>
                <option value="pro">Pro users</option>
                <option value="unverified">Unverified users</option>
                <option value="incomplete_resume">Incomplete resumes</option>
              </select>
              <button
                type="button"
                disabled={loading !== null}
                onClick={async () => {
                  const err = await handleBulk();
                  if (err) toast.error(err);
                }}
                className="inline-flex items-center gap-2 rounded-xl border border-violet-300 bg-white px-4 py-2.5 text-sm font-semibold text-violet-800 hover:bg-violet-50 disabled:opacity-50"
              >
                {loading === "bulk" ? <Loader2 className="h-4 w-4 animate-spin" /> : null}
                Bulk queue
              </button>
            </>
          )}
        </div>
      </div>
    </div>
  );
}
