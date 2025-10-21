<?php
session_start();
require_once('./inc/functions.php');
require_once('./inc/db_helpers.php');

// .env file implimentieren
require_once __DIR__ . '/vendor/autoload.php';

use Dotenv\Dotenv;

$dotenv = Dotenv::createImmutable(__DIR__);
$dotenv->load();

$dotenv->required([
  'LDAP_HOST',
  'LDAP_DOMAIN',
  'LDAP_BASE_DN',
  'LDAP_SERVICE_USER',
  'LDAP_SERVICE_PASS'
])->notEmpty();

// .env Variablen laden
$ldap_host = $_ENV['LDAP_HOST'];
$ldap_domain = $_ENV['LDAP_DOMAIN'];
$ldap_base_dn = $_ENV['LDAP_BASE_DN'];
$ldap_service_user = $_ENV['LDAP_SERVICE_USER'];
$ldap_service_pass = $_ENV['LDAP_SERVICE_PASS'];

// provisorisch sollte mit cron job gemacht werden (führt nur aus wenn diese seite um 12:00 geöffnet ist)
$now = new DateTime('now', new DateTimeZone('Europe/Vienna'));
if ($now->format('H:i') === '12:00') {
  readLdap($ldap_host);
}


if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  checkUser($ldap_host);
}

function checkUser($ldap_host)
{
  // Verbindung zum Domänencontroller herstellen
  $ldap_conn = ldap_connect($ldap_host) or die("Fehler beim Verbinden mit AD-Server");
  ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);

  $username = $_POST['username'];
  $password = $_POST['password'];
  

  
  if ($ldap_conn) {
    $ldap_bind = @ldap_bind($ldap_conn, "HAINZL\\" . $username, $password);

    if ($ldap_bind) {
      echo "Bind was succesfull";


    } else {
      echo "Error, bind failed!";
    }
  }
}

// Bind: Service Acc mit Read permission
// Verbesserung: whenchanged in ldap, lastchanged in db tracken alle danach nur lesen (möglich mit $filter)
function readLdap($ldap_host)
{
  global $ldap_service_user, $ldap_service_pass;

  // Verbindung zum Domänencontroller herstellen
  $ldap_conn = ldap_connect($ldap_host) or die("Fehler beim Verbinden mit AD-Server");
  ldap_set_option($ldap_conn, LDAP_OPT_PROTOCOL_VERSION, 3);


  if ($ldap_conn) {
    $ldap_bind = @ldap_bind($ldap_conn, $ldap_service_user, $ldap_service_pass); 
 
    if ($ldap_bind) {
      echo "Bind was succesfull";
    } else {
      echo "Error, bind failed!";
    }
  }


  $root = "dc=hainzl,dc=at";
  $filter = "";
  $justthese = array("ou", "sn", "givenname", "mail");
  $sr = ldap_search($ldap_conn, $root, $filter, $justthese);

  $entries = ldap_get_entries($ldap_conn, $sr);

  syncDBwithLDAP($entries);
}

function syncDBwithLDAP(array $dataset)
{
  // verlicht primary key wegen duplikaten. 
  $query = "INSERT INTO `ldap_users` (`cn`, `mail`, `abteilung`) VALUES (?, ?, ?) 
    ON DUPLICATE KEY UPDATE 
    `mail` = VALUES(`mail`), 
    `abteilung` = VALUES(`abteilung`)";

  if (!empty($dataset) && $dataset['count'] > 0) {
    for ($i = 0; $i < $dataset['count']; $i++) {
      $data = $dataset[$i];

      $name = $data['cn'][0] ?? null;
      $mail = $data['mail'][0] ?? null;
      $abteilung = $data['abteilung'][0] ?? null;
      // und mehr...


      $stmt = makeStatement($query, [$name, $mail, $abteilung]);

      if (!$stmt) {
        error_log("DB insert failed!");
        continue;
      }
    }
  }
}

?>



<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>

<body class="d-flex justify-content-center align-items-center vh-100">
  <div class="card p-4">
    <h2>Login</h2>
    <?php if (isset($error)) echo "<div class='alert alert-danger'>$error</div>"; ?>
    <form method="POST">
      <div class="mb-3">
        <label class="form-label">Benutzername</label>
        <input type="text" name="username" class="form-control" required>
      </div>
      <div class="mb-3">
        <label class="form-label">Passwort</label>
        <input type="password" name="password" class="form-control" required>
      </div>
      <button type="submit" class="btn btn-primary">Login</button>
    </form>
  </div>
</body>

</html>