import { __ } from "./i18n.js";
function messages() {
  const declined = __("Votre carte a été refusée. Contactez votre banque ou choisissez un autre moyen de paiement.");
  const number = __("Le numéro de carte est incorrect.");
  const cvc = __("Le code de sécurité (CVC) est incorrect.");
  const expiry = __("La date d’expiration de la carte est incorrecte.");
  const authenticate = __("Votre banque n’a pas pu confirmer le paiement. Réessayez ou choisissez un autre moyen de paiement.");
  const tooMany = __("Trop de tentatives avec cette carte. Réessayez plus tard ou choisissez une autre carte.");
  return {
    processing_error: __("Une erreur est survenue pendant le traitement de la carte. Merci de réessayer."),
    card_declined: declined,
    generic_decline: declined,
    do_not_honor: declined,
    call_issuer: declined,
    fraudulent: declined,
    lost_card: declined,
    stolen_card: declined,
    pickup_card: declined,
    restricted_card: declined,
    security_violation: declined,
    transaction_not_allowed: declined,
    insufficient_funds: __("Votre carte a été refusée : solde insuffisant. Choisissez une autre carte ou un autre moyen de paiement."),
    expired_card: __("Votre carte a expiré. Choisissez une autre carte."),
    card_not_supported: __("Cette carte ne permet pas ce type d’achat. Choisissez une autre carte."),
    currency_not_supported: __("Cette carte ne permet pas de payer dans cette devise. Choisissez une autre carte."),
    card_velocity_exceeded: tooMany,
    card_decline_rate_limit_exceeded: tooMany,
    incorrect_number: number,
    invalid_number: number,
    incomplete_number: __("Le numéro de carte est incomplet."),
    incorrect_cvc: cvc,
    invalid_cvc: cvc,
    incomplete_cvc: __("Le code de sécurité (CVC) est incomplet."),
    invalid_expiry_month: expiry,
    invalid_expiry_year: expiry,
    invalid_expiry_year_past: expiry,
    incomplete_expiry: __("La date d’expiration de la carte est incomplète."),
    authentication_required: authenticate,
    payment_intent_authentication_failure: authenticate,
    setup_intent_authentication_failure: authenticate,
    payment_cancelled: __("Le paiement a été annulé.")
  };
}
function paypalErrorMessage(messages2, fallback) {
  if (typeof messages2 !== "string" || messages2.trim() === "") {
    return fallback;
  }
  const text = (new DOMParser().parseFromString(messages2, "text/html").body.textContent ?? "").replace(/\s+/g, " ").trim();
  if (/instrument presented was either declined|INSTRUMENT_DECLINED/i.test(text)) {
    return __("PayPal a refusé ce moyen de paiement. Choisissez-en un autre dans la fenêtre PayPal ou un autre moyen de paiement.");
  }
  if (/PAYER_ACTION_REQUIRED|payer has not yet approved|ORDER_NOT_APPROVED/i.test(text)) {
    return __("Le paiement PayPal n’a pas été validé. Merci de réessayer.");
  }
  return text || fallback;
}
function paymentErrorMessage(error, fallback) {
  if (!error) {
    return fallback;
  }
  const map = messages();
  return error.decline_code && map[error.decline_code] || error.code && map[error.code] || error.message || fallback;
}
export {
  paymentErrorMessage,
  paypalErrorMessage
};
