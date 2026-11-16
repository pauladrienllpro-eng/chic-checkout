function toNumericAmount(minorUnitAmount, currencyMinorUnit) {
  return Number(minorUnitAmount) / 10 ** currencyMinorUnit;
}
function formatMoney(minorUnitAmount, totals) {
  const value = toNumericAmount(minorUnitAmount, totals.currency_minor_unit);
  const [integerPart, decimalPart] = value.toFixed(totals.currency_minor_unit).split(".");
  const withThousands = integerPart.replace(/\B(?=(\d{3})+(?!\d))/g, totals.currency_thousand_separator);
  const formattedNumber = decimalPart ? `${withThousands}${totals.currency_decimal_separator}${decimalPart}` : withThousands;
  return `${totals.currency_prefix}${formattedNumber}${totals.currency_suffix}`;
}
export {
  formatMoney,
  toNumericAmount
};
