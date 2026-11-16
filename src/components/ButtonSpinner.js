import { jsxs, Fragment, jsx } from "react/jsx-runtime";
function ButtonSpinner({ label }) {
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx("span", { className: "chic-spinner", "aria-hidden": "true" }),
    /* @__PURE__ */ jsx("span", { className: "chic-sr-only", children: label })
  ] });
}
export {
  ButtonSpinner
};
