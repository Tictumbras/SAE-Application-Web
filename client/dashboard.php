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

$stmt = $pdo->prepare("SELECT * FROM incidents WHERE id_client = ? AND archive = 0 ORDER BY date_creation DESC");
$stmt->execute([$_SESSION['user_id']]);
$incidents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Mes incidents</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Mes incidents</a>
        <a href="nouvel_incident.php">Nouvel incident</a>
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
    <h2>Mes incidents</h2>

    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:20px;">
        <a href="nouvel_incident.php" class="btn btn-primary">+ Signaler un nouvel incident</a>
        <button onclick="window.location.reload()" class="btn btn-secondary">↻ Actualiser</button>
    </div>

    <?php if (empty($incidents)): ?>
        <p class="vide">Aucun incident pour le moment.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Titre</th><th>Priorité</th>
                <th>Statut</th><th>Date</th><th>Détail</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($incidents as $inc): ?>
            <tr>
                <td><?= $inc['id'] ?></td>
                <td><?= htmlspecialchars($inc['titre']) ?></td>
                <td><span class="badge badge-<?= $inc['priorite'] ?>"><?= $inc['priorite'] ?></span></td>
                <td><span class="statut statut-<?= $inc['statut'] ?>"><?= $inc['statut'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($inc['date_creation'])) ?></td>
                <td>
                    <a href="incident_detail.php?id=<?= $inc['id'] ?>"
                       class="btn btn-secondary"
                       onclick="event.stopPropagation()">
                        Voir
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script src="../assets/js/main.js"></script>
<script>
setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>