import { jsxs, jsx } from "react/jsx-runtime";
import { useEffect } from "react";
import { useConfig } from "../lib/config.js";
import { __ } from "../lib/i18n.js";
import { countBrowserOnce, countBrowser } from "../lib/stats.js";
function BrandingNote({ place }) {
  const branding = useConfig().branding;
  const visible = branding?.visible === true && typeof branding.url === "string" && branding.url !== "";
  useEffect(() => {
    if (visible) {
      countBrowserOnce("badge_shown", ["place", place]);
    }
  }, [visible, place]);
  if (!visible) {
    return null;
  }
  return /* @__PURE__ */ jsxs("p", { className: "chic-branding", children: [
    /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "12", height: "12", fill: "none", "aria-hidden": "true", children: [
      /* @__PURE__ */ jsx("rect", { x: "5", y: "11", width: "14", height: "10", rx: "2", stroke: "currentColor", strokeWidth: "1.8" }),
      /* @__PURE__ */ jsx("path", { d: "M8 11V7a4 4 0 0 1 8 0v4", stroke: "currentColor", strokeWidth: "1.8" })
    ] }),
    /* @__PURE__ */ jsx("span", { children: __("Paiement sécurisé") }),
    /* @__PURE__ */ jsx("span", { "aria-hidden": "true", children: "·" }),
    /* @__PURE__ */ jsx(
      "a",
      {
        href: branding.url,
        target: "_blank",
        rel: "nofollow noopener",
        "aria-label": __("Checkout par Chic-checkout (nouvel onglet)"),
        onClick: () => countBrowser("badge_clicked", ["place", place]),
        children: __("Checkout par Chic-checkout")
      }
    )
  ] });
}
export {
  BrandingNote
};
