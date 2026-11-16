import { jsx, jsxs } from "react/jsx-runtime";
import { __ } from "../../lib/i18n.js";
function EmptyCart({ shopUrl, text }) {
  return /* @__PURE__ */ jsx("div", { className: "chic-cart-shell", children: /* @__PURE__ */ jsxs("div", { className: "chic-cart-empty", children: [
    /* @__PURE__ */ jsx("span", { className: "chic-cart-empty__icon", children: /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "1.6", "aria-hidden": "true", children: [
      /* @__PURE__ */ jsx("circle", { cx: "9", cy: "21", r: "1" }),
      /* @__PURE__ */ jsx("circle", { cx: "20", cy: "21", r: "1" }),
      /* @__PURE__ */ jsx("path", { d: "M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6" })
    ] }) }),
    /* @__PURE__ */ jsx("h1", { children: __("Votre panier est vide") }),
    /* @__PURE__ */ jsx("p", { children: text || __("Votre panier est vide pour le moment.") }),
    /* @__PURE__ */ jsx("a", { className: "chic-cta-btn chic-cart-empty__cta", href: shopUrl, children: __("Continuer mes achats") })
  ] }) });
}
export {
  EmptyCart
};
