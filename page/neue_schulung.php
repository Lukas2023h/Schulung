<?php
if ($_SERVER["REQUEST_METHOD"] == "POST") {

  // Formulardaten aus POST-Array holen
  $schulungsname = getValueFromPostArray('schulung', '');
  $datum = getValueFromPostArray('date', '');
  $leiter = getValueFromPostArray('leiter', '');
  $ort = getValueFromPostArray('ort', '');
  $ziele = getValueFromPostArray('ziele', '');
  $oe = getValueFromPostArray('OE', '');
  $dms = getValueFromPostArray('DMS', '');
  $aa = getValueFromPostArray('AA', '');
  $qm = isset($_POST['QM']) ? 1 : 0;
  $mitarbeiter = getValueFromPostArray('mitarbeiter', []);
  $module = getValueFromPostArray('module', []);
  $sgu_aspekte = getValueFromPostArray('SGU', []);

  // **1. Schulung in die Datenbank einfügen**
  $query_schulung = "INSERT INTO `Schulungen` (`SchulungsName`, `SchulungsDatum`, `SchulungsLeiter`, `SchulungsOrt`, `SchulungsZiel`, `OE`, `DMS`, `AA`, `QM`) 
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
  $stmt = makeStatement($query_schulung, [$schulungsname, $datum, $leiter, $ort, $ziele, $oe, $dms, $aa, $qm]);

  if (!$stmt) {
    echo "<h2>Fehler beim Speichern des Schulungsnachweises</h2>";
    exit();
  }

  // Schulungs-ID abrufen
  $query_schulungs_id = "SELECT ID FROM Schulungen WHERE `SchulungsName` = ? AND `SchulungsDatum` = ?";
  $stmt = makeStatement($query_schulungs_id, [$schulungsname, $datum]);
  $schulungsID = $stmt->fetch(PDO::FETCH_NUM);

  if (!$schulungsID) {
    echo "<p>Fehler: Schulung nicht gefunden</p>";
    exit();
  }

  $schulungsID = $schulungsID[0];

  // **2. Mitarbeiter mit der Schulung verknüpfen**
  $query_mitarbeiter = "SELECT ID FROM mitarbeiter WHERE `MitarbeiterKZN` = ?";
  $query_insert_mitarbeiter = "INSERT INTO `SchulungsTeilnahmen` (`Mitarbeiter_ID`, `Schulungen_ID`) VALUES (?, ?)";

  foreach ($mitarbeiter as $mitarbeiterKZN) {
    $stmt = makeStatement($query_mitarbeiter, [$mitarbeiterKZN]);
    $row = $stmt->fetch(PDO::FETCH_NUM);

    if (!$row) {
      echo "<p>Fehler: Mitarbeiter mit KZN '$mitarbeiterKZN' nicht gefunden</p>";
      continue;
    }

    $stmt = makeStatement($query_insert_mitarbeiter, [$row[0], $schulungsID]);

    if (!$stmt) {
      echo "<p>Fehler beim Einfügen der Schulungsteilnahme für Mitarbeiter $mitarbeiterKZN</p>";
    }
  }

  // **3. Module mit der Schulung verknüpfen**
  $query_insert_module = "INSERT INTO `module_has_schulungen` (`Module_ID`, `Schulungen_ID`) VALUES (?, ?)";

  foreach ($module as $modulID) {
    $stmt = makeStatement($query_insert_module, [$modulID, $schulungsID]);

    if (!$stmt) {
      echo "<p>Fehler beim Verknüpfen des Moduls ID $modulID mit der Schulung</p>";
    }
  }

  // **4. SGU-Aspekte mit der Schulung verknüpfen**
  $query_insert_sgu = "INSERT INTO `SGU_has_Schulungen` (`SGU_ID`, `Schulungen_ID`, `Beschreibung`) VALUES (?, ?, ?)";

  foreach ($sgu_aspekte as $sguID) {
    $requiresText = in_array($sguID, [3, 4]); // ID 3 = Nachschulung, ID 4 = Sonstiges
    $descriptionKey = 'SGU_text_' . $sguID;
    $description = isset($_POST[$descriptionKey]) ? trim($_POST[$descriptionKey]) : null;

    $stmt = makeStatement($query_insert_sgu, [$sguID, $schulungsID, $description]);

    if (!$stmt) {
      echo "<p>Fehler beim Verknüpfen des SGU-Aspekts ID $sguID mit der Schulung</p>";
    }
  }


  echo "<p>Schulung erfolgreich erstellt und alle Daten wurden gespeichert.</p>";
}
?>


<div class="container mt-4">
  <h3 class="mb-3">Schulungnachweis erstellen</h3>
  <form action="" method="post" class="p-4 border rounded bg-light">

    <!--QM -->
    <div class="mb-3">
      <input type="checkbox" name="QM" id="QM" class="QM">
      <label for="QM" class="form-label"><strong>QM-Schulung</strong> (Bitte ankreuzen, wenn Q-relevant!)</label>
    </div>

    <!--Gegenstand (Schulungsname)-->
    <div class="mb-3">
      <label for="schulung" class="form-label fw-bold">Gegenstand (Schulungsnachweisname):</label>
      <input type="text" name="schulung" id="schulung" class="form-control" required>
    </div>

    <!-- Datum, Ort und OE in einer Zeile -->
    <div class="row mb-3">
      <!-- Datum -->
      <div class="col-md-4">
        <label for="date" class="form-label fw-bold">Datum:</label>
        <input type="date" name="date" id="date" class="form-control" required>
      </div>

      <!-- Ort -->
      <div class="col-md-4">
        <label for="ort" class="form-label fw-bold">Ort:</label>
        <input type="text" name="ort" id="ort" class="form-control" required>
      </div>

      <!-- OE -->
      <div class="col-md-4">
        <label for="OE" class="form-label fw-bold">Zu schulende OE:</label>
        <input type="text" name="OE" id="OE" class="form-control" required>
      </div>
    </div>

    <!--Ziele -->
    <div class="mb-3">
      <label for="ziele" class="form-label fw-bold">Ziele:</label>
      <input type="text" name="ziele" id="ziele" class="form-control" required>
    </div>

    <!--AA -->
    <div class="mb-3">
      <label for="AA" class="form-label fw-bold">AA:</label>
      <input type="text" name="AA" id="AA" class="form-control" required>
    </div>

    <!-- Module Filter-->
    <div class="d-flex justify-content-between align-items-center mb-3">
      <label class="mb-0 fw-bold">Module: </label>
      <div class="d-flex align-items-center">
        <label for="filter" class="me-2">Filtern:</label>
        <select name="filter" id="filter" class="form-select form-select-sm" onchange="filterModules()">
          <option value="ALLE">Alle</option>
          <option value="ES">ES</option>
          <option value="PERS">PERS</option>
          <option value="HEM">HEM</option>
        </select>
      </div>
    </div>

    <!-- Module-Checkboxen -->
    <div class="mb-3">
      <div class="mt-2" id="moduleList">
        <?php
        $queryModules = "SELECT * FROM module";
        $stmtModules = makeStatement($queryModules);

        while ($row = $stmtModules->fetch(PDO::FETCH_NUM)) {
          echo '<div class="form-check module-item" data-category="' . $row[2] . '">
              <input class="form-check-input" type="checkbox" id="module_' . $row[0] . '" name="module[]" value="' . $row[0] . '">
              <label class="form-check-label" for="module_' . $row[0] . '">' . $row[1] . '</label>
            </div>';
        }
        ?>
      </div>
    </div>

    <!--Schulungsleiter (Vortragender)-->
    <div class="mb-3">
      <label class="form-label fw-bold">Schulungsnachweisleiter (+OE):</label>
      <div class="mt-2">
        <div class="row">
          <?php
          $query1 = "SELECT * FROM mitarbeiter WHERE `Admin` = 1 OR `Editor` = 1";
          $stmt = makeStatement($query1);
          $counter = 0;
          $first = true;

          while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            if ($counter % 6 == 0) {
              echo '</div><div class="row mt-2">';
            }

            echo '<div class="col-md-2"> 
                <div class="form-check">
                  <input class="form-check-input" type="radio" id="' . $row[3] . '" name="leiter" value="' . $row[3] . '" ' . ($first ? 'checked' : '') . '>
                  <label class="form-check-label" for="' . $row[3] . '">' . $row[3] . '</label>
                </div>
              </div>';

            $first = false;
            $counter++;
          }
          ?>
        </div>
      </div>
    </div>

    <!--Mitarbeiter-->
    <div class="mb-3">
      <label class="form-label fw-bold">Mitarbeiter:</label>
      <button type="button" class="btn btn-sm btn-secondary" onclick="toggleCheckboxes(true)">Alle auswählen</button>
      <button type="button" class="btn btn-sm btn-secondary" onclick="toggleCheckboxes(false)">Alle abwählen</button>

      <div class="mt-2">
        <div class="row">
          <?php
          $query = "SELECT * FROM mitarbeiter";
          $stmt = makeStatement($query);
          $counter = 0;

          while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
            if ($counter % 6 == 0) {
              echo '</div><div class="row mt-2">';
            }

            echo '<div class="col-md-2"> 
                <div class="form-check">
                  <input class="form-check-input mitarbeiter-checkbox" type="checkbox" id="' . $row[3] . '" name="mitarbeiter[]" value="' . $row[3] . '">
                  <label class="form-check-label" for="' . $row[3] . '">' . $row[3] . '</label>
                </div>
              </div>';

            $counter++;
          }
          ?>
        </div>
      </div>
    </div>

    <!--SGU Aspekte --->

    <div class="mb-3">
      <label class="form-label fw-bold">SGU-Aspekte:</label>
      <div class="row mt-2">
        <?php
        $querySGU = "SELECT * FROM SGU";
        $stmtSGU = makeStatement($querySGU);

        while ($row = $stmtSGU->fetch(PDO::FETCH_NUM)) {
          echo '<div class="col-md-3"> 
              <div class="form-check">
                <input type="checkbox" name="SGU[]" id="sgu_' . $row[0] . '" value="' . $row[0] . '" class="form-check-input" onchange="toggleExtraInput(' . $row[0] . ')">
                <label for="sgu_' . $row[0] . '" class="form-check-label">' . $row[1] . '</label>
              </div>';

          if (in_array($row[1], ['Nachschulung nach Arbeitsunfall', 'Sonstiges'])) {
            echo '<input type="text" class="form-control mt-2 d-none" name="SGU_text_' . $row[0] . '" id="input_sgu_' . $row[0] . '" placeholder="Bitte angeben...">';
          }

          echo '</div>';
        }
        ?>
      </div>
    </div>


    <!--DMS -->
    <div class="mb-3">
      <label for="DMS" class="form-label fw-bold">Bezeichnung im DMS:</label>
      <input type="text" name="DMS" id="DMS" class="form-control" required placeholder="max. 60 Zeichen" maxlength="60">
    </div>


    <button type="submit" class="btn btn-primary">Erstellen</button>
  </form>
</div>

<script>
  function filterModules() {
    let filterValue = document.getElementById("filter").value;
    let modules = document.querySelectorAll(".module-item");

    modules.forEach(module => {
      let category = module.getAttribute("data-category");
      if (filterValue === "ALLE" || category === filterValue) {
        module.style.display = "block"; // Modul anzeigen
      } else {
        module.style.display = "none"; // Modul ausblenden
      }
    });
  }

  function toggleExtraInput(id) {
    const checkbox = document.getElementById('sgu_' + id);
    const input = document.getElementById('input_sgu_' + id);
    if (!checkbox || !input) return;

    if (checkbox.checked) {
      input.classList.remove('d-none');
      input.disabled = false;
      input.required = true;
    } else {
      input.classList.add('d-none');
      input.disabled = true;
      input.required = false;
      //input.value = ""; // leeren, wenn abgewählt
    }
  }

  function toggleCheckboxes(status) {
    let checkboxes = document.querySelectorAll('.mitarbeiter-checkbox');
    checkboxes.forEach(checkbox => checkbox.checked = status);
  }

  document.addEventListener("DOMContentLoaded", function() {
    const form = document.querySelector("form");
    const checkboxes = document.querySelectorAll(".mitarbeiter-checkbox");

    form.addEventListener("submit", function(event) {
      let checkedCount = Array.from(checkboxes).filter(checkbox => checkbox.checked).length;

      if (checkedCount === 0) {
        event.preventDefault();
        alert("Bitte wähle mindestens einen Mitarbeiter aus!");
      }
    });
  });
</script>