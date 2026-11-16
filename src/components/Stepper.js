import { jsxs, jsx } from "react/jsx-runtime";
function Stepper({ steps, activeStep }) {
  const currentLabel = steps.find((step) => step.id === activeStep)?.label ?? "";
  return /* @__PURE__ */ jsxs("div", { className: "chic-stepper", children: [
    /* @__PURE__ */ jsx("div", { className: "chic-stepper__track", children: steps.map((step) => /* @__PURE__ */ jsx(
      "div",
      {
        className: `chic-stepper__segment ${step.id <= activeStep ? "is-filled" : ""}`
      },
      step.id
    )) }),
    /* @__PURE__ */ jsx("p", { className: "chic-stepper__label", children: currentLabel })
  ] });
}
export {
  Stepper
};
