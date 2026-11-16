import { jsx, jsxs } from "react/jsx-runtime";
import { useMemo, useEffect } from "react";
function CartExpirationBanner({ message, items, onExpire }) {
  const earliest = useMemo(() => {
    if (!message) {
      return null;
    }
    const candidates = items.map((item) => item.extensions?.["booking-activities"]).filter(
      (extension) => !!extension && ["in_cart", "pending"].includes(extension.booking_status) && !!extension.expiration_date
    ).map((extension) => ({
      // Format renvoyé par le plugin : 'Y-m-d\TH_i_s' (underscores à la place des
      // ':', contrainte d'encodage dans un nom de classe CSS ailleurs dans le
      // plugin) — un simple replace suffit à en faire une date ISO valide, celle
      // qu'attend aussi bien `new Date()` ici que moment.utc() côté plugin (voir
      // bookacti_wc_refresh_countdown() dans woocommerce-frontend.js).
      iso: `${extension.expiration_date.replace(/_/g, ":")}Z`,
      time: (/* @__PURE__ */ new Date(`${extension.expiration_date.replace(/_/g, ":")}Z`)).getTime()
    })).filter((candidate) => !Number.isNaN(candidate.time));
    if (!candidates.length) {
      return null;
    }
    return candidates.reduce((min, candidate) => candidate.time < min.time ? candidate : min);
  }, [message, items]);
  useEffect(() => {
    if (!earliest) {
      return;
    }
    const msRemaining = earliest.time - Date.now();
    if (msRemaining <= 0) {
      onExpire();
      return;
    }
    const timeout = window.setTimeout(onExpire, msRemaining);
    return () => window.clearTimeout(timeout);
  }, [earliest?.time]);
  if (!earliest || earliest.time <= Date.now()) {
    return null;
  }
  const [before, after] = message.split("{countdown}");
  return /* @__PURE__ */ jsx("div", { className: "chic-cart-expiration", children: /* @__PURE__ */ jsxs("span", { children: [
    before,
    /* @__PURE__ */ jsx("span", { className: "bookacti-countdown", "data-expiration-date": earliest.iso }),
    after
  ] }) });
}
export {
  CartExpirationBanner
};
