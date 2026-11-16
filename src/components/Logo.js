import { jsx } from "react/jsx-runtime";
function Logo({ logoUrl, logoText, homeUrl }) {
  if (logoUrl) {
    return /* @__PURE__ */ jsx("a", { className: "chic-logo", href: homeUrl, children: /* @__PURE__ */ jsx("img", { src: logoUrl, alt: "" }) });
  }
  return /* @__PURE__ */ jsx("a", { className: "chic-logo chic-logo--text", href: homeUrl, children: /* @__PURE__ */ jsx("span", { children: logoText }) });
}
export {
  Logo
};
