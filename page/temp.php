<?php
require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

$heute = date('Y-m-d');

// -------- AJAX: Filter Module --------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['ajax'] === 'filter') {
  $abteilung = $_POST['abteilung'] ?? '';
  $filter = $_POST['status'] ?? '';
  $mitarbeitername = $_POST['mitarbeitername'] ?? '';

  $module = makeStatement("SELECT * FROM module ORDER BY ModulName")->fetchAll(PDO::FETCH_ASSOC);

  echo "<div class='row row-cols-1 g-3 modul-list'>";

  foreach ($module as $modul) {
    $zyklus = (int)$modul['ModulZyklus'];

    // Hole alle relevanten Mitarbeitenden zur Abteilung
    $sql = "SELECT ID, MitarbeiterVorname, MitarbeiterNachname FROM mitarbeiter WHERE 1=1";
    $params = [];

    if ($abteilung) {
      $sql .= " AND MitarbeiterAbteilung = ?";
      $params[] = $abteilung;
    }

    $mitarbeiter = makeStatement($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

    $anzeigen = false;

    foreach ($mitarbeiter as $m) {
      $fullName = $m['MitarbeiterVorname'] . ' ' . $m['MitarbeiterNachname'];
      if ($mitarbeitername && stripos($fullName, $mitarbeitername) === false) continue;

      $stmtLast = makeStatement("
        SELECT MAX(s.SchulungsDatum) AS letzte
        FROM schulungen s
        JOIN module_has_schulungen mhs ON mhs.Schulungen_ID = s.ID
        JOIN schulungsteilnahmen st ON st.Schulungen_ID = s.ID
        WHERE mhs.Module_ID = ? AND st.Mitarbeiter_ID = ?
      ", [$modul['ID'], $m['ID']]);
      $letzte = $stmtLast->fetch(PDO::FETCH_ASSOC)['letzte'];
      $status = !$letzte ? 'nie' : ((strtotime("+$zyklus months", strtotime($letzte)) < time()) ? 'faellig' : 'gueltig');

      if (!$filter || $filter === $status) {
        $anzeigen = true;
        break;
      }
    }

    if ($anzeigen) {
      echo "
      <div class='col'>
        <div class='modul-card card shadow-sm border-0' data-modul-id='{$modul['ID']}' style='cursor:pointer;'>
          <div class='card-body d-flex justify-content-between align-items-center'>
            <div class='fw-semibold'><i class='bi bi-box'></i> " . htmlspecialchars($modul['ModulName']) . "</div>
            <div class='text-muted small'>Klicken für Details</div>
          </div>
        </div>
        <div id='modul-detail-{$modul['ID']}' class='modul-detail mt-2' style='display:none;'></div>
      </div>";
    }
  }

  echo "</div>";
  exit;
}

// -------- AJAX: Detailansicht Modul --------
if ($_SERVER["REQUEST_METHOD"] === "POST" && $_POST['ajax'] === 'detail') {
  $modul_id = $_POST['modul_id'];
  $abteilung = $_POST['abteilung'] ?? '';
  $filter = $_POST['status'] ?? '';
  $mitarbeitername = $_POST['mitarbeitername'] ?? '';

  $zyklus = makeStatement("SELECT ModulZyklus FROM module WHERE ID = ?", [$modul_id])->fetchColumn();

  $sql = "SELECT ID, MitarbeiterVorname, MitarbeiterNachname FROM mitarbeiter WHERE 1=1";
  $params = [];

  if ($abteilung) {
    $sql .= " AND MitarbeiterAbteilung = ?";
    $params[] = $abteilung;
  }

  $mitarbeiter = makeStatement($sql, $params)->fetchAll(PDO::FETCH_ASSOC);

  echo "<div class='card border-start border-info border-3 mb-3'><div class='card-body p-3'><ul class='list-group list-group-flush'>";
  $gefunden = 0;

  foreach ($mitarbeiter as $m) {
    $fullName = $m['MitarbeiterVorname'] . ' ' . $m['MitarbeiterNachname'];
    if ($mitarbeitername && stripos($fullName, $mitarbeitername) === false) continue;

    $stmtLast = makeStatement("
      SELECT MAX(s.SchulungsDatum) AS letzte
      FROM schulungen s
      JOIN module_has_schulungen mhs ON mhs.Schulungen_ID = s.ID
      JOIN schulungsteilnahmen st ON st.Schulungen_ID = s.ID
      WHERE mhs.Module_ID = ? AND st.Mitarbeiter_ID = ?
    ", [$modul_id, $m['ID']]);
    $letzte = $stmtLast->fetch(PDO::FETCH_ASSOC)['letzte'];
    $status = !$letzte ? 'nie' : ((strtotime("+$zyklus months", strtotime($letzte)) < time()) ? 'faellig' : 'gueltig');

    if (!$filter || $filter === $status) {
      $text = match ($status) {
        'nie' => '<em>Nie geschult</em>',
        'faellig' => "Überfällig: <strong>$letzte</strong>",
        'gueltig' => "Letzte Schulung: <strong>$letzte</strong>"
      };

      echo "
        <li class='list-group-item d-flex justify-content-between align-items-start'>
          <div class='ms-2 me-auto'>
            <div class='fw-bold'>{$m['MitarbeiterVorname']} {$m['MitarbeiterNachname']}</div>
            <small class='text-muted'>$text</small>
          </div>
        </li>";
      $gefunden++;
    }
  }

  if (!$gefunden) {
    echo "<p class='text-muted mb-0'>Keine passenden Mitarbeitenden gefunden.</p>";
  }

  echo "</ul></div></div>";
  exit;
}

// -------- Modul speichern (POST) --------
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST['ModulName'])) {
  makeStatement("INSERT INTO module (ModulName, ModulAbteilung, ModulZyklus, ModulGeschaeft) VALUES (?, ?, ?, ?)", [
    getValueFromPostArray('ModulName'),
    getValueFromPostArray('ModulAbteilung'),
    (int)getValueFromPostArray('ModulZyklus', 12),
    getValueFromPostArray('ModulGeschaeft')
  ]);
  header("Location: admin_modulstatus.php");
  exit;
}
?>

<!-- ---------- HTML ---------- -->
<h3 class="mb-3">Modul erstellen</h3>
<form method="POST" class="row g-3 mb-4">
  <div class="col-md-4"><input type="text" name="ModulName" class="form-control" placeholder="Modulname" required></div>
  <div class="col-md-2"><input type="text" name="ModulAbteilung" class="form-control" placeholder="Abteilung" required></div>
  <div class="col-md-3"><input type="text" name="ModulGeschaeft" class="form-control" placeholder="Geschäftsbereich" required></div>
  <div class="col-md-2">
    <select name="ModulZyklus" class="form-select">
      <option value="6">6 Monate</option>
      <option value="12" selected>1 Jahr</option>
      <option value="24">2 Jahre</option>
    </select>
  </div>
  <div class="col-md-1"><button type="submit" class="btn btn-success w-100">+</button></div>
</form>

<h3 class="mb-3">Filter</h3>
<form id="filterForm" class="d-flex flex-wrap gap-2 mb-4">
  <select class="form-select w-auto" id="filterAbteilung">
    <option value="">-- Abteilung --</option>
    <?php
    $stmt = makeStatement("SELECT DISTINCT MitarbeiterAbteilung FROM mitarbeiter ORDER BY MitarbeiterAbteilung");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<option value='{$row['MitarbeiterAbteilung']}'>{$row['MitarbeiterAbteilung']}</option>";
    }
    ?>
  </select>

  <select class="form-select w-auto" id="filterStatus">
    <option value="">-- Status --</option>
    <option value="nie">Nie geschult</option>
    <option value="faellig">Fällig</option>
    <option value="gueltig">Gültig</option>
  </select>

  <input type="text" class="form-control w-auto" id="filterMitarbeiter" placeholder="Mitarbeitername">
</form>

<div id="modulCardContainer"></div>

<script>
  $(function() {
    function ladeModule() {
      $.post('admin_modulstatus.php', {
        ajax: 'filter',
        abteilung: $('#filterAbteilung').val(),
        status: $('#filterStatus').val(),
        mitarbeitername: $('#filterMitarbeiter').val()
      }, function(html) {
        $('#modulCardContainer').html(html);
      });
    }

    $('#filterForm select, #filterForm input').on('change keyup', ladeModule);

    $('#modulCardContainer').on('click', '.modul-card', function() {
      const modulId = $(this).data('modul-id');
      const abteilung = $('#filterAbteilung').val();
      const status = $('#filterStatus').val();
      const mitarbeitername = $('#filterMitarbeiter').val();

      const detailRow = $('#modul-detail-' + modulId);
      const isVisible = detailRow.is(':visible');

      $('.modul-card').removeClass('border-primary');
      $('.modul-detail').slideUp().html('');

      if (isVisible) return;

      $(this).addClass('border-primary');
      $.post('admin_modulstatus.php', {
        ajax: 'detail',
        modul_id: modulId,
        abteilung: abteilung,
        status: status,
        mitarbeitername: mitarbeitername
      }, function(data) {
        detailRow.html(data).slideDown();
      });
    });

    ladeModule(); // Initial laden
  });
</script>