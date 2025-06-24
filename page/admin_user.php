<?php
require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

$kzn = getValueFromPostArray('mitarbeiter_kzn', '');
$abteilung = getValueFromPostArray('abteilung', '');
$modul_id = getValueFromPostArray('modul_id', '');
$isAjax = isset($_POST['isAjax']) && $_POST['isAjax'] == 1;

if ($isAjax) {
  if (!empty($kzn)) {
    // -------------------------- MITARBEITER-VIEW --------------------------
?>
    <h3>Bevorstehende Schulungen</h3>
    <table class="table table-striped table-hover" id="tabelle_offen" data-paginate>
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Datum</th>
          <th>Ort</th>
          <th>Leiter</th>
          <th>Ziel</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = 'SELECT s.ID, s.SchulungsName, s.SchulungsDatum, s.SchulungsOrt, s.SchulungsLeiter, s.SchulungsZiel
                  FROM Schulungen s
                  JOIN SchulungsTeilnahmen st ON s.ID = st.Schulungen_ID
                  JOIN Mitarbeiter m ON st.Mitarbeiter_ID = m.ID
                  WHERE m.MitarbeiterKZN = ? AND s.SchulungsDatum >= CURDATE()
                  GROUP BY s.ID';
        $stmt = makeStatement($query, [$kzn]);
        if ($stmt->rowCount()) {
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['SchulungsName']}</td><td>{$row['SchulungsDatum']}</td><td>{$row['SchulungsOrt']}</td><td>{$row['SchulungsLeiter']}</td><td>{$row['SchulungsZiel']}</td></tr>";
          }
        } else {
          echo "<tr><td colspan='5' class='text-center'>Keine bevorstehenden Schulungen gefunden</td></tr>";
        }
        ?>
      </tbody>
    </table>
    <div id="tabelle_offen-pager"></div>

    <h3>Abgeschlossene Schulungen</h3>
    <table class="table table-striped table-hover" id="tabelle_abge" data-paginate>
      <thead class="table-light">
        <tr>
          <th>Name</th>
          <th>Datum</th>
          <th>Ort</th>
          <th>Leiter</th>
          <th>Ziel</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $query = 'SELECT s.ID, s.SchulungsName, s.SchulungsDatum, s.SchulungsOrt, s.SchulungsLeiter, s.SchulungsZiel
                  FROM Schulungen s
                  JOIN SchulungsTeilnahmen st ON s.ID = st.Schulungen_ID
                  JOIN Mitarbeiter m ON st.Mitarbeiter_ID = m.ID
                  WHERE m.MitarbeiterKZN = ? AND s.SchulungsDatum < CURDATE()
                  GROUP BY s.ID';
        $stmt = makeStatement($query, [$kzn]);
        if ($stmt->rowCount()) {
          while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr><td>{$row['SchulungsName']}</td><td>{$row['SchulungsDatum']}</td><td>{$row['SchulungsOrt']}</td><td>{$row['SchulungsLeiter']}</td><td>{$row['SchulungsZiel']}</td></tr>";
          }
        } else {
          echo "<tr><td colspan='5' class='text-center'>Keine abgeschlossenen Schulungen gefunden</td></tr>";
        }
        ?>
      </tbody>
    </table>
    <div id="tabelle_abge-pager"></div>

    <h3>Überfällige Module</h3>
    <table class="table table-striped table-hover" id="tabelle_modul" data-paginate>
      <thead class="table-light">
        <tr>
          <th>Modul</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        <?php
        $stmtModule = makeStatement("SELECT ID, ModulName, ModulZyklus FROM module");
        $module = $stmtModule->fetchAll(PDO::FETCH_ASSOC);

        $stmtMID = makeStatement("SELECT ID FROM mitarbeiter WHERE MitarbeiterKZN = ?", [$kzn]);
        $row = $stmtMID->fetch(PDO::FETCH_ASSOC);

        if ($row) {
          $mitarbeiterID = $row['ID'];
          foreach ($module as $modul) {
            $stmtLast = makeStatement("
              SELECT MAX(s.SchulungsDatum) AS letzte
              FROM schulungen s
              JOIN module_has_schulungen mhs ON mhs.Schulungen_ID = s.ID
              JOIN schulungsteilnahmen st ON st.Schulungen_ID = s.ID
              WHERE mhs.Module_ID = ? AND st.Mitarbeiter_ID = ?
            ", [$modul['ID'], $mitarbeiterID]);

            $last = $stmtLast->fetch(PDO::FETCH_ASSOC)['letzte'];
            $status = !$last ? "Nie geschult" : (
              strtotime("+{$modul['ModulZyklus']} months", strtotime($last)) < time()
              ? "Überfällig (letzte am $last)" : "Gültig (letzte am $last)"
            );
            if (!$last || strtotime("+{$modul['ModulZyklus']} months", strtotime($last)) < time()) {
              echo "<tr><td>{$modul['ModulName']}</td><td>{$status}</td></tr>";
            }
          }
        } else {
          echo "<tr><td colspan='2' class='text-center'>Keine fälligen Module gefunden</td></tr>";
        }
        ?>
      </tbody>
    </table>
    <div id="tabelle_modul-pager"></div>
<?php
    exit;
  } elseif (!empty($abteilung) && empty($modul_id)) {
    // -------------------------- ABTEILUNG: MODUL-ÜBERSICHT --------------------------    
    echo "<div class='row row-cols-1 g-3 modul-list'>";

    $stmt = makeStatement("SELECT ID, ModulName FROM module ORDER BY ModulName");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      $modulId = $row['ID'];
      $modulName = htmlspecialchars($row['ModulName']);

      echo "
    <div class='col'>
      <div class='modul-card card shadow-sm border-0' data-modul-id='$modulId' style='cursor:pointer;'>
        <div class='card-body d-flex justify-content-between align-items-center'>
          <div class='fw-semibold'><i class='bi bi-box'></i> $modulName</div>
          <div class='text-muted small'>Klicken für Details</div>
        </div>
      </div>
      <div id='modul-detail-$modulId' class='modul-detail mt-2' style='display:none;'></div>
    </div>
  ";
    }

    echo "</div>";
    exit;
  } elseif (!empty($abteilung) && !empty($modul_id)) {
    // -------------------------- ABTEILUNG: ÜBERFÄLLIGE FÜR MODUL --------------------------

    $stmtMA = makeStatement("SELECT ID, MitarbeiterVorname, MitarbeiterNachname FROM mitarbeiter WHERE MitarbeiterAbteilung = ?", [$abteilung]);
    $mitarbeiter = $stmtMA->fetchAll(PDO::FETCH_ASSOC);

    $stmtZyklus = makeStatement("SELECT ModulZyklus FROM module WHERE ID = ?", [$modul_id]);
    $zyklus = $stmtZyklus->fetch(PDO::FETCH_ASSOC)['ModulZyklus'];

    $überfällig = 0;

    echo "
  <div class='card border-start border-danger border-3 mb-3'>
    <div class='card-body p-3'>
      <h5 class='card-title text-danger mb-3'>Überfällige Mitarbeiter</h5>
  ";

    echo "<ul class='list-group list-group-flush'>";

    foreach ($mitarbeiter as $m) {
      $stmtLast = makeStatement("
      SELECT MAX(s.SchulungsDatum) AS letzte
      FROM schulungen s
      JOIN module_has_schulungen mhs ON mhs.Schulungen_ID = s.ID
      JOIN schulungsteilnahmen st ON st.Schulungen_ID = s.ID
      WHERE mhs.Module_ID = ? AND st.Mitarbeiter_ID = ?
    ", [$modul_id, $m['ID']]);

      $letzte = $stmtLast->fetch(PDO::FETCH_ASSOC)['letzte'];
      $fällig = !$letzte || strtotime("+$zyklus months", strtotime($letzte)) < time();

      if ($fällig) {
        $info = $letzte ? "Zuletzt geschult: <strong>$letzte</strong>" : "<em>Nie geschult</em>";
        echo "
      <li class='list-group-item d-flex justify-content-between align-items-start'>
        <div class='ms-2 me-auto'>
          <div class='fw-bold'>{$m['MitarbeiterVorname']} {$m['MitarbeiterNachname']}</div>
          <small class='text-muted'>$info</small>
        </div>
      </li>";
        $überfällig++;
      }
    }

    echo "</ul>";

    if (!$überfällig) {
      echo "<p class='text-muted mt-3 mb-0'>Keine überfälligen Mitarbeiter in dieser Abteilung.</p>";
    }

    echo "</div></div>";
    exit;
  }
}
?>

<!-- Normale Seite (Formular & Container) -->
<h3>Filter wählen:</h3>
<form id="filterForm" class="d-flex gap-2 mb-3">
  <select name="mitarbeiter_kzn" class="form-select w-auto" id="mitarbeiterSelect">
    <option value="">-- Mitarbeiter wählen --</option>
    <?php
    $stmt = makeStatement("SELECT * FROM mitarbeiter ORDER BY MitarbeiterNachname");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<option value='{$row['MitarbeiterKZN']}'>{$row['MitarbeiterVorname']} {$row['MitarbeiterNachname']} ({$row['MitarbeiterKZN']})</option>";
    }
    ?>
  </select>

  <select name="abteilung" class="form-select w-auto" id="abteilungSelect">
    <option value="">-- Abteilung wählen --</option>
    <?php
    $stmt = makeStatement("SELECT DISTINCT MitarbeiterAbteilung FROM mitarbeiter ORDER BY MitarbeiterAbteilung");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<option value='{$row['MitarbeiterAbteilung']}'>{$row['MitarbeiterAbteilung']}</option>";
    }
    ?>
  </select>
</form>

<div id="schulungsContainer"></div>

<script>
  $(document).ready(function() {
    $('#mitarbeiterSelect').on('change', function() {
      $('#abteilungSelect').val('');
      const kzn = $(this).val();
      if (!kzn) return;

      $.post('page/admin_user.php', {
        mitarbeiter_kzn: kzn,
        isAjax: 1
      }, function(data) {
        $('#schulungsContainer').html(data);
        paginateAll();
      });
    });

    $('#abteilungSelect').on('change', function() {
      $('#mitarbeiterSelect').val('');
      const abteilung = $(this).val();
      if (!abteilung) return;

      $.post('page/admin_user.php', {
        abteilung: abteilung,
        isAjax: 1
      }, function(data) {
        $('#schulungsContainer').html(data);
      });
    });

    $('#schulungsContainer').on('click', '.modul-card', function() {
      const modulId = $(this).data('modul-id');
      const abteilung = $('#abteilungSelect').val();
      const detailRow = $('#modul-detail-' + modulId);
      const isVisible = detailRow.is(':visible');

      // Alle schließen
      $('.modul-card').removeClass('border-primary');
      $('.modul-detail').slideUp().html('');

      if (isVisible) return;

      // Aktuelle Karte markieren
      $(this).addClass('border-primary');

      // Inhalt laden und anzeigen
      $.post('page/admin_user.php', {
        abteilung: abteilung,
        modul_id: modulId,
        isAjax: 1
      }, function(data) {
        detailRow.html(data).slideDown();
      });
    });




    function paginateAll() {
      if (typeof paginateTable === "function") {
        document.querySelectorAll("table[data-paginate]").forEach(table => {
          paginateTable(table.id);
        });
      }
    }
  });
</script>