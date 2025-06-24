<?php

require_once('../inc/functions.php');
require_once('../inc/db_helpers.php');

function upload()
{
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['pdfFile'])) {

        $uploadDir = "../uploads/"; // Ordner, in dem die PDFs gespeichert werden

        // Dateiname generieren
        $uploadFile = $uploadDir . "Schulungen_" . intval($_GET['id']) . ".pdf";

        // Maximale Dateigröße überprüfen (5MB)
        if ($_FILES["pdfFile"]["size"] > 5 * 1024 * 1024) {
            die("<div class='alert alert-danger'>Fehler: Die Datei ist zu groß (max. 5MB).</div>");
        }

        // Datei auf den Server verschieben
        if (move_uploaded_file($_FILES["pdfFile"]["tmp_name"], $uploadFile)) {
            echo "<div class='alert alert-success'>Die Datei wurde erfolgreich hochgeladen.</div>";
            echo "<p><a href='$uploadFile' target='_blank' class='btn btn-info btn-sm'>PDF öffnen</a></p>";
        } else {
            echo "<div class='alert alert-danger'>Fehler beim Hochladen der Datei.</div>";
        }
    } else {
        echo "<div class='alert alert-danger'>Fehler: Keine Datei hochgeladen.</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['addMitarbeiter'])) {
    $mitarbeiterID = intval($_POST['mitarbeiter_id']);
    $id = intval($_GET['id']);

    if ($mitarbeiterID > 0) {
        $queryInsert = "INSERT INTO SchulungsTeilnahmen (Mitarbeiter_ID, Schulungen_ID) VALUES (?, ?)";
        $stmt = makeStatement($queryInsert, [$mitarbeiterID, $id]);

        if ($stmt) {
            echo "<div class='alert alert-success'>Mitarbeiter wurde erfolgreich zur Schulung hinzugefügt.</div>";
            header("Refresh:0");
            exit();
        } else {
            echo "<div class='alert alert-danger'>Fehler beim Hinzufügen des Mitarbeiters.</div>";
        }
    } else {
        echo "<div class='alert alert-warning'>Bitte einen gültigen Mitarbeiter auswählen.</div>";
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['pdfFile'])) {
    upload();
}

if (isset($_GET['id'])) {
    require_once('../inc/functions.php');
    require_once('../inc/db_helpers.php');

    $id = intval($_GET['id']);

    $query3 = "SELECT m.* 
               FROM SchulungsTeilnahmen st
               JOIN mitarbeiter m ON st.Mitarbeiter_ID = m.ID
               WHERE st.Schulungen_ID = ?";
    $stmt = makeStatement($query3, [$id]);
    $teilnehmer = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['del'])) {
        $mitarbeiterKZN = $_POST['del'];

        $queryGetMitarbeiterID = "SELECT ID FROM mitarbeiter WHERE MitarbeiterKZN = ?";
        $stmt = makeStatement($queryGetMitarbeiterID, [$mitarbeiterKZN]);
        $mitarbeiter = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($mitarbeiter) {
            $mitarbeiterID = $mitarbeiter['ID'];

            $queryDelete = "DELETE FROM SchulungsTeilnahmen WHERE Schulungen_ID = ? AND Mitarbeiter_ID = ?";
            $stmt = makeStatement($queryDelete, [$id, $mitarbeiterID]);

            if ($stmt) {
                echo "<div class='alert alert-success'>Mitarbeiter $mitarbeiterKZN wurde aus der Schulung entfernt.</div>";
                header("Refresh:0");
                exit();
            } else {
                echo "<div class='alert alert-danger'>Fehler beim Löschen des Mitarbeiters.</div>";
            }
        } else {
            echo "<div class='alert alert-warning'>Mitarbeiter nicht gefunden.</div>";
        }
    }
} else {
    echo "<div class='alert alert-danger'>ID fehlt</div>";
}

?>

<!DOCTYPE html>
<html lang="de">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Schulung</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="bg-light">

    <div class="container mt-5">
        <div class="card shadow-sm">
            <div class="card-body">
                <h2 class="mb-4">Teilnehmerverwaltung</h2>

                <h4 class="mb-3">Teilnehmer</h4>
                <table class="table table-striped table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Name</th>
                            <th>Aktionen</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        foreach ($teilnehmer as $k) {
                            echo "<tr>";
                            echo "<td>" . $k['MitarbeiterVorname'] . " " . $k['MitarbeiterNachname'] . " (" . $k['MitarbeiterKZN'] . ")</td>";
                            echo '<td>
                                <form action="" method="post" class="d-inline">
                                    <button type="submit" name="del" value="' . $k['MitarbeiterKZN'] . '" class="btn btn-danger btn-sm" onclick="return confirm(\'Soll dieser Mitarbeiter wirklich entfernt werden?\')">
                                        Löschen
                                    </button>
                                </form>
                              </td>';
                            echo "</tr>";
                        }
                        ?>
                    </tbody>
                </table>

                <h4 class="mt-4">Mitarbeiter zur Schulung hinzufügen</h4>
                <form action="" method="post" class="d-flex gap-2">
                    <select name="mitarbeiter_id" class="form-select" required>
                        <option value="">-- Bitte wählen --</option>
                        <?php
                        $query = "SELECT m.* FROM mitarbeiter m
                              LEFT JOIN SchulungsTeilnahmen st ON m.ID = st.Mitarbeiter_ID AND st.Schulungen_ID = ?
                              WHERE st.Mitarbeiter_ID IS NULL";
                        $stmt = makeStatement($query, [$id]);
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                            echo "<option value='" . $row['ID'] . "'>" . htmlspecialchars($row['MitarbeiterVorname'] . " " . $row['MitarbeiterNachname'] . " (" . $row['MitarbeiterKZN'] . ")") . "</option>";
                        }
                        ?>
                    </select>
                    <button type="submit" name="addMitarbeiter" class="btn btn-success">Hinzufügen</button>
                </form>

                <h4 class="mt-4">PDF Hochladen</h4>
                <form action="" method="post" enctype="multipart/form-data" class="p-3 border rounded bg-light">
                    <input type="file" name="pdfFile" class="form-control mb-2" accept=".pdf" required>
                    <button type="submit" class="btn btn-primary">Hochladen</button>
                </form>
                <br>

                <?php
                $pdfPath = "../uploads/Schulungsnachweis_" . $_GET['id'] . ".pdf";
                if (file_exists($pdfPath)) {
                    echo "<a href='$pdfPath' target='_blank' class='btn btn-sm btn-success'>PDF anzeigen</a>";
                } else {
                    echo "<span class='text-muted'>Keine PDF</span>";
                }
                ?>
            </div>
        </div>
    </div>

</body>

</html>

<a href=""></a>