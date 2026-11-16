import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { useState, useRef, useMemo, useEffect } from "react";
import { storeApi } from "../../lib/store-api-client.js";
import { trackAddToCartFromUpsell } from "../../lib/ga4.js";
import { __ } from "../../lib/i18n.js";
import { isLatestMutation, startMutation } from "../../lib/optimistic-cart.js";
import { countBrowserOnce, countBrowser } from "../../lib/stats.js";
function UpsellCarousel({ title, products, cart, maxCount, showToast, onChange }) {
  const [leavingIds, setLeavingIds] = useState(/* @__PURE__ */ new Set());
  const [error, setError] = useState("");
  const [toastProduct, setToastProduct] = useState(null);
  const toastTimerRef = useRef(null);
  const visible = useMemo(() => {
    const inCart = new Set(cart.items.map((item) => item.id));
    return products.filter((product) => !inCart.has(product.id)).slice(0, maxCount);
  }, [products, cart.items, maxCount]);
  useEffect(() => {
    if (visible.length > 0) {
      countBrowserOnce("upsell_shown");
    }
  }, [visible.length]);
  if (visible.length === 0) {
    return null;
  }
  function add(product) {
    if (leavingIds.has(product.id)) {
      return;
    }
    setError("");
    setLeavingIds((current) => new Set(current).add(product.id));
    const mutationId = startMutation();
    const request = storeApi.addItem(product.id, 1, true);
    void (async () => {
      try {
        const [updated] = await Promise.all([request, new Promise((resolve) => window.setTimeout(resolve, 300))]);
        if (isLatestMutation(mutationId)) {
          onChange(updated);
        }
        const addedItem = updated.items.find((item) => item.id === product.id);
        countBrowser("upsell_added");
        if (addedItem) {
          trackAddToCartFromUpsell(addedItem, updated, "cart_upsell", title);
        }
        if (showToast) {
          setToastProduct(product);
          if (toastTimerRef.current) {
            clearTimeout(toastTimerRef.current);
          }
          toastTimerRef.current = setTimeout(() => setToastProduct(null), 2400);
        }
      } catch (err) {
        setError(err instanceof Error ? err.message : __("Impossible d'ajouter ce produit."));
        setLeavingIds((current) => {
          const next = new Set(current);
          next.delete(product.id);
          return next;
        });
      }
    })();
  }
  return /* @__PURE__ */ jsxs("div", { className: "chic-cart-card", children: [
    /* @__PURE__ */ jsx("div", { className: "chic-cart-card__head", children: /* @__PURE__ */ jsx("h2", { children: title }) }),
    error && /* @__PURE__ */ jsx("p", { className: "chic-cart-card__error", children: error }),
    /* @__PURE__ */ jsx("div", { className: "chic-upsell-track", children: visible.map((product) => /* @__PURE__ */ jsxs("div", { className: `chic-upsell-card ${leavingIds.has(product.id) ? "is-leaving" : ""}`, children: [
      /* @__PURE__ */ jsxs("div", { className: "chic-upsell-card__thumb-wrap", children: [
        /* @__PURE__ */ jsx("a", { className: "chic-upsell-card__thumb-link", href: product.url, "aria-label": product.name, children: /* @__PURE__ */ jsx("img", { className: "chic-upsell-card__thumb", src: product.image, alt: "" }) }),
        /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            className: "chic-upsell-card__add",
            disabled: leavingIds.has(product.id),
            onClick: () => add(product),
            "aria-label": __("Ajouter au panier"),
            children: /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "2.5", strokeLinecap: "round", children: /* @__PURE__ */ jsx("path", { d: "M12 5v14M5 12h14" }) })
          }
        )
      ] }),
      /* @__PURE__ */ jsx("p", { className: "chic-upsell-card__title", children: product.name }),
      /* @__PURE__ */ jsx("span", { className: "chic-upsell-card__price", children: product.price })
    ] }, product.id)) }),
    showToast && /* @__PURE__ */ jsx("div", { className: `chic-upsell-toast ${toastProduct ? "is-visible" : ""}`, role: "status", "aria-live": "polite", children: toastProduct && /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx("span", { className: "chic-upsell-toast__icon", "aria-hidden": "true", children: /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", fill: "none", stroke: "currentColor", strokeWidth: "3", strokeLinecap: "round", strokeLinejoin: "round", children: /* @__PURE__ */ jsx("path", { d: "M20 6 9 17l-5-5" }) }) }),
      /* @__PURE__ */ jsx("img", { className: "chic-upsell-toast__thumb", src: toastProduct.image, alt: "" }),
      /* @__PURE__ */ jsxs("div", { className: "chic-upsell-toast__body", children: [
        /* @__PURE__ */ jsx("p", { className: "chic-upsell-toast__title", children: __("Ajouté au panier") }),
        /* @__PURE__ */ jsx("p", { className: "chic-upsell-toast__name", children: toastProduct.name })
      ] })
    ] }) })
  ] });
}
export {
  UpsellCarousel
};
