(() => {
  const esc = (v) => String(v ?? "").replace(/[&<>\"']/g, (m) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#39;"
  }[m]));

  const norm = (v) => String(v ?? "").trim().toLowerCase();

  const buildCreateUrl = (baseUrl, query) => {
    const url = new URL(baseUrl, window.location.origin);
    url.searchParams.set("source", "expense_create");
    url.searchParams.set("q", String(query ?? "").trim());
    return url.toString();
  };

  const dispatchSelected = (detail) => {
    document.dispatchEvent(new CustomEvent("expense-category:selected", { detail }));
  };

  const init = (ctx) => {
    const { config, elements } = ctx;
    const options = Array.isArray(config.categoryOptions)
      ? config.categoryOptions.map((item) => ({
          id: String(item.id ?? "").trim(),
          label: String(item.label ?? "").trim(),
          search: norm(item.label),
        })).filter((item) => item.id !== "" && item.label !== "")
      : [];

    const optionById = new Map(options.map((item) => [item.id, item]));
    const minChars = 2;

    let filtered = [];
    let activeIndex = 0;

    const {
      categorySearchWrap,
      categorySearchInput,
      categorySearchResults,
      categorySearchHelper,
      categorySelectWrap,
      categorySelect,
      categorySelected,
      categorySelectedLabel,
      categoryRemove,
    } = elements;

    if (
      !categorySearchWrap ||
      !categorySearchInput ||
      !categorySearchResults ||
      !categorySearchHelper ||
      !categorySelectWrap ||
      !categorySelect
    ) {
      return;
    }

    const setHelper = (message, danger = false) => {
      categorySearchHelper.textContent = message;
      categorySearchHelper.classList.toggle("text-danger", danger);
      categorySearchHelper.classList.toggle("text-muted", !danger);
    };

    const closeResults = () => {
      filtered = [];
      activeIndex = 0;
      categorySearchResults.innerHTML = "";
      categorySearchResults.classList.add("d-none");
    };

    const renderSelection = (option) => {
      categorySelect.value = option?.id || "";
      categorySearchInput.value = "";
      categorySearchWrap.classList.toggle("d-none", Boolean(option));
      categorySelected?.classList.toggle("d-none", !option);
      if (categorySelectedLabel) categorySelectedLabel.textContent = option?.label || "";
      closeResults();
    };

    const selectOption = (option) => {
      renderSelection(option);
      dispatchSelected({
        id: option.id,
        label: option.label,
      });
    };

    const renderResults = () => {
      if (!filtered.length) {
        categorySearchResults.innerHTML = "";
        categorySearchResults.classList.add("d-none");
        return;
      }

      categorySearchResults.innerHTML = filtered.map((item, index) => `
        <button
          type="button"
          class="list-group-item list-group-item-action ${index === activeIndex ? "active" : ""}"
          data-category-index="${index}"
        >
          ${esc(item.label)}
        </button>
      `).join("");

      categorySearchResults.classList.remove("d-none");
    };

    const runSearch = (rawQuery) => {
      if (categorySelect.value) return;
      const query = norm(rawQuery);

      if (query.length < minChars) {
        filtered = [];
        activeIndex = 0;
        categorySelect.value = "";
        setHelper("Ketik minimal 2 karakter untuk cari kategori. Enter pilih hasil. Jika tidak ada, Enter ke form kategori baru.");
        closeResults();
        return;
      }

      filtered = options.filter((item) => item.search.includes(query)).slice(0, 8);
      activeIndex = 0;

      if (!filtered.length) {
        categorySelect.value = "";
        setHelper("Kategori tidak ditemukan. Tekan Enter untuk tambah kategori baru.", true);
        closeResults();
        return;
      }

      setHelper("Pilih kategori dari daftar hasil. Enter pilih hasil aktif.");
      renderResults();
    };

    categorySearchWrap.classList.remove("d-none");
    categorySelectWrap.classList.add("d-none");

    const selectedCategoryId = String(config.selectedCategoryId || categorySelect.value || "").trim();
    const selectedCategory = optionById.get(selectedCategoryId);

    if (selectedCategory) {
      renderSelection(selectedCategory);
    } else {
      setHelper("Ketik minimal 2 karakter untuk cari kategori. Enter pilih hasil. Jika tidak ada, Enter ke form kategori baru.");
    }

    categorySearchInput.addEventListener("input", () => {
      runSearch(categorySearchInput.value);
    });

    categoryRemove?.addEventListener("click", () => {
      renderSelection(null);
      runSearch("");
      categorySearchInput.focus();
    });
    categorySelect.addEventListener("invalid", (event) => {
      event.preventDefault();
      categorySearchInput.focus();
      setHelper("Pilih kategori dari hasil pencarian.", true);
    });

    categorySearchInput.addEventListener("keydown", (event) => {
      if (event.key === "ArrowDown" && filtered.length) {
        event.preventDefault();
        activeIndex = Math.min(activeIndex + 1, filtered.length - 1);
        renderResults();
        return;
      }

      if (event.key === "ArrowUp" && filtered.length) {
        event.preventDefault();
        activeIndex = Math.max(activeIndex - 1, 0);
        renderResults();
        return;
      }

      if (event.key === "Escape") {
        closeResults();
        return;
      }

      if (event.key !== "Enter") {
        return;
      }

      if (event.altKey || event.ctrlKey || event.metaKey || event.shiftKey) {
        return;
      }

      event.preventDefault();

      if (filtered.length && filtered[activeIndex]) {
        selectOption(filtered[activeIndex]);
        return;
      }

      const query = String(categorySearchInput.value || "").trim();

      if (query.length >= minChars) {
        const existing = options.find((item) => item.search.includes(norm(query)));
        if (existing) { selectOption(existing); return; }
        window.location.href = buildCreateUrl(config.createCategoryBaseUrl, query);
      }
    });

    categorySearchResults.addEventListener("click", (event) => {
      const button = event.target.closest("[data-category-index]");
      if (!button) return;

      const index = Number.parseInt(button.getAttribute("data-category-index") || "", 10);
      if (Number.isNaN(index) || !filtered[index]) return;

      selectOption(filtered[index]);
    });

    document.addEventListener("click", (event) => {
      const target = event.target;
      if (
        target instanceof Node &&
        !categorySearchWrap.contains(target) &&
        !categorySearchResults.contains(target)
      ) {
        closeResults();
      }
    });

    ctx.api = ctx.api || {};
    ctx.api.focusCategorySearch = () => {
      window.requestAnimationFrame(() => {
        if (categorySelect.value) { categoryRemove?.focus(); return; }
        categorySearchInput.focus();
        categorySearchInput.select();
      });
    };

    ctx.api.hasSelectedCategory = () => String(categorySelect.value || "").trim() !== "";
  };

  window.AdminExpenseCreateCategorySearch = { init };
})();
