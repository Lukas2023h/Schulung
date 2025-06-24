<?php
if (isset($_GET['id'])) {
  $id = intval($_GET['id']);

  require_once('../lib/tcpdf/tcpdf.php');
  require_once('../inc/functions.php');
  require_once('../inc/db_helpers.php');

  # DB Zugriff
  $query = "SELECT * FROM schulungen WHERE id = ?";
  $stmt = makeStatement($query, [$id]);
  $schulungen = $stmt->fetch(PDO::FETCH_ASSOC);

  if (!$schulungen) {
    die("Fehler: Schulung nicht gefunden.");
  }

  # SGU Aspekte abrufen
  $query2 = "SELECT sg.SGUName, shs.Beschreibung FROM SGU_has_Schulungen shs 
               JOIN SGU sg ON shs.SGU_ID = sg.ID 
               WHERE shs.Schulungen_ID = ?";
  $stmt = makeStatement($query2, [$id]);
  $SGUs = $stmt->fetchAll(PDO::FETCH_ASSOC);

  if (!empty($SGUs)) {
    $lines = [];
    foreach ($SGUs as $row) {
      $line = $row['SGUName'];
      if (!empty($row['Beschreibung'])) {
        $line .= ': ' . $row['Beschreibung'];
      }
      $lines[] = $line;
    }
    $sguText = implode("<br>", $lines);
  } else {
    $sguText = "Keine SGU-Aspekte";
  }



  $query3 = "SELECT m.* 
          FROM SchulungsTeilnahmen st
          JOIN mitarbeiter m ON st.Mitarbeiter_ID = m.ID
          WHERE st.Schulungen_ID = ?";

  $stmt = makeStatement($query3, [$id]);
  $teilnehmer = $stmt->fetchAll(PDO::FETCH_ASSOC);

  // Eigene PDF-Klasse mit Header & Footer
  class CustomPDF extends TCPDF
  {
    public function Header()
    {
      $image_file = '../images/hainzl_logo.png';
      $this->Image($image_file, 160, 10, 40, '', 'PNG');
      $this->SetFont('helvetica', 'B', 12);
      $this->SetXY(15, 10);
      $this->Cell(0, 5, 'Hainzl Industriesysteme GmbH', 0, 1, 'L');
      $this->SetFont('helvetica', '', 10);
      $this->SetX(15);
      $this->Cell(0, 5, 'Industriezeile 56, 4021 Linz, Österreich', 0, 1, 'L');
      $this->SetX(15);
      $this->Cell(0, 5, 'T +43 732 7892 0, info@hainzl.at, www.hainzl.at', 0, 1, 'L');
      $this->Ln(5);
    }

    public function Footer()
    {
      $this->SetY(-20);
      $this->SetFont('helvetica', 'B', 8);
      $this->Cell(0, 5, 'DOK // © 2022 HAINZL', 0, 1, 'L');
      // $this->Cell(0, 5, __FILE__, 0, 1, 'L');
      $this->Cell(0, 5, 'Rev. J, -pol, 241009-1450, Seite ' . $this->getAliasNumPage() . ' von ' . $this->getAliasNbPages(), 0, 0, 'L');
    }
  }

  // PDF erstellen
  $pdf = new CustomPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
  $pdf->SetTitle('Schulungsnachweis');

  // **Layout Einstellungen**
  $pdf->SetMargins(15, 40, 15);  // Seitenränder (links, oben, rechts)
  $pdf->SetHeaderMargin(10);      // Abstand vom Header
  $pdf->SetFooterMargin(15);      // Abstand vom Footer
  $pdf->SetAutoPageBreak(true, 20);  // Automatische Seitenumbrüche

  $pdf->AddPage(); // Neue Seite hinzufügen

  // HTML-Template für die PDF-Ausgabe
  $checkboxQM = $schulungen['QM'] ? '[X]' : '[ ]';

  $teilnehmerRows = "";
  foreach ($teilnehmer as $k) {
    $teilnehmerRows .= "<tr>
                              <td>{$schulungen['SchulungsDatum']}</td>
                              <td>{$k['MitarbeiterVorname']} {$k['MitarbeiterNachname']} ({$k['MitarbeiterKZN']})</td>
                              <td></td>
                          </tr>";
  }

  // HTML mit Teilnehmer-Tabelle in die PDF einfügen
  $html = <<<EOD
  <h1>SCHULUNGSNACHWEIS (SN)</h1>
  <p>HIT-18-SN-01-J</p>

  <p><strong>QM-SCHULUNG:</strong> {$checkboxQM} (Bitte ankreuzen, wenn Q-relevant!)</p>
  
  <table border="1" cellpadding="5" width="100%">
    <tr>
      <th width="30%"><strong>Zu schulende OE</strong></th>
      <td width="70%">{$schulungen['OE']}</td>
    </tr>
    <tr>
      <th width="30%"><strong>Vortragender (+OE)</strong></th>
      <td width="70%">{$schulungen['SchulungsLeiter']}</td>
    </tr>
    <tr>
      <th width="30%"><strong>Datum</strong></th>
      <td width="70%">{$schulungen['SchulungsDatum']}</td>
    </tr>
  </table>
  
  <br><br>
  
  <table border="1" cellpadding="5" width="100%">
    <tr>
      <th width="30%"><strong>Gegenstand</strong></th>
      <td width="70%">{$schulungen['SchulungsName']}</td>
    </tr>
    <tr>
      <th width="30%"><strong>Ziele</strong></th>
      <td width="70%">{$schulungen['SchulungsZiel']}</td>
    </tr>
    <tr>
      <th width="30%"><strong>AA</strong></th>
      <td width="70%">{$schulungen['AA']}</td>
    </tr>
    <tr>
      <th width="30%"><strong>SGU Aspekte</strong></th>
      <td width="70%">{$sguText}</td>
    </tr>
  </table>
  
  <br><br>
  
  <table border="1" cellpadding="5" width="100%">
    <tr>
      <th width="30%"><strong>Bezeichnung im DMS</strong></th>
      <td width="70%">{$schulungen['DMS']}</td>
    </tr>
  </table>
  
  
  <!-- Teilnehmer-Tabelle -->
  <p>Die Unterfertigten bestätigen durch ihre Unterschrift, an der obengenannten Schulung teilgenommen zu haben.</p>
  
  <table border="1" cellpadding="5" width="100%">
    <tr>
      <th width="20%"><strong>Datum</strong></th>
      <th width="40%"><strong>Vor- und Zuname</strong> (+Kurzzeichen)</th>
      <th width="40%"><strong>Unterschrift</strong></th>
    </tr>
    {$teilnehmerRows} <!-- Hier werden die Teilnehmer eingefügt -->
  </table>
  EOD;

  // HTML in das PDF schreiben
  $pdf->writeHTML($html, true, false, false, false, '');


  // PDF ausgeben
  $pdf->Output("Schulungsnachweis_{$id}.pdf", "I");
} else {
  echo "ID fehlt!";
}
