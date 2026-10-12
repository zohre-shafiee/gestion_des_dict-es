<?php

$host = "localhost";
$user = "m2dilipem";
$password = "m2dilipem";   // remplacer par le mot de passe de la base de données
$dbname = "gr1m1IDL";

$conn = new mysqli($host, $user, $password, $dbname);

if ($conn->connect_error) {
    die("Erreur connexion : " . $conn->connect_error);
}

$conn->set_charset("utf8");

?>