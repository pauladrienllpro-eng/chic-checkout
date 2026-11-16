import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { useState } from "react";
import { createPortal } from "react-dom";
import { formatMoney } from "../lib/money.js";
import { __ } from "../lib/i18n.js";
import { decodeHtmlEntities } from "../lib/text.js";
function OrderSummary({
  cart,
  label,
  freeShippingLabel,
  pricesIncludeTax,
  variant = "collapsible",
  packagingProductIds = [],
  giftProductIds = [],
  loyaltyEnabled = false,
  loyaltyText = "",
  shippingRateChosen = true
}) {
  const [open, setOpen] = useState(false);
  if (!cart) {
    return null;
  }
  const packagingItem = null;
  const giftItem = null;
  const regularItems = cart.items.filter(
    (item) => item.key !== packagingItem?.key && item.key !== giftItem?.key
  );
  const itemsList = /* @__PURE__ */ jsx("ul", { className: "chic-order-summary__items", children: regularItems.map((item) => /* @__PURE__ */ jsxs("li", { className: "chic-order-summary__item", children: [
    /* @__PURE__ */ jsx("img", { src: item.images[0]?.thumbnail, alt: item.name }),
    /* @__PURE__ */ jsxs("span", { className: "chic-order-summary__item-name", children: [
      decodeHtmlEntities(item.name),
      item.variation.length > 0 && /* @__PURE__ */ jsx("span", { className: "chic-order-summary__item-variation", children: item.variation.map(
        (attribute) => `${decodeHtmlEntities(attribute.attribute)}: ${decodeHtmlEntities(attribute.value)}`
      ).join(", ") })
    ] }),
    /* @__PURE__ */ jsxs("span", { className: "chic-order-summary__item-qty", children: [
      __("Qté :"),
      " ",
      item.quantity
    ] }),
    /* @__PURE__ */ jsx("span", { className: "chic-order-summary__item-price", children: formatMoney(item.totals.line_total, cart.totals) })
  ] }, item.key)) });
  const showShipping = cart.needs_shipping && shippingRateChosen;
  const hiddenShipping = showShipping ? 0 : Number(cart.totals.total_shipping || 0) + Number(cart.totals.total_shipping_tax || 0);
  const displayedTotal = String(Number(cart.totals.total_price) - hiddenShipping);
  const displayedTax = String(
    Number(cart.totals.total_tax) - (showShipping ? 0 : Number(cart.totals.total_shipping_tax || 0))
  );
  const showTaxBreakdown = !pricesIncludeTax && Number(displayedTax) > 0;
  const shippingIsFree = Number(cart.totals.total_shipping) === 0;
  const giftDetails = false;
  const hasTotalsRows = showTaxBreakdown || cart.coupons.length > 0 || showShipping;
  const totalRow = /* @__PURE__ */ jsxs(Fragment, { children: [
    hasTotalsRows && /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__totals", children: [
      showTaxBreakdown && /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__totals-row", children: [
        /* @__PURE__ */ jsx("span", { children: __("Sous-total") }),
        /* @__PURE__ */ jsx("span", { children: formatMoney(cart.totals.total_items, cart.totals) })
      ] }),
      cart.coupons.map((coupon) => /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__totals-row chic-order-summary__totals-row--coupon", children: [
        /* @__PURE__ */ jsx("span", { children: coupon.code }),
        /* @__PURE__ */ jsxs("span", { children: [
          "-",
          formatMoney(coupon.totals.total_discount, coupon.totals)
        ] })
      ] }, coupon.code)),
      showTaxBreakdown && /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__totals-row", children: [
        /* @__PURE__ */ jsx("span", { children: __("Taxes") }),
        /* @__PURE__ */ jsx("span", { children: formatMoney(displayedTax, cart.totals) })
      ] }),
      showShipping && /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__totals-row", children: [
        /* @__PURE__ */ jsx("span", { children: __("Livraison") }),
        /* @__PURE__ */ jsx("span", { children: shippingIsFree ? freeShippingLabel : formatMoney(cart.totals.total_shipping, cart.totals) })
      ] })
    ] }),
    /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__total-row", children: [
      /* @__PURE__ */ jsx("span", { children: __("Total") }),
      /* @__PURE__ */ jsx("span", { children: formatMoney(displayedTotal, cart.totals) })
    ] }),
    false
  ] });
  if (variant === "sidebar") {
    return /* @__PURE__ */ jsxs("div", { className: "chic-order-summary chic-order-summary--sidebar", children: [
      /* @__PURE__ */ jsx("p", { className: "chic-order-summary__title", children: label }),
      itemsList,
      giftDetails,
      totalRow
    ] });
  }
  return /* @__PURE__ */ jsxs("div", { className: "chic-order-summary", children: [
    /* @__PURE__ */ jsxs(
      "button",
      {
        type: "button",
        className: "chic-order-summary__toggle",
        onClick: () => setOpen((value) => !value),
        "aria-expanded": open,
        children: [
          /* @__PURE__ */ jsx("span", { className: "chic-order-summary__title", children: label }),
          /* @__PURE__ */ jsxs("span", { className: "chic-order-summary__toggle-right", children: [
            /* @__PURE__ */ jsx("span", { className: "chic-order-summary__toggle-total", children: formatMoney(displayedTotal, cart.totals) }),
            /* @__PURE__ */ jsx(
              "svg",
              {
                className: `chic-chevron ${open ? "is-open" : ""}`,
                viewBox: "0 0 24 24",
                width: "16",
                height: "16",
                fill: "none",
                "aria-hidden": "true",
                children: /* @__PURE__ */ jsx("path", { d: "m9 6 6 6-6 6", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })
              }
            )
          ] })
        ]
      }
    ),
    /* @__PURE__ */ jsx("div", { className: `chic-order-summary__panel ${open ? "is-open" : ""}`, "aria-hidden": !open, children: /* @__PURE__ */ jsxs("div", { className: "chic-order-summary__content", children: [
      itemsList,
      giftDetails,
      totalRow
    ] }) }),
    open && createPortal(
      /* @__PURE__ */ jsx("div", { className: "chic-order-summary-backdrop", onClick: () => setOpen(false), "aria-hidden": "true" }),
      document.body
    )
  ] });
}
export {
  OrderSummary
};
