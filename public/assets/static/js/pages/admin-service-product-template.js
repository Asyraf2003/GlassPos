(() => {
  const root = document.querySelector("[data-package-picker]");
  if (!root) return;
  const products = JSON.parse(root.dataset.products);
  const services = JSON.parse(root.dataset.services);
  const ids = [...root.querySelectorAll("[data-package-product-id]")];
  const serviceId = root.querySelector("[data-package-service-id]");
  let selectedProducts = [...new Set(ids.map((input) => input.value).filter(Boolean))]
    .map((id) => products.find((item) => item.id === id)).filter(Boolean).slice(0, 3);
  let selectedService = services.find((item) => item.id === serviceId.value) || null;
  const productSearch = root.querySelector("[data-package-product-search]");
  const serviceSearch = root.querySelector("[data-package-service-search]");

  const cancelSearches = [];
  const card = (item, remove, product = false) => {
    const block = document.createElement("div");
    block.className = "admin-selected-card";
    const copy = document.createElement("strong");
    copy.className = "admin-selected-copy";
    copy.textContent = product ? window.ProductDisplay.identity(item) : item.name;
    {
      const meta = document.createElement("small");
      meta.className = "admin-selected-meta";
      meta.textContent = product ? window.ProductDisplay.price(item) : `Rp${Number(item.price_rupiah).toLocaleString("id-ID")}`;
      copy.append(meta);
    }
    const button = document.createElement("button");
    button.type = "button";
    button.className = "admin-selected-remove";
    button.textContent = "×";
    button.setAttribute("aria-label", `Lepas ${item.name}`);
    button.addEventListener("click", remove);
    block.append(copy, button);
    return block;
  };

  // All selection mutations and hydration rebuild the legacy ordered payload here.
  const syncSelection = () => {
    cancelSearches.forEach((cancel) => cancel());
    ids.forEach((input, index) => { input.value = selectedProducts[index]?.id || ""; });
    serviceId.value = selectedService?.id || "";
    productSearch.value = "";
    serviceSearch.value = "";
    root.querySelector("[data-product-search-stage]").classList.toggle("d-none", selectedProducts.length === 3);
    root.querySelector("[data-service-search-stage]").classList.toggle("d-none", Boolean(selectedService));
    root.querySelector("[data-package-products-selected]").replaceChildren(...selectedProducts.map((item) =>
      card(item, () => {
        selectedProducts = selectedProducts.filter((product) => product.id !== item.id);
        syncSelection();
        productSearch.focus();
      }, true)
    ));
    root.querySelector("[data-package-service-selected]").replaceChildren(...(selectedService ? [
      card(selectedService, () => {
        selectedService = null;
        syncSelection();
        serviceSearch.focus();
      }),
    ] : []));
    root.querySelector("[data-package-count]").textContent = `${selectedProducts.length} dari maksimal 3 produk`;
    const preview = selectedProducts.reduce((sum, item) => sum + item.price_rupiah, 0) + (selectedService?.price_rupiah || 0);
    root.querySelector("[data-package-total]").textContent = `Rp${preview.toLocaleString("id-ID")}`;
    [productSearch, serviceSearch].forEach((input) => {
      input.setAttribute("aria-expanded", "false");
      input.removeAttribute("aria-activedescendant");
    });
    root.querySelectorAll("[role=listbox]").forEach((list) => {
      list.replaceChildren();
      list.classList.add("d-none");
    });
  };

  const bindSearch = (input, list, available, select, product = false) => {
    let request = 0;
    let timer;
    let controller;
    let active = -1;
    const close = () => {
      request += 1;
      clearTimeout(timer);
      controller?.abort();
      list.classList.add("d-none");
      input.setAttribute("aria-expanded", "false");
      input.removeAttribute("aria-activedescendant");
      active = -1;
    };
    cancelSearches.push(close);
    const render = async () => {
      active = -1;
      input.removeAttribute("aria-activedescendant");
      const query = input.value.trim().toLocaleLowerCase("id-ID");
      if (query.length < 2) {
        list.replaceChildren();
        close();
        return;
      }
      const token = ++request;
      controller?.abort();
      controller = new AbortController();
      let matches;
      try {
        matches = (await available(query, controller.signal)).slice(0, 50);
      } catch (error) {
        if (token !== request || error.name === "AbortError") return;
        list.replaceChildren();
        const message = document.createElement("div");
        message.textContent = "Gagal memuat produk.";
        list.append(message);
        list.classList.remove("d-none");
        input.setAttribute("aria-expanded", "true");
        return;
      }
      if (token !== request) return;
      list.replaceChildren(...matches.map((item, index) => {
        const button = document.createElement("button");
        button.type = "button";
        button.className = "list-group-item list-group-item-action";
        button.id = `${list.id}-${index}`;
        button.setAttribute("role", "option");
        button.setAttribute("aria-selected", "false");
        const name = document.createElement("div");
        name.textContent = product ? window.ProductDisplay.identity(item) : item.name;
        const price = document.createElement("small");
        price.className = "d-block text-muted";
        price.textContent = product ? window.ProductDisplay.price(item) : `Rp${Number(item.price_rupiah).toLocaleString("id-ID")}`;
        button.append(name, price);
        button.addEventListener("click", () => { select(item); close(); });
        return button;
      }));
      if (!matches.length) {
        const empty = document.createElement("div");
        empty.className = "p-2 text-muted";
        empty.textContent = "Tidak ditemukan.";
        list.append(empty);
      }
      list.classList.remove("d-none");
      input.setAttribute("aria-expanded", "true");
    };
    input.addEventListener("input", () => {
      close();
      list.replaceChildren();
      if (input.value.trim().length >= 2) timer = setTimeout(render, 200);
    });
    input.addEventListener("focus", render);
    input.addEventListener("keydown", (event) => {
      if (event.key === "Escape") { event.preventDefault(); close(); return; }
      if (event.key === "ArrowDown" || event.key === "ArrowUp") {
        event.preventDefault();
        if (list.classList.contains("d-none")) render();
        const buttons = [...list.querySelectorAll("button")];
        if (!buttons.length) return;
        active = Math.max(0, Math.min(buttons.length - 1, active + (event.key === "ArrowDown" ? 1 : -1)));
        buttons.forEach((button, index) => {
          button.classList.toggle("active", index === active);
          button.setAttribute("aria-selected", String(index === active));
        });
        input.setAttribute("aria-activedescendant", buttons[active].id);
        buttons[active].scrollIntoView({ block: "nearest" });
      }
      if (event.key === "Enter") {
        event.preventDefault();
        if (!list.classList.contains("d-none")) list.querySelectorAll("button")[Math.max(0, active)]?.click();
      }
    });
    document.addEventListener("click", (event) => {
      if (!input.parentElement.contains(event.target)) close();
    });
    input.addEventListener("blur", () => setTimeout(() => {
      if (!input.parentElement.contains(document.activeElement)) close();
    }, 0));
  };
  bindSearch(productSearch, root.querySelector("[data-package-product-results]"),
    async (query, signal) => {
      const response = await fetch(`${root.dataset.productEndpoint}?q=${encodeURIComponent(query)}`, { signal, headers: { Accept: "application/json" } });
      const payload = await response.json();
      if (!response.ok || !payload.success) throw new Error("product lookup");
      return (payload.data?.rows || []).filter((item) => !selectedProducts.some((selected) => selected.id === item.id))
        .map((item) => ({ ...item, name: item.nama_barang, price_rupiah: item.default_unit_price_rupiah }));
    },
    (item) => {
      if (selectedProducts.length >= 3 || selectedProducts.some((selected) => selected.id === item.id)) return;
      selectedProducts.push(item);
      syncSelection();
      (selectedProducts.length < 3 ? productSearch : selectedService ? root.querySelector(".admin-selected-remove") : serviceSearch).focus();
    }, true);
  bindSearch(serviceSearch, root.querySelector("[data-package-service-results]"), (query) => services.filter((item) => item.label.toLocaleLowerCase("id-ID").includes(query)),
    (item) => { selectedService = item; syncSelection(); root.querySelector("[data-package-service-selected] button").focus(); });
  syncSelection();
})();
