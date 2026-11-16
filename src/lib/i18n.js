const DOMAIN = "chic-checkout";
function textOverride(text) {
  {
    return void 0;
  }
}
function __(text) {
  const override = textOverride();
  if (override !== void 0) {
    return override;
  }
  return window.wp?.i18n?.__ ? window.wp.i18n.__(text, DOMAIN) : text;
}
export {
  __
};
