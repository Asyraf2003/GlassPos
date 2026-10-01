(() => {
  "use strict";
  // One gate per independent listing/lookup. Invalidate before debounce, not at fetch time.
  const create = () => {
    let generation = 0, controller = null, timer = null;
    const invalidate = () => {
      generation += 1;
      clearTimeout(timer);
      timer = null;
      controller?.abort();
      controller = null;
    };
    const begin = () => {
      invalidate();
      const token = generation;
      const active = new AbortController();
      controller = active;
      return {
        signal: active.signal,
        isCurrent: () => token === generation && !active.signal.aborted,
        finish: () => { if (controller === active) controller = null; },
      };
    };
    const schedule = (callback, delay = 220) => {
      const token = generation;
      clearTimeout(timer);
      timer = setTimeout(() => {
        timer = null;
        if (token === generation) callback();
      }, delay);
    };
    return { invalidate, begin, schedule, pending: () => controller !== null };
  };
  const bind = ({ gate, input, form, getQuery, onQuery, load, delay = 220 }) => {
    if (!input) return;
    let needsDefault = Boolean(getQuery());
    const update = (immediate = false) => {
      const value = input.value.trim();
      const previous = getQuery();
      const wasPending = gate.pending();
      gate.invalidate();
      const query = value.length >= 2 ? value : "";
      onQuery(query);
      if (query || previous || wasPending) needsDefault = true;
      if (value.length === 1) return;
      // An unchanged empty default does not issue a search request. Clearing an
      // active query loads the existing unfiltered list, without a search parameter.
      if (!query && !previous && !needsDefault && !wasPending) return;
      const run = () => {
        needsDefault = Boolean(query);
        void load();
      };
      if (immediate) run();
      else gate.schedule(run, query ? delay : 160);
    };
    input.addEventListener("input", () => update());
    form?.addEventListener("submit", (event) => { event.preventDefault(); update(true); });
  };
  window.LiveSearch = { create, bind };
})();
