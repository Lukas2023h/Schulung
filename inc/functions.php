<?php

function loadPage()
{
  if (isset($_GET["page"])) {
    include 'page/' . $_GET["page"] . '.php';
  } else {
    include 'page/meine_schulungen.php';
  }
}


function getValueFromPostArray($name, $defaultValue = "")
{
  if (isset($_POST[$name])) {
    return $_POST[$name];
  } else {
    return $defaultValue;
  }
}


function makeStatement($query, $arrayValues = NULL)
{
  global $dbCon;
  $stmt = $dbCon->prepare($query);
  $stmt->execute($arrayValues);
  return $stmt;
}
