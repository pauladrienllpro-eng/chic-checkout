import { createRoot } from "react-dom/client";
import { getConfig, computeColors, classicUrl } from "./config.js";
import { __ } from "./i18n.js";
const RETRY_INTERVAL_MS = 200;
const RETRY_TIMEOUT_MS = 5e3;
function boot(render) {
  const startedAt = Date.now();
  let mounted = false;
  function tryMount() {
    if (mounted) {
      return true;
    }
    const container = document.getElementById("chic-checkout-root");
    const config = getConfig();
    if (!container || !config) {
      return false;
    }
    mounted = true;
    try {
      document.body.style.background = computeColors(config).bg;
      createRoot(container).render(render(config));
    } catch (error) {
      console.error("[chic-checkout] Échec du montage :", error);
      showMountError(container);
    }
    return true;
  }
  if (tryMount()) {
    return;
  }
  function poll() {
    if (tryMount()) {
      return;
    }
    if (Date.now() - startedAt >= RETRY_TIMEOUT_MS) {
      console.error("[chic-checkout] config introuvable : un optimiseur JS a probablement retardé le script inline");
      return;
    }
    window.setTimeout(poll, RETRY_INTERVAL_MS);
  }
  if (document.readyState === "loading") {
    document.addEventListener("DOMContentLoaded", poll, { once: true });
  } else {
    window.setTimeout(poll, RETRY_INTERVAL_MS);
  }
}
function showMountError(container) {
  const wrapper = document.createElement("div");
  wrapper.className = "chic-boot";
  wrapper.setAttribute("role", "alert");
  const text = document.createElement("p");
  text.textContent = __("Le paiement n'a pas pu s'afficher correctement.");
  const link = document.createElement("a");
  link.href = classicUrl();
  link.textContent = __("Continuer avec la version standard");
  text.appendChild(document.createElement("br"));
  text.appendChild(link);
  wrapper.appendChild(text);
  container.replaceChildren(wrapper);
}
export {
  boot
};
