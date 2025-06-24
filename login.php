<?php
session_start();
require_once('./inc/functions.php');
require_once('./inc/db_helpers.php');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
  $username = $_POST['username'];
  $password = $_POST['password'];

  $query = 'select MitarbeiterKZN, Admin, Editor from mitarbeiter where `MitarbeiterKZN` = ?';

  $stmt = makeStatement($query, [$username]);

  $user = $stmt->fetch(PDO::FETCH_NUM);

  if ($user && $password > 0) {
    $_SESSION['loggedin'] = true;
    $_SESSION['username'] = $username;

    if (intval($user[1]) === 1) {
      $_SESSION['admin'] = true;
    } else {
      $_SESSION['admin'] = false;
    }

    if (intval($user[2]) === 1) {
      $_SESSION['editor'] = true;
    } else {
      $_SESSION['editor'] = false;
    }

    header("Location: index.php");
    exit;
  } else {
    $error = "Falsche Anmeldedaten!";
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