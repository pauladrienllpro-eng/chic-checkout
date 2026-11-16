import { jsxs, jsx } from "react/jsx-runtime";
import { useState, useRef, useEffect } from "react";
import { formatMoney } from "../../lib/money.js";
import { decodeHtmlEntities } from "../../lib/text.js";
import { storeApi } from "../../lib/store-api-client.js";
import { withQuantity, withoutItem, isLatestMutation, startMutation } from "../../lib/optimistic-cart.js";
import { __ } from "../../lib/i18n.js";
const QUANTITY_DEBOUNCE_MS = 400;
const REMOVE_ANIMATION_MS = 220;
const sleep = (ms) => new Promise((resolve) => window.setTimeout(resolve, ms));
function errorMessage(err, fallback) {
  return err instanceof Error && !(err instanceof TypeError) ? err.message : fallback;
}
function CartItem({ item, cart, onChange }) {
  const [removing, setRemoving] = useState(false);
  const [error, setError] = useState("");
  const cartRef = useRef(cart);
  cartRef.current = cart;
  const targetQuantityRef = useRef(item.quantity);
  const debounceRef = useRef(null);
  const inFlightRef = useRef(0);
  useEffect(() => {
    if (debounceRef.current === null && inFlightRef.current === 0) {
      targetQuantityRef.current = item.quantity;
    }
  }, [item.quantity]);
  useEffect(
    () => () => {
      if (debounceRef.current !== null) {
        window.clearTimeout(debounceRef.current);
      }
    },
    []
  );
  async function resync(mutationId, message) {
    setError(message);
    try {
      const fresh = await storeApi.getCart();
      if (isLatestMutation(mutationId)) {
        targetQuantityRef.current = fresh.items.find((candidate) => candidate.key === item.key)?.quantity ?? 0;
        onChange(fresh);
      }
    } catch {
    }
  }
  async function sendQuantity(quantity) {
    const mutationId = startMutation();
    inFlightRef.current += 1;
    try {
      const updated = await storeApi.updateItem(item.key, quantity);
      if (isLatestMutation(mutationId) && debounceRef.current === null) {
        onChange(updated);
      }
    } catch (err) {
      await resync(mutationId, errorMessage(err, __("Impossible de modifier la quantité. Vérifiez votre connexion.")));
    } finally {
      inFlightRef.current -= 1;
    }
  }
  function changeQuantity(delta) {
    const quantity = targetQuantityRef.current + delta;
    if (quantity < 1 || removing) {
      return;
    }
    setError("");
    targetQuantityRef.current = quantity;
    onChange(withQuantity(cartRef.current, item.key, quantity));
    if (debounceRef.current !== null) {
      window.clearTimeout(debounceRef.current);
    }
    debounceRef.current = window.setTimeout(() => {
      debounceRef.current = null;
      void sendQuantity(quantity);
    }, QUANTITY_DEBOUNCE_MS);
  }
  async function remove() {
    if (removing) {
      return;
    }
    if (debounceRef.current !== null) {
      window.clearTimeout(debounceRef.current);
      debounceRef.current = null;
    }
    setError("");
    setRemoving(true);
    const mutationId = startMutation();
    const request = storeApi.removeItem(item.key);
    await sleep(REMOVE_ANIMATION_MS);
    onChange(withoutItem(cartRef.current, item.key));
    try {
      const updated = await request;
      if (isLatestMutation(mutationId)) {
        onChange(updated);
      }
    } catch (err) {
      setRemoving(false);
      await resync(mutationId, errorMessage(err, __("Impossible de retirer cet article. Vérifiez votre connexion.")));
    }
  }
  return /* @__PURE__ */ jsxs("div", { className: `chic-cart-item ${removing ? "is-removing" : ""}`, children: [
    /* @__PURE__ */ jsx("img", { className: "chic-cart-item__thumb", src: item.images[0]?.thumbnail, alt: "" }),
    /* @__PURE__ */ jsxs("div", { className: "chic-cart-item__body", children: [
      /* @__PURE__ */ jsxs("div", { className: "chic-cart-item__top", children: [
        /* @__PURE__ */ jsxs("div", { children: [
          /* @__PURE__ */ jsx("p", { className: "chic-cart-item__name", children: decodeHtmlEntities(item.name) }),
          item.variation.length > 0 && /* @__PURE__ */ jsx("p", { className: "chic-cart-item__meta", children: item.variation.map(
            (attribute) => `${decodeHtmlEntities(attribute.attribute)}: ${decodeHtmlEntities(attribute.value)}`
          ).join(", ") })
        ] }),
        /* @__PURE__ */ jsx("p", { className: "chic-cart-item__price", children: formatMoney(item.totals.line_total, cart.totals) })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "chic-cart-item__bottom", children: [
        /* @__PURE__ */ jsxs("div", { className: "chic-qty-stepper", children: [
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              disabled: removing || item.quantity <= 1,
              onClick: () => changeQuantity(-1),
              "aria-label": __("Diminuer la quantité"),
              children: "−"
            }
          ),
          /* @__PURE__ */ jsx("span", { className: "chic-qty-stepper__value", children: item.quantity }),
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              disabled: removing,
              onClick: () => changeQuantity(1),
              "aria-label": __("Augmenter la quantité"),
              children: "+"
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("button", { type: "button", className: "chic-cart-remove-btn", disabled: removing, onClick: remove, children: [
          /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "14", height: "14", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx(
            "path",
            {
              d: "M3 6h18M8 6V4a1 1 0 0 1 1-1h6a1 1 0 0 1 1 1v2m3 0-.8 13.6a2 2 0 0 1-2 1.9H6.8a2 2 0 0 1-2-1.9L4 6",
              stroke: "currentColor",
              strokeWidth: "1.6",
              strokeLinecap: "round",
              strokeLinejoin: "round"
            }
          ) }),
          __("Retirer")
        ] })
      ] }),
      error && /* @__PURE__ */ jsx("p", { className: "chic-cart-item__error", role: "alert", children: error })
    ] })
  ] });
}
export {
  CartItem
};
