import { __ } from "./i18n.js";
function getDayAbbreviations() {
  return ["", __("Lun"), __("Mar"), __("Mer"), __("Jeu"), __("Ven"), __("Sam"), __("Dim")];
}
function formatDayRanges(days) {
  const dayAbbreviations = getDayAbbreviations();
  const sorted = [...days].sort((a, b) => a - b);
  const ranges = [];
  let rangeStart = sorted[0];
  let previous = sorted[0];
  for (let i = 1; i <= sorted.length; i++) {
    const current = sorted[i];
    if (current !== previous + 1) {
      ranges.push(rangeStart === previous ? dayAbbreviations[rangeStart] : `${dayAbbreviations[rangeStart]}-${dayAbbreviations[previous]}`);
      rangeStart = current;
    }
    previous = current;
  }
  return ranges.join(", ");
}
function formatTime(time) {
  const [hours, minutes] = time.split(":").map(Number);
  const date = new Date(2e3, 0, 1, hours, minutes || 0);
  const options = minutes ? { hour: "numeric", minute: "2-digit" } : { hour: "numeric" };
  try {
    return new Intl.DateTimeFormat(document.documentElement.lang || void 0, options).format(date);
  } catch {
    return time;
  }
}
function formatContactHours(hours) {
  return `${formatDayRanges(hours.days)}, ${formatTime(hours.start)} - ${formatTime(hours.end)}`;
}
function isContactAvailableNow(hours) {
  const now = /* @__PURE__ */ new Date();
  const isoDay = now.getDay() === 0 ? 7 : now.getDay();
  if (!hours.days.includes(isoDay)) {
    return false;
  }
  const currentTime = `${String(now.getHours()).padStart(2, "0")}:${String(now.getMinutes()).padStart(2, "0")}`;
  return currentTime >= hours.start && currentTime <= hours.end;
}
export {
  formatContactHours,
  isContactAvailableNow
};
