(() => {
  const configNode = document.getElementById("expense-create-config");
  if (!configNode) return;

  let config = {};
  try {
    config = JSON.parse(configNode.textContent || "{}");
  } catch (_) {
    config = {};
  }

  const ctx = {
    config,
    elements: {
      form: document.getElementById("expense-create-form"),
      categorySearchWrap: document.getElementById("expense-category-search-wrap"),
      categorySearchInput: document.getElementById("expense-category-search-input"),
      categorySearchResults: document.getElementById("expense-category-search-results"),
      categorySearchHelper: document.getElementById("expense-category-search-helper"),
      categorySelectWrap: document.getElementById("expense-category-select-wrap"),
      categorySelect: document.getElementById("category_id"),
      categorySelected: document.getElementById("expense-category-selected"),
      categorySelectedLabel: document.getElementById("expense-category-selected-label"),
      categoryRemove: document.getElementById("expense-category-remove"),
      expenseDate: document.getElementById("expense_date"),
      amountDisplay: document.getElementById("amount_rupiah_display"),
      paymentMethod: document.getElementById("payment_method"),
      description: document.getElementById("description"),
    },
    api: {},
  };

  window.AdminMoneyInput?.bindBySelector(document);
  window.AdminDateInput?.bindBySelector(document);

  window.AdminExpenseCreateCategorySearch?.init(ctx);
  window.AdminExpenseCreateFlow?.init(ctx);
})();
