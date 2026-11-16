function safeGetItem(key) {
  try {
    return window.localStorage.getItem(key);
  } catch {
    return null;
  }
}
function safeSetItem(key, value) {
  try {
    window.localStorage.setItem(key, value);
  } catch {
  }
}
export {
  safeGetItem,
  safeSetItem
};
