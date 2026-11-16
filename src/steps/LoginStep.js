import { jsxs, jsx } from "react/jsx-runtime";
import { useState, useRef, useEffect } from "react";
import { validateForm } from "../lib/validation.js";
import { authApi } from "../lib/auth-api-client.js";
import { toSentenceCase } from "../lib/text.js";
import { ButtonSpinner } from "../components/ButtonSpinner.js";
import { __ } from "../lib/i18n.js";
import { trackLogin, trackSignUp } from "../lib/ga4.js";
import { useConsent } from "../lib/consent.js";
import { useConfig } from "../lib/config.js";
function EyeIcon({ open }) {
  return open ? /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "18", height: "18", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx(
      "path",
      {
        d: "M2 12s3.5-6.5 10-6.5S22 12 22 12s-3.5 6.5-10 6.5S2 12 2 12Z",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinecap: "round",
        strokeLinejoin: "round"
      }
    ),
    /* @__PURE__ */ jsx("circle", { cx: "12", cy: "12", r: "2.5", stroke: "currentColor", strokeWidth: "1.5" })
  ] }) : /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "18", height: "18", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx(
    "path",
    {
      d: "M3 3l18 18M10.6 10.6a2.5 2.5 0 0 0 3.53 3.53M9.3 5.2A10.8 10.8 0 0 1 12 5c6.5 0 10 7 10 7a15.6 15.6 0 0 1-3.2 4.05M6.5 6.7C4 8.3 2 12 2 12s3.5 6.5 10 6.5c1.3 0 2.5-.24 3.6-.66",
      stroke: "currentColor",
      strokeWidth: "1.5",
      strokeLinecap: "round",
      strokeLinejoin: "round"
    }
  ) });
}
function LoginStep({
  introTitle,
  introText,
  socialLogins,
  socialGoogleClientId,
  socialAppleServicesId,
  guestCheckoutEnabled,
  registrationEnabled = true,
  noAccountText,
  ctaLabels,
  onComplete
}) {
  const { homeUrl } = useConfig();
  const [phase, setPhase] = useState("email");
  const [email, setEmail] = useState("");
  const [password, setPassword] = useState("");
  const [confirmPassword, setConfirmPassword] = useState("");
  const [showPassword, setShowPassword] = useState(false);
  const [isSubmitting, setIsSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [serverError, setServerError] = useState("");
  const [socialError, setSocialError] = useState("");
  const [appleSubmitting, setAppleSubmitting] = useState(false);
  useRef(null);
  const hasMarketingConsent = useConsent("marketing");
  useEffect(() => {
    {
      return;
    }
  }, [phase, socialLogins, socialGoogleClientId, hasMarketingConsent]);
  function resetToEmail() {
    setPhase("email");
    setPassword("");
    setConfirmPassword("");
    setServerError("");
    setErrors({});
  }
  async function handleEmailSubmit(event) {
    event.preventDefault();
    const formErrors = validateForm(event.currentTarget);
    setErrors(formErrors);
    if (Object.keys(formErrors).length > 0) {
      return;
    }
    setServerError("");
    setPhase("checking");
    try {
      const result = await authApi.checkEmail(email);
      if (typeof result.exists !== "boolean") {
        setServerError(result.message || __("Impossible de vérifier cet email pour le moment. Merci de réessayer."));
        setPhase("email");
        return;
      }
      setPhase(result.exists ? "password" : "choice");
    } catch {
      setServerError(__("Impossible de vérifier cet email pour le moment. Merci de réessayer."));
      setPhase("email");
    }
  }
  async function handlePasswordSubmit(event) {
    event.preventDefault();
    const formErrors = validateForm(event.currentTarget);
    setErrors(formErrors);
    if (Object.keys(formErrors).length > 0) {
      return;
    }
    setServerError("");
    setIsSubmitting(true);
    const result = await authApi.login(email, password);
    if (result.success) {
      trackLogin("password");
      window.location.reload();
      return;
    }
    setIsSubmitting(false);
    setServerError(result.message || __("Email ou mot de passe incorrect."));
  }
  async function handleRegisterSubmit(event) {
    event.preventDefault();
    const formErrors = validateForm(event.currentTarget);
    if (password !== confirmPassword) {
      formErrors.confirmPassword = __("Les mots de passe ne correspondent pas.");
    }
    setErrors(formErrors);
    if (Object.keys(formErrors).length > 0) {
      return;
    }
    setServerError("");
    setIsSubmitting(true);
    const result = await authApi.register(email, password);
    if (result.success) {
      trackSignUp("password");
      window.location.reload();
      return;
    }
    setIsSubmitting(false);
    setServerError(result.message || __("Impossible de créer le compte."));
  }
  const emailChip = /* @__PURE__ */ jsxs("p", { className: "chic-login-email-chip", children: [
    email,
    /* @__PURE__ */ jsx("button", { type: "button", onClick: resetToEmail, children: __("Modifier") })
  ] });
  return /* @__PURE__ */ jsxs("div", { className: "chic-step", children: [
    /* @__PURE__ */ jsxs("div", { className: "chic-login-intro", children: [
      /* @__PURE__ */ jsx("h2", { children: introTitle }),
      /* @__PURE__ */ jsx("p", { children: introText })
    ] }),
    phase === "email" || phase === "checking" ? /* @__PURE__ */ jsxs("form", { onSubmit: handleEmailSubmit, className: "chic-login-form", noValidate: true, children: [
      /* @__PURE__ */ jsxs("label", { children: [
        __("Email"),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: "email",
            name: "email",
            required: true,
            value: email,
            "aria-invalid": Boolean(errors.email),
            disabled: phase === "checking",
            onChange: (event) => {
              setEmail(event.target.value);
              if (errors.email) {
                setErrors((current) => ({ ...current, email: "" }));
              }
            }
          }
        ),
        errors.email && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.email })
      ] }),
      serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
      /* @__PURE__ */ jsx("button", { type: "submit", disabled: phase === "checking", children: phase === "checking" ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Vérification en cours") }) : toSentenceCase(ctaLabels.loginEmail) })
    ] }) : emailChip,
    phase === "password" && /* @__PURE__ */ jsxs("form", { onSubmit: handlePasswordSubmit, className: "chic-login-form", noValidate: true, children: [
      /* @__PURE__ */ jsxs("label", { children: [
        __("Mot de passe"),
        /* @__PURE__ */ jsxs("div", { className: "chic-password-field", children: [
          /* @__PURE__ */ jsx(
            "input",
            {
              type: showPassword ? "text" : "password",
              name: "password",
              autoComplete: "current-password",
              required: true,
              value: password,
              "aria-invalid": Boolean(errors.password),
              onChange: (event) => {
                setPassword(event.target.value);
                if (errors.password) {
                  setErrors((current) => ({ ...current, password: "" }));
                }
              }
            }
          ),
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              className: "chic-password-toggle",
              "aria-label": showPassword ? __("Masquer le mot de passe") : __("Afficher le mot de passe"),
              onClick: () => setShowPassword((value) => !value),
              children: /* @__PURE__ */ jsx(EyeIcon, { open: showPassword })
            }
          )
        ] }),
        errors.password && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.password })
      ] }),
      serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
      /* @__PURE__ */ jsx(
        "a",
        {
          className: "chic-forgot-password",
          href: `${homeUrl}wp-login.php?action=lostpassword`,
          target: "_blank",
          rel: "noopener noreferrer",
          children: __("Mot de passe oublié ?")
        }
      ),
      /* @__PURE__ */ jsx("button", { type: "submit", disabled: isSubmitting, children: isSubmitting ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Connexion en cours") }) : toSentenceCase(ctaLabels.loginPassword) })
    ] }),
    phase === "choice" && /* @__PURE__ */ jsxs("div", { className: "chic-login-choice", children: [
      /* @__PURE__ */ jsx("p", { className: "chic-login-choice__text", children: __("Aucun compte n'est associé à cet email.") }),
      registrationEnabled && /* @__PURE__ */ jsx("button", { type: "button", className: "chic-social-button", onClick: () => setPhase("register"), children: __("Créer un compte") }),
      !registrationEnabled && !guestCheckoutEnabled && /* @__PURE__ */ jsx("p", { className: "chic-login-choice__text", children: noAccountText || __("Cette boutique réserve la commande aux clients qui ont déjà un compte.") }),
      guestCheckoutEnabled && /* @__PURE__ */ jsx(
        "button",
        {
          type: "button",
          className: "chic-social-button",
          onClick: () => onComplete({ email, isAccount: false }),
          children: toSentenceCase(ctaLabels.loginGuest)
        }
      )
    ] }),
    phase === "register" && /* @__PURE__ */ jsxs("form", { onSubmit: handleRegisterSubmit, className: "chic-login-form", noValidate: true, children: [
      /* @__PURE__ */ jsxs("label", { children: [
        __("Mot de passe"),
        /* @__PURE__ */ jsxs("div", { className: "chic-password-field", children: [
          /* @__PURE__ */ jsx(
            "input",
            {
              type: showPassword ? "text" : "password",
              name: "password",
              autoComplete: "new-password",
              required: true,
              value: password,
              "aria-invalid": Boolean(errors.password),
              onChange: (event) => {
                setPassword(event.target.value);
                if (errors.password) {
                  setErrors((current) => ({ ...current, password: "" }));
                }
              }
            }
          ),
          /* @__PURE__ */ jsx(
            "button",
            {
              type: "button",
              className: "chic-password-toggle",
              "aria-label": showPassword ? __("Masquer le mot de passe") : __("Afficher le mot de passe"),
              onClick: () => setShowPassword((value) => !value),
              children: /* @__PURE__ */ jsx(EyeIcon, { open: showPassword })
            }
          )
        ] }),
        errors.password && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.password })
      ] }),
      /* @__PURE__ */ jsxs("label", { children: [
        __("Confirmer le mot de passe"),
        /* @__PURE__ */ jsx(
          "input",
          {
            type: showPassword ? "text" : "password",
            name: "confirmPassword",
            autoComplete: "new-password",
            required: true,
            value: confirmPassword,
            "aria-invalid": Boolean(errors.confirmPassword),
            onChange: (event) => {
              setConfirmPassword(event.target.value);
              if (errors.confirmPassword) {
                setErrors((current) => ({ ...current, confirmPassword: "" }));
              }
            }
          }
        ),
        errors.confirmPassword && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.confirmPassword })
      ] }),
      serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
      /* @__PURE__ */ jsx("button", { type: "submit", disabled: isSubmitting, children: isSubmitting ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Création du compte en cours") }) : toSentenceCase(ctaLabels.loginRegister) })
    ] }),
    false,
    false
  ] });
}
export {
  LoginStep
};
