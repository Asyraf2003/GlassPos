(() => {
  window.bindProcurementProductSearch = ({ input, results, config, select, save, submit, previous }) => {
    let timer, controller, request = 0, active = -1;
    const close = () => {
      clearTimeout(timer); controller?.abort(); request += 1; active = -1;
      results.replaceChildren(); results.classList.add("d-none");
      input.setAttribute("aria-expanded", "false"); input.removeAttribute("aria-activedescendant");
    };
    const showMessage = (message) => {
      const text = document.createElement("div"); text.className = "list-group-item"; text.textContent = message;
      results.replaceChildren(text); results.classList.remove("d-none"); input.setAttribute("aria-expanded", "true");
    };
    const load = async () => {
      const query = input.value.trim();
      if (query.length < 2) { close(); return; }
      const token = ++request;
      controller?.abort(); controller = new AbortController();
      try {
        const response = await fetch(`${config.lookupEndpoint}?q=${encodeURIComponent(query)}`, { signal: controller.signal, headers: { Accept: "application/json" } });
        const data = await response.json();
        if (token !== request) return;
        if (!response.ok || !data.success) throw new Error("lookup");
        const rows = (data.data?.rows || []).slice(0, 50);
        results.replaceChildren(); active = -1;
        if (!rows.length) {
          showMessage("Produk tidak ditemukan.");
          if (config.createProductUrl) {
            const create = document.createElement("button"); create.type = "button";
            create.className = "list-group-item list-group-item-action"; create.textContent = "Buat produk baru";
            create.addEventListener("click", () => location.assign(config.createProductUrl)); results.append(create);
          }
          return;
        }
        rows.forEach((row, index) => {
          const button = document.createElement("button"); button.type = "button";
          button.className = "list-group-item list-group-item-action"; button.dataset.productChoice = "1";
          button.id = `procurement-product-choice-${index}`; button.setAttribute("role", "option"); button.setAttribute("aria-selected", "false");
          const name = document.createElement("div"); name.className = "fw-semibold"; name.textContent = window.ProductDisplay.identity(row);
          const price = document.createElement("small"); price.className = "text-muted"; price.textContent = window.ProductDisplay.price(row);
          button.append(name, price);
          button.addEventListener("click", (event) => {
            event.stopPropagation();
            if (!select(row)) { close(); showMessage("Produk sudah dipakai dalam rincian."); return; }
            input.value = ""; close(); save();
          });
          results.append(button);
        });
        results.classList.remove("d-none"); input.setAttribute("aria-expanded", "true");
      } catch (error) { if (token === request && error.name !== "AbortError") showMessage("Gagal memuat produk."); }
    };
    input.addEventListener("input", () => { close(); if (input.value.trim().length >= 2) timer = setTimeout(load, 250); });
    input.addEventListener("focus", () => { if (input.value.trim().length >= 2) void load(); });
    input.addEventListener("keydown", (event) => {
      if ((event.ctrlKey || event.metaKey) && event.key.toLowerCase() === "s") { event.preventDefault(); submit(); return; }
      if (event.ctrlKey && event.key.toLowerCase() === "k") { event.preventDefault(); if (config.createProductUrl) location.assign(config.createProductUrl); return; }
      if (event.key === "Escape") { event.preventDefault(); close(); return; }
      if (event.ctrlKey && event.key === "Enter") { event.preventDefault(); close(); input.focus(); return; }
      if (event.shiftKey && event.key === "Enter") { event.preventDefault(); close(); previous?.(); return; }
      const buttons = [...results.querySelectorAll("button")];
      if (["ArrowDown", "ArrowUp"].includes(event.key) && buttons.length) {
        event.preventDefault(); active = Math.max(0, Math.min(buttons.length - 1, active + (event.key === "ArrowDown" ? 1 : -1)));
        buttons.forEach((button, index) => { button.classList.toggle("active", index === active); button.setAttribute("aria-selected", String(index === active)); });
        input.setAttribute("aria-activedescendant", buttons[active].id); buttons[active].scrollIntoView({ block: "nearest" });
      }
      if (event.key === "Enter") { event.preventDefault(); buttons[Math.max(0, active)]?.click(); }
    });
    document.addEventListener("click", (event) => { if (!input.parentElement.contains(event.target)) close(); });
    return { close };
  };
})();
