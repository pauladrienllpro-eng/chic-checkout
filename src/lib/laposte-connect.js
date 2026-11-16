async function fetchLaPosteParcelPointLabel(shippingRateId, packageKey = "0") {
  const data = window.laposteproexpData;
  if (!data?.ajaxurl || !data?.getShippingMethodExtraLabelNonce) {
    return null;
  }
  const params = new URLSearchParams({
    action: "laposteproexp_get_shipping_method_extra_label",
    shippingMethod: shippingRateId,
    packageKey,
    _wpnonce: data.getShippingMethodExtraLabelNonce
  });
  const response = await fetch(`${data.ajaxurl}?${params.toString()}`, { credentials: "same-origin" });
  if (!response.ok) {
    return null;
  }
  const json = await response.json();
  return json?.success && json.data?.label ? json.data.label : null;
}
export {
  fetchLaPosteParcelPointLabel
};
