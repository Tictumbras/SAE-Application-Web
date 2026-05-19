<?php
function verifierConnexion() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: /~u22504341/SAE/TrackInsi/index.php');
        exit();
    }
    global $pdo;
    $pdo->prepare("UPDATE utilisateurs SET derniere_activite = NOW() WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
}

function verifierRole($role) {
    if ($_SESSION['role'] !== $role) {
        header('Location: /~u22504341/SAE/TrackInsi/index.php');
        exit();
    }
}

function estActif($derniereActivite) {
    if (!$derniereActivite) return false;
    return (time() - strtotime($derniereActivite)) < 60;
}
?>