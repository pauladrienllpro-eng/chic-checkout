import "@stripe/stripe-js";
import "./config.js";
function applePayPossible() {
  try {
    return typeof window.ApplePaySession !== "undefined" && window.ApplePaySession.canMakePayments();
  } catch {
    return false;
  }
}
export {
  applePayPossible
};
