import { getConfig } from "./config.js";
function resolveDefaultCountry(cartCountry, allowed) {
  const allowedCodes = Object.keys(allowed);
  const isAllowed = (code) => Boolean(code) && (allowedCodes.length === 0 || allowedCodes.includes(code));
  for (const candidate of [cartCountry, getConfig()?.storeCountry]) {
    if (isAllowed(candidate)) {
      return candidate;
    }
  }
  return allowedCodes[0] ?? "";
}
export {
  resolveDefaultCountry
};
