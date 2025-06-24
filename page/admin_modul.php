<?php
require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

// Modul einfügen per AJAX
if ($_SERVER["REQUEST_METHOD"] === "POST") {
  $name = getValueFromPostArray('ModulName', '');
  $abteilung = getValueFromPostArray('ModulAbteilung', '');
  $zyklus = (int)getValueFromPostArray('ModulZyklus', 12);
  $geschaeft = getValueFromPostArray('ModulGeschaeft', '');

  makeStatement(
    "INSERT INTO module (ModulName, ModulAbteilung, ModulZyklus, ModulGeschaeft) VALUES (?, ?, ?, ?)",
    [$name, $abteilung, $zyklus, $geschaeft]
  );
}

// Module & Mitarbeiter laden
$module = makeStatement("SELECT ID, ModulName, ModulZyklus FROM module")->fetchAll(PDO::FETCH_ASSOC);
$alleMitarbeiter = makeStatement("SELECT ID FROM mitarbeiter")->fetchAll(PDO::FETCH_COLUMN);
$heute = date('Y-m-d');
?>

<div class="container mt-4">
  <h1 class="mb-4">Alle Module</h1>

  <!-- Formular -->
  <div class="card mb-4">
    <div class="card-header fw-bold">Neues Modul hinzufügen</div>
    <div class="card-body">
      <form id="modulForm">
        <div class="row mb-3">
          <div class="col-md-4">
            <label class="form-label">Modulname</label>
            <input type="text" name="ModulName" class="form-control" required>
          </div>
          <div class="col-md-2">
            <label class="form-label">Abteilung</label>
            <input type="text" name="ModulAbteilung" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Geschäftsbereich</label>
            <input type="text" name="ModulGeschaeft" class="form-control" required>
          </div>
          <div class="col-md-3">
            <label class="form-label">Zyklus</label>
            <select name="ModulZyklus" class="form-select">
              <option value="6">6 Monate</option>
              <option value="12" selected>1 Jahr</option>
              <option value="24">2 Jahre</option>
            </select>
          </div>
        </div>
        <button type="submit" class="btn btn-primary">Modul hinzufügen</button>
      </form>
    </div>
  </div>

  <!-- Modulliste -->
  <ul class="list-group" id="modulliste">
    <?php foreach ($module as $modul): ?>
      <?php
      $modulId = $modul['ID'];
      $zyklus = (int)$modul['ModulZyklus'];
      $faellig = 0;

      foreach ($alleMitarbeiter as $mid) {
        $stmt = makeStatement("
            SELECT MAX(s.SchulungsDatum) AS letzte
            FROM schulungen s
            JOIN module_has_schulungen mhs ON mhs.Schulungen_ID = s.ID
            JOIN schulungsteilnahmen st ON st.Schulungen_ID = s.ID
            WHERE mhs.Module_ID = ? AND st.Mitarbeiter_ID = ?
          ", [$modulId, $mid]);

        $last = $stmt->fetch(PDO::FETCH_ASSOC)['letzte'];

        if (!$last || date('Y-m-d', strtotime("+$zyklus months", strtotime($last))) < $heute) {
          $faellig++;
        }
      }

      //$klasse = ($faellig > 0) ? 'list-group-item-warning' : '';
      ?>
      <li class="list-group-item d-flex justify-content-between align-items-center <?= $klasse ?>">
        <div>
          <strong><?= htmlspecialchars($modul['ModulName']) ?></strong><br>
          <small>Fällige Mitarbeiter : <strong><?= $faellig ?></strong></small>
        </div>
        <a href="page/modul_status.php?modul_id=<?= $modulId ?>" class="btn btn-sm btn-primary">
          Status anzeigen
        </a>
      </li>
    <?php endforeach; ?>
  </ul>
</div>