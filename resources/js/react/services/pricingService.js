import axiosInstance from '../api/axiosInstance';

const CACHE_KEY = 'hresume_pricing_region';
const CACHE_TTL_MS = 24 * 60 * 60 * 1000;

const FALLBACK = {
  region: 'international',
  currency: 'USD',
  free: { amount: 0, formatted: '$0' },
  pro: { amount: 5, formatted: '$5' },
};

export function getCachedPricingRegion() {
  try {
    const raw = sessionStorage.getItem(CACHE_KEY);
    if (!raw) return null;
    const parsed = JSON.parse(raw);
    if (!parsed?.data || !parsed?.cachedAt) return null;
    if (Date.now() - parsed.cachedAt > CACHE_TTL_MS) {
      sessionStorage.removeItem(CACHE_KEY);
      return null;
    }
    return parsed.data;
  } catch {
    return null;
  }
}

function setCachedPricingRegion(data) {
  try {
    sessionStorage.setItem(
      CACHE_KEY,
      JSON.stringify({ data, cachedAt: Date.now() })
    );
  } catch {
    // ignore quota / private mode
  }
}

export async function fetchPricingRegion() {
  const cached = getCachedPricingRegion();
  if (cached) {
    return cached;
  }

  const response = await axiosInstance.get('/pricing-region');
  if (response.data?.status && response.data?.data) {
    setCachedPricingRegion(response.data.data);
    return response.data.data;
  }

  return FALLBACK;
}

export { FALLBACK as DEFAULT_PRICING_REGION };
