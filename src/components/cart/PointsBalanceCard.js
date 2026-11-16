import { jsxs, jsx } from "react/jsx-runtime";
function PointsBalanceCard({ mycredData }) {
  if (mycredData.cost === null || mycredData.balance === null) {
    return null;
  }
  return /* @__PURE__ */ jsxs("div", { className: "chic-loyalty-card", children: [
    /* @__PURE__ */ jsx("span", { className: "chic-loyalty-card__icon", "aria-hidden": "true", children: /* @__PURE__ */ jsx("svg", { viewBox: "0 0 24 24", width: "18", height: "18", fill: "none", stroke: "currentColor", strokeWidth: "1.6", strokeLinejoin: "round", children: /* @__PURE__ */ jsx("path", { d: "M12 3.5 14.5 9.3 21 10.1 16.2 14.4 17.5 20.5 12 17.3 6.5 20.5 7.8 14.4 3 10.1 9.5 9.3Z" }) }) }),
    /* @__PURE__ */ jsxs("div", { className: "chic-loyalty-card__rows", children: [
      /* @__PURE__ */ jsxs("p", { className: "chic-loyalty-card__text", children: [
        mycredData.total_label,
        " ",
        /* @__PURE__ */ jsx("strong", { children: mycredData.cost })
      ] }),
      /* @__PURE__ */ jsxs("p", { className: "chic-loyalty-card__text", children: [
        mycredData.balance_label,
        " ",
        /* @__PURE__ */ jsx("strong", { children: mycredData.balance })
      ] })
    ] })
  ] });
}
export {
  PointsBalanceCard
};
