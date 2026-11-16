import { useRef, useEffect } from "react";
const STABLE_FRAMES = 5;
const MAX_FRAMES = 90;
function useScrollIntoViewOnChange(ref, value) {
  const isFirstRender = useRef(true);
  useEffect(() => {
    if (isFirstRender.current) {
      isFirstRender.current = false;
      return;
    }
    let frame = 0;
    let stableFrames = 0;
    let lastTop = Number.NaN;
    let raf = 0;
    const scroll = (element) => {
      const reduceMotion = window.matchMedia("(prefers-reduced-motion: reduce)").matches;
      element.scrollIntoView({ behavior: reduceMotion ? "auto" : "smooth", block: "start" });
    };
    const tick = () => {
      frame += 1;
      const element = ref.current;
      if (element) {
        const top = element.getBoundingClientRect().top + window.scrollY;
        stableFrames = Math.abs(top - lastTop) < 1 ? stableFrames + 1 : 0;
        lastTop = top;
        if (stableFrames >= STABLE_FRAMES || frame >= MAX_FRAMES) {
          scroll(element);
          return;
        }
      } else if (frame >= MAX_FRAMES) {
        return;
      }
      raf = requestAnimationFrame(tick);
    };
    raf = requestAnimationFrame(tick);
    return () => cancelAnimationFrame(raf);
  }, [value]);
}
export {
  useScrollIntoViewOnChange
};
