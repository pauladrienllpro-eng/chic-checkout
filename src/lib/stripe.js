import { loadStripe } from "@stripe/stripe-js";
import { requireConfig, getConfig } from "./config.js";
let stripePromise = null;
function getStripePromise() {
  if (!stripePromise) {
    const stripePublishableKey = getConfig()?.stripePublishableKey;
    if (!stripePublishableKey) {
      stripePromise = Promise.resolve(null);
    } else if (window.Stripe) {
      stripePromise = Promise.resolve(window.Stripe(stripePublishableKey));
    } else {
      stripePromise = loadStripe(stripePublishableKey);
    }
  }
  return stripePromise;
}
async function getStripe() {
  return getStripePromise();
}
function createCardElements(stripe, containers) {
  const rootStyles = getComputedStyle(document.documentElement);
  const textColor = rootStyles.getPropertyValue("--chic-text").trim() || "#0c0a09";
  const mutedColor = rootStyles.getPropertyValue("--chic-text-muted").trim() || "#57534e";
  const style = {
    base: {
      fontSize: "16px",
      fontFamily: 'DM Sans, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
      color: textColor,
      "::placeholder": { color: mutedColor }
    },
    invalid: {
      color: "#b91c1c",
      iconColor: "#b91c1c"
    }
  };
  const elements = stripe.elements();
  const cardNumber = elements.create("cardNumber", {
    // Stripe Link (autofill de carte enregistrée) s'affiche par-dessus le champ dès
    // qu'il est activé sur le compte Stripe — observé en direct : iframes Stripe
    // supplémentaires recouvrant les champs. On ne construit pas d'intégration Link
    // dédiée, donc on la désactive explicitement plutôt que de laisser son iframe
    // s'imposer par-dessus la nôtre.
    disableLink: true,
    style
  });
  const cardExpiry = elements.create("cardExpiry", { style });
  const cardCvc = elements.create("cardCvc", { style });
  cardNumber.mount(containers.number);
  cardExpiry.mount(containers.expiry);
  cardCvc.mount(containers.cvc);
  return { elements, cardNumber, cardExpiry, cardCvc };
}
function parseStripeNextAction(redirect) {
  const match = redirect.match(/#response=(.+)/);
  if (!match) {
    return null;
  }
  return JSON.parse(atob(decodeURIComponent(match[1])));
}
function walletsExcept(wallet) {
  return [wallet === "applePay" ? "googlePay" : "applePay", "browserCard", "link"];
}
function createWalletPaymentRequest(stripe, cart, wallet) {
  const { storeCountry, logoText } = requireConfig();
  return stripe.paymentRequest({
    country: storeCountry,
    currency: cart.totals.currency_code.toLowerCase(),
    total: {
      label: logoText || document.title,
      amount: Number(cart.totals.total_price)
    },
    requestPayerName: true,
    requestPayerEmail: true,
    requestShipping: false,
    disableWallets: walletsExcept(wallet)
  });
}
async function walletAvailable(stripe, cart, wallet) {
  try {
    const result = await createWalletPaymentRequest(stripe, cart, wallet).canMakePayment();
    return Boolean(result?.[wallet]);
  } catch {
    return false;
  }
}
function mountWalletButton(stripe, paymentRequest, container, height = "48px") {
  const elements = stripe.elements();
  const button = elements.create("paymentRequestButton", {
    paymentRequest,
    style: { paymentRequestButton: { type: "buy", theme: "dark", height } }
  });
  button.mount(container);
  return button;
}
function createLocalPaymentElements(stripe, cart, paymentMethodType, container) {
  const elements = stripe.elements({
    mode: "payment",
    amount: Number(cart.totals.total_price),
    currency: cart.totals.currency_code.toLowerCase(),
    paymentMethodTypes: [paymentMethodType],
    // Exigé par Stripe pour appeler createPaymentMethod sur un Payment Element
    // (createLocalPaymentMethod ci-dessous) — sinon erreur « your elements instance
    // must be created with paymentMethodCreation: 'manual' », constatée sur Klarna.
    paymentMethodCreation: "manual",
    // L'Element affiche sa méthode unique dans un bloc encadré, en plus de l'encart
    // du moyen de paiement qui le contient déjà : on retire ce cadre (et son ombre)
    // pour n'en garder qu'un seul.
    appearance: {
      rules: {
        ".AccordionItem": { border: "none", boxShadow: "none", paddingLeft: "0", paddingRight: "0" },
        ".Block": { border: "none", boxShadow: "none" },
        ".Tab": { border: "none", boxShadow: "none" }
      }
    }
  });
  const paymentElement = elements.create("payment", {
    fields: { billingDetails: "never" }
  });
  paymentElement.mount(container);
  return { elements, paymentElement };
}
async function createLocalPaymentMethod(stripe, elements, billingDetails) {
  const { error: submitError } = await elements.submit();
  if (submitError) {
    return { paymentMethod: void 0, error: submitError };
  }
  return stripe.createPaymentMethod({ elements, params: { billing_details: billingDetails } });
}
export {
  createCardElements,
  createLocalPaymentElements,
  createLocalPaymentMethod,
  createWalletPaymentRequest,
  getStripe,
  mountWalletButton,
  parseStripeNextAction,
  walletAvailable,
  walletsExcept
};
