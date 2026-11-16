import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { useEffect } from "react";
import { addressLocale } from "../lib/address-locale.js";
import { getFieldPattern } from "../lib/validation.js";
import { __ } from "../lib/i18n.js";
function AddressLocalityFields({
  country,
  city,
  postcode,
  state,
  errors,
  onCity,
  onPostcode,
  onState,
  clearError,
  countryField
}) {
  const locale = addressLocale(country);
  useEffect(() => {
    if (state && (locale.state.hidden || locale.state.options && !(state in locale.state.options))) {
      onState("");
    }
  }, [country]);
  const cityField = locale.city.hidden ? null : /* @__PURE__ */ jsxs("label", { children: [
    locale.city.label || __("Ville"),
    /* @__PURE__ */ jsx(
      "input",
      {
        name: "city",
        required: locale.city.required,
        pattern: getFieldPattern(),
        autoComplete: "address-level2",
        value: city,
        "aria-invalid": Boolean(errors.city),
        onChange: (event) => {
          onCity(event.target.value);
          clearError("city");
        }
      }
    ),
    errors.city && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.city })
  ] }, "city");
  const postcodeField = locale.postcode.hidden ? null : /* @__PURE__ */ jsxs("label", { children: [
    locale.postcode.label || __("Code postal"),
    /* @__PURE__ */ jsx(
      "input",
      {
        name: "postcode",
        required: locale.postcode.required,
        pattern: getFieldPattern(),
        autoComplete: "postal-code",
        value: postcode,
        "aria-invalid": Boolean(errors.postcode),
        onChange: (event) => {
          onPostcode(event.target.value);
          clearError("postcode");
        }
      }
    ),
    errors.postcode && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.postcode })
  ] }, "postcode");
  const stateLabelText = locale.state.label || __("Région");
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx("div", { className: "chic-field-row", children: locale.postcodeFirst ? [postcodeField, cityField] : [cityField, postcodeField] }),
    countryField,
    !locale.state.hidden && /* @__PURE__ */ jsxs("label", { children: [
      locale.state.required ? stateLabelText : `${stateLabelText} ${__("(facultatif)")}`,
      locale.state.options ? /* @__PURE__ */ jsxs(
        "select",
        {
          name: "state",
          required: locale.state.required,
          autoComplete: "address-level1",
          value: state,
          "aria-invalid": Boolean(errors.state),
          onChange: (event) => {
            onState(event.target.value);
            clearError("state");
          },
          children: [
            /* @__PURE__ */ jsx("option", { value: "", children: __("Choisir…") }),
            Object.entries(locale.state.options).map(([code, name]) => /* @__PURE__ */ jsx("option", { value: code, children: name }, code))
          ]
        }
      ) : /* @__PURE__ */ jsx(
        "input",
        {
          name: "state",
          required: locale.state.required,
          autoComplete: "address-level1",
          value: state,
          "aria-invalid": Boolean(errors.state),
          onChange: (event) => {
            onState(event.target.value);
            clearError("state");
          }
        }
      ),
      errors.state && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: errors.state })
    ] })
  ] });
}
export {
  AddressLocalityFields
};
