<?php
$host   = 'localhost';
$dbname = 'db_DUMONT';
$user   = '22504341';          // login IUT
$pass   = '764904'; // mdp phpMyAdmin

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>