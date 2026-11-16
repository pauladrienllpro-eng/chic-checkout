import { jsxs, jsx, Fragment } from "react/jsx-runtime";
import { useState } from "react";
import { formatMoney } from "../../lib/money.js";
import { storeApi } from "../../lib/store-api-client.js";
import { __ } from "../../lib/i18n.js";
import { countBrowser } from "../../lib/stats.js";
import { ButtonSpinner } from "../ButtonSpinner.js";
import { decodeHtmlEntities } from "../../lib/text.js";
function PromoCode({ cart, onChange }) {
  const [editing, setEditing] = useState(false);
  const [code, setCode] = useState("");
  const [error, setError] = useState("");
  const [pending, setPending] = useState(false);
  async function apply() {
    const trimmed = code.trim();
    if (!trimmed || pending) {
      return;
    }
    setPending(true);
    setError("");
    try {
      const updated = await storeApi.applyCoupon(trimmed);
      countBrowser("coupon_applied", ["result", "success"]);
      setCode("");
      setEditing(false);
      onChange(updated);
    } catch (err) {
      countBrowser("coupon_applied", ["result", "failure"]);
      setError(err instanceof Error ? decodeHtmlEntities(err.message) : __("Ce code promo n’est pas valide."));
    } finally {
      setPending(false);
    }
  }
  async function remove(couponCode) {
    if (pending) {
      return;
    }
    setPending(true);
    try {
      const updated = await storeApi.removeCoupon(couponCode);
      onChange(updated);
    } finally {
      setPending(false);
    }
  }
  return /* @__PURE__ */ jsxs("div", { className: "chic-promo-code", children: [
    cart.coupons.map((coupon) => /* @__PURE__ */ jsxs("div", { className: "chic-promo-code__applied", children: [
      /* @__PURE__ */ jsxs("span", { className: "chic-promo-code__applied-code", children: [
        /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "14", height: "14", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "M20 6 9 17l-5-5", stroke: "currentColor", strokeWidth: "2", strokeLinecap: "round", strokeLinejoin: "round" }) }),
        /* @__PURE__ */ jsxs("span", { children: [
          coupon.code,
          " (-",
          formatMoney(coupon.totals.total_discount, coupon.totals),
          ")"
        ] })
      ] }),
      /* @__PURE__ */ jsx(
        "button",
        {
          type: "button",
          className: "chic-promo-code__remove",
          disabled: pending,
          onClick: () => remove(coupon.code),
          "aria-label": __("Retirer le code promo"),
          children: /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "14", height: "14", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "M18 6 6 18M6 6l12 12", stroke: "currentColor", strokeWidth: "1.8", strokeLinecap: "round" }) })
        }
      )
    ] }, coupon.code)),
    editing ? /* @__PURE__ */ jsxs(Fragment, { children: [
      /* @__PURE__ */ jsx("label", { className: "chic-sr-only", htmlFor: "chic-promo-code-input", children: __("Code promo") }),
      /* @__PURE__ */ jsxs("div", { className: `chic-promo-code__field ${error ? "has-error" : ""}`, children: [
        /* @__PURE__ */ jsx(
          "input",
          {
            id: "chic-promo-code-input",
            type: "text",
            value: code,
            onChange: (event) => {
              setCode(event.target.value);
              setError("");
            },
            onKeyDown: (event) => {
              if (event.key === "Enter") {
                event.preventDefault();
                apply();
              }
            },
            placeholder: __("Code promo"),
            autoComplete: "off",
            autoCapitalize: "characters",
            spellCheck: false,
            "aria-invalid": error ? true : void 0,
            "aria-describedby": error ? "chic-promo-code-error" : void 0,
            autoFocus: true
          }
        ),
        /* @__PURE__ */ jsx(
          "button",
          {
            type: "button",
            className: "chic-promo-code__apply",
            disabled: pending || code.trim() === "",
            "aria-busy": pending || void 0,
            onClick: apply,
            children: pending ? /* @__PURE__ */ jsx(ButtonSpinner, { label: __("Vérification du code") }) : __("Appliquer")
          }
        )
      ] }),
      error && /* @__PURE__ */ jsx("p", { id: "chic-promo-code-error", className: "chic-promo-code__error", role: "alert", children: error })
    ] }) : /* @__PURE__ */ jsxs("button", { type: "button", className: "chic-promo-code__trigger", onClick: () => setEditing(true), children: [
      /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "13", height: "13", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "M12 5v14M5 12h14", stroke: "currentColor", strokeWidth: "2", strokeLinecap: "round" }) }),
      __("Ajouter un code promo")
    ] })
  ] });
}
export {
  PromoCode
};
