import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { useState, useEffect, useRef } from "react";
import { Header } from "./components/Header.js";
import { Stepper } from "./components/Stepper.js";
import { StepSummary } from "./components/StepSummary.js";
import { OrderSummary } from "./components/OrderSummary.js";
import { Collapse } from "./components/Collapse.js";
import { Footer } from "./components/Footer.js";
import { ReassuranceList } from "./components/ReassuranceList.js";
import { LoginStep } from "./steps/LoginStep.js";
import { ShippingStep } from "./steps/ShippingStep.js";
import { PaymentStep } from "./steps/PaymentStep.js";
import { useCart } from "./hooks/useCart.js";
import { authApi } from "./lib/auth-api-client.js";
import { useAutoScrollFocusedField } from "./hooks/useAutoScrollFocusedField.js";
import { useScrollIntoViewOnChange } from "./hooks/useScrollIntoViewOnChange.js";
import { __ } from "./lib/i18n.js";
import { trackBeginCheckout, trackAddShippingInfo } from "./lib/ga4.js";
import { useConfig, styleMode, computeColors } from "./lib/config.js";
import { safeGetItem, safeSetItem } from "./lib/storage.js";
import { initStats, stepCompleted, stepEntered, countBrowser } from "./lib/stats.js";
const HIGH_CONTRAST_STORAGE_KEY = "chic-checkout-high-contrast";
const HIGH_CONTRAST_VARS = {
  "--chic-bg": "#ffffff",
  "--chic-surface": "#ffffff",
  "--chic-border": "#000000",
  "--chic-border-strong": "#000000",
  "--chic-text": "#000000",
  "--chic-text-muted": "#000000",
  "--chic-accent": "#000000",
  "--chic-cta": "#000000"
};
function App() {
  const config = useConfig();
  const colors = computeColors();
  const { cart, refresh } = useCart();
  const needsShipping = cart?.needs_shipping ?? true;
  const stepNamesForFlow = needsShipping ? config.stepNames : [config.stepNames[0], config.stepNames[2]];
  const STEPS = stepNamesForFlow.map((name, index) => ({
    id: index + 1,
    label: config.stepNumbering ? `${index + 1} - ${name}` : name
  }));
  const PAYMENT_STEP_LABEL_INDEX = needsShipping ? 2 : 1;
  const STEP_HASHES = needsShipping ? ["", "login", "shipping", "payment"] : ["", "login", "payment"];
  const PAYMENT_STEP = needsShipping ? 3 : 2;
  const [activeStep, setActiveStep] = useState(config.isLoggedIn ? 2 : 1);
  const [email, setEmail] = useState(config.isLoggedIn ? config.userEmail : "");
  const [isAccount, setIsAccount] = useState(config.isLoggedIn);
  const [shipping, setShipping] = useState(null);
  const [shippingRateChosen, setShippingRateChosen] = useState(false);
  const [highestStepReached, setHighestStepReached] = useState(activeStep);
  useEffect(() => {
    setHighestStepReached((current) => Math.max(current, activeStep));
  }, [activeStep]);
  useEffect(() => initStats("checkout"), []);
  const stepNameOf = (step) => step === 1 ? "identification" : step === PAYMENT_STEP ? "payment" : "shipping";
  const previousStepRef = useRef(activeStep);
  useEffect(() => {
    if (activeStep > previousStepRef.current) {
      stepCompleted(stepNameOf(previousStepRef.current));
    }
    previousStepRef.current = activeStep;
    stepEntered(stepNameOf(activeStep));
  }, [activeStep]);
  const [highContrast, setHighContrast] = useState(
    () => safeGetItem(HIGH_CONTRAST_STORAGE_KEY) === "1"
  );
  const activeStepRef = useRef(null);
  const checkoutStartedAtRef = useRef(Date.now());
  const beginCheckoutFiredRef = useRef(false);
  useEffect(() => {
    if (cart && !beginCheckoutFiredRef.current) {
      beginCheckoutFiredRef.current = true;
      trackBeginCheckout(cart);
    }
  }, [cart]);
  const shippingForHashRef = useRef(shipping);
  const isAccountForHashRef = useRef(isAccount);
  const emailForHashRef = useRef(email);
  shippingForHashRef.current = shipping;
  isAccountForHashRef.current = isAccount;
  emailForHashRef.current = email;
  useEffect(() => {
    function applyHashStep() {
      const requestedStep = STEP_HASHES.indexOf(window.location.hash.slice(1));
      if (requestedStep < 1) {
        return;
      }
      if (requestedStep === 1 && isAccountForHashRef.current) {
        return;
      }
      if (requestedStep === 3 && !shippingForHashRef.current) {
        return;
      }
      if (requestedStep === 2 && !isAccountForHashRef.current && !emailForHashRef.current) {
        return;
      }
      setActiveStep(requestedStep);
    }
    applyHashStep();
    window.addEventListener("hashchange", applyHashStep);
    return () => window.removeEventListener("hashchange", applyHashStep);
  }, []);
  useEffect(() => {
    const hash = `#${STEP_HASHES[activeStep]}`;
    if (window.location.hash !== hash) {
      window.history.replaceState(null, "", hash);
    }
  }, [activeStep]);
  function toggleHighContrast() {
    if (!highContrast) {
      countBrowser("high_contrast_used");
    }
    setHighContrast((current) => {
      const next = !current;
      safeSetItem(HIGH_CONTRAST_STORAGE_KEY, next ? "1" : "0");
      return next;
    });
  }
  useAutoScrollFocusedField();
  useScrollIntoViewOnChange(activeStepRef, activeStep);
  const selectedShippingRate = shipping ? cart?.shipping_rates[0]?.shipping_rates.find((rate) => rate.rate_id === shipping.rateId) : void 0;
  async function handleLogout() {
    await authApi.logout();
    window.location.href = config.backUrl;
  }
  return /* @__PURE__ */ jsx(
    "div",
    {
      className: `chic-checkout chic-checkout--${styleMode()} ${highContrast ? "chic-checkout--high-contrast" : ""}`,
      style: {
        "--chic-accent": colors.accent,
        "--chic-bg": colors.bg,
        "--chic-cta": colors.cta,
        ...highContrast ? HIGH_CONTRAST_VARS : {}
      },
      children: /* @__PURE__ */ jsxs("div", { className: "chic-checkout__container", children: [
        /* @__PURE__ */ jsx(
          Header,
          {
            backUrl: config.backUrl,
            backLabel: config.backLabel,
            logoUrl: config.logoUrl,
            logoText: config.logoText,
            homeUrl: config.homeUrl,
            contactStyle: config.contactStyle,
            contactLabel: config.contactLabel,
            contactIconUrl: "",
            contactPopupTitle: config.contactPopupTitle,
            contactPopupText: config.contactPopupText,
            contactChannels: config.contactChannels
          }
        ),
        /* @__PURE__ */ jsxs("div", { className: "chic-sticky-group chic-mobile-only", children: [
          config.showStepper && /* @__PURE__ */ jsx(Stepper, { steps: STEPS, activeStep }),
          /* @__PURE__ */ jsx(
            OrderSummary,
            {
              cart,
              label: config.orderSummaryLabel,
              freeShippingLabel: config.freeShippingLabel,
              pricesIncludeTax: config.pricesIncludeTax,
              packagingProductIds: [],
              giftProductIds: [],
              loyaltyEnabled: false,
              loyaltyText: "",
              shippingRateChosen
            }
          )
        ] }),
        /* @__PURE__ */ jsxs("div", { className: "chic-layout", children: [
          /* @__PURE__ */ jsxs("div", { className: "chic-checkout__steps", children: [
            /* @__PURE__ */ jsx(Collapse, { open: activeStep === 1, innerRef: activeStep === 1 ? activeStepRef : void 0, children: /* @__PURE__ */ jsx(
              LoginStep,
              {
                introTitle: config.loginIntroTitle,
                introText: config.loginIntroText,
                socialLogins: [],
                socialGoogleClientId: "",
                socialAppleServicesId: "",
                guestCheckoutEnabled: config.guestCheckoutEnabled,
                registrationEnabled: config.registrationEnabled,
                ctaLabels: config.ctaLabels,
                onComplete: (result) => {
                  setEmail(result.email);
                  setIsAccount(result.isAccount);
                  setActiveStep(2);
                }
              }
            ) }),
            /* @__PURE__ */ jsx(Collapse, { open: activeStep > 1, children: /* @__PURE__ */ jsx(
              StepSummary,
              {
                title: STEPS[0].label,
                summary: email,
                editLabel: isAccount ? __("Déconnexion") : __("Modifier"),
                onEdit: isAccount ? handleLogout : () => setActiveStep(1)
              }
            ) }),
            shipping && /* @__PURE__ */ jsx(Collapse, { open: activeStep > 2, children: /* @__PURE__ */ jsx(
              StepSummary,
              {
                title: STEPS[1].label,
                summary: /* @__PURE__ */ jsxs(Fragment, { children: [
                  /* @__PURE__ */ jsxs("span", { children: [
                    shipping.address2 ? `${shipping.address2}, ` : "",
                    shipping.address1,
                    ", ",
                    shipping.city
                  ] }),
                  selectedShippingRate && /* @__PURE__ */ jsxs("span", { className: "chic-step-summary__value-secondary", children: [
                    selectedShippingRate.name,
                    selectedShippingRate.delivery_time && /* @__PURE__ */ jsxs(Fragment, { children: [
                      ", ",
                      __("livraison estimée"),
                      " : ",
                      selectedShippingRate.delivery_time
                    ] })
                  ] })
                ] }),
                onEdit: () => setActiveStep(2)
              }
            ) }),
            needsShipping && /* @__PURE__ */ jsx(Collapse, { open: activeStep === 2, innerRef: activeStep === 2 ? activeStepRef : void 0, children: /* @__PURE__ */ jsx(
              ShippingStep,
              {
                stepName: STEPS[1].label,
                cart,
                isAccount,
                progressiveForm: config.progressiveShippingForm,
                googlePlacesApiKey: config.googlePlacesApiKey,
                shippingCountries: config.shippingCountries,
                freeShippingLabel: config.freeShippingLabel,
                infoBanner: { text: "", linkUrl: "" },
                ctaLabels: config.ctaLabels,
                onRefreshCart: refresh,
                onSelectRate: () => setShippingRateChosen(true),
                onComplete: (data) => {
                  setShipping(data);
                  setShippingRateChosen(true);
                  setActiveStep(3);
                  if (cart) {
                    const rate = cart.shipping_rates[0]?.shipping_rates.find(
                      (candidate) => candidate.rate_id === data.rateId
                    );
                    trackAddShippingInfo(cart, rate?.name ?? data.rateId);
                  }
                }
              }
            ) }),
            highestStepReached >= PAYMENT_STEP && /* @__PURE__ */ jsx(Collapse, { open: activeStep === PAYMENT_STEP, innerRef: activeStep === PAYMENT_STEP ? activeStepRef : void 0, children: /* @__PURE__ */ jsx(
              PaymentStep,
              {
                stepName: STEPS[PAYMENT_STEP_LABEL_INDEX].label,
                shippingAddress: shipping,
                email,
                cart,
                isAccount,
                checkoutStartedAt: checkoutStartedAtRef.current,
                ctaLabels: config.ctaLabels,
                shippingCountries: config.shippingCountries,
                termsCheckbox: config.termsCheckbox,
                paymentLogoMode: config.paymentLogoMode ?? "beside",
                onRefreshCart: refresh
              }
            ) })
          ] }),
          /* @__PURE__ */ jsxs("aside", { className: "chic-layout__summary chic-desktop-only", children: [
            config.showStepper && /* @__PURE__ */ jsx(Stepper, { steps: STEPS, activeStep }),
            /* @__PURE__ */ jsx(
              OrderSummary,
              {
                cart,
                label: config.orderSummaryLabel,
                freeShippingLabel: config.freeShippingLabel,
                pricesIncludeTax: config.pricesIncludeTax,
                packagingProductIds: [],
                giftProductIds: [],
                loyaltyEnabled: false,
                loyaltyText: "",
                shippingRateChosen,
                variant: "sidebar"
              }
            ),
            /* @__PURE__ */ jsx(ReassuranceList, { items: config.reassuranceItems })
          ] })
        ] }),
        config.reassuranceMobileEnabled && /* @__PURE__ */ jsx("div", { className: "chic-mobile-only chic-reassurance-mobile", children: /* @__PURE__ */ jsx(ReassuranceList, { items: config.reassuranceItems }) }),
        /* @__PURE__ */ jsx(
          Footer,
          {
            links: config.footerLinks,
            highContrast,
            onToggleHighContrast: toggleHighContrast,
            contrastToggleEnabled: config.footerContrastToggleEnabled,
            contrastToggleLabel: config.footerContrastToggleLabel
          }
        )
      ] })
    }
  );
}
export {
  App as default
};
