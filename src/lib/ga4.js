import { toNumericAmount } from "./money.js";
import { hasConsent } from "./consent.js";
import { getConfig } from "./config.js";
function isEnabled() {
  return getConfig()?.trackingGa4Enabled === true && hasConsent("statistics");
}
function maybeLog(name, params) {
  if (getConfig()?.trackingDebugMode) {
    console.log("[chic-checkout] GA4", name, params);
  }
}
function push(payload) {
  window.dataLayer = window.dataLayer || [];
  window.dataLayer.push(payload);
}
function trackEcommerce(name, ecommerce) {
  if (!isEnabled()) {
    return;
  }
  push({ ecommerce: null });
  push({ event: name, ecommerce });
  if (typeof window.gtag === "function") {
    window.gtag("event", name, ecommerce);
  }
  maybeLog(name, ecommerce);
}
function trackEvent(name, params = {}) {
  if (!isEnabled()) {
    return;
  }
  push({ event: name, ...params });
  if (typeof window.gtag === "function") {
    window.gtag("event", name, params);
  }
  maybeLog(name, params);
}
function buildItems(cart) {
  return cart.items.map((item) => ({
    item_id: String(item.id),
    item_name: item.name,
    price: toNumericAmount(item.prices.price, cart.totals.currency_minor_unit),
    quantity: item.quantity
  }));
}
function buildEcommerceBase(cart) {
  const base = {
    currency: cart.totals.currency_code,
    value: toNumericAmount(cart.totals.total_price, cart.totals.currency_minor_unit),
    items: buildItems(cart)
  };
  if (cart.coupons.length > 0) {
    base.coupon = cart.coupons.map((coupon) => coupon.code).join(", ");
  }
  return base;
}
function trackBeginCheckout(cart) {
  trackEcommerce("begin_checkout", buildEcommerceBase(cart));
}
function trackAddShippingInfo(cart, shippingTier) {
  trackEcommerce("add_shipping_info", { ...buildEcommerceBase(cart), shipping_tier: shippingTier });
}
function trackAddPaymentInfo(cart, paymentType) {
  trackEcommerce("add_payment_info", { ...buildEcommerceBase(cart), payment_type: paymentType });
}
function trackPurchase(cart, orderId, durationSeconds) {
  trackEcommerce("purchase", {
    ...buildEcommerceBase(cart),
    transaction_id: String(orderId),
    shipping: toNumericAmount(cart.totals.total_shipping, cart.totals.currency_minor_unit),
    tax: toNumericAmount(cart.totals.total_tax, cart.totals.currency_minor_unit),
    checkout_duration_seconds: Math.round(durationSeconds)
  });
}
function trackAddToCartFromUpsell(item, cart, listId, listName) {
  trackEcommerce("add_to_cart", {
    currency: cart.totals.currency_code,
    value: toNumericAmount(item.prices.price, cart.totals.currency_minor_unit),
    items: [
      {
        item_id: String(item.id),
        item_name: item.name,
        price: toNumericAmount(item.prices.price, cart.totals.currency_minor_unit),
        quantity: 1,
        item_list_id: listId,
        item_list_name: listName
      }
    ]
  });
}
function trackLogin(method) {
  trackEvent("login", { method });
}
function trackSignUp(method) {
  trackEvent("sign_up", { method });
}
export {
  trackAddPaymentInfo,
  trackAddShippingInfo,
  trackAddToCartFromUpsell,
  trackBeginCheckout,
  trackLogin,
  trackPurchase,
  trackSignUp
};
