import { useEffect } from "react";
const FOCUSABLE_TAGS = /* @__PURE__ */ new Set(["INPUT", "SELECT", "TEXTAREA"]);
function useAutoScrollFocusedField() {
  useEffect(() => {
    function handleFocusIn(event) {
      if (window.innerWidth >= 1024) {
        return;
      }
      const target = event.target;
      if (!(target instanceof HTMLElement) || !FOCUSABLE_TAGS.has(target.tagName)) {
        return;
      }
      window.requestAnimationFrame(() => {
        target.scrollIntoView({ behavior: "smooth", block: "center" });
      });
    }
    document.addEventListener("focusin", handleFocusIn);
    return () => document.removeEventListener("focusin", handleFocusIn);
  }, []);
}
export {
  useAutoScrollFocusedField
};
