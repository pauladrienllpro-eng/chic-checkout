import { jsxs, jsx } from "react/jsx-runtime";
import { requireConfig } from "../../lib/config.js";
import { __ } from "../../lib/i18n.js";
function UserIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "20", height: "20", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("circle", { cx: "12", cy: "8.5", r: "3.75", stroke: "currentColor", strokeWidth: "2" }),
    /* @__PURE__ */ jsx("path", { d: "M4.75 19.5c1.2-3.3 4-5 7.25-5s6.05 1.7 7.25 5", stroke: "currentColor", strokeWidth: "2", strokeLinecap: "round" })
  ] });
}
function AccountChip({ onLogin }) {
  const account = requireConfig().account;
  if (!account) {
    return null;
  }
  if (!account.loggedIn) {
    return /* @__PURE__ */ jsxs("button", { type: "button", className: "chic-account", onClick: onLogin, "aria-label": __("Se connecter"), children: [
      /* @__PURE__ */ jsx("span", { className: "chic-account__icon", children: /* @__PURE__ */ jsx(UserIcon, {}) }),
      /* @__PURE__ */ jsx("span", { className: "chic-account__label", children: __("Se connecter") })
    ] });
  }
  const name = account.firstName.trim();
  const greeting = name ? `${__("Bonjour")} ${name}` : __("Mon compte");
  return /* @__PURE__ */ jsxs("a", { className: "chic-account is-logged-in", href: account.accountUrl, "aria-label": `${__("Connecté")} · ${greeting}`, children: [
    /* @__PURE__ */ jsxs("span", { className: "chic-account__icon", children: [
      /* @__PURE__ */ jsx(UserIcon, {}),
      /* @__PURE__ */ jsx("span", { className: "chic-account__dot", "aria-hidden": "true" })
    ] }),
    /* @__PURE__ */ jsx("span", { className: "chic-account__label", children: greeting })
  ] });
}
export {
  AccountChip
};
