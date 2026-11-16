import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { useState, useRef, useEffect } from "react";
import { storeApi } from "../lib/store-api-client.js";
import { getFieldPattern, validateForm } from "../lib/validation.js";
import { loadGooglePlaces, parseAddressComponents } from "../lib/google-places.js";
import { useConsent } from "../lib/consent.js";
import { formatMoney } from "../lib/money.js";
import { toSentenceCase } from "../lib/text.js";
import { useScrollIntoViewOnChange } from "../hooks/useScrollIntoViewOnChange.js";
import { ButtonSpinner } from "../components/ButtonSpinner.js";
import { fetchBoxtalParcelPointLabel } from "../lib/boxtal-connect.js";
import { fetchLaPosteParcelPointLabel } from "../lib/laposte-connect.js";
import { __ } from "../lib/i18n.js";
import { resolveDefaultCountry } from "../lib/country.js";
import { matchState, stateLabel, addressLocale } from "../lib/address-locale.js";
import { AddressLocalityFields } from "../components/AddressLocalityFields.js";
import { useConfig } from "../lib/config.js";
import { countBrowser } from "../lib/stats.js";
const BOXTAL_CONNECT_METHOD_ID = "boxtal_connect";
const LAPOSTE_METHOD_ID = "la_poste_pro_expeditions_woocommerce";
function parseParcelPointFromDom(container) {
  if (!container) {
    return null;
  }
  const name = container.querySelector('[class*="parcel-name"]')?.textContent?.trim();
  const rawAddress = container.querySelector('[class*="parcel-address"]')?.textContent?.trim();
  if (!name || !rawAddress) {
    return null;
  }
  const match = rawAddress.match(/^(.*?)\s+(\d{5}),\s*(.*?)(?:\s*\(.*\))?$/);
  if (!match) {
    return null;
  }
  const [, address, postcode, city] = match;
  return { name, address: address.trim(), postcode, city: city.trim() };
}
function InfoIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "16", height: "16", fill: "none", stroke: "currentColor", strokeWidth: "1.75", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("circle", { cx: "12", cy: "12", r: "9.5" }),
    /* @__PURE__ */ jsx("line", { x1: "12", y1: "11", x2: "12", y2: "16.5" }),
    /* @__PURE__ */ jsx("circle", { cx: "12", cy: "7.75", r: "0.75", fill: "currentColor", stroke: "none" })
  ] });
}
function ShippingStep({
  stepName,
  cart,
  isAccount,
  progressiveForm,
  googlePlacesApiKey,
  shippingCountries,
  freeShippingLabel,
  infoBanner,
  ctaLabels,
  onRefreshCart,
  onComplete,
  onSelectRate
}) {
  const [firstName, setFirstName] = useState("");
  const [lastName, setLastName] = useState("");
  const [address1, setAddress1] = useState("");
  const [address2, setAddress2] = useState("");
  const [city, setCity] = useState("");
  const [postcode, setPostcode] = useState("");
  const [country, setCountry] = useState(() => resolveDefaultCountry(cart?.shipping_address?.country, shippingCountries));
  const [state, setState] = useState("");
  const [phone, setPhone] = useState("");
  const [rateId, setRateId] = useState("");
  const [submitting, setSubmitting] = useState(false);
  const [errors, setErrors] = useState({});
  const [serverError, setServerError] = useState("");
  const [manualEntry, setManualEntry] = useState(false);
  const [expanded, setExpanded] = useState(!progressiveForm);
  const [subStep, setSubStep] = useState("address");
  const [confirmedAddress, setConfirmedAddress] = useState(null);
  const [updatingRate, setUpdatingRate] = useState(false);
  const [boxtalLabelHtml, setBoxtalLabelHtml] = useState("");
  const [laposteLabelHtml, setLaposteLabelHtml] = useState("");
  const address1InputRef = useRef(null);
  const stepRootRef = useRef(null);
  const addressFormRef = useRef(null);
  const boxtalLabelRef = useRef(null);
  const laposteLabelRef = useRef(null);
  const relayMapOpenRef = useRef(false);
  function onRelayLabelClick(event) {
    if (event.target.closest("a, button")) {
      relayMapOpenRef.current = true;
      countBrowser("relay_map_opened");
    }
  }
  useEffect(() => {
    const containers = [boxtalLabelRef.current, laposteLabelRef.current].filter((node) => node !== null);
    if (containers.length === 0) {
      return;
    }
    const observer = new MutationObserver(() => {
      if (relayMapOpenRef.current) {
        relayMapOpenRef.current = false;
        countBrowser("relay_point_changed");
      }
    });
    containers.forEach((node) => observer.observe(node, { childList: true, subtree: true, characterData: true }));
    return () => observer.disconnect();
  });
  useScrollIntoViewOnChange(stepRootRef, subStep);
  useScrollIntoViewOnChange(addressFormRef, manualEntry);
  const rates = cart?.shipping_rates[0]?.shipping_rates ?? [];
  const selectedRate = rates.find((rate) => rate.rate_id === rateId);
  const { integrations } = useConfig();
  const boxtalEnabled = integrations?.boxtal === true;
  const laposteEnabled = integrations?.laposte === true;
  const isBoxtalConnectSelected = boxtalEnabled && selectedRate?.method_id === BOXTAL_CONNECT_METHOD_ID;
  const isLaPosteSelected = laposteEnabled && selectedRate?.method_id === LAPOSTE_METHOD_ID;
  const hasMarketingConsent = useConsent("marketing");
  const savedAddress = isAccount && cart?.shipping_address?.address_1 ? cart.shipping_address : null;
  const useSavedAddress = Boolean(savedAddress) && !manualEntry;
  useEffect(() => {
    if (!isBoxtalConnectSelected || !confirmedAddress || !rateId) {
      setBoxtalLabelHtml("");
      return;
    }
    let cancelled = false;
    (async () => {
      try {
        const label = await fetchBoxtalParcelPointLabel(rateId);
        if (!cancelled) {
          setBoxtalLabelHtml(label ?? "");
        }
      } catch {
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [isBoxtalConnectSelected, confirmedAddress, rateId]);
  useEffect(() => {
    if (!isLaPosteSelected || !confirmedAddress || !rateId) {
      setLaposteLabelHtml("");
      return;
    }
    let cancelled = false;
    (async () => {
      try {
        const label = await fetchLaPosteParcelPointLabel(rateId);
        if (!cancelled) {
          setLaposteLabelHtml(label ?? "");
        }
      } catch {
      }
    })();
    return () => {
      cancelled = true;
    };
  }, [isLaPosteSelected, confirmedAddress, rateId]);
  useEffect(() => {
    if (subStep !== "shipping-method" || rates.length === 0) {
      return;
    }
    setRateId((current) => {
      if (current && rates.some((rate) => rate.rate_id === current)) {
        return current;
      }
      return rates.find((rate) => rate.selected)?.rate_id ?? rates[0].rate_id;
    });
  }, [subStep, rates]);
  useEffect(() => {
    if (!progressiveForm || !googlePlacesApiKey || useSavedAddress || !hasMarketingConsent) {
      return;
    }
    let autocomplete = null;
    let listener = null;
    let cancelled = false;
    loadGooglePlaces(googlePlacesApiKey).then(() => {
      if (cancelled || !address1InputRef.current) {
        return;
      }
      autocomplete = new google.maps.places.Autocomplete(address1InputRef.current, {
        types: ["address"]
      });
      listener = autocomplete.addListener("place_changed", () => {
        const place = autocomplete?.getPlace();
        if (!place) {
          return;
        }
        const parsed = parseAddressComponents(place);
        countBrowser("address_autocomplete_used");
        setAddress1(parsed.address1 || address1InputRef.current?.value || "");
        setCity(parsed.city);
        setPostcode(parsed.postcode);
        if (parsed.country) {
          setCountry(parsed.country);
        }
        setState(matchState(parsed.country || country, parsed.stateShort, parsed.stateLong));
        setExpanded(true);
      });
    }).catch(() => {
    });
    return () => {
      cancelled = true;
      listener?.remove();
    };
  }, [progressiveForm, googlePlacesApiKey, useSavedAddress, hasMarketingConsent]);
  function handleEditSavedAddress() {
    if (!savedAddress) {
      return;
    }
    setFirstName(savedAddress.first_name);
    setLastName(savedAddress.last_name);
    setAddress1(savedAddress.address_1);
    setAddress2(savedAddress.address_2);
    setCity(savedAddress.city);
    setPostcode(savedAddress.postcode);
    setCountry(savedAddress.country);
    setState(savedAddress.state ?? "");
    setPhone(savedAddress.phone);
    setManualEntry(true);
    setExpanded(true);
  }
  function handleUseNewAddress() {
    setFirstName("");
    setLastName("");
    setAddress1("");
    setAddress2("");
    setCity("");
    setPostcode("");
    setCountry(resolveDefaultCountry(cart?.shipping_address?.country, shippingCountries));
    setState("");
    setPhone("");
    setManualEntry(true);
    setExpanded(!progressiveForm);
  }
  function clearError(field) {
    setErrors((current) => current[field] ? { ...current, [field]: "" } : current);
  }
  async function handleAddressSubmit(event) {
    event.preventDefault();
    if (!useSavedAddress && progressiveForm && !expanded) {
      const hiddenFieldErrors = {};
      const locale = addressLocale(country);
      if (!city && locale.city.required && !locale.city.hidden) hiddenFieldErrors.city = __("Ce champ est obligatoire.");
      if (!postcode && locale.postcode.required && !locale.postcode.hidden) hiddenFieldErrors.postcode = __("Ce champ est obligatoire.");
      if (!state && locale.state.required) hiddenFieldErrors.state = __("Ce champ est obligatoire.");
      if (!phone) hiddenFieldErrors.phone = __("Ce champ est obligatoire.");
      if (Object.keys(hiddenFieldErrors).length > 0) {
        setExpanded(true);
        setErrors((current) => ({ ...current, ...hiddenFieldErrors }));
        return;
      }
    }
    const formErrors = validateForm(event.currentTarget);
    setErrors(formErrors);
    if (Object.keys(formErrors).length > 0) {
      return;
    }
    setSubmitting(true);
    setServerError("");
    const address = useSavedAddress && savedAddress ? savedAddress : {
      first_name: firstName,
      last_name: lastName,
      address_1: address1,
      address_2: address2,
      city,
      postcode,
      country,
      state,
      phone
    };
    try {
      if (!useSavedAddress) {
        await storeApi.updateCustomer({ shipping_address: { ...address }, billing_address: { ...address } });
      }
      await onRefreshCart();
      setConfirmedAddress(address);
      setSubStep("shipping-method");
    } catch (error) {
      setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
    } finally {
      setSubmitting(false);
    }
  }
  function handleChangeAddress() {
    setSubStep("address");
  }
  async function handleSelectRate(newRateId) {
    const previousRateId = rateId;
    setRateId(newRateId);
    setServerError("");
    setUpdatingRate(true);
    onSelectRate();
    try {
      await storeApi.selectShippingRate(cart?.shipping_rates[0]?.package_id ?? 0, newRateId);
      await onRefreshCart();
    } catch (error) {
      setRateId(previousRateId);
      setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
    } finally {
      setUpdatingRate(false);
    }
  }
  async function handleConfirmShipping(event) {
    event.preventDefault();
    if (!confirmedAddress) {
      return;
    }
    setSubmitting(true);
    setServerError("");
    try {
      let addressForOrder = confirmedAddress;
      let pickupPoint;
      if (isBoxtalConnectSelected || isLaPosteSelected) {
        const point = parseParcelPointFromDom(isBoxtalConnectSelected ? boxtalLabelRef.current : laposteLabelRef.current);
        if (point) {
          addressForOrder = {
            first_name: confirmedAddress.first_name,
            last_name: confirmedAddress.last_name,
            address_1: point.address,
            address_2: point.name,
            city: point.city,
            postcode: point.postcode,
            // Non fourni par le libellé — le point relais est dans tous les cas
            // rattaché à la même zone de livraison que l'adresse du client.
            country: confirmedAddress.country,
            // Même zone que l'adresse du client : sa région reste valable (exigée par
            // WooCommerce dans certains pays).
            state: confirmedAddress.state,
            phone: confirmedAddress.phone
          };
          pickupPoint = {
            name: point.name,
            address: point.address,
            postcode: point.postcode,
            city: point.city,
            country: confirmedAddress.country
          };
          await storeApi.updateCustomer({ shipping_address: { ...addressForOrder } });
        }
      }
      if (rateId) {
        await storeApi.selectShippingRate(cart?.shipping_rates[0]?.package_id ?? 0, rateId);
      }
      await onRefreshCart();
      onComplete({
        firstName: addressForOrder.first_name,
        lastName: addressForOrder.last_name,
        address1: addressForOrder.address_1,
        address2: addressForOrder.address_2,
        city: addressForOrder.city,
        postcode: addressForOrder.postcode,
        country: addressForOrder.country,
        state: addressForOrder.state ?? "",
        phone: addressForOrder.phone,
        rateId,
        pickupPoint,
        customerAddress: confirmedAddress
      });
    } catch (error) {
      setServerError(error instanceof Error ? error.message : __("Une erreur est survenue, merci de réessayer."));
    } finally {
      setSubmitting(false);
    }
  }
  const showProgressiveFields = !progressiveForm || expanded;
  if (subStep === "shipping-method" && confirmedAddress) {
    return /* @__PURE__ */ jsxs("form", { ref: stepRootRef, className: "chic-step", onSubmit: handleConfirmShipping, children: [
      /* @__PURE__ */ jsx("h2", { children: stepName }),
      /* @__PURE__ */ jsxs("div", { className: "chic-shipping-address-summary", children: [
        /* @__PURE__ */ jsx("h3", { children: __("Adresse de livraison") }),
        /* @__PURE__ */ jsx("button", { type: "button", className: "chic-shipping-address-summary__change", onClick: handleChangeAddress, children: __("Changer d'adresse") })
      ] }),
      /* @__PURE__ */ jsxs("div", { className: "chic-shipping-address-card", children: [
        /* @__PURE__ */ jsxs("strong", { children: [
          confirmedAddress.first_name,
          " ",
          confirmedAddress.last_name
        ] }),
        /* @__PURE__ */ jsx("span", { children: [confirmedAddress.address_1, confirmedAddress.address_2].filter(Boolean).join(", ") }),
        /* @__PURE__ */ jsxs("span", { children: [
          confirmedAddress.postcode,
          ", ",
          confirmedAddress.city,
          ",",
          " ",
          [stateLabel(confirmedAddress.country, confirmedAddress.state), shippingCountries[confirmedAddress.country] ?? confirmedAddress.country].filter(Boolean).join(", ")
        ] }),
        confirmedAddress.phone && /* @__PURE__ */ jsx("span", { children: confirmedAddress.phone })
      ] }),
      /* @__PURE__ */ jsx("h3", { className: "chic-shipping-methods-title", children: __("Sélectionnez un mode d'expédition") }),
      false,
      rates.length > 0 ? /* @__PURE__ */ jsx("div", { className: "chic-shipping-rates", children: rates.map((rate) => {
        const isFree = Number(rate.price) === 0;
        const isSelected = rateId === rate.rate_id;
        const isThisRateBoxtal = boxtalEnabled && rate.method_id === BOXTAL_CONNECT_METHOD_ID;
        const isThisRateLaPoste = laposteEnabled && rate.method_id === LAPOSTE_METHOD_ID;
        return /* @__PURE__ */ jsxs("label", { className: `chic-shipping-rate ${isSelected ? "is-selected" : ""}`, children: [
          /* @__PURE__ */ jsxs("span", { className: "chic-shipping-rate__row", children: [
            /* @__PURE__ */ jsx(
              "input",
              {
                type: "radio",
                name: "shipping-rate",
                value: rate.rate_id,
                checked: isSelected,
                disabled: updatingRate,
                onChange: () => handleSelectRate(rate.rate_id)
              }
            ),
            /* @__PURE__ */ jsxs("span", { className: "chic-shipping-rate__body", children: [
              /* @__PURE__ */ jsx("strong", { children: rate.name }),
              rate.delivery_time && /* @__PURE__ */ jsxs("span", { className: "chic-shipping-rate__delivery", children: [
                __("Livraison estimée :"),
                " ",
                rate.delivery_time
              ] })
            ] }),
            /* @__PURE__ */ jsx("span", { className: "chic-shipping-rate__price", children: isFree ? freeShippingLabel : cart ? formatMoney(rate.price, cart.totals) : rate.price }),
            rate.description && /* @__PURE__ */ jsx("span", { className: "chic-shipping-rate__info", title: rate.description, children: /* @__PURE__ */ jsx(InfoIcon, {}) })
          ] }),
          isThisRateBoxtal && isSelected && boxtalLabelHtml && // HTML fourni par le plugin Boxtal Connect lui-même (point relais le
          // plus proche + lien "Choisir un autre point relais") — son propre
          // script, déjà chargé sur la page, repère ce même balisage par ses
          // classes/attributs data- et gère l'ouverture de la carte ainsi que la
          // mise à jour de l'affichage après sélection, indépendamment de React.
          // Imbriqué dans la tile du mode de livraison (pas en dessous de la
          // liste) pour que le point relais choisi reste visuellement rattaché
          // à son mode de livraison.
          /* @__PURE__ */ jsx(
            "div",
            {
              ref: boxtalLabelRef,
              onClickCapture: onRelayLabelClick,
              className: "chic-boxtal-parcel-point",
              dangerouslySetInnerHTML: { __html: boxtalLabelHtml }
            }
          ),
          isThisRateLaPoste && isSelected && laposteLabelHtml && // Même patron que pour Boxtal Connect ci-dessus — voir le
          // commentaire de la branche Boxtal pour le détail.
          /* @__PURE__ */ jsx(
            "div",
            {
              ref: laposteLabelRef,
              onClickCapture: onRelayLabelClick,
              className: "chic-laposte-parcel-point",
              dangerouslySetInnerHTML: { __html: laposteLabelHtml }
            }
          )
        ] }, rate.rate_id);
      }) }) : /* @__PURE__ */ jsx("p", { className: "chic-shipping-rates-empty", children: __("Aucun mode de livraison disponible pour cette adresse.") }),
      serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
      /* @__PURE__ */ jsx("button", { type: "submit", disabled: submitting || updatingRate || !rateId, children: submitting ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Enregistrement en cours") }) : toSentenceCase(ctaLabels.shippingMethod) })
    ] });
  }
  return /* @__PURE__ */ jsxs("form", { ref: stepRootRef, className: "chic-step", onSubmit: handleAddressSubmit, noValidate: true, children: [
    /* @__PURE__ */ jsx("h2", { children: stepName }),
    savedAddress && /* @__PURE__ */ jsxs("div", { className: "chic-address-choice", children: [
      /* @__PURE__ */ jsxs("div", { className: `chic-address-tile ${useSavedAddress ? "is-selected" : ""}`, children: [
        /* @__PURE__ */ jsxs("button", { type: "button", className: "chic-address-tile__select", onClick: () => setManualEntry(false), children: [
          /* @__PURE__ */ jsx("span", { className: "chic-address-tile__radio", "aria-hidden": "true" }),
          /* @__PURE__ */ jsxs("span", { className: "chic-address-tile__text", children: [
            /* @__PURE__ */ jsxs("strong", { children: [
              savedAddress.first_name,
              " ",
              savedAddress.last_name
            ] }),
            /* @__PURE__ */ jsxs("span", { children: [
              [savedAddress.address_1, savedAddress.address_2].filter(Boolean).join(", "),
              ", ",
              savedAddress.postcode,
              " ",
              savedAddress.city,
              ", ",
              savedAddress.country
            ] })
          ] })
        ] }),
        /* @__PURE__ */ jsx("button", { type: "button", className: "chic-address-tile__edit", onClick: handleEditSavedAddress, children: __("Éditer") })
      ] }),
      /* @__PURE__ */ jsx("button", { type: "button", className: "chic-address-choice__other", onClick: handleUseNewAddress, children: __("Utiliser une autre adresse") })
    ] }),
    !useSavedAddress && /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsxs("div", { ref: addressFormRef, className: "chic-field-row", children: [
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
      progressiveForm && /* @__PURE__ */ jsxs("label", { children: [
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
      ] }),
      /* @__PURE__ */ jsxs("label", { children: [
        __("Adresse"),
        /* @__PURE__ */ jsx(
          "input",
          {
            ref: address1InputRef,
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
      progressiveForm && !expanded && /* @__PURE__ */ jsx("button", { type: "button", className: "chic-shipping-manual-toggle", onClick: () => setExpanded(true), children: __("Entrer mon adresse manuellement") }),
      showProgressiveFields && /* @__PURE__ */ jsxs(Fragment, { children: [
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
            countryField: progressiveForm ? null : /* @__PURE__ */ jsxs("label", { children: [
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
      ] })
    ] }),
    serverError && /* @__PURE__ */ jsx("p", { className: "chic-field-error", children: serverError }),
    /* @__PURE__ */ jsx("button", { type: "submit", disabled: submitting, children: submitting ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Enregistrement en cours") }) : toSentenceCase(ctaLabels.shippingAddress) })
  ] });
}
export {
  ShippingStep
};
