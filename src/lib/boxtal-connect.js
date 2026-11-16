async function fetchBoxtalParcelPointLabel(shippingRateId, packageKey = "0") {
  const bwData = window.bwData;
  if (!bwData?.ajaxurl || !bwData?.getShippingMethodExtraLabelNonce) {
    return null;
  }
  const params = new URLSearchParams({
    action: "bw_get_shipping_method_extra_label",
    shippingMethod: shippingRateId,
    packageKey,
    _wpnonce: bwData.getShippingMethodExtraLabelNonce
  });
  const response = await fetch(`${bwData.ajaxurl}?${params.toString()}`, { credentials: "same-origin" });
  if (!response.ok) {
    return null;
  }
  const data = await response.json();
  return data?.success && data.data?.label ? data.data.label : null;
}
export {
  fetchBoxtalParcelPointLabel
};
