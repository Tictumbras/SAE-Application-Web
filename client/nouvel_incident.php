<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
verifierConnexion();
verifierRole('client');

$userConnecte = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$userConnecte->execute([$_SESSION['user_id']]);
$userConnecte = $userConnecte->fetch();
$photoSrc = $userConnecte['photo'] ?? null;

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titre       = trim($_POST['titre']);
    $description = trim($_POST['description']);
    $priorite    = $_POST['priorite'];

    if (empty($titre) || empty($description)) {
        $erreur = "Veuillez remplir tous les champs.";
    } else {
        $stmt = $pdo->prepare("INSERT INTO incidents (titre, description, priorite, id_client) VALUES (?, ?, ?, ?)");
        $stmt->execute([$titre, $description, $priorite, $_SESSION['user_id']]);
        $idIncident = $pdo->lastInsertId();

        $pdo->prepare("INSERT INTO historique (id_incident, id_utilisateur, action) VALUES (?, ?, ?)")
            ->execute([$idIncident, $_SESSION['user_id'], 'Incident créé']);

        header('Location: dashboard.php');
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Nouvel incident</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Mes incidents</a>
        <a href="nouvel_incident.php" class="active">Nouvel incident</a>
    </div>
    <div style="padding:0 8px; margin-bottom:4px;">
        <button id="toggleTheme" class="btn btn-secondary" style="width:100%; justify-content:flex-start; gap:10px;">
            <span id="themeIcon">☀</span>
            <span id="themeLabel">Thème clair</span>
        </button>
    </div>
    <div class="nav-bottom">
        <a href="../logout.php" class="nav-logout">Deconnexion</a>
        <a href="../profil.php" class="nav-user">
            <?php if ($photoSrc): ?>
                <img src="<?= htmlspecialchars($photoSrc) ?>" class="avatar" alt="photo">
            <?php else: ?>
                <div class="avatar-placeholder"></div>
            <?php endif; ?>
            <span><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
        </a>
    </div>
</nav>

<div class="container">
    <h2>Signaler un nouvel incident</h2>

    <?php if ($erreur): ?>
        <p class="alert erreur"><?= $erreur ?></p>
    <?php endif; ?>

    <div class="form-card">
        <form method="POST">
            <input type="text" name="titre" placeholder="Titre de l'incident" required>
            <textarea name="description" placeholder="Décrivez l'incident en détail..." rows="5" required></textarea>
            <select name="priorite" required>
                <option value="">-- Niveau de priorité --</option>
                <option value="faible">🟢 Faible</option>
                <option value="moyen">🟡 Moyen</option>
                <option value="critique">🔴 Critique</option>
            </select>
            <button type="submit" class="btn btn-primary">Envoyer l'incident</button>
            <a href="dashboard.php" class="btn btn-secondary">Annuler</a>
        </form>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>