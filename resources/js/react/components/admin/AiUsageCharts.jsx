const KIND_LABELS = {
  enhance_text: "Enhance",
  tailor_resume: "Tailor",
  ats_score: "ATS",
};

const COLORS = ["#a855f7", "#ec4899", "#6366f1", "#14b8a6", "#f59e0b"];

function fmt(n) {
  if (n == null) return "0";
  return Number(n).toLocaleString();
}

export function HorizontalBarChart({ items, valueKey = "total_tokens", labelKey = "kind", maxBars = 10 }) {
  const data = (items || []).slice(0, maxBars);
  const max = Math.max(...data.map((d) => Number(d[valueKey]) || 0), 1);

  if (data.length === 0) {
    return <p className="py-8 text-center text-sm text-gray-500">No data for this period.</p>;
  }

  return (
    <div className="space-y-3">
      {data.map((row, i) => {
        const val = Number(row[valueKey]) || 0;
        const pct = Math.round((val / max) * 100);
        const label =
          KIND_LABELS[row[labelKey]] || row.name || row[labelKey] || row.email || `#${row.user_id}`;
        return (
          <div key={row[labelKey] ?? row.user_id ?? i}>
            <div className="mb-1 flex justify-between text-xs">
              <span className="font-medium text-gray-700 truncate pr-2">{label}</span>
              <span className="tabular-nums text-gray-500 shrink-0">{fmt(val)}</span>
            </div>
            <div className="h-2.5 w-full overflow-hidden rounded-full bg-gray-100">
              <div
                className="h-full rounded-full transition-all duration-500"
                style={{ width: `${pct}%`, backgroundColor: COLORS[i % COLORS.length] }}
              />
            </div>
          </div>
        );
      })}
    </div>
  );
}

export function DailyTokensChart({ byDay }) {
  const data = (byDay || []).map((d) => ({
    date: d.date,
    tokens: Number(d.total_tokens) || 0,
    calls: Number(d.calls) || 0,
  }));

  if (data.length === 0) {
    return <p className="py-8 text-center text-sm text-gray-500">No daily activity in this range.</p>;
  }

  const max = Math.max(...data.map((d) => d.tokens), 1);
  const chartH = 160;
  const labelEvery = Math.max(1, Math.ceil(data.length / 7));

  return (
    <div>
      <div className="flex items-end gap-1 sm:gap-1.5" style={{ height: chartH }}>
        {data.map((d) => {
          const h = Math.max(4, Math.round((d.tokens / max) * (chartH - 8)));
          return (
            <div
              key={d.date}
              className="group flex flex-1 flex-col items-center justify-end min-w-0"
              title={`${d.date}: ${fmt(d.tokens)} tokens, ${d.calls} calls`}
            >
              <div
                className="w-full max-w-[28px] rounded-t-md bg-gradient-to-t from-fuchsia-600 to-purple-400 opacity-90 group-hover:opacity-100"
                style={{ height: h }}
              />
            </div>
          );
        })}
      </div>
      <div className="mt-2 flex gap-1">
        {data.map((d, i) => (
          <span
            key={d.date}
            className="flex-1 truncate text-center text-[10px] text-gray-400 min-w-0"
            title={d.date}
          >
            {i % labelEvery === 0 ? d.date?.slice(5) : ""}
          </span>
        ))}
      </div>
    </div>
  );
}

export function KindDonutChart({ byKind }) {
  const data = (byKind || []).map((k) => ({
    kind: k.kind,
    tokens: Number(k.total_tokens) || 0,
  }));
  const total = data.reduce((s, d) => s + d.tokens, 0) || 1;

  if (data.length === 0) {
    return <p className="py-8 text-center text-sm text-gray-500">No breakdown available.</p>;
  }

  let offset = 0;
  const segments = data.map((d, i) => {
    const pct = (d.tokens / total) * 100;
    const seg = { ...d, pct, color: COLORS[i % COLORS.length], offset };
    offset += pct;
    return seg;
  });

  const gradient = segments.map((s) => `${s.color} ${s.offset}% ${s.offset + s.pct}%`).join(", ");

  return (
    <div className="flex flex-col items-center gap-4 sm:flex-row sm:items-center sm:justify-center sm:gap-8">
      <div
        className="relative h-36 w-36 shrink-0 rounded-full"
        style={{ background: `conic-gradient(${gradient})` }}
      >
        <div className="absolute inset-3 flex flex-col items-center justify-center rounded-full bg-white text-center">
          <span className="text-xs text-gray-500">Total</span>
          <span className="text-sm font-bold text-gray-900 tabular-nums">{fmt(total)}</span>
        </div>
      </div>
      <ul className="space-y-2 text-sm">
        {segments.map((s) => (
          <li key={s.kind} className="flex items-center gap-2">
            <span className="h-3 w-3 rounded-full shrink-0" style={{ backgroundColor: s.color }} />
            <span className="text-gray-700">{KIND_LABELS[s.kind] || s.kind}</span>
            <span className="ml-auto tabular-nums text-gray-500">
              {fmt(s.tokens)} ({Math.round(s.pct)}%)
            </span>
          </li>
        ))}
      </ul>
    </div>
  );
}
