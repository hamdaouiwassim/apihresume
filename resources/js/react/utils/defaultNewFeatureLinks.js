/** Default CTA links for new-features announcement emails (app routes). */
export function defaultNewFeatureLinks() {
  const base = typeof window !== "undefined" ? window.location.origin.replace(/\/$/, "") : "";
  return [
    { label: "My resumes", url: `${base}/resumes` },
    { label: "Create a resume", url: `${base}/resume/create` },
    { label: "Cover letters", url: `${base}/cover-letters` },
  ];
}
