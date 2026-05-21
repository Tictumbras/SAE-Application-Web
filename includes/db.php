<?php
$host   = 'localhost';           // Adresse du serveur MySQL
$dbname = 'nom_de_votre_bdd';   // Nom de votre base de données
$user   = 'votre_login';        // Identifiant MySQL
$pass   = 'votre_mot_de_passe'; // Mot de passe MySQL

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die("Erreur de connexion : " . $e->getMessage());
}
?>
