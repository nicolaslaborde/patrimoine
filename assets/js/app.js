document.querySelectorAll(".js-delete").forEach((button) => {
  button.addEventListener("click", async () => {
    if (!confirm("Supprimer cette fiche ?")) return;
    const params = new URLSearchParams({
      action: "delete",
      rubrique: button.dataset.rubrique,
      id: button.dataset.id,
    });
    const response = await fetch(`api/rubrique.php?${params}`, { method: "POST" });
    if (!response.ok) {
      alert((await response.json()).error || "Suppression impossible");
      return;
    }
    location.reload();
  });
});

document.querySelectorAll(".info-toggle").forEach((button) => {
  button.addEventListener("click", () => {
    const panel = document.querySelector(`#info-${button.dataset.info}`);
    if (panel) panel.hidden = !panel.hidden;
  });
});

document.querySelectorAll(".synth-toggle").forEach((button) => {
  button.addEventListener("click", () => {
    const row = document.querySelector(`#${button.dataset.target}`);
    if (!row) return;
    row.hidden = !row.hidden;
    button.classList.toggle("open", !row.hidden);
  });
});

const printSynthesis = document.querySelector("#printSynthesis");
if (printSynthesis) {
  printSynthesis.addEventListener("click", () => {
    document.querySelectorAll(".poste-detail-row").forEach((row) => {
      row.hidden = false;
    });
    document.querySelectorAll(".synth-toggle").forEach((button) => {
      button.classList.add("open");
    });
    window.print();
  });
}

document.querySelectorAll(".table-sort").forEach((button) => {
  button.addEventListener("click", () => {
    const table = document.querySelector(`#${button.dataset.sortTable}`);
    const tbody = table?.querySelector("tbody");
    if (!tbody) return;

    const column = Number(button.dataset.sortColumn);
    const type = button.dataset.sortType || "text";
    const direction = button.dataset.sortDirection === "asc" ? "desc" : "asc";
    button.dataset.sortDirection = direction;
    button.setAttribute("aria-sort", direction === "asc" ? "ascending" : "descending");

    const rows = [...tbody.querySelectorAll("tr")].filter((row) => row.children.length > column);
    rows.sort((a, b) => {
      const aCell = a.children[column];
      const bCell = b.children[column];
      const aValue = aCell.dataset.sortValue ?? aCell.textContent;
      const bValue = bCell.dataset.sortValue ?? bCell.textContent;
      const result = type === "number"
        ? Number(aValue || 0) - Number(bValue || 0)
        : String(aValue).localeCompare(String(bValue), "fr", { sensitivity: "base" });
      return direction === "asc" ? result : -result;
    });
    rows.forEach((row) => tbody.appendChild(row));
  });
});

const TABLE_FILTER_STORAGE_PREFIX = "patrimoine.tableFilter.";

function tableFilterStorageKey(control) {
  const scope = control.dataset.filterScope || control.dataset.filterTable || window.location.pathname;
  const key = control.dataset.filterKey || control.name || "filter";
  return `${TABLE_FILTER_STORAGE_PREFIX}${scope}.${key}`;
}

function normalizeFilterValue(value) {
  return String(value || "").trim().toLocaleLowerCase("fr-FR");
}

function formatEuro(value) {
  return new Intl.NumberFormat("fr-FR", {
    style: "currency",
    currency: "EUR",
    maximumFractionDigits: 0,
  }).format(value);
}

function refreshFilteredTotals(table) {
  const monthlyTotal = table.querySelector('[data-filter-total="monthly"]');
  const annualTotal = table.querySelector('[data-filter-total="annual"]');
  if (!monthlyTotal && !annualTotal) return;

  let monthly = 0;
  let annual = 0;
  table.querySelectorAll("tbody tr[data-filter-row]").forEach((row) => {
    if (row.hidden) return;
    monthly += Number(row.dataset.monthlyValue || 0);
    annual += Number(row.dataset.annualValue || 0);
  });
  if (monthlyTotal) monthlyTotal.textContent = formatEuro(monthly);
  if (annualTotal) annualTotal.textContent = formatEuro(annual);
}

function applyTableFilters(table) {
  const controls = [...document.querySelectorAll(`.table-filter[data-filter-table="${table.id}"]`)];
  const rows = [...table.querySelectorAll("tbody tr[data-filter-row]")];
  const emptyRow = table.querySelector(".filter-empty-row");
  let visibleRows = 0;

  rows.forEach((row) => {
    const matches = controls.every((control) => {
      const expected = normalizeFilterValue(control.value);
      if (!expected) return true;
      const rowKey = control.dataset.filterRowKey || control.dataset.filterKey || "";
      const actual = normalizeFilterValue(row.dataset[`filter${rowKey.charAt(0).toUpperCase()}${rowKey.slice(1)}`]);
      return actual === expected;
    });
    row.hidden = !matches;
    if (matches) visibleRows += 1;
  });

  if (emptyRow) emptyRow.hidden = visibleRows !== 0;
  refreshFilteredTotals(table);
}

document.querySelectorAll(".table-filter").forEach((control) => {
  const savedValue = localStorage.getItem(tableFilterStorageKey(control));
  if (savedValue !== null && [...control.options].some((option) => option.value === savedValue)) {
    control.value = savedValue;
  }

  const table = document.querySelector(`#${control.dataset.filterTable}`);
  if (table) applyTableFilters(table);

  control.addEventListener("change", () => {
    localStorage.setItem(tableFilterStorageKey(control), control.value);
    const targetTable = document.querySelector(`#${control.dataset.filterTable}`);
    if (targetTable) applyTableFilters(targetTable);
  });
});
