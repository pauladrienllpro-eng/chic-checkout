import { requireConfig } from "./config.js";
async function request(path, body) {
  const { homeUrl, restNonce } = requireConfig();
  const response = await fetch(`${homeUrl}wp-json/chic-checkout/v1/${path}`, {
    method: "POST",
    credentials: "same-origin",
    headers: {
      "Content-Type": "application/json",
      "X-WP-Nonce": restNonce
    },
    body: body ? JSON.stringify(body) : void 0
  });
  return await response.json().catch(() => ({}));
}
const authApi = {
  // `exists` absent : refus du serveur (limite de tentatives atteinte, nonce…) ; `message` l'explique.
  checkEmail: (email) => request("check-email", { email }),
  login: (email, password) => request("login", { email, password }),
  register: (email, password) => request("register", { email, password }),
  logout: () => request("logout"),
  // `credential` est le jeton d'identité (JWT) renvoyé par le SDK JS du fournisseur —
  // vérifié côté serveur (signature, émetteur, audience) avant de faire confiance à
  // l'email qu'il contient. `firstName`/`lastName` ne sont utiles que pour Apple (fournis
  // par leur SDK une seule fois, à la toute première autorisation — jamais présents dans
  // le jeton lui-même, contrairement à Google qui les inclut directement dedans).
  // Connexion Google / Apple : fonction Pro (route absente de la version gratuite).
  socialLogin: (provider, credential, firstName, lastName) => Promise.reject(new Error("unavailable"))
};
export {
  authApi
};
