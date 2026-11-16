import { jsxs, jsx } from "react/jsx-runtime";
import { createPortal } from "react-dom";
function ProcessingOverlay({ label }) {
  return createPortal(
    /* @__PURE__ */ jsxs("div", { className: "chic-processing-overlay", role: "status", "aria-live": "polite", children: [
      /* @__PURE__ */ jsx("span", { className: "chic-processing-overlay__spinner", "aria-hidden": "true" }),
      /* @__PURE__ */ jsx("p", { className: "chic-processing-overlay__label", children: label })
    ] }),
    document.body
  );
}
export {
  ProcessingOverlay
};
