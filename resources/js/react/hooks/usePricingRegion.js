import { useEffect, useState } from 'react';
import {
  DEFAULT_PRICING_REGION,
  fetchPricingRegion,
  getCachedPricingRegion,
} from '../services/pricingService';

export function usePricingRegion() {
  const [pricing, setPricing] = useState(() => getCachedPricingRegion() || null);
  const [loading, setLoading] = useState(!getCachedPricingRegion());

  useEffect(() => {
    let cancelled = false;

    (async () => {
      try {
        const data = await fetchPricingRegion();
        if (!cancelled) {
          setPricing(data);
        }
      } catch {
        if (!cancelled) {
          setPricing(DEFAULT_PRICING_REGION);
        }
      } finally {
        if (!cancelled) {
          setLoading(false);
        }
      }
    })();

    return () => {
      cancelled = true;
    };
  }, []);

  const resolved = pricing || DEFAULT_PRICING_REGION;
  const isTunisia = resolved.region === 'tunisia';

  return {
    loading,
    region: resolved.region,
    isTunisia,
    countryCode: resolved.country_code ?? null,
    currency: resolved.currency,
    freePrice: resolved.free?.formatted ?? (isTunisia ? '0 TND' : '$0'),
    proPrice: resolved.pro?.formatted ?? (isTunisia ? '10 TND' : '$5'),
    proAmount: resolved.pro?.amount ?? (isTunisia ? 10 : 5),
  };
}
