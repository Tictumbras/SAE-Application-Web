<?php
session_start();

$host   = '';
$dbname = '';
$user   = '';
$pass   = '';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8", $user, $pass);
    if (isset($_SESSION['user_id'])) {
        $pdo->prepare("UPDATE utilisateurs SET derniere_activite = NULL WHERE id = ?")
            ->execute([$_SESSION['user_id']]);
    }
} catch (PDOException $e) {}

session_unset();
session_destroy();
header('Location: /~u22504341/SAE/TrackInsi/index.php');
exit();
?>
