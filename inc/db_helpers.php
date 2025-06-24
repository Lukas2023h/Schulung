<?php

$server = "localhost";
const USER = "root";
const PWD = "";
const DB = "mydb";
const PORT = 3306;

global $dbCon;

function getDBConnection(&$dbCon, &$errMsg)
{
  global $server;

  try {
    $user = USER;
    $pwd = PWD;
    $db = DB;
    $port = PORT;

    $dbCon = new PDO("mysql:host=$server;port=$port;dbname=$db;charset=utf8mb4", $user, $pwd);
    $dbCon->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $GLOBALS['dbCon'] = $dbCon;

    return true;
  } catch (Exception $ex) {
    $errMsg = $ex->getMessage();
    return false;
  }
}

$errMsg = "";
if (!getDBConnection($dbCon, $errMsg)) {
  die("Fehler bei der Datenbankverbindung: " . $errMsg);
}
