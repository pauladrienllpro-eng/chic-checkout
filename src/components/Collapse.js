import { jsx } from "react/jsx-runtime";
import { useState, useEffect } from "react";
function Collapse({ open, children, innerRef }) {
  const [everOpened, setEverOpened] = useState(open);
  const [animatedOpen, setAnimatedOpen] = useState(false);
  useEffect(() => {
    if (open) {
      setEverOpened(true);
    }
  }, [open]);
  useEffect(() => {
    if (!everOpened) {
      return;
    }
    const raf = requestAnimationFrame(() => setAnimatedOpen(open));
    return () => cancelAnimationFrame(raf);
  }, [everOpened, open]);
  if (!everOpened) {
    return null;
  }
  return /* @__PURE__ */ jsx("div", { className: `chic-step-collapse ${animatedOpen ? "is-open" : ""}`, "aria-hidden": !open, children: /* @__PURE__ */ jsx("div", { className: "chic-step-collapse__inner", ref: innerRef, children }) });
}
export {
  Collapse
};
