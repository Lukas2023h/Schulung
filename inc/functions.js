function paginateTable(tableId) {
  const table = document.getElementById(tableId);
  if (!table) return;

  const tbody = table.querySelector("tbody");
  const rows = Array.from(tbody.rows);
  const pagerId = tableId + "-pager";
  const pager = document.getElementById(pagerId);

  if (!pager) return;

  // Lokale Variablen - keine Überschneidungen mehr
  let currentPage = 1;
  let rowsPerPage = 10;

  // Vorher leeren
  pager.innerHTML = '';
  pager.className = "d-flex align-items-center justify-content-end my-2";

  const input = document.createElement("input");
  input.type = "number";
  input.min = 1;
  input.value = rowsPerPage;
  input.className = "form-control form-control-sm w-auto d-inline mx-2";
  input.addEventListener("change", () => {
    rowsPerPage = parseInt(input.value) || 5;
    currentPage = 1;
    render();
  });

  const prevBtn = document.createElement("button");
  prevBtn.className = "btn btn-sm btn-outline-secondary mx-1";
  prevBtn.textContent = "◀";
  prevBtn.addEventListener("click", () => {
    if (currentPage > 1) {
      currentPage--;
      render();
    }
  });

  const nextBtn = document.createElement("button");
  nextBtn.className = "btn btn-sm btn-outline-secondary mx-1";
  nextBtn.textContent = "▶";
  nextBtn.addEventListener("click", () => {
    if (currentPage < Math.ceil(rows.length / rowsPerPage)) {
      currentPage++;
      render();
    }
  });

  const info = document.createElement("span");
  info.className = "mx-2 small";

  pager.append("Zeilen pro Seite:", input, prevBtn, nextBtn, info);

  function render() {
    rows.forEach((row, i) => {
      row.style.display = (i >= (currentPage - 1) * rowsPerPage &&  i < currentPage * rowsPerPage) ? "" : "none";
    });


    const start = (currentPage - 1) * rowsPerPage + 1;
    const end = Math.min(currentPage * rowsPerPage, rows.length);
    info.textContent = `Zeige ${start}-${end} von ${rows.length}`;
  }

  render();
}
