import { getConfig } from "./config.js";
const FALLBACK = {
  state: { hidden: true, required: false, label: "", options: null },
  postcode: { hidden: false, required: true, label: "" },
  city: { hidden: false, required: true, label: "" },
  postcodeFirst: false
};
function addressLocale(country) {
  const locales = getConfig()?.addressLocales;
  return locales?.[country] ?? FALLBACK;
}
function matchState(country, shortName, longName = "") {
  const { state } = addressLocale(country);
  if (state.hidden) {
    return "";
  }
  if (!state.options) {
    return longName || shortName;
  }
  const codes = Object.keys(state.options);
  const upper = shortName.toUpperCase();
  if (codes.includes(upper)) {
    return upper;
  }
  const normalize = (text) => text.normalize("NFD").replace(/[̀-ͯ]/g, "").toLowerCase().trim();
  const wanted = [normalize(longName), normalize(shortName)].filter(Boolean);
  return codes.find((code) => wanted.includes(normalize(state.options[code]))) ?? "";
}
function stateLabel(country, state) {
  if (!state) {
    return "";
  }
  return addressLocale(country).state.options?.[state] ?? state;
}
export {
  addressLocale,
  matchState,
  stateLabel
};
