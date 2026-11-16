import { jsxs, jsx } from "react/jsx-runtime";
import { useRef, useEffect } from "react";
import { createPortal } from "react-dom";
import { LoginStep } from "../../steps/LoginStep.js";
import { useSheetTransition, trapFocus, popinRoot } from "../../hooks/useSheetTransition.js";
import { requireConfig } from "../../lib/config.js";
import { __ } from "../../lib/i18n.js";
const EXPRESS_LOGIN_FLAG = "chic_express_login";
function LoginSheet({ open, reason, onClose }) {
  const { mounted, visible } = useSheetTransition(open);
  const panelRef = useRef(null);
  const closeRef = useRef(null);
  const login = requireConfig().login;
  useEffect(() => {
    if (visible) {
      closeRef.current?.focus({ preventScroll: true });
    }
  }, [visible]);
  if (!mounted || !login) {
    return null;
  }
  const rememberExpress = () => {
    if (reason !== "express") {
      return;
    }
    try {
      sessionStorage.setItem(EXPRESS_LOGIN_FLAG, "1");
    } catch {
    }
  };
  return createPortal(
    /* @__PURE__ */ jsxs(
      "div",
      {
        className: `chic-contact-popup chic-login-sheet ${visible ? "is-open" : ""}`,
        role: "dialog",
        "aria-modal": "true",
        "aria-label": __("Connexion"),
        onKeyDown: (event) => {
          if (event.key === "Escape") {
            event.stopPropagation();
            onClose();
            return;
          }
          trapFocus(event, panelRef.current);
        },
        onSubmitCapture: rememberExpress,
        onClickCapture: (event) => {
        },
        children: [
          /* @__PURE__ */ jsx("div", { className: "chic-contact-popup__backdrop", onClick: onClose }),
          /* @__PURE__ */ jsxs("div", { className: "chic-contact-popup__panel", ref: panelRef, children: [
            /* @__PURE__ */ jsx("span", { className: "chic-contact-popup__grabber", "aria-hidden": "true" }),
            /* @__PURE__ */ jsx("button", { ref: closeRef, type: "button", className: "chic-contact-popup__close chic-login-sheet__close", onClick: onClose, "aria-label": __("Fermer"), children: /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "18", height: "18", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "M6 6l12 12M18 6 6 18", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round" }) }) }),
            /* @__PURE__ */ jsx("div", { className: "chic-login-sheet__body", children: /* @__PURE__ */ jsx(
              LoginStep,
              {
                introTitle: reason === "express" ? __("Connectez-vous pour payer") : login.introTitle,
                introText: reason === "express" ? __("Cette boutique demande un compte pour payer avec Apple Pay ou PayPal. Votre panier est conservé.") : __("Retrouvez vos adresses et suivez vos commandes. Votre panier est conservé."),
                socialLogins: login.socialLogins,
                socialGoogleClientId: login.socialGoogleClientId,
                socialAppleServicesId: login.socialAppleServicesId,
                guestCheckoutEnabled: false,
                registrationEnabled: login.registrationEnabled,
                noAccountText: reason === "express" ? __("Le paiement express est réservé aux clients qui ont déjà un compte. Vérifiez votre email ou passez par « Aller au paiement ».") : __("La création de compte n’est pas ouverte sur cette boutique. Vérifiez votre email."),
                ctaLabels: login.ctaLabels,
                onComplete: onClose
              }
            ) })
          ] })
        ]
      }
    ),
    popinRoot()
  );
}
export {
  EXPRESS_LOGIN_FLAG,
  LoginSheet
};
