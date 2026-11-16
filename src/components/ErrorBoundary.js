var __defProp = Object.defineProperty;
var __defNormalProp = (obj, key, value) => key in obj ? __defProp(obj, key, { enumerable: true, configurable: true, writable: true, value }) : obj[key] = value;
var __publicField = (obj, key, value) => __defNormalProp(obj, typeof key !== "symbol" ? key + "" : key, value);
import { jsx, jsxs } from "react/jsx-runtime";
import { Component } from "react";
import { __ } from "../lib/i18n.js";
import { classicUrl, getConfig } from "../lib/config.js";
import { countServer, flush } from "../lib/stats.js";
class ErrorBoundary extends Component {
  constructor() {
    super(...arguments);
    __publicField(this, "state", { hasError: false });
  }
  static getDerivedStateFromError() {
    return { hasError: true };
  }
  componentDidCatch(error, info) {
    console.error("[chic-checkout] Erreur de rendu :", error, info.componentStack);
    countServer("error_boundary", ["app", this.props.app ?? "checkout"]);
    flush();
  }
  render() {
    if (!this.state.hasError) {
      return this.props.children;
    }
    return /* @__PURE__ */ jsx("div", { className: "chic-checkout chic-error-boundary", children: /* @__PURE__ */ jsxs("div", { className: "chic-error-boundary__panel", children: [
      /* @__PURE__ */ jsx("p", { className: "chic-error-boundary__title", children: __("Une erreur est survenue") }),
      /* @__PURE__ */ jsx("p", { className: "chic-error-boundary__text", children: __(
        "Le paiement n'a pas pu s'afficher correctement. Rechargez la page pour réessayer : votre panier est conservé."
      ) }),
      /* @__PURE__ */ jsxs("div", { className: "chic-error-boundary__actions", children: [
        /* @__PURE__ */ jsx("button", { type: "button", onClick: () => window.location.reload(), children: __("Recharger la page") }),
        /* @__PURE__ */ jsx("a", { href: classicUrl(), children: __("Continuer avec la version standard") }),
        /* @__PURE__ */ jsx("a", { href: getConfig()?.backUrl || classicUrl(), children: __("Retour au panier") })
      ] })
    ] }) });
  }
}
export {
  ErrorBoundary
};
