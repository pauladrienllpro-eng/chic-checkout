import { jsxs, jsx } from "react/jsx-runtime";
import { Logo } from "./Logo.js";
import { ContactBubble } from "./ContactBubble.js";
function Header({
  backUrl,
  backLabel,
  logoUrl,
  logoText,
  homeUrl,
  contactStyle,
  contactLabel,
  contactIconUrl,
  contactPopupTitle,
  contactPopupText,
  contactChannels,
  accountSlot
}) {
  const contact = /* @__PURE__ */ jsx(
    ContactBubble,
    {
      style: contactStyle,
      label: contactLabel,
      iconUrl: contactIconUrl,
      popupTitle: contactPopupTitle,
      popupText: contactPopupText,
      channels: contactChannels
    }
  );
  return /* @__PURE__ */ jsxs("header", { className: "chic-header", children: [
    /* @__PURE__ */ jsxs("a", { className: "chic-header__back", href: backUrl, children: [
      /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "16", height: "16", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "m15 6-6 6 6 6", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" }) }),
      backLabel
    ] }),
    /* @__PURE__ */ jsx(Logo, { logoUrl, logoText, homeUrl }),
    accountSlot ? /* @__PURE__ */ jsxs("div", { className: "chic-header__actions", children: [
      accountSlot,
      contact
    ] }) : contact
  ] });
}
export {
  Header
};
