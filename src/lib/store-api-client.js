import { requireConfig } from "./config.js";
import { runSerial } from "./optimistic-cart.js";
let storeApiNonce = "";
async function primeNonce() {
  const { storeApiUrl } = requireConfig();
  const response = await fetch(`${storeApiUrl}cart`, { credentials: "same-origin" });
  const nonce = response.headers.get("Nonce") ?? response.headers.get("X-WC-Store-API-Nonce");
  if (nonce) {
    storeApiNonce = nonce;
  }
}
function request(path, options = {}) {
  const isMutation = (options.method ?? "GET").toUpperCase() !== "GET";
  return isMutation ? runSerial(() => send(path, options)) : send(path, options);
}
async function send(path, options = {}, isRetry = false) {
  if (!storeApiNonce) {
    storeApiNonce = requireConfig().storeApiNonce ?? "";
  }
  if (!storeApiNonce) {
    await primeNonce();
  }
  const { storeApiUrl } = requireConfig();
  const response = await fetch(`${storeApiUrl}${path}`, {
    ...options,
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      // Le nom du header attendu par le serveur est bien "Nonce" (voir
      // AbstractCartRoute::check_nonce() côté WooCommerce) — "X-WC-Store-API-Nonce"
      // n'est qu'un des deux noms sous lequel la Store API RENVOIE le nonce en réponse,
      // pas celui qu'elle valide en entrée.
      ...storeApiNonce ? { Nonce: storeApiNonce } : {},
      // Requête de l'app Chic-checkout : permet au serveur de compter les étapes et les
      // tentatives de paiement de ce checkout (statistiques, si activées), rien d'autre.
      "X-Chic-Checkout": "1",
      ...options.headers
    }
  });
  const nonce = response.headers.get("Nonce") ?? response.headers.get("X-WC-Store-API-Nonce");
  if (nonce) {
    storeApiNonce = nonce;
  }
  if (!response.ok) {
    const error = await response.json().catch(() => null);
    if (!isRetry && typeof error?.code === "string" && error.code.includes("nonce")) {
      storeApiNonce = "";
      await primeNonce();
      return send(path, options, true);
    }
    throw new Error(error?.message ?? `Store API error: ${response.status}`);
  }
  return response.json();
}
async function sendBatch(requests) {
  if (!storeApiNonce) {
    storeApiNonce = requireConfig().storeApiNonce ?? "";
  }
  if (!storeApiNonce) {
    await primeNonce();
  }
  const result = await send("batch", {
    method: "POST",
    body: JSON.stringify({
      requests: requests.map((entry) => ({
        method: "POST",
        // Route interne de la Store API, indépendante de la structure des permaliens.
        path: `/wc/store/v1/${entry.path}`,
        headers: { Nonce: storeApiNonce, "X-Chic-Checkout": "1" },
        body: entry.body
      }))
    })
  });
  const responses = result.responses ?? [];
  const failed = responses.find((response) => response.status >= 400);
  if (failed || responses.length !== requests.length) {
    throw new Error(failed?.body?.message ?? "Store API batch error");
  }
  return responses[responses.length - 1].body;
}
const storeApi = {
  getCart: () => request("cart"),
  // Remplace un article par un autre (choix d'emballage) en un seul aller-retour ; repli
  // sur deux requêtes successives si la route batch échoue (ancienne version de
  // WooCommerce, nonce expiré entre-temps…).
  replaceItem: (removeKey, addId) => runSerial(
    () => sendBatch([
      { path: "cart/remove-item", body: { key: removeKey } },
      { path: "cart/add-item", body: { id: addId, quantity: 1 } }
    ])
  ).catch(async () => {
    await request("cart/remove-item", { method: "POST", body: JSON.stringify({ key: removeKey }) }).catch(() => null);
    return request("cart/add-item", { method: "POST", body: JSON.stringify({ id: addId, quantity: 1 }) });
  }),
  // `express` (ici et plus bas) : requête d'un paiement express du panier, mesurée à part
  // par Stats_Server (ni étapes du checkout, ni tentatives de paiement du checkout).
  updateCustomer: (payload, express) => request("cart/update-customer", {
    method: "POST",
    body: JSON.stringify(payload),
    ...expressHeader(express)
  }),
  selectShippingRate: (packageId, rateId, express) => request("cart/select-shipping-rate", {
    method: "POST",
    body: JSON.stringify({ package_id: packageId, rate_id: rateId }),
    ...expressHeader(express)
  }),
  // `fromUpsell` : article ajouté depuis le carrousel d'upsell, marqué côté serveur
  // (Stats_Server) pour mesurer le CA généré par l'upsell.
  addItem: (id, quantity = 1, fromUpsell = false) => request("cart/add-item", {
    method: "POST",
    body: JSON.stringify({ id, quantity }),
    ...fromUpsell ? { headers: { "X-Chic-Upsell": "1" } } : {}
  }),
  updateItem: (key, quantity) => request("cart/update-item", {
    method: "POST",
    body: JSON.stringify({ key, quantity })
  }),
  removeItem: (key) => request("cart/remove-item", {
    method: "POST",
    body: JSON.stringify({ key })
  }),
  applyCoupon: (code) => request("cart/apply-coupon", {
    method: "POST",
    body: JSON.stringify({ code })
  }),
  removeCoupon: (code) => request("cart/remove-coupon", {
    method: "POST",
    body: JSON.stringify({ code })
  }),
  // Point d'extension officiel de la Store API (voir Gift_Message_Api côté PHP,
  // enregistré via woocommerce_store_api_register_update_callback) — namespace =
  // identifiant choisi côté PHP, data = payload libre passé tel quel au callback.
  updateExtension: (namespace, data) => request("cart/extensions", {
    method: "POST",
    body: JSON.stringify({ namespace, data })
  }),
  checkout: (payload, express) => request("checkout", {
    method: "POST",
    body: JSON.stringify(payload),
    ...expressHeader(express)
  })
};
function expressHeader(express) {
  return express ? { headers: { "X-Chic-Express": express } } : {};
}
export {
  storeApi
};
