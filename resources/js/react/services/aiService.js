import axiosInstance from "../api/axiosInstance";

/** AI calls should fail fast enough to stay under a ~60s product target (server may still cap lower). */
const AI_REQUEST_TIMEOUT_MS = 55_000;

const ENHANCE_CACHE_TTL_MS = 3 * 60 * 1000;
const ENHANCE_CACHE_MAX = 48;

/** Simple stable key for identical enhance requests in-session */
function enhanceCacheKey(payload) {
  const text = (payload?.text ?? "").trim();
  const ctx = (payload?.context ?? "").trim();
  let h = 2166136261 >>> 0;
  const s = `${ctx}\n${text}`;
  for (let i = 0; i < s.length; i++) {
    h ^= s.charCodeAt(i);
    h = Math.imul(h, 16777619) >>> 0;
  }
  return String(h);
}

const enhanceCache = new Map();

const ATS_CACHE_TTL_MS = 90 * 1000;
const ATS_CACHE_MAX = 16;
const atsCache = new Map();

function atsCacheKey(payload) {
  const rid = payload?.resume_id ?? "";
  const jd = (payload?.job_description ?? "").trim();
  let h = 5381;
  const s = `${rid}::${jd}`;
  for (let i = 0; i < s.length; i++) {
    h = (h * 33) ^ s.charCodeAt(i);
  }
  return String(h >>> 0);
}

export const tailorResume = (payload) =>
  axiosInstance.post("/ai/tailor-resume", payload, { timeout: AI_REQUEST_TIMEOUT_MS });

export const enhanceText = (payload) => {
  const text = (payload?.text ?? "").trim();
  if (text.length < 4 || text.length > 14000) {
    return axiosInstance.post("/ai/enhance-text", payload, { timeout: AI_REQUEST_TIMEOUT_MS });
  }
  const key = enhanceCacheKey(payload);
  const hit = enhanceCache.get(key);
  if (hit && Date.now() - hit.at < ENHANCE_CACHE_TTL_MS) {
    return Promise.resolve({ data: hit.data, status: 200 });
  }
  return axiosInstance.post("/ai/enhance-text", payload, { timeout: AI_REQUEST_TIMEOUT_MS }).then((res) => {
    const clone = JSON.parse(JSON.stringify(res.data));
    if (enhanceCache.size >= ENHANCE_CACHE_MAX) {
      const first = enhanceCache.keys().next().value;
      enhanceCache.delete(first);
    }
    enhanceCache.set(key, { at: Date.now(), data: clone });
    return res;
  });
};

export const getAtsScore = (payload) => {
  const key = atsCacheKey(payload);
  const hit = atsCache.get(key);
  if (hit && Date.now() - hit.at < ATS_CACHE_TTL_MS) {
    return Promise.resolve({ data: hit.data, status: 200 });
  }
  return axiosInstance
    .post("/ai/ats-score", payload, { timeout: AI_REQUEST_TIMEOUT_MS })
    .then((res) => {
      const clone = JSON.parse(JSON.stringify(res.data));
      if (atsCache.size >= ATS_CACHE_MAX) {
        const first = atsCache.keys().next().value;
        atsCache.delete(first);
      }
      atsCache.set(key, { at: Date.now(), data: clone });
      return res;
    });
};

export const parseCvText = (payload) =>
  axiosInstance.post("/ai/parse-cv-text", payload, { timeout: AI_REQUEST_TIMEOUT_MS });
