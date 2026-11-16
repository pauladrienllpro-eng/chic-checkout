import { jsxs, jsx } from "react/jsx-runtime";
import { CartItem } from "./CartItem.js";
import { __ } from "../../lib/i18n.js";
function CartItemsList({ items, cart, imageSize, onChange }) {
  const count = items.reduce((sum, item) => sum + item.quantity, 0);
  return /* @__PURE__ */ jsxs("div", { className: "chic-cart-card", children: [
    /* @__PURE__ */ jsxs("div", { className: "chic-cart-card__head", children: [
      /* @__PURE__ */ jsx("h2", { children: __("Votre panier") }),
      /* @__PURE__ */ jsxs("span", { className: "chic-cart-card__count", children: [
        count,
        " ",
        count > 1 ? __("articles") : __("article")
      ] })
    ] }),
    /* @__PURE__ */ jsx("div", { className: `chic-cart-items chic-cart-items--${imageSize}`, children: items.map((item) => /* @__PURE__ */ jsx(CartItem, { item, cart, onChange }, item.key)) })
  ] });
}
export {
  CartItemsList
};
