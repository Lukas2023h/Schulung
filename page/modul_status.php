<?php

require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

$modul_id = intval($_GET['modul_id'] ?? 0);
if ($modul_id === 0) {
  die("Ungültige Modul-ID");
}

// Modulinfos holen
$stmt = makeStatement("SELECT ModulName, ModulZyklus FROM module WHERE ID = ?", [$modul_id]);
$modul = $stmt->fetch(PDO::FETCH_ASSOC);
$modul_name = $modul['ModulName'];
$zyklus = (int)$modul['ModulZyklus'];

// Alle Mitarbeiter durchgehen
$mitarbeiter = [];
$res_m = makeStatement("SELECT ID, CONCAT(MitarbeiterVorname, ' ', MitarbeiterNachname) AS name FROM mitarbeiter");

while ($m = $res_m->fetch(PDO::FETCH_ASSOC)) {
  $mid = $m['ID'];

  $stmt = makeStatement("
    SELECT MAX(s.SchulungsDatum) AS letzte
    FROM schulungen s
    JOIN module_has_schulungen mhs ON mhs.Schulungen_ID = s.ID
    JOIN schulungsteilnahmen st ON st.Schulungen_ID = s.ID
    WHERE mhs.Module_ID = ? AND st.Mitarbeiter_ID = ?
  ", [$modul_id, $mid]);

  $last = $stmt->fetch(PDO::FETCH_ASSOC)['letzte'];
  $heute = date('Y-m-d');

  if (!$last) {
    $status = "Nie geschult";
    $sort_index = 0;
    $sort_date = '0000-00-00';
  } else {
    $grenze = date('Y-m-d', strtotime("+$zyklus months", strtotime($last)));

    if ($grenze < $heute) {
      $status = "Überfällig ($last)";
      $sort_index = 1;
      $sort_date = $last;
    } else {
      $status = "Aktuell ($last)";
      $sort_index = 2;
      $sort_date = $last;
    }
  }

  $mitarbeiter[] = [
    'name' => $m['name'],
    'status' => $status,
    'sort_index' => $sort_index,
    'sort_date' => $sort_date
  ];
}

// Sortieren nach Priorität und Datum
usort($mitarbeiter, function ($a, $b) {
  if ($a['sort_index'] !== $b['sort_index']) {
    return $a['sort_index'] - $b['sort_index'];
  }
  return strcmp($a['sort_date'], $b['sort_date']);
});

?>

<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <title>Status für Modul</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<div class="row mb-3">
  <div class="col-md-4">
    <label for="filterSelect" class="form-label fw-bold">Status filtern:</label>
    <select id="filterSelect" class="form-select" onchange="filterTabelle()">
      <option value="alle">Alle</option>
      <option value="nie">Nie geschult</option>
      <option value="ueberfaellig">Überfällig</option>
      <option value="aktuell">Aktuell</option>
    </select>
  </div>
  <div class="col-md-4">
    <label for="nameFilter" class="form-label fw-bold">Mitarbeitername filtern:</label>
    <input type="text" id="nameFilter" class="form-control" onkeyup="filterTabelle()" placeholder="Name eingeben...">
  </div>
</div>

<body class="container mt-4">
  <h2>Status für Modul: <?= htmlspecialchars($modul_name) ?></h2>
  <table class="table table-bordered">
    <thead class="table-dark">
      <tr>
        <th>Mitarbeiter</th>
        <th>Status</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach ($mitarbeiter as $m): ?>
        <?php
        // Klassenzuweisung nach Status
        if (str_starts_with($m['status'], "Nie")) {
          $status_code = 'nie';
          $row_class = 'table-danger';
        } elseif (str_starts_with($m['status'], "Überfällig")) {
          $status_code = 'ueberfaellig';
          $row_class = 'table-danger';
        } elseif (str_starts_with($m['status'], "Aktuell")) {
          $status_code = 'aktuell';
          $row_class = 'table-success';
        }

        ?>
        <tr class="<?= $row_class ?>" data-status="<?= $status_code ?>">
          <td><?= htmlspecialchars($m['name']) ?></td>
          <td><?= htmlspecialchars($m['status']) ?></td>
        </tr>
      <?php endforeach; ?>
    </tbody>
  </table>
  <a href="admin_modul.php" class="btn btn-secondary">Zurück</a>
</body>

</html>

<script>
  function filterTabelle() {
    const statusFilter = document.getElementById("filterSelect").value;
    const nameFilter = document.getElementById("nameFilter").value.toLowerCase();
    const rows = document.querySelectorAll("tbody tr");

    rows.forEach(row => {
      const status = row.getAttribute("data-status");
      const name = row.children[0].textContent.toLowerCase();

      const statusMatch = (statusFilter === "alle" || status === statusFilter);
      const nameMatch = name.includes(nameFilter);

      if (statusMatch && nameMatch) {
        row.style.display = "";
      } else {
        row.style.display = "none";
      }
    });
  }
</script>