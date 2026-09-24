/**
 * Warm Vite chunks for logged-in editor paths after idle time or hover.
 * Safe no-ops if import fails (e.g. offline).
 */
export function prefetchEditorChunks() {
  const tasks = [
    () => import("../pages/createResume"),
    () => import("../pages/EditResume"),
    () => import("../components/ResumeTemplatePreview"),
  ];
  tasks.forEach((fn) => {
    try {
      void fn();
    } catch {
      /* ignore */
    }
  });
}
