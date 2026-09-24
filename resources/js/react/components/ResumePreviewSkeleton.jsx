/** Placeholder while lazy-loaded resume preview chunk downloads. */
export default function ResumePreviewSkeleton({ className = "" }) {
  return (
    <div
      className={`rounded-xl border border-slate-200 bg-slate-50 p-6 sm:p-8 animate-pulse min-h-[320px] sm:min-h-[420px] flex flex-col gap-3 ${className}`}
      aria-hidden
    >
      <div className="h-8 w-2/5 max-w-xs rounded-lg bg-slate-200" />
      <div className="h-3 w-full rounded bg-slate-200" />
      <div className="h-3 w-[92%] rounded bg-slate-200" />
      <div className="h-3 w-4/5 rounded bg-slate-200" />
      <div className="flex-1 min-h-[180px] rounded-lg bg-slate-200/80 mt-4" />
    </div>
  );
}
