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

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: dashboard.php'); exit(); }

$stmt = $pdo->prepare("SELECT * FROM incidents WHERE id = ? AND id_client = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$incident = $stmt->fetch();
if (!$incident) { header('Location: dashboard.php'); exit(); }

$hist = $pdo->prepare("SELECT h.*, u.nom, u.prenom, u.role FROM historique h
                        JOIN utilisateurs u ON h.id_utilisateur = u.id
                        WHERE h.id_incident = ? ORDER BY h.date ASC");
$hist->execute([$id]);
$historique = $hist->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Détail incident</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Mes incidents</a>
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
    <a href="dashboard.php" class="btn btn-secondary" style="margin-bottom:20px; display:inline-flex;">← Retour</a>

    <h2>Incident #<?= $incident['id'] ?> — <?= htmlspecialchars($incident['titre']) ?></h2>

    <!-- Infos générales -->
    <div class="form-card">
        <p><strong>Description :</strong> <?= htmlspecialchars($incident['description']) ?></p>
        <p><strong>Priorité :</strong> <span class="badge badge-<?= $incident['priorite'] ?>"><?= $incident['priorite'] ?></span></p>
        <p><strong>Statut :</strong> <span class="statut statut-<?= $incident['statut'] ?>"><?= $incident['statut'] ?></span></p>
        <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($incident['date_creation'])) ?></p>
    </div>

    <!-- Description de résolution visible uniquement si résolu ou archivé -->
    <?php if (in_array($incident['statut'], ['resolu', 'archive']) && $incident['commentaire_resolution']): ?>
    <div class="form-card" style="border-color:rgba(63,185,80,0.35);">
        <h3 style="color:var(--success-text); text-transform:none; letter-spacing:0; font-size:0.95rem; margin-bottom:12px;">
            ✅ Résolution du technicien
        </h3>
        <p style="color:var(--text-primary); line-height:1.7;">
            <?= nl2br(htmlspecialchars($incident['commentaire_resolution'])) ?>
        </p>
    </div>
    <?php elseif ($incident['statut'] === 'en_cours'): ?>
    <div class="form-card" style="border-color:rgba(210,153,34,0.25); opacity:0.7;">
        <h3 style="color:var(--warning-text); text-transform:none; letter-spacing:0; font-size:0.95rem;">
            🟡 Incident en cours de traitement
        </h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:6px;">
            Un technicien travaille sur votre incident. La description de résolution apparaîtra ici une fois l'incident résolu.
        </p>
    </div>
    <?php elseif ($incident['statut'] === 'ouvert'): ?>
    <div class="form-card" style="border-color:rgba(248,81,73,0.2); opacity:0.7;">
        <h3 style="color:var(--danger-text); text-transform:none; letter-spacing:0; font-size:0.95rem;">
            🔴 En attente d'assignation
        </h3>
        <p style="color:var(--text-muted); font-size:0.85rem; margin-top:6px;">
            Votre incident est en attente d'assignation à un technicien.
        </p>
    </div>
    <?php endif; ?>

    <!-- Historique -->
    <h3>Historique</h3>
    <?php if (empty($historique)): ?>
        <p class="vide">Aucune action enregistrée.</p>
    <?php else: ?>
    <div class="historique">
        <?php foreach ($historique as $h): ?>
        <div class="historique-item">
            <span class="hist-date"><?= date('d/m/Y à H:i', strtotime($h['date'])) ?></span>
            <span class="hist-user"><?= htmlspecialchars($h['prenom'].' '.$h['nom']) ?> (<?= $h['role'] ?>)</span>
            <span class="hist-action"><?= htmlspecialchars($h['action']) ?></span>
            <?php if ($h['commentaire']): ?>
                <p class="hist-commentaire"><?= htmlspecialchars($h['commentaire']) ?></p>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script src="../assets/js/main.js"></script>
<script>
setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>