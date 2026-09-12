(() => {
  const modalElement = document.getElementById("service-action-modal");
  if (!modalElement) return;

  const subtitle = document.getElementById("service-action-modal-subtitle");
  const editLink = document.getElementById("service-action-edit-link");
  const statusForm = document.getElementById("service-action-status-form");
  const statusButton = document.getElementById("service-action-status-button");
  const statusTitle = document.getElementById("service-action-status-title");

  const modal = window.bootstrap && window.bootstrap.Modal
    ? new window.bootstrap.Modal(modalElement)
    : null;

  document.addEventListener("click", (event) => {
    const button = event.target.closest("[data-service-action='open']");
    if (!button) return;

    const status = button.dataset.serviceStatus || "inactive";
    const isActive = status === "active";

    if (subtitle) {
      subtitle.textContent = button.dataset.serviceName || "Jasa";
    }

    if (editLink) editLink.href = button.dataset.editUrl || "#";

    if (statusForm) {
      statusForm.action = isActive
        ? (button.dataset.deactivateUrl || "#")
        : (button.dataset.activateUrl || "#");
    }

    if (statusTitle) {
      statusTitle.textContent = isActive ? "Nonaktifkan Jasa" : "Aktifkan Jasa";
    }

    if (statusButton) {
      statusButton.classList.toggle("btn-outline-warning", isActive);
      statusButton.classList.toggle("btn-outline-success", !isActive);
    }

    if (modal) {
      modal.show();
      return;
    }

    window.location.href = button.dataset.editUrl || "#";
  });
})();
