import { jsxs, jsx } from "react/jsx-runtime";
function ContrastIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "15", height: "15", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("circle", { cx: "12", cy: "12", r: "9", fill: "none", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("path", { d: "M12 3a9 9 0 0 1 0 18z", fill: "currentColor" })
  ] });
}
function Footer({ links, highContrast, onToggleHighContrast, contrastToggleEnabled, contrastToggleLabel }) {
  if (links.length === 0 && !contrastToggleEnabled) {
    return null;
  }
  return /* @__PURE__ */ jsxs("footer", { className: "chic-footer", children: [
    links.length > 0 && /* @__PURE__ */ jsx("div", { className: "chic-footer__links", children: links.map((link) => /* @__PURE__ */ jsx("a", { href: link.href, target: "_blank", rel: "noopener noreferrer", children: link.label }, link.href)) }),
    contrastToggleEnabled && /* @__PURE__ */ jsxs(
      "button",
      {
        type: "button",
        className: "chic-footer__contrast-toggle",
        onClick: onToggleHighContrast,
        "aria-pressed": highContrast,
        children: [
          /* @__PURE__ */ jsx(ContrastIcon, {}),
          contrastToggleLabel
        ]
      }
    )
  ] });
}
export {
  Footer
};
