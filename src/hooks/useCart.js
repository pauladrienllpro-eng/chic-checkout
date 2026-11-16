import { useState, useCallback, useEffect } from "react";
import { storeApi } from "../lib/store-api-client.js";
import { useConfig } from "../lib/config.js";
function useCart() {
  const { initialCart } = useConfig();
  const [cart, setCart] = useState(initialCart);
  const [loading, setLoading] = useState(!initialCart);
  const refresh = useCallback(async () => {
    setLoading(true);
    const data = await storeApi.getCart();
    setCart(data);
    setLoading(false);
  }, []);
  useEffect(() => {
    if (!initialCart) {
      refresh();
    }
  }, [initialCart, refresh]);
  return { cart, loading, refresh, setCart };
}
export {
  useCart
};
