<table class="table table-striped table-hover" id="meineTabelle">
  <thead class="table-light">
    <tr>
      <th>Name</th>
      <th>Datum</th>
      <th>Ort</th>
      <th>Leiter</th>
      <th>Ziel</th>
      <!-- <th>Aktionen</th> -->
    </tr>
  </thead>
  <tbody>
    <?php
    // SQL-Query: Holt alle zukünftigen Schulungen des eingeloggten Benutzers
    $query = 'SELECT s.ID, s.SchulungsName, s.SchulungsDatum, s.SchulungsOrt, s.SchulungsLeiter, s.SchulungsZiel
              FROM Schulungen s
              JOIN SchulungsTeilnahmen st ON s.ID = st.Schulungen_ID
              JOIN Mitarbeiter m ON st.Mitarbeiter_ID = m.ID
              WHERE m.MitarbeiterKZN = ?
              AND s.SchulungsDatum < CURDATE()
              GROUP BY s.ID';

    $stmt = makeStatement($query, [$_SESSION['username']]);

    // Prüfen, ob Ergebnisse vorhanden sind
    if ($stmt->rowCount() > 0) {
      while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "<tr>";
        echo "<td>" . htmlspecialchars($row['SchulungsName']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsDatum']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsOrt']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsLeiter']) . "</td>";
        echo "<td>" . htmlspecialchars($row['SchulungsZiel']) . "</td>";
        // echo "<td>
        //         <a href='page/pdf_erstellen.php?id=" . $row['ID'] . "' class='btn btn-sm btn-primary'>PDF erstellen</a>
        //         <a href='page/schulung_details.php?id=" . $row['ID'] . "' class='btn btn-sm btn-info'>Details anzeigen</a>
        //       </td>";
        echo "</tr>";
      }
    } else {
      // Falls keine Schulungen gefunden wurden
      echo "<tr><td colspan='7' class='text-center'>Keine abgeschlossenen Schulungen gefunden</td></tr>";
    }
    ?>
  </tbody>
</table>