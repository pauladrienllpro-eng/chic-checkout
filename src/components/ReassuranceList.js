import { jsx, jsxs } from "react/jsx-runtime";
function ChevronIcon() {
  return /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "16", height: "16", fill: "none", "aria-hidden": "true", children: /* @__PURE__ */ jsx("path", { d: "m9 6 6 6-6 6", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" }) });
}
function CardPresetIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "26", height: "26", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("rect", { x: "2", y: "5", width: "20", height: "14", rx: "2", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("line", { x1: "2", y1: "10", x2: "22", y2: "10", stroke: "currentColor", strokeWidth: "1.5" })
  ] });
}
function ShieldPresetIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "26", height: "26", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx(
      "path",
      {
        d: "M12 3l7 3v6c0 4.5-3 8-7 9-4-1-7-4.5-7-9V6z",
        stroke: "currentColor",
        strokeWidth: "1.5",
        strokeLinejoin: "round"
      }
    ),
    /* @__PURE__ */ jsx("path", { d: "M9 12l2 2 4-4", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })
  ] });
}
function DeliveryPresetIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "26", height: "26", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("rect", { x: "1", y: "7", width: "13", height: "9", rx: "1", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("path", { d: "M14 10h4l3 3v3h-7", stroke: "currentColor", strokeWidth: "1.5", strokeLinejoin: "round" }),
    /* @__PURE__ */ jsx("circle", { cx: "5.5", cy: "18", r: "1.5", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("circle", { cx: "17.5", cy: "18", r: "1.5", stroke: "currentColor", strokeWidth: "1.5" })
  ] });
}
function ReturnPresetIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "26", height: "26", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("path", { d: "M9 14 4 9l5-5", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" }),
    /* @__PURE__ */ jsx("path", { d: "M4 9h10a6 6 0 0 1 0 12h-2", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round", strokeLinejoin: "round" })
  ] });
}
function SupportPresetIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "26", height: "26", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("path", { d: "M4 13v-1a8 8 0 0 1 16 0v1", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round" }),
    /* @__PURE__ */ jsx("rect", { x: "2", y: "13", width: "4", height: "6", rx: "1.5", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("rect", { x: "18", y: "13", width: "4", height: "6", rx: "1.5", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("path", { d: "M20 19v1a3 3 0 0 1-3 3h-3", stroke: "currentColor", strokeWidth: "1.5", strokeLinecap: "round" })
  ] });
}
function GiftPresetIcon() {
  return /* @__PURE__ */ jsxs("svg", { viewBox: "0 0 24 24", width: "26", height: "26", fill: "none", "aria-hidden": "true", children: [
    /* @__PURE__ */ jsx("rect", { x: "3", y: "8", width: "18", height: "13", rx: "1", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("rect", { x: "2", y: "5", width: "20", height: "4", rx: "1", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("line", { x1: "12", y1: "5", x2: "12", y2: "21", stroke: "currentColor", strokeWidth: "1.5" }),
    /* @__PURE__ */ jsx("path", { d: "M12 5C9.5 5 7 3.5 7 2s2.5-1 3.5.5S12 5 12 5Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinejoin: "round" }),
    /* @__PURE__ */ jsx("path", { d: "M12 5c2.5 0 5-1.5 5-3s-2.5-1-3.5.5S12 5 12 5Z", stroke: "currentColor", strokeWidth: "1.5", strokeLinejoin: "round" })
  ] });
}
const PRESET_ICONS = {
  card: CardPresetIcon,
  shield: ShieldPresetIcon,
  delivery: DeliveryPresetIcon,
  return: ReturnPresetIcon,
  support: SupportPresetIcon,
  gift: GiftPresetIcon
};
function ReassuranceList({ items }) {
  if (items.length === 0) {
    return null;
  }
  return /* @__PURE__ */ jsx("div", { className: "chic-reassurance", children: items.map((item, index) => {
    const PresetIcon = item.iconPreset ? PRESET_ICONS[item.iconPreset] : void 0;
    return /* @__PURE__ */ jsxs("div", { className: "chic-reassurance__item", children: [
      PresetIcon ? /* @__PURE__ */ jsx("span", { className: "chic-reassurance__icon chic-reassurance__icon--preset", children: /* @__PURE__ */ jsx(PresetIcon, {}) }) : false,
      /* @__PURE__ */ jsxs("div", { className: "chic-reassurance__body", children: [
        /* @__PURE__ */ jsx("strong", { children: item.title }),
        item.text && /* @__PURE__ */ jsx("span", { children: item.text }),
        item.paymentIcons.length > 0 && /* @__PURE__ */ jsx("div", { className: "chic-reassurance__payment-icons", children: item.paymentIcons.map((gateway) => (
          // HTML fourni par la passerelle elle-même, comme sous le bouton du
          // panier (voir CartSummary.tsx et Cart_Assets::resolve_payment_icons()).
          /* @__PURE__ */ jsx(
            "span",
            {
              className: "chic-payment-icons__icon",
              "data-gateway": gateway.id,
              title: gateway.title,
              dangerouslySetInnerHTML: { __html: gateway.iconHtml }
            },
            gateway.id
          )
        )) })
      ] }),
      item.linkUrl && /* @__PURE__ */ jsx(
        "a",
        {
          href: item.linkUrl,
          target: "_blank",
          rel: "noopener noreferrer",
          className: "chic-reassurance__link",
          "aria-label": item.title,
          children: /* @__PURE__ */ jsx(ChevronIcon, {})
        }
      )
    ] }, index);
  }) });
}
export {
  ReassuranceList
};
