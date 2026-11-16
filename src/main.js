import "./styles/checkout.css";
import { jsx } from "react/jsx-runtime";
import App from "./App.js";
import { ErrorBoundary } from "./components/ErrorBoundary.js";
import { boot } from "./lib/boot.js";
import { ConfigProvider } from "./lib/config.js";
/* empty css                    */
boot((config) => /* @__PURE__ */ jsx(ErrorBoundary, { app: "checkout", children: /* @__PURE__ */ jsx(ConfigProvider, { config, children: /* @__PURE__ */ jsx(App, {}) }) }));
