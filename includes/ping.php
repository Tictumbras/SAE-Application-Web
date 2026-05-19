<?php
session_start();
require 'db.php';

if (isset($_SESSION['user_id'])) {
    $pdo->prepare("UPDATE utilisateurs SET derniere_activite = NOW() WHERE id = ?")
        ->execute([$_SESSION['user_id']]);
    echo 'ok';
} else {
    echo 'no session';
}
?>