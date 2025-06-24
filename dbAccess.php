<?php

require_once("./inc/db_helpers.php");

$dbCon = null;
$errMsg = "";

if (getDBConnection($dbCon, $errMsg)) {
} else {
  echo $errMsg;
}
