import { requireConfig } from "./config.js";
import { __ } from "./i18n.js";
let scriptPromise = null;
function loadPaypalScript(clientId, currency) {
  if (window.paypal) {
    return Promise.resolve();
  }
  if (!scriptPromise) {
    scriptPromise = new Promise((resolve, reject) => {
      const script = document.createElement("script");
      script.src = `https://www.paypal.com/sdk/js?client-id=${encodeURIComponent(clientId)}&currency=${encodeURIComponent(currency)}&components=buttons`;
      script.onload = () => resolve();
      script.onerror = () => {
        scriptPromise = null;
        reject(new Error("Impossible de charger le SDK PayPal"));
      };
      document.head.appendChild(script);
    });
  }
  return scriptPromise;
}
async function loadPaypalSdk(clientId, currency) {
  await loadPaypalScript(clientId, currency);
  if (!window.paypal) {
    throw new Error(__("PayPal est indisponible pour le moment, merci de réessayer."));
  }
  return window.paypal;
}
function mountPaypalButtons(paypal, container, handlers) {
  const buttons = paypal.Buttons({
    createOrder: handlers.createOrder,
    onApprove: (data) => handlers.onApprove(data.orderID),
    onError: handlers.onError,
    // `layout: 'vertical'` empile un bouton par ligne (PayPal, "Payer en plusieurs
    // fois"...) au lieu de les juxtaposer côte à côte (comportement par défaut du SDK
    // en `horizontal`) — demandé explicitement. `color: 'blue'` plutôt que le doré par
    // défaut, également demandé.
    style: { layout: "vertical", color: "blue", height: 48 }
  });
  buttons.render(container);
  return buttons;
}
function classicAddressFields(prefix, address) {
  return {
    [`${prefix}_first_name`]: address.first_name,
    [`${prefix}_last_name`]: address.last_name,
    [`${prefix}_address_1`]: address.address_1,
    [`${prefix}_address_2`]: address.address_2,
    [`${prefix}_city`]: address.city,
    [`${prefix}_postcode`]: address.postcode,
    [`${prefix}_country`]: address.country,
    [`${prefix}_phone`]: address.phone,
    ...address.state ? { [`${prefix}_state`]: address.state } : {}
  };
}
async function ppcpRequest(path, body) {
  const { homeUrl } = requireConfig();
  const url = `${homeUrl}?wc-ajax=wc_ppcp_frontend_request&path=${encodeURIComponent(`/wc-ppcp/v1/${path}`)}`;
  const response = await fetch(url, {
    method: "POST",
    credentials: "same-origin",
    headers: { "Content-Type": "application/json" },
    body: JSON.stringify(body)
  });
  const data = await response.json().catch(() => null);
  if (!response.ok) {
    const message = data && typeof data === "object" && "message" in data ? String(data.message) : "";
    throw new Error(message || "Erreur PayPal");
  }
  return data;
}
function createPaypalOrder(billing, shipping) {
  return ppcpRequest("cart/order", {
    payment_method: "ppcp",
    context: "checkout",
    ship_to_different_address: true,
    ...classicAddressFields("billing", billing),
    billing_email: billing.email ?? "",
    ...classicAddressFields("shipping", shipping)
  });
}
function submitPaypalCheckout(orderId, billing, shipping) {
  return ppcpRequest("cart/checkout", {
    payment_method: "ppcp",
    context: "checkout",
    ship_to_different_address: true,
    ppcp_paypal_order_id: orderId,
    ...classicAddressFields("billing", billing),
    billing_email: billing.email ?? "",
    ...classicAddressFields("shipping", shipping)
  });
}
export {
  createPaypalOrder,
  loadPaypalSdk,
  mountPaypalButtons,
  submitPaypalCheckout
};
