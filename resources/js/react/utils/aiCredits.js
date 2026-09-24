/**
 * Sync ai_quota / ai_tokens from API responses into auth user state.
 */
export function syncAiUsageFromResponse(data, setUser) {
  if (!data || typeof setUser !== "function") return;
  const { ai_quota, ai_tokens } = data;
  if (ai_quota === undefined && ai_tokens === undefined) return;
  setUser((prev) => {
    if (!prev) return prev;
    return {
      ...prev,
      ...(ai_quota !== undefined ? { ai_quota } : {}),
      ...(ai_tokens !== undefined ? { ai_tokens } : {}),
    };
  });
}

export function hasAiTokenBudget(user) {
  const t = user?.ai_tokens;
  if (!t) return true;
  if (t.is_unlimited) return true;
  if (t.credits_remaining == null) return true;
  return t.credits_remaining > 0;
}

/** Unlimited monthly tokens (admins only by default). */
export function isAiUnlimited(user) {
  return Boolean(user?.is_admin || user?.ai_tokens?.is_unlimited);
}

export function isProPlan(user) {
  return Boolean(user?.is_pro && !user?.is_admin);
}
