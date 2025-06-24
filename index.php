<?php
session_start();
$page = $_GET['page'] ?? 'meine_schulungen';
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
  header("Location: login.php");
  exit;
}

require_once('./inc/functions.php');
require_once('./inc/db_helpers.php');
?>

<!DOCTYPE html>
<html lang="de">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Schulung</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link rel="stylesheet" href="inc/style.css">
  <script src="inc/functions.js"></script>
</head>

<body>

  <!-- Navbar -->
  <nav class="navbar navbar-expand-lg navbar-light bg-white shadow-sm border-bottom ">
    <div class="container">
      <a class="navbar-brand" href="#">
        <img src="images/hainzl_logo.png" alt="Logo" style="height: 50px; margin-top: 5px;">
      </a>

      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
        <span class="navbar-toggler-icon"></span>
      </button>

      <div class="collapse navbar-collapse" id="navbarNav">
        <ul class="navbar-nav">
          <li class="nav-item">
            <a class="nav-link <?php if ($page == 'meine_schulungen') echo 'active'; ?>" href="?page=meine_schulungen">Meine Schulungsnachweise</a>
          <li class="nav-item">
            <a class="nav-link <?php if ($page == 'abge_schulungen') echo 'active'; ?>" href="?page=abge_schulungen">Abgeschlossene Schulungsnachweise</a>
          </li>
          <?php if ($_SESSION['admin'] || $_SESSION['editor']) : ?>
            <li class="nav-item">
              <a class="nav-link <?php if ($page == 'neue_schulung') echo 'active'; ?>" href="?page=neue_schulung">Neuer Schulungsnachweis</a>
            </li>
          <?php endif; ?>
          <?php if ($_SESSION['admin']) : ?>
            <li class="nav-item">
              <a class="nav-link <?php if ($page == 'admin_panel') echo 'active'; ?>" href="?page=admin_panel">Admin</a>
            </li>
          <?php endif; ?>
          <li class="nav-item">
            <a class="nav-link <?php if ($page == 'logout') echo 'active'; ?>" href="?page=logout">Logout</a>
          </li>
        </ul>
      </div>
    </div>
  </nav>


  <!-- Hauptinhalt -->
  <main class="container mt-4">
    <?php loadPage(); ?>
  </main>

  <div id="meineTabelle-pager"></div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
<script>
  document.addEventListener("DOMContentLoaded", () => {
    if (typeof paginateTable === "function") {
      paginateTable("meineTabelle");
    }
  });
</script>

</html>