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
      $status = !$letzte ? 'faellig' : ((strtotime("+$zyklus months", strtotime($letzte)) < time()) ? 'faellig' : 'gueltig');

      if (!$filter || $filter === $status) {
        $anzeigen = true;
        break;
      }
    }

    if ($anzeigen) {
      echo "
      <div class='col'>
        <div class='modul-card card border border-light-subtle shadow-sm' data-modul-id='{$modul['ID']}' style='overflow: visible;'>
          <div class='modul-toggle card-body d-flex justify-content-between align-items-center' style='cursor: pointer;'>
            <div class='fw-semibold'>
              <i class='bi bi-box me-2 text-primary'></i> " . htmlspecialchars($modul['ModulName']) . "
            </div>
            <div class='text-muted small'>Klicken für Details</div>
          </div>
          <div id='modul-detail-{$modul['ID']}' class='modul-detail border-top bg-light-subtle' style='display: none;'></div>
        </div>
      </div>";
    }
  }

  echo "</div>";
  exit;
}

// -------- AJAX: Detailansicht mit paginierter Tabelle --------
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

  $tableId = "modulDetailTable_" . $modul_id;
  $pagerId = $tableId . "-pager";

  echo "
  <div class='table-responsive'>
    <table class='table table-sm table-hover mb-0' data-paginate id='$tableId'>
      <thead class='table-light'>
        <tr>
          <th>Name</th>
          <th>Status</th>
        </tr>
      </thead>
    <tbody>";

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
    $status = !$letzte ? 'faellig' : ((strtotime("+$zyklus months", strtotime($letzte)) < time()) ? 'faellig' : 'gueltig');

    if (!$filter || $filter === $status) {
      $text = match ($status) {
        'faellig' => !$letzte ? '<em>Nie geschult</em>' : "Überfällig: <strong>$letzte</strong>",
        'gueltig' => "Letzte Schulung: <strong>$letzte</strong>"
      };

      echo "
      <tr>
        <td><strong>{$m['MitarbeiterVorname']} {$m['MitarbeiterNachname']}</strong><br><small class='text-muted'>$text</small></td>
        <td>" . ucfirst($status) . "</td>
      </tr>";
      $gefunden++;
    }
  }

  echo "</tbody></table></div>";

  if (!$gefunden) {
    echo "<p class='text-muted mt-2 mb-0'>Keine passenden Mitarbeitenden gefunden.</p>";
  }

  exit;
}
?>

<!-- ---------- Filterbereich ---------- -->
<div class="card mt-3 mb-4 bg-white border-0 shadow">
  <div class="card-body py-3">
    <form id="filterForm">
      <div class="row g-3 align-items-center">
        <div class="col-md-auto">
          <label for="filterAbteilung" class="form-label mb-0 small text-muted">Abteilung</label>
          <select class="form-select form-select-sm" id="filterAbteilung">
            <option value="">Alle Abteilungen</option>
            <?php
            $stmt = makeStatement("SELECT DISTINCT MitarbeiterAbteilung FROM mitarbeiter ORDER BY MitarbeiterAbteilung");
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
              echo "<option value='{$row['MitarbeiterAbteilung']}'>{$row['MitarbeiterAbteilung']}</option>";
            }
            ?>
          </select>
        </div>

        <div class="col-md-auto">
          <label for="filterStatus" class="form-label mb-0 small text-muted">Status</label>
          <select class="form-select form-select-sm" id="filterStatus">
            <option value="">Alle</option>
            <option value="faellig">Fällig / Nie geschult</option>
            <option value="gueltig">Gültig</option>
          </select>
        </div>

        <div class="col-md-auto">
          <label for="filterMitarbeiter" class="form-label mb-0 small text-muted">Mitarbeiter</label>
          <input type="text" class="form-control form-control-sm" id="filterMitarbeiter" placeholder="z. B. Müller">
        </div>
      </div>
    </form>
  </div>
</div>

<!-- ---------- Modul-Karten ---------- -->
<div id="modulCardContainer"></div>

<!-- ---------- JS ---------- -->
<script>
  $(function() {
    function ladeModule() {
      $.post('page/admin_modulstatus.php', {
        ajax: 'filter',
        abteilung: $('#filterAbteilung').val(),
        status: $('#filterStatus').val(),
        mitarbeitername: $('#filterMitarbeiter').val()
      }, function(html) {
        $('#modulCardContainer').html(html);
      });
    }

    $('#filterForm select, #filterForm input').on('change keyup', ladeModule);

    // Event-Handler für das Öffnen/Schließen der Module
    $('#modulCardContainer').on('click', '.modul-toggle', function(e) {
      // Nur reagieren, wenn exakt der .modul-toggle-Bereich selbst angeklickt wurde
      if (e.target !== this) return;

      const card = $(this).closest('.modul-card');
      const modulId = card.data('modul-id');
      const abteilung = $('#filterAbteilung').val();
      const status = $('#filterStatus').val();
      const mitarbeitername = $('#filterMitarbeiter').val();

      const detailRow = $('#modul-detail-' + modulId);
      const isVisible = detailRow.is(':visible');

      // Vorher alles schließen
      $('.modul-card').removeClass('border-primary');
      $('.modul-detail').slideUp().html('');

      if (isVisible) return;

      card.addClass('border-primary');

      $.post('page/admin_modulstatus.php', {
        ajax: 'detail',
        modul_id: modulId,
        abteilung: abteilung,
        status: status,
        mitarbeitername: mitarbeitername
      }, function(data) {
        detailRow.html(data).slideDown(() => {
          setTimeout(() => {
            detailRow.find("table[data-paginate]").each(function() {
              if (this.id && $(this).find('tbody tr').length > 0) {
                paginateTable(this.id);
              }
            });
          }, 50);
        });
      });
    });

    // Event-Handler um das Schließen bei Klicks innerhalb der Detail-Ansicht zu verhindern
    $('#modulCardContainer').on('click', '.modul-detail', function(e) {
      e.stopPropagation();
    });

    $('#modulCardContainer').on('click', '.modul-detail .pagination, .modul-detail .pagination *', function(e) {
      e.stopPropagation();
    });

    ladeModule();
  });
</script>

<!-- ---------- CSS ---------- -->
<style>
  .modul-card {
    transition: border-color 0.2s, box-shadow 0.2s;
  }

  .modul-card.border-primary {
    border-width: 2px !important;
    border-color: #0d6efd !important;
    box-shadow: 0 0.25rem 1rem rgba(13, 110, 253, 0.1);
  }

  .modul-detail {
    padding: 0.75rem 1rem;
  }

  table[data-paginate] td {
    vertical-align: middle;
    border-bottom: 1px solid #eee;
    padding: 0.5rem 0.75rem;
  }

  table[data-paginate] tr:hover {
    background-color: #f8f9fa;
  }
</style>