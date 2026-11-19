import { createContext, createElement, useContext } from "react";
function getConfig() {
  const config = window.chicCheckout;
  if (!config || typeof config !== "object") {
    return null;
  }
  if (typeof config.storeApiUrl !== "string" || typeof config.homeUrl !== "string" || typeof config.restNonce !== "string") {
    return null;
  }
  return config;
}
function requireConfig() {
  const config = getConfig();
  if (!config) {
    throw new Error("[chic-checkout] config introuvable (window.chicCheckout absent ou invalide)");
  }
  return config;
}
function classicUrl() {
  const url = new URL(window.location.href);
  url.searchParams.set("chic-classic", "1");
  url.hash = "";
  return url.toString();
}
const ConfigContext = createContext(null);
function ConfigProvider({ config, children }) {
  return createElement(ConfigContext.Provider, { value: config }, children);
}
function useConfig() {
  const config = useContext(ConfigContext);
  if (!config) {
    throw new Error("[chic-checkout] useConfig() appelé hors de <ConfigProvider>");
  }
  return config;
}
function styleMode(config) {
  return "theme";
}
function computeColors(config) {
  {
    return {
      accent: "var(--wp--preset--color--primary, #1c1917)",
      bg: "var(--wp--preset--color--base, #ffffff)",
      cta: "#44403c"
    };
  }
}
export {
  ConfigProvider,
  classicUrl,
  computeColors,
  getConfig,
  requireConfig,
  styleMode,
  useConfig
};
