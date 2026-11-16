import { jsx, jsxs, Fragment } from "react/jsx-runtime";
import { useState, useRef, useEffect } from "react";
import { createPortal } from "react-dom";
import { useSheetTransition, trapFocus, popinRoot } from "../hooks/useSheetTransition.js";
import { isContactAvailableNow, formatContactHours } from "../lib/contact-hours.js";
import { __ } from "../lib/i18n.js";
import { countBrowser } from "../lib/stats.js";
function getChannelLabels() {
  return {
    page: __("Formulaire de contact"),
    email: __("Email"),
    whatsapp: __("WhatsApp"),
    phone: __("Téléphone")
  };
}
function ChatIcon() {
  return /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "20", height: "20", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx(
    "path",
    {
      d: "M7.9 20A9 9 0 1 0 4 16.1L2 22Z",
      stroke: "currentColor",
      strokeWidth: "2",
      strokeLinecap: "round",
      strokeLinejoin: "round"
    }
  ) });
}
function CloseIcon() {
  return /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "18", height: "18", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "m6 6 12 12M18 6 6 18", stroke: "currentColor", strokeWidth: "1.75", strokeLinecap: "round" }) });
}
function PageIcon() {
  return /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "20", height: "20", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx(
    "path",
    {
      d: "M6 3h9l4 4v14H6z M14 3v5h5 M9 12h7 M9 16h7",
      stroke: "currentColor",
      strokeWidth: "1.5",
      strokeLinecap: "round",
      strokeLinejoin: "round"
    }
  ) });
}
function EmailIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "20", height: "20", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("rect", { x: "3", y: "5", width: "18", height: "14", rx: "2", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("path", { d: "m4 6.5 8 6 8-6", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })
  ] });
}
function WhatsAppIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "20", height: "20", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx(
      "path",
      {
        d: "M12 3a8 8 0 0 0-6.9 12L4 21l6.2-1.1A8 8 0 1 0 12 3Z",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinejoin: "round"
      }
    ),
    /* @__PURE__ */ jsx("path", { d: "M8.7 8.6c.3 3.2 2.8 5.6 6 6l1-1.3-2.3-1-1 1a5 5 0 0 1-2.4-2.4l1-1-1-2.3Z", fill: "currentColor" })
  ] });
}
function PhoneIcon() {
  return /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "20", height: "20", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx(
    "path",
    {
      d: "M6.6 10.8c1.4 2.8 3.8 5.2 6.6 6.6l2.2-2.2c.3-.3.7-.4 1-.2 1.1.4 2.3.6 3.6.6.6 0 1 .4 1 1V20c0 .6-.4 1-1 1C10.8 21 3 13.2 3 3.7c0-.6.4-1 1-1h3.4c.6 0 1 .4 1 1 0 1.3.2 2.5.6 3.6.1.3 0 .7-.2 1L6.6 10.8Z",
      stroke: "currentColor",
      strokeWidth: "1.5",
      strokeLinecap: "round",
      strokeLinejoin: "round"
    }
  ) });
}
const CHANNEL_ICONS = {
  page: PageIcon,
  email: EmailIcon,
  whatsapp: WhatsAppIcon,
  phone: PhoneIcon
};
function isExternalChannel(type) {
  return type === "whatsapp";
}
function ContactBubble({ style, label, channels, iconUrl, popupTitle, popupText }) {
  const [open, setOpen] = useState(false);
  const channelLabels = getChannelLabels();
  const { mounted, visible } = useSheetTransition(open);
  const panelRef = useRef(null);
  const closeRef = useRef(null);
  useEffect(() => {
    if (visible) {
      closeRef.current?.focus({ preventScroll: true });
    }
  }, [visible]);
  useEffect(() => {
    if (!open) {
      return;
    }
    function handleKeyDown(event) {
      if (event.key === "Escape") {
        setOpen(false);
      }
    }
    document.addEventListener("keydown", handleKeyDown);
    return () => document.removeEventListener("keydown", handleKeyDown);
  }, [open]);
  if (channels.length === 0) {
    return null;
  }
  const className = `chic-contact chic-contact--${style}`;
  const ariaLabel = style === "text" ? void 0 : label;
  let content = /* @__PURE__ */ jsx(ChatIcon, {});
  if (style === "text") {
    content = label;
  } else if (iconUrl) {
    content = /* @__PURE__ */ jsx("img", { className: "chic-contact__icon", src: iconUrl, alt: "" });
  }
  if (channels.length === 1) {
    const channel = channels[0];
    return /* @__PURE__ */ jsx(
      "a",
      {
        className,
        href: channel.href,
        "aria-label": ariaLabel,
        onClick: () => countBrowser("contact_clicked", ["channel", channel.type]),
        target: isExternalChannel(channel.type) ? "_blank" : void 0,
        rel: isExternalChannel(channel.type) ? "noopener noreferrer" : void 0,
        children: content
      }
    );
  }
  return /* @__PURE__ */ jsxs(Fragment, { children: [
    /* @__PURE__ */ jsx("button", { type: "button", className, "aria-label": ariaLabel, onClick: () => setOpen(true), children: content }),
    mounted && createPortal(
      /* @__PURE__ */ jsxs(
        "div",
        {
          className: `chic-contact-popup ${visible ? "is-open" : ""}`,
          role: "dialog",
          "aria-modal": "true",
          "aria-label": popupTitle,
          onKeyDown: (event) => trapFocus(event, panelRef.current),
          children: [
            /* @__PURE__ */ jsx("div", { className: "chic-contact-popup__backdrop", onClick: () => setOpen(false) }),
            /* @__PURE__ */ jsxs("div", { className: "chic-contact-popup__panel", ref: panelRef, children: [
              /* @__PURE__ */ jsx("span", { className: "chic-contact-popup__grabber", "aria-hidden": "true" }),
              /* @__PURE__ */ jsxs("div", { className: "chic-contact-popup__header", children: [
                /* @__PURE__ */ jsx("p", { className: "chic-contact-popup__title", children: popupTitle }),
                /* @__PURE__ */ jsx("button", { ref: closeRef, type: "button", className: "chic-contact-popup__close", onClick: () => setOpen(false), "aria-label": __("Fermer"), children: /* @__PURE__ */ jsx(CloseIcon, {}) })
              ] }),
              popupText && /* @__PURE__ */ jsx("p", { className: "chic-contact-popup__text", children: popupText }),
              /* @__PURE__ */ jsx("ul", { className: "chic-contact-popup__list", children: channels.map((channel) => {
                const ChannelIcon = CHANNEL_ICONS[channel.type];
                const available = !channel.hours || isContactAvailableNow(channel.hours);
                return /* @__PURE__ */ jsx("li", { children: /* @__PURE__ */ jsxs(
                  "a",
                  {
                    href: channel.href,
                    onClick: () => countBrowser("contact_clicked", ["channel", channel.type]),
                    target: isExternalChannel(channel.type) ? "_blank" : void 0,
                    rel: isExternalChannel(channel.type) ? "noopener noreferrer" : void 0,
                    children: [
                      /* @__PURE__ */ jsxs("span", { className: "chic-contact-popup__row-icon", children: [
                        /* @__PURE__ */ jsx(ChannelIcon, {}),
                        channel.hours && /* @__PURE__ */ jsx(
                          "span",
                          {
                            className: `chic-contact-popup__status-dot ${available ? "is-available" : "is-unavailable"}`,
                            "aria-hidden": "true"
                          }
                        )
                      ] }),
                      /* @__PURE__ */ jsxs("span", { className: "chic-contact-popup__row-text", children: [
                        /* @__PURE__ */ jsx("span", { className: "chic-contact-popup__row-label", children: channelLabels[channel.type] }),
                        channel.hours && /* @__PURE__ */ jsx("span", { className: "chic-contact-popup__row-hours", children: formatContactHours(channel.hours) })
                      ] })
                    ]
                  }
                ) }, channel.type);
              }) })
            ] })
          ]
        }
      ),
      popinRoot()
    )
  ] });
}
export {
  ContactBubble
};
