/**
 * Lightweight skeleton for AI operations (enhance, tailor, ATS, etc.).
 */
export default function AiLoadingSkeleton({ rows = 4, className = "" }) {
  return (
    <div
      className={`rounded-xl border border-slate-200 bg-slate-50/80 p-4 space-y-3 animate-pulse ${className}`}
      role="status"
      aria-live="polite"
    >
      <div className="h-3 w-1/3 rounded bg-slate-200" />
      {Array.from({ length: rows }).map((_, i) => (
        <div
          key={i}
          className="h-3 rounded bg-slate-200"
          style={{ width: `${85 - i * 12}%` }}
        />
      ))}
      <span className="sr-only">Loading AI result</span>
    </div>
  );
}
