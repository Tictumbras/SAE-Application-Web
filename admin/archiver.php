<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
verifierConnexion();
verifierRole('admin');

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $stmt = $pdo->prepare("UPDATE incidents SET archive = 1, statut = 'archive' WHERE id = ?");
    $stmt->execute([$id]);

    $hist = $pdo->prepare("INSERT INTO historique (id_incident, id_utilisateur, action) VALUES (?, ?, ?)");
    $hist->execute([$id, $_SESSION['user_id'], 'Incident archivé']);
}

header('Location: incidents.php');
exit();