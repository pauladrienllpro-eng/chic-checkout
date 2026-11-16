import { useState, useRef, useEffect } from "react";
const CLOSE_MS = 380;
function useSheetTransition(open) {
  const [mounted, setMounted] = useState(open);
  const [visible, setVisible] = useState(false);
  const returnFocusRef = useRef(null);
  useEffect(() => {
    if (open) {
      returnFocusRef.current = document.activeElement;
      setMounted(true);
      let second = 0;
      const first = requestAnimationFrame(() => {
        second = requestAnimationFrame(() => setVisible(true));
      });
      return () => {
        cancelAnimationFrame(first);
        cancelAnimationFrame(second);
      };
    }
    setVisible(false);
    const timer = window.setTimeout(() => {
      setMounted(false);
      returnFocusRef.current?.focus({ preventScroll: true });
    }, CLOSE_MS);
    return () => window.clearTimeout(timer);
  }, [open]);
  useEffect(() => {
    if (!mounted) {
      return;
    }
    const previous = document.body.style.overflow;
    document.body.style.overflow = "hidden";
    return () => {
      document.body.style.overflow = previous;
    };
  }, [mounted]);
  return { mounted, visible, returnFocusRef };
}
function trapFocus(event, container) {
  if (event.key !== "Tab" || !container) {
    return;
  }
  const focusable = container.querySelectorAll("a[href], button:not([disabled]), textarea, input, select");
  const first = focusable[0];
  const last = focusable[focusable.length - 1];
  if (event.shiftKey && document.activeElement === first) {
    event.preventDefault();
    last?.focus();
  } else if (!event.shiftKey && document.activeElement === last) {
    event.preventDefault();
    first?.focus();
  }
}
function popinRoot() {
  return document.querySelector("#chic-checkout-root .chic-checkout") ?? document.getElementById("chic-checkout-root") ?? document.body;
}
export {
  popinRoot,
  trapFocus,
  useSheetTransition
};
