/** Allowed post-login path from ?next= (open redirect safe list). */
export const POST_AUTH_CLAIM_DRAFT = "/resume/claim-draft";

export function safePostAuthRedirect(nextParam) {
  if (nextParam === POST_AUTH_CLAIM_DRAFT) {
    return POST_AUTH_CLAIM_DRAFT;
  }
  return null;
}
