import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { useState, useRef, useEffect } from "react";
import { storeApi } from "../lib/store-api-client.js";
import { getFieldPattern, validateForm } from "../lib/validation.js";
import { toSentenceCase } from "../lib/text.js";
import { fetchPaymentMethods } from "../lib/payment-methods-api.js";
import { MYCRED_GATEWAY_ID } from "../lib/mycred.js";
import { getStripe, walletAvailable, createCardElements, createLocalPaymentElements, createWalletPaymentRequest, mountWalletButton, parseStripeNextAction, createLocalPaymentMethod } from "../lib/stripe.js";
import { loadPaypalSdk, mountPaypalButtons, submitPaypalCheckout, createPaypalOrder } from "../lib/paypal.js";
import { useScrollIntoViewOnChange } from "../hooks/useScrollIntoViewOnChange.js";
import { ButtonSpinner } from "../components/ButtonSpinner.js";
import { BrandingNote } from "../components/BrandingNote.js";
import { ProcessingOverlay } from "../components/ProcessingOverlay.js";
import { __ } from "../lib/i18n.js";
import { trackPurchase, trackAddPaymentInfo } from "../lib/ga4.js";
import { checkoutCompleted, reportPaymentFailure } from "../lib/stats.js";
import { resolveDefaultCountry } from "../lib/country.js";
import { paymentErrorMessage, paypalErrorMessage } from "../lib/payment-errors.js";
import { applePayPossible } from "../lib/apple-pay.js";
import { stateLabel } from "../lib/address-locale.js";
import { AddressLocalityFields } from "../components/AddressLocalityFields.js";
const STRIPE_CARD_GATEWAY_ID = "stripe_cc";
const STRIPE_APPLE_PAY_GATEWAY_ID = "stripe_applepay";
const STRIPE_GOOGLE_PAY_GATEWAY_ID = "stripe_googlepay";
const WALLET_GATEWAYS = {
  [STRIPE_APPLE_PAY_GATEWAY_ID]: "applePay",
  [STRIPE_GOOGLE_PAY_GATEWAY_ID]: "googlePay"
};
const STRIPE_LOCAL_PAYMENT_TYPES = {
  stripe_klarna: "klarna",
  stripe_wero: "wero",
  stripe_upm: "alma"
};
const PAYPAL_GATEWAY_IDS = ["ppcp"];
function isPaypalGatewayId(id) {
  return PAYPAL_GATEWAY_IDS.includes(id);
}
function safely(fn) {
  try {
    fn();
  } catch (error) {
    console.error("[chic-checkout] Suivi après paiement :", error);
  }
}
const STRIPE_UNFINISHED_STATUSES = ["requires_action", "requires_payment_method"];
function isCardGateway(gateway) {
  return gateway.id === STRIPE_CARD_GATEWAY_ID;
}
function isLocalPaymentGateway(gateway) {
  return gateway.id in STRIPE_LOCAL_PAYMENT_TYPES;
}
function PaymentStep({
  stepName,
  shippingAddress,
  email,
  cart,
  isAccount,
  checkoutStartedAt,
  ctaLabels,
  shippingCountries,
  termsCheckbox,
  paymentLogoMode,
  onRefreshCart
}) {
  const [subStep, setSubStep] = useState("billing");
  const stepRootRef = useRef(null);
  const billingFormRef = useRef(null);
  useScrollIntoViewOnChange(stepRootRef, subStep);
  const defaultBillingAddress = !shippingAddress ? null : shippingAddress.pickupPoint ? { ...shippingAddress.customerAddress, email } : {
    first_name: shippingAddress.firstName,
    last_name: shippingAddress.lastName,
    address_1: shippingAddress.address1,
    address_2: shippingAddress.address2,
    city: shippingAddress.city,
    postcode: shippingAddress.postcode,
    country: shippingAddress.country,
    state: shippingAddress.state,
    phone: shippingAddress.phone,
    email
  };
  const [sameAsShipping, setSameAsShipping] = useState(shippingAddress !== null);
  useScrollIntoViewOnChange(billingFormRef, sameAsShipping);
  const savedBillingAddress = isAccount && cart?.billing_address?.address_1 ? cart.billing_address : null;
  const [manualBillingEntry, setManualBillingEntry] = useState(false);
  const useSavedBilling = Boolean(savedBillingAddress) && !manualBillingEntry;
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [address1, setAddress1] = useState("");
  const [address2, setAddress2] = useState("");
  const [city, setCity] = useState("");
  const [postcode, setPostcode] = useState("");
  const [country, setCountry] = useState(() => resolveDefaultCountry(cart?.billing_address?.country, shippingCountries));
  const [state, setState] = useState("");
  const [phone, setPhone] = useState("");
  const [errors, setErrors] = useState({});
  const [serverError, setServerError] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [paymentMethods, setPaymentMethods] = useState([]);
  const [loadingMethods, setLoadingMethods] = useState(true);
  const [method, setMethod] = useState("");
  const [confirmedBillingAddress, setConfirmedBillingAddress] = useState(null);
  const [termsAccepted, setTermsAccepted] = useState(false);
  const [termsExpanded, setTermsExpanded] = useState(false);
  const [cardNumberError, setCardNumberError] = useState("");
  const [cardExpiryError, setCardExpiryError] = useState("");
  const [cardCvcError, setCardCvcError] = useState("");
  const stripeRef = useRef(null);
  const cardNumberRef = useRef(null);
  const cardExpiryRef = useRef(null);
  const cardCvcRef = useRef(null);
  const cardNumberContainerRef = useRef(null);
  const cardExpiryContainerRef = useRef(null);
  const cardCvcContainerRef = useRef(null);
  const walletContainerRef = useRef(null);
  const walletButtonRef = useRef(null);
  const paymentRequestRef = useRef(null);
  const [walletSupported, setWalletSupported] = useState(null);
  const paypalContainerRef = useRef(null);
  const paypalButtonsRef = useRef(null);
  const localPaymentContainerRef = useRef(null);
  const localPaymentElementsRef = useRef(null);
  useEffect(() => {
    let cancelled = false;
    fetchPaymentMethods().then((allMethods) => {
      if (cancelled) {
        return;
      }
      const methods = allMethods.filter((gateway) => gateway.id !== STRIPE_APPLE_PAY_GATEWAY_ID || applePayPossible());
      setPaymentMethods(methods);
      if (methods.some((gateway) => gateway.id === STRIPE_GOOGLE_PAY_GATEWAY_ID)) {
        getStripe().then(async (stripe) => {
          if (!stripe || !cart) {
            return;
          }
          const available = await walletAvailable(stripe, cart, "googlePay");
          if (!cancelled && !available) {
            setPaymentMethods((current) => current.filter((gateway) => gateway.id !== STRIPE_GOOGLE_PAY_GATEWAY_ID));
            setMethod((current) => current === STRIPE_GOOGLE_PAY_GATEWAY_ID ? "" : current);
          }
        });
      }
      setLoadingMethods(false);
    });
    return () => {
      cancelled = true;
    };
  }, []);
  const isCardSelected = method === STRIPE_CARD_GATEWAY_ID;
  const selectedWallet = WALLET_GATEWAYS[method] ?? null;
  const isApplePaySelected = selectedWallet === "applePay";
  const isPaypalSelected = isPaypalGatewayId(method);
  const localPaymentType = STRIPE_LOCAL_PAYMENT_TYPES[method] ?? null;
  const canPay = Boolean(method) && (!termsCheckbox.enabled || termsAccepted);
  const addPaymentInfoTrackedRef = useRef(false);
  function selectMethod(gatewayId) {
    setMethod(gatewayId);
    if (cart && !addPaymentInfoTrackedRef.current) {
      addPaymentInfoTrackedRef.current = true;
      trackAddPaymentInfo(cart, gatewayId);
    }
  }
  useEffect(() => {
    if (!isCardSelected || !cardNumberContainerRef.current || !cardExpiryContainerRef.current || !cardCvcContainerRef.current) {
      return;
    }
    let cancelled = false;
    (async () => {
      const stripe = await getStripe();
      if (cancelled || !stripe || !cardNumberContainerRef.current || !cardExpiryContainerRef.current || !cardCvcContainerRef.current) {
        return;
      }
      stripeRef.current = stripe;
      const { cardNumber, cardExpiry, cardCvc } = createCardElements(stripe, {
        number: cardNumberContainerRef.current,
        expiry: cardExpiryContainerRef.current,
        cvc: cardCvcContainerRef.current
      });
      cardNumberRef.current = cardNumber;
      cardExpiryRef.current = cardExpiry;
      cardCvcRef.current = cardCvc;
      cardNumber.on("change", (event) => setCardNumberError(paymentErrorMessage(event.error, "")));
      cardExpiry.on("change", (event) => setCardExpiryError(paymentErrorMessage(event.error, "")));
      cardCvc.on("change", (event) => setCardCvcError(paymentErrorMessage(event.error, "")));
    })();
    return () => {
      cancelled = true;
      cardNumberRef.current?.unmount();
      cardExpiryRef.current?.unmount();
      cardCvcRef.current?.unmount();
      cardNumberRef.current = null;
      cardExpiryRef.current = null;
      cardCvcRef.current = null;
    };
  }, [isCardSelected, subStep]);
  useEffect(() => {
    if (!localPaymentType || !cart || !localPaymentContainerRef.current) {
      return;
    }
    let cancelled = false;
    (async () => {
      const stripe = await getStripe();
      if (cancelled || !stripe || !localPaymentContainerRef.current) {
        return;
      }
      stripeRef.current = stripe;
      localPaymentElementsRef.current = createLocalPaymentElements(
        stripe,
        cart,
        localPaymentType,
        localPaymentContainerRef.current
      );
    })();
    return () => {
      cancelled = true;
      localPaymentElementsRef.current?.paymentElement.unmount();
      localPaymentElementsRef.current = null;
    };
  }, [localPaymentType, subStep, cart]);
  useEffect(() => {
    if (!selectedWallet || subStep !== "payment" || !cart) {
      setWalletSupported(null);
      paymentRequestRef.current = null;
      return;
    }
    let cancelled = false;
    (async () => {
      const stripe = await getStripe();
      if (cancelled || !stripe) {
        return;
      }
      stripeRef.current = stripe;
      const paymentRequest = createWalletPaymentRequest(stripe, cart, selectedWallet);
      paymentRequestRef.current = paymentRequest;
      paymentRequest.on("paymentmethod", async (event) => {
        setSubmitting(true);
        setServerError("");
        const success = await submitOrder([
          { key: `${method}_payment_method_id`, value: event.paymentMethod.id }
        ]);
        event.complete(success ? "success" : "fail");
        if (!success) {
          setSubmitting(false);
        }
      });
      const canMakePaymentResult = await paymentRequest.canMakePayment();
      if (cancelled) {
        return;
      }
      setWalletSupported(Boolean(canMakePaymentResult?.[selectedWallet]));
    })();
    return () => {
      cancelled = true;
      paymentRequestRef.current = null;
      setWalletSupported(null);
    };
  }, [selectedWallet, subStep, cart]);
  useEffect(() => {
    if (!canPay || walletSupported !== true || !walletContainerRef.current || !stripeRef.current || !paymentRequestRef.current) {
      return;
    }
    walletButtonRef.current = mountWalletButton(stripeRef.current, paymentRequestRef.current, walletContainerRef.current);
    return () => {
      walletButtonRef.current?.unmount();
      walletButtonRef.current = null;
    };
  }, [canPay, walletSupported]);
  useEffect(() => {
    if (!isPaypalSelected || subStep !== "payment" || !cart || !canPay || !paypalContainerRef.current) {
      return;
    }
    const clientId = paymentMethods.find((gateway) => isPaypalGatewayId(gateway.id))?.config?.clientId;
    if (!clientId) {
      setServerError(__("PayPal n'est pas configuré correctement, merci de contacter le site."));
      return;
    }
    let cancelled = false;
    loadPaypalSdk(clientId, cart.totals.currency_code).then((paypal) => {
      if (cancelled || !paypalContainerRef.current) {
        return;
      }
      paypalButtonsRef.current = mountPaypalButtons(paypal, paypalContainerRef.current, {
        createOrder: () => createPaypalOrder(confirmedBillingAddress, buildShippingAddress()),
        onApprove: async (orderId) => {
          setSubmitting(true);
          setServerError("");
          try {
            const result = await submitPaypalCheckout(orderId, confirmedBillingAddress, buildShippingAddress());
            if (result.result !== "success" || !result.redirect) {
              const message = paypalErrorMessage(result.messages, __("Le paiement n'a pas pu être finalisé, merci de réessayer."));
              reportPaymentFailure(method, void 0, message);
              setServerError(message);
              setSubmitting(false);
              return;
            }
            const redirect = result.redirect;
            safely(() => {
              if (cart) {
                const orderIdMatch = redirect.match(/order-received=(\d+)/);
                trackPurchase(cart, orderIdMatch ? Number(orderIdMatch[1]) : 0, (Date.now() - checkoutStartedAt) / 1e3);
              }
            });
            safely(() => checkoutCompleted(checkoutStartedAt));
            await resyncPersonalAddress();
            window.location.href = redirect;
          } catch (error) {
            setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
            setSubmitting(false);
          }
        },
        onError: (error) => {
          reportPaymentFailure(method, void 0, error instanceof Error ? error.message : "");
          setServerError(error instanceof Error ? error.message : __("Paiement PayPal impossible, merci de réessayer."));
          setSubmitting(false);
        }
      });
    }).catch(() => {
      if (!cancelled) {
        setServerError(__("PayPal n'a pas pu être chargé, merci de réessayer."));
      }
    });
    return () => {
      cancelled = true;
      paypalButtonsRef.current?.close();
      paypalButtonsRef.current = null;
    };
  }, [isPaypalSelected, subStep, cart, canPay, paymentMethods]);
  function clearError(field) {
    setErrors((current) => current[field] ? { ...current, [field]: "" } : current);
  }
  function handleEditSavedBilling() {
    if (!savedBillingAddress) {
      return;
    }
    setFirstName(savedBillingAddress.first_name);
    setLastName(savedBillingAddress.last_name);
    setAddress1(savedBillingAddress.address_1);
    setAddress2(savedBillingAddress.address_2);
    setCity(savedBillingAddress.city);
    setPostcode(savedBillingAddress.postcode);
    setCountry(savedBillingAddress.country);
    setState(savedBillingAddress.state ?? "");
    setPhone(savedBillingAddress.phone);
    setManualBillingEntry(true);
  }
  function handleUseNewBilling() {
    setFirstName("");
    setLastName("");
    setAddress1("");
    setAddress2("");
    setCity("");
    setPostcode("");
    setCountry(resolveDefaultCountry(cart?.billing_address?.country, shippingCountries));
    setState("");
    setPhone("");
    setManualBillingEntry(true);
  }
  async function handleBillingSubmit(event) {
    event.preventDefault();
    if (!sameAsShipping && !useSavedBilling) {
      const formErrors = validateForm(event.currentTarget);
      setErrors(formErrors);
      if (Object.keys(formErrors).length > 0) {
        return;
      }
    }
    setSubmitting(true);
    setServerError("");
    const billingAddress = sameAsShipping ? defaultBillingAddress : useSavedBilling && savedBillingAddress ? { ...savedBillingAddress, email } : {
      first_name: firstName,
      last_name: lastName,
      address_1: address1,
      address_2: address2,
      city,
      postcode,
      country,
      state,
      phone,
      email
    };
    try {
      await storeApi.updateCustomer({ billing_address: { ...billingAddress } });
      await onRefreshCart();
      setConfirmedBillingAddress(billingAddress);
      setSubStep("payment");
    } catch (error) {
      setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
    } finally {
      setSubmitting(false);
    }
  }
  function handleChangeBillingAddress() {
    setSubStep("billing");
  }
  async function resyncPersonalAddress() {
    if (!shippingAddress) {
      return;
    }
    try {
      await storeApi.updateCustomer({ shipping_address: { ...shippingAddress.customerAddress } });
    } catch {
    }
  }
  function buildShippingAddress() {
    if (!shippingAddress) {
      return confirmedBillingAddress;
    }
    return {
      first_name: shippingAddress.firstName,
      last_name: shippingAddress.lastName,
      address_1: shippingAddress.address1,
      address_2: shippingAddress.address2,
      city: shippingAddress.city,
      postcode: shippingAddress.postcode,
      country: shippingAddress.country,
      phone: shippingAddress.phone
    };
  }
  async function submitOrder(paymentData) {
    try {
      const response = await storeApi.checkout({
        payment_method: method,
        payment_data: paymentData,
        billing_address: confirmedBillingAddress,
        shipping_address: buildShippingAddress()
      });
      const redirect = response.payment_result?.redirect_url || response.payment_result?.payment_details?.find((detail) => detail.key === "redirect")?.value;
      if (!redirect) {
        setServerError(__("Le paiement n'a pas pu être finalisé, merci de réessayer."));
        return false;
      }
      const nextAction = parseStripeNextAction(redirect);
      if (!nextAction) {
        safely(() => cart && trackPurchase(cart, response.order_id, (Date.now() - checkoutStartedAt) / 1e3));
        safely(() => checkoutCompleted(checkoutStartedAt));
        await resyncPersonalAddress();
        window.location.href = redirect;
        return true;
      }
      if (!stripeRef.current) {
        setServerError(__("Le formulaire de carte n'est pas encore prêt, merci de patienter puis réessayer."));
        return false;
      }
      let returnUrlBase = nextAction.return_url;
      try {
        returnUrlBase = decodeURI(nextAction.return_url);
      } catch {
      }
      const confirmResult = nextAction.type === "setup_intent" ? await stripeRef.current.confirmSetup({
        clientSecret: nextAction.client_secret,
        redirect: "if_required",
        confirmParams: { return_url: returnUrlBase }
      }) : await stripeRef.current.confirmPayment({
        clientSecret: nextAction.client_secret,
        redirect: "if_required",
        confirmParams: { return_url: returnUrlBase }
      });
      if (confirmResult.error) {
        reportPaymentFailure(method, confirmResult.error.code, confirmResult.error.message);
        setServerError(paymentErrorMessage(confirmResult.error, __("Le paiement a échoué, merci de réessayer.")));
        return false;
      }
      const intent = "paymentIntent" in confirmResult ? confirmResult.paymentIntent : confirmResult.setupIntent;
      if (!intent || STRIPE_UNFINISHED_STATUSES.includes(intent.status)) {
        reportPaymentFailure(method, "payment_cancelled");
        setServerError(__("Le paiement a été annulé ou refusé, merci de réessayer."));
        return false;
      }
      let target = nextAction.order_received_url || returnUrlBase;
      try {
        const returnUrl = new URL(returnUrlBase, window.location.href);
        if ("paymentIntent" in confirmResult) {
          returnUrl.searchParams.set("payment_intent", intent.id);
          returnUrl.searchParams.set("payment_intent_client_secret", intent.client_secret ?? "");
        } else {
          returnUrl.searchParams.set("setup_intent", intent.id);
          returnUrl.searchParams.set("setup_intent_client_secret", intent.client_secret ?? "");
        }
        target = returnUrl.toString();
      } catch (error) {
        console.error("[chic-checkout] URL de retour Stripe inexploitable, repli sur la confirmation :", error);
      }
      safely(() => cart && trackPurchase(cart, response.order_id, (Date.now() - checkoutStartedAt) / 1e3));
      safely(() => checkoutCompleted(checkoutStartedAt));
      await resyncPersonalAddress();
      window.location.href = target;
      return true;
    } catch (error) {
      setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
      return false;
    }
  }
  async function handlePaymentSubmit(event) {
    event.preventDefault();
    if (!method) {
      return;
    }
    setSubmitting(true);
    setServerError("");
    try {
      await submitPayment();
    } catch (error) {
      setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
      setSubmitting(false);
    }
  }
  async function submitPayment() {
    const paymentData = [];
    if (isCardSelected) {
      if (!stripeRef.current || !cardNumberRef.current) {
        setServerError(__("Le formulaire de carte n'est pas encore prêt, merci de patienter puis réessayer."));
        setSubmitting(false);
        return;
      }
      const { paymentMethod, error } = await stripeRef.current.createPaymentMethod({
        type: "card",
        card: cardNumberRef.current
      });
      if (error || !paymentMethod) {
        setCardNumberError(paymentErrorMessage(error, __("Carte refusée, merci de vérifier les informations saisies.")));
        setSubmitting(false);
        return;
      }
      paymentData.push({ key: `${STRIPE_CARD_GATEWAY_ID}_payment_method_id`, value: paymentMethod.id });
    }
    if (localPaymentType) {
      if (!stripeRef.current || !localPaymentElementsRef.current) {
        setServerError(__("Le moyen de paiement n'est pas encore prêt, merci de patienter puis réessayer."));
        setSubmitting(false);
        return;
      }
      const billingAddress = confirmedBillingAddress;
      const { paymentMethod, error } = await createLocalPaymentMethod(
        stripeRef.current,
        localPaymentElementsRef.current.elements,
        {
          name: `${billingAddress.first_name} ${billingAddress.last_name}`.trim(),
          email: billingAddress.email ?? "",
          phone: billingAddress.phone,
          address: {
            line1: billingAddress.address_1,
            line2: billingAddress.address_2,
            city: billingAddress.city,
            postal_code: billingAddress.postcode,
            country: billingAddress.country,
            state: ""
          }
        }
      );
      if (error || !paymentMethod) {
        setServerError(paymentErrorMessage(error, __("Le paiement a été refusé, merci de réessayer.")));
        setSubmitting(false);
        return;
      }
      paymentData.push({ key: `${method}_payment_method_id`, value: paymentMethod.id });
      paymentData.push({ key: "_stripe_payment_method_type", value: localPaymentType });
    }
    const success = await submitOrder(paymentData);
    if (!success) {
      setSubmitting(false);
    }
  }
  const termsLabelMatch = termsCheckbox.label.match(/^(.*)\[(.+)\](.*)$/s);
  const termsLabelBefore = termsLabelMatch ? termsLabelMatch[1] : termsCheckbox.label;
  const termsLabelLink = termsLabelMatch ? termsLabelMatch[2] : "";
  const termsLabelAfter = termsLabelMatch ? termsLabelMatch[3] : "";
  if (subStep === "payment" && confirmedBillingAddress) {
    return /* @__PURE__ */ jsxs("form", { ref: stepRootRef, className: "chic-step", onSubmit: handlePaymentSubmit, children: [
      submitting && /* @__PURE__ */ jsx(ProcessingOverlay, { label: __("Paiement en cours…") }),
      /* @__PURE__ */ jsx("h2", { children: stepName }),
      /* @__PURE__ */ jsxs("div", { className: "chic-shipping-address-summary", children: [
        /* @__PURE__ */ jsx("h3", { children: __("Adresse de facturation") }),
        /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            className: "chic-shipping-address-summary__change",
            onClick: handleChangeBillingAddress,
            disabled: submitting,
            children: __("Changer d'adresse")
          }
        )
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "chic-shipping-address-card", children: [
        /* @__PURE__ */ jsxs("strong", { children: [
          confirmedBillingAddress.first_name,
          " ",
          confirmedBillingAddress.last_name
        ] }),
        /* @__PURE__ */ jsx("span", { children: [confirmedBillingAddress.address_1, confirmedBillingAddress.address_2].filter(Boolean).join(", ") }),
        /* @__PURE__ */ jsxs("span", { children: [
          confirmedBillingAddress.postcode,
          ", ",
          confirmedBillingAddress.city,
          ",",
          " ",
          [stateLabel(confirmedBillingAddress.country, confirmedBillingAddress.state), shippingCountries[confirmedBillingAddress.country] ?? confirmedBillingAddress.country].filter(Boolean).join(", ")
        ] }),
        confirmedBillingAddress.phone && /* @__PURE__ */ jsx("span", { children: confirmedBillingAddress.phone })
      ] }),
      /* @__PURE__ */ jsx("h3", { className: "chic-shipping-methods-title", children: __("Sélectionnez un mode de paiement") }),
      paymentMethods.length > 0 ? /* @__PURE__ */ jsx("div", { className: "chic-payment-methods", children: paymentMethods.map((gateway) => {
        const isAvailable = gateway.available !== false;
        const isSelected = isAvailable && method === gateway.id;
        const isCard = isCardGateway(gateway);
        const isLocalPayment = isLocalPaymentGateway(gateway);
        const isMycred = gateway.id === MYCRED_GATEWAY_ID;
        const mycredData = cart?.extensions?.["chic-mycred-balance"];
        const mycredRemaining = mycredData && mycredData.cost !== null && mycredData.balance !== null ? Math.max(0, mycredData.balance - mycredData.cost) : null;
        return /* @__PURE__ */ jsxs(
          "div",
          {
            className: `chic-payment-method ${isSelected ? "is-selected" : ""} ${isAvailable ? "" : "chic-payment-method--disabled"}`,
            children: [
              /* @__PURE__ */ jsxs("label", { className: "chic-payment-method__row", children: [
                /* @__PURE__ */ jsx(
                  "input",
                  {
                    type: "radio",
                    name: "payment-method",
                    value: gateway.id,
                    checked: isSelected,
                    disabled: submitting || !isAvailable,
                    onChange: () => selectMethod(gateway.id)
                  }
                ),
                /* @__PURE__ */ jsx(
                  "span",
                  {
                    className: `chic-payment-method__title ${""}`,
                    children: gateway.title
                  }
                ),
                gateway.icon && /* @__PURE__ */ jsx(
                  "span",
                  {
                    className: `chic-payment-method__icon ${""}`,
                    "data-gateway": gateway.id,
                    "aria-hidden": "true",
                    dangerouslySetInnerHTML: { __html: gateway.icon }
                  }
                )
              ] }),
              isMycred && mycredRemaining !== null && isAvailable && /* @__PURE__ */ jsx("div", { className: "chic-mycred-info", children: /* @__PURE__ */ jsxs("p", { children: [
                __("Solde restant après commande"),
                " ",
                /* @__PURE__ */ jsx("strong", { children: mycredRemaining })
              ] }) }),
              isMycred && !isAvailable && /* @__PURE__ */ jsx("div", { className: "chic-mycred-info chic-mycred-info--unavailable", children: /* @__PURE__ */ jsx("p", { children: __("Cette méthode de paiement n'est pas utilisable sur cette commande car votre solde est insuffisant.") }) }),
              isCard && /* @__PURE__ */ jsx("div", { className: `chic-payment-method__form-wrapper ${isSelected ? "is-open" : ""}`, children: /* @__PURE__ */ jsxs("div", { className: "chic-payment-method__form-inner", children: [
                /* @__PURE__ */ jsxs("label", { children: [
                  __("Numéro de carte"),
                  /* @__PURE__ */ jsx(
                    "div",
                    {
                      ref: cardNumberContainerRef,
                      className: `chic-stripe-input ${cardNumberError ? "chic-stripe-input--invalid" : ""}`
                    }
                  ),
                  cardNumberError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: cardNumberError })
                ] }),
                /* @__PURE__ */ jsxs("label", { children: [
                  __("Date d'expiration"),
                  /* @__PURE__ */ jsx(
                    "div",
                    {
                      ref: cardExpiryContainerRef,
                      className: `chic-stripe-input ${cardExpiryError ? "chic-stripe-input--invalid" : ""}`
                    }
                  ),
                  cardExpiryError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: cardExpiryError })
                ] }),
                /* @__PURE__ */ jsxs("label", { children: [
                  "CVC",
                  /* @__PURE__ */ jsx(
                    "div",
                    {
                      ref: cardCvcContainerRef,
                      className: `chic-stripe-input ${cardCvcError ? "chic-stripe-input--invalid" : ""}`
                    }
                  ),
                  cardCvcError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: cardCvcError })
                ] })
              ] }) }),
              isLocalPayment && /* @__PURE__ */ jsx("div", { className: `chic-payment-method__form-wrapper ${isSelected ? "is-open" : ""}`, children: /* @__PURE__ */ jsx("div", { className: "chic-payment-method__form-inner", children: isSelected && /* @__PURE__ */ jsx(
                "div",
                {
                  ref: localPaymentContainerRef,
                  className: "chic-stripe-input chic-stripe-input--payment-element"
                }
              ) }) })
            ]
          },
          gateway.id
        );
      }) }) : /* @__PURE__ */ jsx("p", { className: "chic-shipping-rates-empty", children: loadingMethods ? __("Chargement…") : __("Aucun moyen de paiement disponible.") }),
      termsCheckbox.enabled && /* @__PURE__ */ jsxs("div", { className: "chic-terms-checkbox", children: [
        /* @__PURE__ */ jsxs("label", { className: "chic-checkbox-row chic-checkbox-row--wrap", children: [
          /* @__PURE__ */ jsx(
            "input",
            {
              type: "checkbox",
              checked: termsAccepted,
              disabled: submitting,
              onChange: (event) => setTermsAccepted(event.target.checked)
            }
          ),
          /* @__PURE__ */ jsxs("span", { children: [
            termsLabelBefore,
            termsLabelLink && /* @__PURE__ */ jsx(
              "button",
              {
                type: "button",
                className: "chic-terms-checkbox__link",
                onClick: (event) => {
                  event.preventDefault();
                  setTermsExpanded((value) => !value);
                },
                children: termsLabelLink
              }
            ),
            termsLabelAfter
          ] })
        ] }),
        /* @__PURE__ */ jsx("div", { className: `chic-terms-checkbox__content-wrapper ${termsExpanded ? "is-open" : ""}`, children: /* @__PURE__ */ jsx("div", { className: "chic-terms-checkbox__content-inner", children: /* @__PURE__ */ jsx(
          "div",
          {
            className: "chic-terms-checkbox__content-box",
            dangerouslySetInnerHTML: { __html: termsCheckbox.content }
          }
        ) }) })
      ] }),
      serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
      selectedWallet && walletSupported === false ? /* @__PURE__ */ jsx("p", { className: "chic-apple-pay-hint", children: isApplePaySelected ? __("Apple Pay n'est pas disponible sur cet appareil ou ce navigateur.") : __("Google Pay n'est pas disponible sur cet appareil ou ce navigateur.") }) : selectedWallet && canPay && walletSupported === true ? /* @__PURE__ */ jsx("div", { ref: walletContainerRef, className: "chic-apple-pay-button" }) : isPaypalSelected && canPay ? /* @__PURE__ */ jsx("div", { ref: paypalContainerRef, className: "chic-paypal-button" }) : /* @__PURE__ */ jsx("button", { type: "submit", disabled: !canPay || submitting || Boolean(selectedWallet) || isPaypalSelected, children: submitting ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Paiement en cours") }) : toSentenceCase(ctaLabels.payment) }),
      /* @__PURE__ */ jsx(BrandingNote, { place: "checkout" })
    ] });
  }
  return /* @__PURE__ */ jsxs("form", { ref: stepRootRef, className: "chic-step", onSubmit: handleBillingSubmit, noValidate: true, children: [
    /* @__PURE__ */ jsx("h2", { children: stepName }),
    shippingAddress && (shippingAddress.pickupPoint ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { className: "chic-shipping-address-summary", children: [
        /* @__PURE__ */ jsx("h3", { children: __("Adresse de facturation") }),
        sameAsShipping && /* @__PURE__ */ jsx("button", { type: "button", className: "chic-shipping-address-summary__change", onClick: () => setSameAsShipping(false), children: __("Changer d'adresse") })
      ] }),
      sameAsShipping && /* @__PURE__ */ jsxs("div", { className: "chic-shipping-address-card", children: [
        /* @__PURE__ */ jsxs("strong", { children: [
          defaultBillingAddress.first_name,
          " ",
          defaultBillingAddress.last_name
        ] }),
        /* @__PURE__ */ jsx("span", { children: [defaultBillingAddress.address_1, defaultBillingAddress.address_2].filter(Boolean).join(", ") }),
        /* @__PURE__ */ jsxs("span", { children: [
          defaultBillingAddress.postcode,
          ", ",
          defaultBillingAddress.city,
          ",",
          " ",
          [stateLabel(defaultBillingAddress.country, defaultBillingAddress.state), shippingCountries[defaultBillingAddress.country] ?? defaultBillingAddress.country].filter(Boolean).join(", ")
        ] }),
        defaultBillingAddress.phone && /* @__PURE__ */ jsx("span", { children: defaultBillingAddress.phone })
      ] }),
      !sameAsShipping && /* @__PURE__ */ jsx("button", { type: "button", className: "chic-address-choice__other", onClick: () => setSameAsShipping(true), children: __("Utiliser mon adresse personnelle") })
    ] }) : /* @__PURE__ */ jsxs("label", { className: "chic-checkbox-row", children: [
      /* @__PURE__ */ jsx(
        "input",
        {
          type: "checkbox",
          checked: sameAsShipping,
          onChange: (event) => setSameAsShipping(event.target.checked)
        }
      ),
      __("Adresse de facturation identique à l'adresse de livraison")
    ] })),
    !sameAsShipping && savedBillingAddress && /* @__PURE__ */ jsxs("div", { className: "chic-address-choice", children: [
      /* @__PURE__ */ jsxs("div", { className: `chic-address-tile ${useSavedBilling ? "is-selected" : ""}`, children: [
        /* @__PURE__ */ jsxs("button", { type: "button", className: "chic-address-tile__select", onClick: () => setManualBillingEntry(false), children: [
          /* @__PURE__ */ jsx("span", { className: "chic-address-tile__radio", "aria-hidden": "true" }),
          /* @__PURE__ */ jsxs("span", { className: "chic-address-tile__text", children: [
            /* @__PURE__ */ jsxs("strong", { children: [
              savedBillingAddress.first_name,
              " ",
              savedBillingAddress.last_name
            ] }),
            /* @__PURE__ */ jsxs("span", { children: [
              [savedBillingAddress.address_1, savedBillingAddress.address_2].filter(Boolean).join(", "),
              ",",
              " ",
              savedBillingAddress.postcode,
              " ",
              savedBillingAddress.city,
              ",",
              " ",
              [stateLabel(savedBillingAddress.country, savedBillingAddress.state), shippingCountries[savedBillingAddress.country] ?? savedBillingAddress.country].filter(Boolean).join(", ")
            ] })
          ] })
        ] }),
        /* @__PURE__ */ jsx("button", { type: "button", className: "chic-address-tile__edit", onClick: handleEditSavedBilling, children: __("Éditer") })
      ] }),
      /* @__PURE__ */ jsx("button", { type: "button", className: "chic-address-choice__other", onClick: handleUseNewBilling, children: __("Utiliser une autre adresse") })
    ] }),
    !sameAsShipping && !useSavedBilling && /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { ref: billingFormRef, className: "chic-field-row", children: [
        /* @__PURE__ */ jsxs("label", { children: [
          __("Prénom"),
          /* @__PURE__ */ jsx(
            "input",
            {
              name: "firstName",
              required: true,
              pattern: getFieldPattern(),
              value: firstName,
              "aria-invalid": Boolean(errors.firstName),
              onChange: (event) => {
                setFirstName(event.target.value);
                clearError("firstName");
              }
            }
          ),
          errors.firstName && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.firstName })
        ] }),
        /* @__PURE__ */ jsxs("label", { children: [
          __("Nom"),
          /* @__PURE__ */ jsx(
            "input",
            {
              name: "lastName",
              required: true,
              pattern: getFieldPattern(),
              value: lastName,
              "aria-invalid": Boolean(errors.lastName),
              onChange: (event) => {
                setLastName(event.target.value);
                clearError("lastName");
              }
            }
          ),
          errors.lastName && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.lastName })
        ] })
      ] }),
      /* @__PURE__ */ jsxs("label", { children: [
        __("Adresse"),
        /* @__PURE__ */ jsx(
          "input",
          {
            name: "address1",
            required: true,
            pattern: getFieldPattern(),
            value: address1,
            "aria-invalid": Boolean(errors.address1),
            onChange: (event) => {
              setAddress1(event.target.value);
              clearError("address1");
            }
          }
        ),
        errors.address1 && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.address1 })
      ] }),
      /* @__PURE__ */ jsxs("label", { children: [
        __("Adresse (ligne 2)"),
        /* @__PURE__ */ jsx(
          "input",
          {
            name: "address2",
            pattern: getFieldPattern(),
            value: address2,
            "aria-invalid": Boolean(errors.address2),
            onChange: (event) => {
              setAddress2(event.target.value);
              clearError("address2");
            }
          }
        ),
        errors.address2 && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.address2 })
      ] }),
      /* @__PURE__ */ jsx(
        AddressLocalityFields,
        {
          country,
          city,
          postcode,
          state,
          errors,
          onCity: setCity,
          onPostcode: setPostcode,
          onState: setState,
          clearError,
          countryField: /* @__PURE__ */ jsxs("label", { children: [
            __("Pays"),
            /* @__PURE__ */ jsx(
              "select",
              {
                name: "country",
                required: true,
                value: country,
                "aria-invalid": Boolean(errors.country),
                onChange: (event) => {
                  setCountry(event.target.value);
                  clearError("country");
                },
                children: Object.entries(shippingCountries).map(([code, name]) => /* @__PURE__ */ jsx("option", { value: code, children: name }, code))
              }
            ),
            errors.country && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.country })
          ] })
        }
      ),
      /* @__PURE__ */ jsxs("label", { children: [
        __("Téléphone"),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "tel",
            name: "phone",
            required: true,
            pattern: getFieldPattern(),
            value: phone,
            "aria-invalid": Boolean(errors.phone),
            onChange: (event) => {
              setPhone(event.target.value);
              clearError("phone");
            }
          }
        ),
        errors.phone && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.phone })
      ] })
    ] }),
    serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
    /* @__PURE__ */ jsx("button", { type: "submit", disabled: submitting, children: submitting ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Enregistrement en cours") }) : toSentenceCase(ctaLabels.billing) })
  ] });
}
export {
  PaymentStep
};
