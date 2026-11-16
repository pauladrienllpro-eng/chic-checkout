function shiftTotals(cart, deltaMinor) {
  return {
    ...cart.totals,
    total_items: String(Number(cart.totals.total_items) + deltaMinor),
    total_price: String(Number(cart.totals.total_price) + deltaMinor)
  };
}
function withQuantity(cart, key, quantity) {
  const item = cart.items.find((candidate) => candidate.key === key);
  if (!item || item.quantity === quantity || item.quantity < 1) {
    return cart;
  }
  const oldLine = Number(item.totals.line_total);
  const newLine = Math.round(oldLine / item.quantity * quantity);
  return {
    ...cart,
    items: cart.items.map(
      (candidate) => candidate.key === key ? { ...candidate, quantity, totals: { ...candidate.totals, line_total: String(newLine) } } : candidate
    ),
    totals: shiftTotals(cart, newLine - oldLine)
  };
}
function withoutItem(cart, key) {
  const item = cart.items.find((candidate) => candidate.key === key);
  if (!item) {
    return cart;
  }
  return {
    ...cart,
    items: cart.items.filter((candidate) => candidate.key !== key),
    totals: shiftTotals(cart, -Number(item.totals.line_total))
  };
}
let latestMutation = 0;
function startMutation() {
  latestMutation += 1;
  return latestMutation;
}
function isLatestMutation(id) {
  return id === latestMutation;
}
let queue = Promise.resolve();
function runSerial(task) {
  const run = queue.then(task, task);
  queue = run.catch(() => void 0);
  return run;
}
export {
  isLatestMutation,
  runSerial,
  startMutation,
  withQuantity,
  withoutItem
};
