import "./styles/cart.css";
import "./styles/cart-login.css";
import { jsx } from "react/jsx-runtime";
import { CartApp } from "./CartApp.js";
import { ErrorBoundary } from "./components/ErrorBoundary.js";
import { boot } from "./lib/boot.js";
import { ConfigProvider } from "./lib/config.js";
/* empty css                */
/* empty css                      */
boot((config) => /* @__PURE__ */ jsx(ErrorBoundary, { app: "cart", children: /* @__PURE__ */ jsx(ConfigProvider, { config, children: /* @__PURE__ */ jsx(CartApp, {}) }) }));
