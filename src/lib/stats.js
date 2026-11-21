import { getConfig } from "./config.js";
import { onConsentChange } from "./consent.js";
const HISTOGRAM_EDGES = {
  checkout_duration_s: [0, 30, 60, 120, 180, 300, 600, 1200, 1800],
  step_duration_s_identification: [0, 10, 20, 30, 60, 90, 120, 180, 300, 600],
  step_duration_s_shipping: [0, 10, 20, 30, 60, 90, 120, 180, 300, 600],
  step_duration_s_payment: [0, 10, 20, 30, 60, 90, 120, 180, 300, 600],
  app_ready_ms: [0, 250, 500, 750, 1e3, 1500, 2e3, 3e3, 5e3, 8e3]
};
const counters = /* @__PURE__ */ new Map();
const histograms = /* @__PURE__ */ new Map();
const countedOnce = /* @__PURE__ */ new Set();
const stepStartedAt = /* @__PURE__ */ new Map();
let currentApp = null;
function statsConfig() {
  const stats = getConfig()?.stats;
  return stats && stats.enabled && typeof stats.url === "string" ? stats : null;
}
function browserMeasuresAllowed() {
  const stats = statsConfig();
  if (!stats || !stats.browser) {
    return false;
  }
  const hasConsent = typeof window.wp_has_consent === "function" ? window.wp_has_consent : null;
  return !!hasConsent && (hasConsent("statistics-anonymous") === true || hasConsent("statistics") === true);
}
function add(metric, dim, value) {
  const key = [metric, dim?.[0] ?? "", dim?.[1] ?? ""].join("|");
  counters.set(key, (counters.get(key) ?? 0) + value);
}
function countBrowser(metric, dim = null, value = 1) {
  if (browserMeasuresAllowed()) {
    add(metric, dim, value);
  }
}
function countBrowserOnce(metric, dim = null) {
  const key = `${metric}|${dim?.join("=") ?? ""}`;
  if (!countedOnce.has(key) && browserMeasuresAllowed()) {
    countedOnce.add(key);
    add(metric, dim, 1);
  }
}
function countServer(metric, dim) {
  if (statsConfig()) {
    add(metric, dim, 1);
  }
}
function observe(metric, value) {
  if (!browserMeasuresAllowed() || !Number.isFinite(value) || value < 0) {
    return;
  }
  const edges = HISTOGRAM_EDGES[metric];
  let bucket = 0;
  edges.forEach((edge, index) => {
    if (value >= edge) {
      bucket = index;
    }
  });
  const key = `${metric}|${bucket}`;
  histograms.set(key, (histograms.get(key) ?? 0) + 1);
}
function paymentMethodOf(gatewayId) {
  const map = {
    stripe_cc: "card",
    stripe_applepay: "apple_pay",
    stripe_googlepay: "google_pay",
    stripe_klarna: "klarna",
    stripe_wero: "wero",
    stripe_upm: "alma",
    ppcp: "paypal",
    "ppcp-gateway": "paypal",
    mycred: "mycred",
    bacs: "bacs",
    cheque: "cheque",
    cod: "cod"
  };
  return map[gatewayId] ?? "other";
}
function failureCategory(code, message) {
  const text = `${code ?? ""} ${message ?? ""}`.toLowerCase();
  if (/3d|secure|authenticat|authentif|requires_action/.test(text)) {
    return "authentication";
  }
  if (/declin|refus|insufficient|insuffisant|do_not_honor|expired|expir|incorrect|invalid_cvc|lost|stolen|card_/.test(text)) {
    return "declined";
  }
  return "technical";
}
function reportPaymentFailure(gatewayId, code, message) {
  countServer("payment_failed", ["method", paymentMethodOf(gatewayId)]);
  countServer("payment_failed_by", ["failure", failureCategory(code, message)]);
}
function stepEntered(step) {
  if (!stepStartedAt.has(step)) {
    stepStartedAt.set(step, Date.now());
  }
  if (step === "shipping") {
    reportShippingReached();
  }
}
let shippingReported = false;
function reportShippingReached() {
  const config = getConfig();
  if (shippingReported || !statsConfig() || !config) {
    return;
  }
  shippingReported = true;
  fetch(`${config.homeUrl}wp-json/chic-checkout/v1/step-reached`, {
    method: "POST",
    credentials: "same-origin",
    keepalive: true,
    headers: { "Content-Type": "application/json", "X-WP-Nonce": config.restNonce },
    body: JSON.stringify({ step: "shipping" })
  }).catch(() => {
  });
}
function stepCompleted(step) {
  const startedAt = stepStartedAt.get(step);
  if (startedAt !== void 0) {
    observe(`step_duration_s_${step}`, (Date.now() - startedAt) / 1e3);
    stepStartedAt.delete(step);
  }
}
function checkoutCompleted(checkoutStartedAt) {
  stepCompleted("payment");
  observe("checkout_duration_s", (Date.now() - checkoutStartedAt) / 1e3);
  flush();
}
function flush() {
  const stats = statsConfig();
  const config = getConfig();
  if (!stats || !config || counters.size === 0 && histograms.size === 0) {
    return;
  }
  const body = JSON.stringify({
    app: currentApp,
    browser: browserMeasuresAllowed(),
    counters: [...counters].map(([key, value]) => {
      const [metric, dimKey, dimValue] = key.split("|");
      return [metric, dimKey, dimValue, value];
    }),
    histograms: [...histograms].map(([key, count]) => {
      const [metric, bucket] = key.split("|");
      return [metric, Number(bucket), count];
    })
  });
  counters.clear();
  histograms.clear();
  const url = `${stats.url}${stats.url.includes("?") ? "&" : "?"}_wpnonce=${encodeURIComponent(config.restNonce)}`;
  const blob = new Blob([body], { type: "application/json" });
  if (typeof navigator.sendBeacon !== "function" || !navigator.sendBeacon(url, blob)) {
    void fetch(url, { method: "POST", body, keepalive: true, credentials: "same-origin", headers: { "Content-Type": "application/json" } }).catch(() => void 0);
  }
}
function initStats(app) {
  if (currentApp !== null || !statsConfig()) {
    return;
  }
  currentApp = app;
  if (app !== "thankyou") {
    observe("app_ready_ms", performance.now());
  }
  const countCheckoutView = () => {
    if (app === "checkout") {
      countBrowserOnce("browser_checkout_views");
    }
  };
  countCheckoutView();
  onConsentChange(countCheckoutView);
  document.addEventListener("visibilitychange", () => {
    if (document.visibilityState === "hidden") {
      flush();
    }
  });
  window.addEventListener("pagehide", flush);
}
export {
  browserMeasuresAllowed,
  checkoutCompleted,
  countBrowser,
  countBrowserOnce,
  countServer,
  failureCategory,
  flush,
  initStats,
  observe,
  paymentMethodOf,
  reportPaymentFailure,
  stepCompleted,
  stepEntered
};
