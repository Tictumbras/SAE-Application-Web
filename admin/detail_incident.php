<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
verifierConnexion();
verifierRole('admin');

$userConnecte = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$userConnecte->execute([$_SESSION['user_id']]);
$userConnecte = $userConnecte->fetch();
$photoSrc = $userConnecte['photo'] ?? null;

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: incidents.php'); exit(); }

$stmt = $pdo->prepare("SELECT i.*,
                               u_client.nom AS nom_client, u_client.prenom AS prenom_client,
                               u_tech.nom AS nom_tech, u_tech.prenom AS prenom_tech
                        FROM incidents i
                        JOIN utilisateurs u_client ON i.id_client = u_client.id
                        LEFT JOIN utilisateurs u_tech ON i.id_technicien = u_tech.id
                        WHERE i.id = ?");
$stmt->execute([$id]);
$incident = $stmt->fetch();
if (!$incident) { header('Location: incidents.php'); exit(); }

$hist = $pdo->prepare("SELECT h.*, u.nom, u.prenom, u.role
                        FROM historique h
                        JOIN utilisateurs u ON h.id_utilisateur = u.id
                        WHERE h.id_incident = ? ORDER BY h.date ASC");
$hist->execute([$id]);
$historique = $hist->fetchAll();

$nbDemandes = $pdo->query("SELECT COUNT(*) FROM demandes_mdp WHERE statut = 'en_attente'")->fetchColumn();
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
        <a href="dashboard.php">Dashboard</a>
        <a href="incidents.php" class="active">Incidents</a>
        <a href="utilisateurs.php">Utilisateurs</a>
        <a href="demandes_mdp.php">
            Demandes MDP
            <?php if ($nbDemandes > 0): ?>
                <span style="background:var(--danger-text);color:#fff;border-radius:50%;width:16px;height:16px;font-size:10px;font-weight:700;display:inline-flex;align-items:center;justify-content:center;margin-left:4px;"><?= $nbDemandes ?></span>
            <?php endif; ?>
        </a>
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
    <a href="incidents.php" class="btn btn-secondary" style="margin-bottom:20px; display:inline-flex;">
        ← Retour aux incidents
    </a>

    <h2>Incident #<?= $incident['id'] ?> — <?= htmlspecialchars($incident['titre']) ?></h2>

    <div class="form-card">
        <h3>Informations générales</h3>
        <p><strong>Client :</strong> <?= htmlspecialchars($incident['prenom_client'].' '.$incident['nom_client']) ?></p>
        <p><strong>Technicien :</strong>
            <?= $incident['nom_tech']
                ? htmlspecialchars($incident['prenom_tech'].' '.$incident['nom_tech'])
                : '<em style="color:var(--text-muted)">Non assigné</em>' ?>
        </p>
        <p><strong>Priorité :</strong> <span class="badge badge-<?= $incident['priorite'] ?>"><?= $incident['priorite'] ?></span></p>
        <p><strong>Statut :</strong> <span class="statut statut-<?= $incident['statut'] ?>"><?= $incident['statut'] ?></span></p>
        <p><strong>Date de création :</strong> <?= date('d/m/Y à H:i', strtotime($incident['date_creation'])) ?></p>
    </div>

    <div class="form-card">
        <h3>Description du problème (client)</h3>
        <p style="color:var(--text-primary); line-height:1.7;">
            <?= nl2br(htmlspecialchars($incident['description'])) ?>
        </p>
    </div>

    <?php if ($incident['commentaire_resolution']): ?>
    <div class="form-card" style="border-color:rgba(63,185,80,0.3);">
        <h3 style="color:var(--success-text);">Description de la résolution (technicien)</h3>
        <p style="color:var(--text-primary); line-height:1.7;">
            <?= nl2br(htmlspecialchars($incident['commentaire_resolution'])) ?>
        </p>
    </div>
    <?php else: ?>
    <div class="form-card" style="opacity:0.5;">
        <h3>Description de la résolution</h3>
        <p style="color:var(--text-muted); font-style:italic;">Aucune description de résolution fournie.</p>
    </div>
    <?php endif; ?>

    <h3>Historique des actions</h3>
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