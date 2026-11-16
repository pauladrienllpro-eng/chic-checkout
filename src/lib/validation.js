import { __ } from "./i18n.js";
import "./config.js";
function getFieldErrorMessage(input) {
  if (input.validity.valid) {
    return "";
  }
  if (input.validity.valueMissing) {
    return __("Ce champ est obligatoire.");
  }
  if (input.validity.typeMismatch && input.type === "email") {
    return __("Merci d'indiquer une adresse email valide.");
  }
  return __("Ce champ est invalide.");
}
function getFieldPattern(name) {
  {
    return void 0;
  }
}
function validateForm(form) {
  const errors = {};
  form.querySelectorAll("input[required], input[pattern], select[required]").forEach((input) => {
    const message = getFieldErrorMessage(input);
    if (message) {
      errors[input.name] = message;
    }
  });
  return errors;
}
export {
  getFieldErrorMessage,
  getFieldPattern,
  validateForm
};
