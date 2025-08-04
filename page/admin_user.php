<?php
require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

$kzn = getValueFromPostArray('mitarbeiter_kzn', '');
$isAjax = isset($_POST['isAjax']) && $_POST['isAjax'] == 1;

if ($isAjax && !empty($kzn)) {
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
}
?>

<!-- Normale Seite (nur Mitarbeiterauswahl) -->
<h3>Mitarbeiter wählen:</h3>
<form id="filterForm" class="mb-3">
  <select name="mitarbeiter_kzn" class="form-select w-auto" id="mitarbeiterSelect">
    <option value="">-- Mitarbeiter wählen --</option>
    <?php
    $stmt = makeStatement("SELECT * FROM mitarbeiter ORDER BY MitarbeiterNachname");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
      echo "<option value='{$row['MitarbeiterKZN']}'>{$row['MitarbeiterVorname']} {$row['MitarbeiterNachname']} ({$row['MitarbeiterKZN']})</option>";
    }
    ?>
  </select>
</form>

<div id="schulungsContainer"></div>

<script>
  $(document).ready(function() {
    $('#mitarbeiterSelect').on('change', function() {
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

    function paginateAll() {  
      if (typeof paginateTable === "function") {
        document.querySelectorAll("table[data-paginate]").forEach(table => {
          paginateTable(table.id);
        });
      }
    }
  });
</script>
