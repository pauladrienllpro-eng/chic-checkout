import { jsxs, jsx } from "react/jsx-runtime";
import { useState, useEffect, useMemo } from "react";
import { Header } from "./components/Header.js";
import { AccountChip } from "./components/cart/AccountChip.js";
import { LoginSheet } from "./components/cart/LoginSheet.js";
import { Footer } from "./components/Footer.js";
import { ReassuranceList } from "./components/ReassuranceList.js";
import { CartItemsList } from "./components/cart/CartItemsList.js";
import { useConfig, computeColors } from "./lib/config.js";
import "./lib/optimistic-cart.js";
import { initStats, countBrowser } from "./lib/stats.js";
import "react-dom";
import { toNumericAmount } from "./lib/money.js";
import { UpsellCarousel } from "./components/cart/UpsellCarousel.js";
import { useMediaQuery } from "./hooks/useMediaQuery.js";
import { CartExpirationBanner } from "./components/cart/CartExpirationBanner.js";
import { safeGetItem, safeSetItem } from "./lib/storage.js";
import { CartSummary } from "./components/cart/CartSummary.js";
import { EmptyCart } from "./components/cart/EmptyCart.js";
import { useCart } from "./hooks/useCart.js";
const DEFAULT_CART_BLOCKS_ORDER = {
  desktop: ["packaging", "gift_message", "gift_carousel", "upsell"],
  mobile: ["packaging", "gift_message", "gift_carousel", "summary", "upsell"]
};
const HIGH_CONTRAST_STORAGE_KEY = "chic-checkout-high-contrast";
const HIGH_CONTRAST_VARS = {
  "--chic-bg": "#ffffff",
  "--chic-surface": "#ffffff",
  "--chic-border": "#000000",
  "--chic-border-strong": "#000000",
  "--chic-text": "#000000",
  "--chic-text-muted": "#000000",
  "--chic-accent": "#000000",
  "--chic-cta": "#000000"
};
function CartApp() {
  const config = useConfig();
  const colors = computeColors(config);
  const { cart, setCart, refresh } = useCart();
  const [highContrast, setHighContrast] = useState(
    () => safeGetItem(HIGH_CONTRAST_STORAGE_KEY) === "1"
  );
  useEffect(() => initStats("cart"), []);
  const [loginSheet, setLoginSheet] = useState(null);
  function toggleHighContrast() {
    if (!highContrast) {
      countBrowser("high_contrast_used");
    }
    setHighContrast((current) => {
      const next = !current;
      safeSetItem(HIGH_CONTRAST_STORAGE_KEY, next ? "1" : "0");
      return next;
    });
  }
  const isEmpty = cart !== null && cart.items.length === 0;
  const packagingIds = useMemo(
    () => /* @__PURE__ */ new Set([]),
    []
  );
  const giftIds = useMemo(
    () => /* @__PURE__ */ new Set([]),
    []
  );
  const regularItems = cart?.items.filter((item) => !packagingIds.has(item.id) && !giftIds.has(item.id)) ?? [];
  const packagingItem = cart?.items.find((item) => packagingIds.has(item.id)) ?? null;
  cart?.items.find((item) => giftIds.has(item.id)) ?? null;
  const packagingExtension = void 0;
  const [packagingNotice, setPackagingNotice] = useState("");
  useEffect(() => {
  }, [packagingExtension?.removed]);
  const merchandiseSubtotalMinor = regularItems.reduce(
    (sum, item) => sum + Number(item.totals.line_total),
    0
  );
  cart ? toNumericAmount(String(merchandiseSubtotalMinor), cart.totals.currency_minor_unit) : 0;
  const isDesktop = useMediaQuery("(min-width: 900px)");
  const blocksOrder = DEFAULT_CART_BLOCKS_ORDER;
  const summaryIndex = blocksOrder.mobile.indexOf("summary");
  const mainBlocks = isDesktop ? blocksOrder.desktop : blocksOrder.mobile.slice(0, summaryIndex < 0 ? void 0 : summaryIndex);
  const sideBlocks = isDesktop || summaryIndex < 0 ? [] : blocksOrder.mobile.slice(summaryIndex + 1);
  function renderBlock(key) {
    if (!cart) {
      return null;
    }
    switch (key) {
      case "upsell":
        return config.cartUpsell.enabled && config.cartUpsell.products.length > 0 ? /* @__PURE__ */ jsx(
          UpsellCarousel,
          {
            title: config.cartUpsell.title,
            products: config.cartUpsell.products,
            cart,
            maxCount: config.cartUpsell.maxCount,
            showToast: sideBlocks.includes("upsell"),
            onChange: setCart
          },
          key
        ) : null;
      case "packaging": {
        return null;
      }
      case "gift_message": {
        return null;
      }
      case "gift_carousel": {
        return null;
      }
      default:
        return null;
    }
  }
  return /* @__PURE__ */ jsxs(
    "div",
    {
      className: `chic-checkout chic-checkout--${config.styleMode} ${highContrast ? "chic-checkout--high-contrast" : ""}`,
      style: {
        "--chic-accent": colors.accent,
        "--chic-bg": colors.bg,
        "--chic-cta": colors.cta,
        ...highContrast ? HIGH_CONTRAST_VARS : {}
      },
      children: [
        /* @__PURE__ */ jsx(
          Header,
          {
            backUrl: config.backUrl,
            backLabel: config.backLabel,
            logoUrl: config.logoUrl,
            logoText: config.logoText,
            homeUrl: config.homeUrl,
            contactStyle: config.contactStyle,
            contactLabel: config.contactLabel,
            contactIconUrl: config.contactIconUrl,
            contactPopupTitle: config.contactPopupTitle,
            contactPopupText: config.contactPopupText,
            contactChannels: config.contactChannels,
            accountSlot: config.account && config.login ? /* @__PURE__ */ jsx(AccountChip, { onLogin: () => setLoginSheet("account") }) : void 0
          }
        ),
        /* @__PURE__ */ jsx(LoginSheet, { open: loginSheet !== null, reason: loginSheet ?? "account", onClose: () => setLoginSheet(null) }),
        false,
        cart && !isEmpty && config.cartExpirationMessage && /* @__PURE__ */ jsx(CartExpirationBanner, { message: config.cartExpirationMessage, items: cart.items, onExpire: refresh }),
        false,
        isEmpty && /* @__PURE__ */ jsx(EmptyCart, { shopUrl: config.backUrl, text: config.emptyCartText }),
        cart && !isEmpty && /* @__PURE__ */ jsx("div", { className: "chic-cart-shell", children: /* @__PURE__ */ jsxs("div", { className: "chic-cart-layout", children: [
          /* @__PURE__ */ jsxs("div", { className: "chic-cart-layout__main", children: [
            /* @__PURE__ */ jsx(CartItemsList, { items: regularItems, cart, imageSize: config.cartImageSize, onChange: setCart }),
            mainBlocks.map(renderBlock)
          ] }),
          /* @__PURE__ */ jsxs("div", { className: "chic-cart-layout__side", children: [
            /* @__PURE__ */ jsx(
              CartSummary,
              {
                cart,
                packagingItem,
                promoCodeEnabled: config.cartPromoCodeEnabled,
                checkoutUrl: config.checkoutUrl,
                paymentIcons: config.cartPaymentIcons,
                loyaltyEnabled: false,
                loyaltyText: "",
                express: void 0,
                onExpressLoginRequired: () => setLoginSheet("express"),
                onChange: setCart
              }
            ),
            sideBlocks.map(renderBlock),
            /* @__PURE__ */ jsx(ReassuranceList, { items: config.reassuranceItems })
          ] })
        ] }) }),
        /* @__PURE__ */ jsx(
          Footer,
          {
            links: config.footerLinks,
            highContrast,
            onToggleHighContrast: toggleHighContrast,
            contrastToggleEnabled: config.footerContrastToggleEnabled,
            contrastToggleLabel: config.footerContrastToggleLabel
          }
        )
      ]
    }
  );
}
export {
  CartApp
};
