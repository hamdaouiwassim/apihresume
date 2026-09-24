import { useEffect, useState } from 'react';

function easeOutCubic(t) {
  return 1 - (1 - t) ** 3;
}

/**
 * Animates a number from 0 to `value` when `active` becomes true.
 */
export default function CountUpNumber({
  value = 0,
  duration = 2200,
  active = false,
  format = (n) => String(n),
  className = '',
}) {
  const [display, setDisplay] = useState(0);

  useEffect(() => {
    const target = Math.max(0, Math.round(Number(value) || 0));

    if (!active) {
      setDisplay(0);
      return;
    }

    if (
      typeof window !== 'undefined' &&
      window.matchMedia('(prefers-reduced-motion: reduce)').matches
    ) {
      setDisplay(target);
      return;
    }

    if (target === 0) {
      setDisplay(0);
      return;
    }

    let startTime = null;
    let rafId = 0;

    const tick = (timestamp) => {
      if (startTime === null) startTime = timestamp;
      const elapsed = timestamp - startTime;
      const progress = Math.min(elapsed / duration, 1);
      setDisplay(Math.round(target * easeOutCubic(progress)));

      if (progress < 1) {
        rafId = requestAnimationFrame(tick);
      } else {
        setDisplay(target);
      }
    };

    setDisplay(0);
    rafId = requestAnimationFrame(tick);

    return () => cancelAnimationFrame(rafId);
  }, [value, duration, active]);

  return <span className={className}>{format(display)}</span>;
}
