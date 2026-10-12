<?php

$host = "localhost";
$user = "m2dilipem";
$password = "m2dilipem";   // заменить на пароль базы
$dbname = "gr1m1IDL";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Erreur connexion : " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>
