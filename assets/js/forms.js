function formDataToObject(form) {
  const root = {};
  for (const [name, value] of new FormData(form).entries()) {
    const parts = name.replaceAll("]", "").split("[");
    let target = root;
    parts.forEach((part, index) => {
      if (index === parts.length - 1) {
        target[part] = value;
      } else {
        target[part] ??= {};
        target = target[part];
      }
    });
  }
  return root;
}

function updatePeriodicAmount(box) {
  const input = box.querySelector("[data-periodic-input]");
  const select = box.querySelector("[data-periodic-select]");
  const monthly = box.querySelector("[data-periodic-monthly]");
  const annual = box.querySelector("[data-periodic-annual]");
  const value = Number(input.value || 0);
  let monthlyValue = value / 12;
  let annualValue = value;
  if (select.value === "mensuel") {
    monthlyValue = value;
    annualValue = value * 12;
  } else if (select.value === "trimestriel") {
    monthlyValue = value / 3;
    annualValue = value * 4;
  }
  monthly.value = monthlyValue ? monthlyValue.toFixed(2) : "";
  annual.value = annualValue ? annualValue.toFixed(2) : "";
  box.querySelector("[data-periodic-hidden-value]").value = input.value;
  box.querySelector("[data-periodic-hidden-period]").value = select.value;
  box.querySelector("[data-periodic-hidden-monthly]").value = monthly.value;
  box.querySelector("[data-periodic-hidden-annual]").value = annual.value;
}

function bindPeriodicAmounts() {
  document.querySelectorAll(".periodic-amount").forEach((box) => {
    box.querySelectorAll("input, select").forEach((control) => {
      control.addEventListener("input", () => updatePeriodicAmount(box));
      control.addEventListener("change", () => updatePeriodicAmount(box));
    });
    updatePeriodicAmount(box);
  });
}

document.querySelectorAll("[data-type-specific]").forEach((select) => {
  select.addEventListener("change", () => {
    const form = select.closest("form");
    if (!form) return;
    const url = new URL(location.href);
    if (select.value) {
      url.searchParams.set("type", select.value);
    } else {
      url.searchParams.delete("type");
    }
    location.href = url.toString();
  });
});

function addLinkRow(url = "", description = "") {
  const box = document.querySelector("#linksBox");
  if (!box) return;
  const isImmobilier = document.querySelector("#ficheForm")?.dataset.rubrique === "immobilier";
  const row = document.createElement("div");
  row.className = isImmobilier ? "link-row has-open-icon" : "link-row";
  row.innerHTML = `
    <input data-link-url placeholder="https://..." value="${escapeAttribute(url)}">
    <input data-link-description placeholder="Description" value="${escapeAttribute(description)}">
    ${isImmobilier ? `<a class="url-open-icon" href="${escapeAttribute(url)}" target="_blank" rel="noopener noreferrer" title="Ouvrir le lien" aria-label="Ouvrir le lien"${url ? "" : " hidden"}>↗</a>` : ""}
    <button class="button tiny danger" type="button" data-remove-link>Supprimer</button>
  `;
  box.appendChild(row);
}

function escapeAttribute(value) {
  return String(value ?? "").replace(/[&<>"']/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
    '"': "&quot;",
    "'": "&#039;",
  })[char]);
}

function collectLinks() {
  return [...document.querySelectorAll(".link-row")].map((row) => ({
    url: row.querySelector("[data-link-url]").value.trim(),
    description: row.querySelector("[data-link-description]").value.trim(),
  })).filter((link) => link.url);
}

function todayIsoDate() {
  const date = new Date();
  date.setMinutes(date.getMinutes() - date.getTimezoneOffset());
  return date.toISOString().slice(0, 10);
}

function addHistoryRow(date = todayIsoDate(), texte = "", url = "") {
  const box = document.querySelector("#historyBox");
  if (!box) return;
  const row = document.createElement("div");
  row.className = "history-row";
  row.innerHTML = `
    <input type="date" data-history-date value="${escapeAttribute(date || todayIsoDate())}">
    <textarea data-history-texte placeholder="Information historique">${escapeTextarea(texte)}</textarea>
    <span class="url-open-field">
      <input type="url" data-history-url value="${escapeAttribute(url)}" placeholder="https://...">
      <a class="url-open-icon" href="${escapeAttribute(url)}" target="_blank" rel="noopener noreferrer" title="Ouvrir le lien" aria-label="Ouvrir le lien"${url ? "" : " hidden"}>â†—</a>
    </span>
    <button class="button tiny danger" type="button" data-remove-history>Supprimer</button>
  `;
  box.appendChild(row);
}

function escapeTextarea(value) {
  return String(value ?? "").replace(/[&<>]/g, (char) => ({
    "&": "&amp;",
    "<": "&lt;",
    ">": "&gt;",
  })[char]);
}

function collectHistory() {
  return [...document.querySelectorAll(".history-row")].map((row) => ({
    date: row.querySelector("[data-history-date]").value,
    texte: row.querySelector("[data-history-texte]").value.trim(),
    url: row.querySelector("[data-history-url]").value.trim(),
  })).filter((item) => item.texte || item.url);
}

function updateUrlOpenIcon(input) {
  const row = input.closest(".link-row");
  const icon = row?.querySelector(".url-open-icon") ?? input.closest(".url-open-field")?.querySelector(".url-open-icon");
  if (!icon) return;
  const url = input.value.trim();
  icon.href = url || "#";
  icon.hidden = !url;
}

document.addEventListener("click", (event) => {
  if (event.target?.id === "addLink") addLinkRow();
  if (event.target?.matches("[data-remove-link]")) event.target.closest(".link-row")?.remove();
  if (event.target?.id === "addHistory") addHistoryRow();
  if (event.target?.matches("[data-remove-history]")) event.target.closest(".history-row")?.remove();
});

document.addEventListener("input", (event) => {
  if (event.target?.matches("[data-link-url], [data-url-open-source], [data-history-url]")) {
    updateUrlOpenIcon(event.target);
  }
});

const ficheForm = document.querySelector("#ficheForm");
if (ficheForm) {
  bindPeriodicAmounts();
  ficheForm.addEventListener("submit", async (event) => {
    event.preventDefault();
    const payload = formDataToObject(ficheForm);
    payload.liens = collectLinks();
    if (ficheForm.dataset.rubrique === "immobilier") {
      payload.historique = collectHistory();
    }

    const rubrique = ficheForm.dataset.rubrique;
    const id = ficheForm.dataset.id;
    const isProfil = ficheForm.dataset.isProfil === "1";
    const url = isProfil
      ? "api/profil.php"
      : `api/rubrique.php?action=${id ? "update" : "create"}&rubrique=${encodeURIComponent(rubrique)}${id ? `&id=${encodeURIComponent(id)}` : ""}`;

    const response = await fetch(url, {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify(payload),
    });
    if (!response.ok) {
      alert((await response.json()).error || "Enregistrement impossible");
      return;
    }
    location.href = isProfil ? "dashboard.php" : `rubrique.php?rubrique=${encodeURIComponent(rubrique)}`;
  });
}
