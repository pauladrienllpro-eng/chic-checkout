import { jsxs, jsx } from "react/jsx-runtime";
import { __ } from "../lib/i18n.js";
function StepSummary({ title, summary, editLabel = __("Modifier"), onEdit }) {
  return /* @__PURE__ */ jsxs("div", { className: "chic-step-summary", children: [
    /* @__PURE__ */ jsxs("div", { children: [
      /* @__PURE__ */ jsx("p", { className: "chic-step-summary__title", children: title }),
      /* @__PURE__ */ jsx("div", { className: "chic-step-summary__value", children: summary })
    ] }),
    /* @__PURE__ */ jsx("button", { type: "button", className: "chic-step-summary__edit", onClick: onEdit, children: editLabel })
  ] });
}
export {
  StepSummary
};
