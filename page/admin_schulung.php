<?php

require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

?>
<!-- Zeit filtern -->
<!-- <div class="mb-3 d-flex align-items-center gap-2">
  <label for="yearFilter" class="form-label fw-bold mb-0">Zeitraum filtern:</label>
  <select id="yearFilter" class="form-select w-auto" onchange="applySchulungsFilter()">
    <option value="all">Alle Schulungen</option>
    <option value="this">Nur dieses Jahr</option>
    <option value="last">Nur letztes Jahr</option>
    <option value="both">Dieses & letztes Jahr</option>
  </select>
</div> -->

<input type="checkbox" name="years" id="years" onchange="setupTimeFilter()">
<label for="years">Nur dieses und letztes Jahr</label>

<!-- Suchfeld -->
<input id="txtFilter" type="text" class="form-control mb-3" placeholder="Schulungen filtern..." />

<!-- Alle Schulungen -->
<table id="meineTabelle" data-paginate class="table table-striped table-hover">
  <thead class="table-light">
    <tr>
      <th>Name</th>
      <th>Datum</th>
      <th>Ort</th>
      <th>Leiter</th>
      <th>Ziel</th>
      <th>Aktionen</th>
    </tr>
  </thead>
  <tbody>
    <?php
    $query = 'SELECT ID, SchulungsName, SchulungsDatum, SchulungsOrt, SchulungsLeiter, 
                 SchulungsZiel
              FROM Schulungen 
              ORDER BY SchulungsDatum ASC';

    $stmt = makeStatement($query);

    if ($stmt->rowCount() > 0) {
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr class='dataRow'>";
        echo "<td>" . htmlspecialchars($row['SchulungsName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsDatum']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsOrt']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsLeiter']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsZiel']) . "</td>";
        echo "<td>
                <a href='page/pdf_erstellen.php?id=" . $row['ID'] . "' class='btn btn-sm btn-primary'>PDF erstellen</a>
                <a href='page/schulung_details.php?id=" . $row['ID'] . "' class='btn btn-sm btn-info'>Details anzeigen</a>
              </td>";
        echo "</tr>";
      }
    } else {
      echo "<tr><td colspan='7' class='text-center'>Keine zukünftigen Schulungen gefunden</td></tr>";
    }
    ?>
  </tbody>
</table>

<div id="meineTabelle-pager"></div>


<script>
  function setupSchulungsFilter() {
    jQuery.expr[':'].casecontains = function(a, i, m) {
      return jQuery(a).text().toUpperCase().indexOf(m[3].toUpperCase()) >= 0;
    };

    $("#txtFilter").on("keyup", function() {
      var data = this.value.trim().split(" ");
      var rows = $("#meineTabelle").find("tbody tr"); // :visible

      if (this.value === "") {
        rows.show();
        return;
      }

      rows.hide().filter(function() {
        var row = $(this);
        return data.some(function(term) {
          return row.is(":casecontains('" + term + "')");
        });
      }).show();
    });
  }

  function setupTimeFilter() {
    if ($("#years").is(":checked")) {
      $("#meineTabelle tbody tr").each(function() {
        let zelle = $(this).find("td").eq(1);
        let inhalt = new Date(zelle.text().trim());

        let minDate = new Date(new Date().getFullYear() - 1, 0, 1);
        let maxDate = new Date(new Date().getFullYear(), 11, 31);

        if (inhalt >= minDate && inhalt <= maxDate) {
          $(this).show();
        } else {
          $(this).hide();
        }
      });
    } else {
      $("#meineTabelle tbody tr").show();
    }
  }

  setupSchulungsFilter();
</script>