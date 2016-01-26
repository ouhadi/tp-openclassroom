<?php
// CONNEXION A LA BASE DE DONNEES
// Changez ces informations si nécessaire
$host = 'localhost';
$dbname = 'octp_minichat';
$charset = 'utf8';
$user = 'root';
$password = '';
$bdd = new PDO('mysql:host=' . $host . ';dbname=' . $dbname . ';charset=' . $charset . '', $user, $password);
?>