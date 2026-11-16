let loadPromise = null;
function loadGooglePlaces(apiKey) {
  if (window.google?.maps?.places) {
    return Promise.resolve();
  }
  if (loadPromise) {
    return loadPromise;
  }
  loadPromise = new Promise((resolve, reject) => {
    const script = document.createElement("script");
    script.src = `https://maps.googleapis.com/maps/api/js?key=${encodeURIComponent(apiKey)}&libraries=places`;
    script.async = true;
    script.onload = () => resolve();
    script.onerror = () => {
      loadPromise = null;
      reject(new Error("Impossible de charger Google Places"));
    };
    document.head.appendChild(script);
  });
  return loadPromise;
}
function parseAddressComponents(place) {
  const components = place.address_components ?? [];
  function component(type, useShortName = false) {
    const match = components.find((entry) => entry.types.includes(type));
    if (!match) {
      return "";
    }
    return useShortName ? match.short_name : match.long_name;
  }
  const streetNumber = component("street_number");
  const route = component("route");
  const address1 = [streetNumber, route].filter(Boolean).join(" ");
  return {
    address1,
    city: component("locality") || component("postal_town"),
    postcode: component("postal_code"),
    country: component("country", true),
    // Région (État américain, province…) : code court et nom, rapprochés des régions
    // WooCommerce du pays par matchState().
    stateShort: component("administrative_area_level_1", true),
    stateLong: component("administrative_area_level_1")
  };
}
export {
  loadGooglePlaces,
  parseAddressComponents
};
