<?php
session_start();

$DOM = file_get_contents("html/index.html");

$contenutoLogin = "";

if (!isset($_SESSION["is_logged_in"]) || $_SESSION["is_logged_in"] == false) {
  $contenutoLogin = "<p style='color:red'>Devi effettuare il login.</p>";
} else {
  $contenutoLogin = "<p style='color:green'>Accesso effettuato! 🎉</p>";
}
$DOM = str_replace("<!--LOGIN_PLACEHOLDER-->", $contenutoLogin, $DOM);

echo $DOM;
?>
