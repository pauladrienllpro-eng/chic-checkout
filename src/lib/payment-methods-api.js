import { requireConfig } from "./config.js";
async function fetchPaymentMethods() {
  const { homeUrl, restNonce } = requireConfig();
  const response = await fetch(`${homeUrl}wp-json/chic-checkout/v1/payment-methods`, {
    credentials: "same-origin",
    headers: { "X-WP-Nonce": restNonce }
  });
  if (!response.ok) {
    return [];
  }
  return await response.json().catch(() => []);
}
export {
  fetchPaymentMethods
};
