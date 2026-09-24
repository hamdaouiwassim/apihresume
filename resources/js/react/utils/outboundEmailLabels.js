export const OUTBOUND_EMAIL_TYPES = {
  admin_custom: "Custom message",
  resume_incomplete_reminder: "Resume reminder",
  email_verification_reminder: "Verification reminder",
  new_features_announcement: "New features",
};

export const OUTBOUND_EMAIL_STATUS = {
  queued: { label: "Queued", className: "bg-amber-100 text-amber-900" },
  processing: { label: "Processing", className: "bg-blue-100 text-blue-900" },
  sent: { label: "Sent", className: "bg-emerald-100 text-emerald-900" },
  failed: { label: "Failed", className: "bg-red-100 text-red-900" },
  skipped: { label: "Skipped", className: "bg-gray-100 text-gray-700" },
};

export function formatOutboundType(type) {
  return OUTBOUND_EMAIL_TYPES[type] || type || "—";
}

export function statusBadge(status) {
  return OUTBOUND_EMAIL_STATUS[status] || { label: status, className: "bg-gray-100 text-gray-700" };
}
