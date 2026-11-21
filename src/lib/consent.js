import { useState, useEffect } from "react";
import { getConfig } from "./config.js";
function hasConsent(category) {
  const config = getConfig();
  if (!config) {
    return false;
  }
  if (!config.hasConsentApi) {
    return category !== "statistics";
  }
  return typeof window.wp_has_consent === "function" && window.wp_has_consent(category) === true;
}
function useConsent(category) {
  const [consent, setConsent] = useState(() => hasConsent(category));
  useEffect(() => {
    setConsent(hasConsent(category));
    return onConsentChange(() => setConsent(hasConsent(category)));
  }, [category]);
  return consent;
}
function onConsentChange(callback) {
  document.addEventListener("wp_listen_for_consent_change", callback);
  if (typeof window.wp_listen_for_consent_change === "function") {
    window.wp_listen_for_consent_change(callback);
  }
  return () => document.removeEventListener("wp_listen_for_consent_change", callback);
}
export {
  hasConsent,
  onConsentChange,
  useConsent
};
