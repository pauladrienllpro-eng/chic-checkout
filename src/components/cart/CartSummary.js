import { jsxs, jsx } from "react/jsx-runtime";
import { useState } from "react";
import { formatMoney } from "../../lib/money.js";
import { PromoCode } from "./PromoCode.js";
import { ButtonSpinner } from "../ButtonSpinner.js";
import { PointsBalanceCard } from "./PointsBalanceCard.js";
import { __ } from "../../lib/i18n.js";
import "../../lib/config.js";
import "../../lib/optimistic-cart.js";
import "@stripe/stripe-js";
import "react-dom";
function CartSummary({
  cart,
  packagingItem,
  promoCodeEnabled,
  checkoutUrl,
  paymentIcons,
  loyaltyEnabled,
  loyaltyText,
  express,
  onExpressLoginRequired,
  onChange
}) {
  const [isNavigating, setIsNavigating] = useState(false);
  const packagingMinor = packagingItem ? Number(packagingItem.totals.line_subtotal ?? packagingItem.totals.line_total) : 0;
  const subtotalMinor = Number(cart.totals.total_items) - packagingMinor;
  const totalExcludingShippingMinor = Number(cart.totals.total_price) - Number(cart.totals.total_shipping || 0) - Number(cart.totals.total_shipping_tax || 0);
  return /* @__PURE__ */ jsxs("div", { className: "chic-cart-card", children: [
    /* @__PURE__ */ jsx("div", { className: "chic-cart-card__head", children: /* @__PURE__ */ jsx("h2", { children: __("Résumé") }) }),
    /* @__PURE__ */ jsxs("div", { className: "chic-summary-row", children: [
      /* @__PURE__ */ jsx("span", { children: __("Sous-total") }),
      /* @__PURE__ */ jsx("span", { children: formatMoney(String(subtotalMinor), cart.totals) })
    ] }),
    false,
    promoCodeEnabled && /* @__PURE__ */ jsx(PromoCode, { cart, onChange }),
    /* @__PURE__ */ jsxs("div", { className: "chic-summary-row is-total", children: [
      /* @__PURE__ */ jsx("span", { children: __("Total") }),
      /* @__PURE__ */ jsx("span", { children: formatMoney(String(totalExcludingShippingMinor), cart.totals) })
    ] }),
    cart.needs_shipping && /* @__PURE__ */ jsx("p", { className: "chic-summary-note", children: __("Frais de livraison calculés à l'étape suivante, selon votre adresse.") }),
    /* @__PURE__ */ jsx(
      "a",
      {
        className: "chic-cta-btn",
        href: checkoutUrl,
        "aria-disabled": isNavigating,
        onClick: (event) => {
          if (isNavigating) {
            event.preventDefault();
            return;
          }
          setIsNavigating(true);
        },
        children: isNavigating ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Aller au paiement") }) : __("Aller au paiement")
      }
    ),
    paymentIcons.enabled && paymentIcons.gateways.length > 0 && /* @__PURE__ */ jsxs("div", { className: "chic-payment-icons", children: [
      paymentIcons.label && /* @__PURE__ */ jsx("span", { className: "chic-payment-icons__label", children: paymentIcons.label }),
      /* @__PURE__ */ jsx("div", { className: "chic-payment-icons__row", children: paymentIcons.gateways.map((gateway) => (
        // HTML fourni par la passerelle de paiement elle-même (voir
        // Cart_Assets::resolve_payment_icons()), même patron que pour le point
        // relais Boxtal/La Poste dans ShippingStep.tsx.
        /* @__PURE__ */ jsx(
          "span",
          {
            className: "chic-payment-icons__icon",
            "data-gateway": gateway.id,
            title: gateway.title,
            dangerouslySetInnerHTML: { __html: gateway.iconHtml }
          },
          gateway.id
        )
      )) })
    ] }),
    false,
    false,
    cart.extensions?.["chic-mycred-balance"] && /* @__PURE__ */ jsx(PointsBalanceCard, { mycredData: cart.extensions["chic-mycred-balance"] })
  ] });
}
export {
  CartSummary
};
