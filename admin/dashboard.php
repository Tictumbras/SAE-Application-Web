<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
verifierConnexion();
verifierRole('admin');

$totalIncidents = $pdo->query("SELECT COUNT(*) FROM incidents WHERE archive = 0")->fetchColumn();
$ouverts        = $pdo->query("SELECT COUNT(*) FROM incidents WHERE statut = 'ouvert' AND archive = 0")->fetchColumn();
$enCours        = $pdo->query("SELECT COUNT(*) FROM incidents WHERE statut = 'en_cours' AND archive = 0")->fetchColumn();
$resolus        = $pdo->query("SELECT COUNT(*) FROM incidents WHERE statut = 'resolu' AND archive = 0")->fetchColumn();
$totalUsers     = $pdo->query("SELECT COUNT(*) FROM utilisateurs WHERE derniere_activite >= DATE_SUB(NOW(), INTERVAL 15 MINUTE)")->fetchColumn();
$nbDemandes     = $pdo->query("SELECT COUNT(*) FROM demandes_mdp WHERE statut = 'en_attente'")->fetchColumn();

$userConnecte = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$userConnecte->execute([$_SESSION['user_id']]);
$userConnecte = $userConnecte->fetch();
$photoSrc = $userConnecte['photo'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Dashboard</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .dashboard-welcome {
            background:var(--bg-card); border:1px solid var(--border-gold);
            border-left:4px solid var(--accent); border-radius:var(--radius);
            padding:20px 24px; margin-bottom:28px;
            display:flex; align-items:center; justify-content:space-between;
        }
        .dashboard-welcome-text p { font-size:0.85rem; color:var(--text-secondary); margin:0; }
        .dashboard-date { font-size:0.8rem; color:var(--text-muted); font-family:'IBM Plex Mono',monospace; }
        .section-title {
            font-size:11px; font-weight:600; color:rgba(212,175,55,0.5);
            text-transform:uppercase; letter-spacing:1px; margin-bottom:14px;
        }
        .quick-actions-grid {
            display:grid; grid-template-columns:repeat(auto-fit, minmax(200px,1fr));
            gap:12px; margin-bottom:28px;
        }
        .quick-action-card {
            background:var(--bg-card); border:1px solid var(--border-gold);
            border-radius:var(--radius); padding:18px 20px; text-decoration:none;
            display:flex; align-items:center; gap:14px; transition:all 0.2s; color:var(--text-secondary);
        }
        .quick-action-card:hover {
            background:var(--bg-hover); border-color:rgba(212,175,55,0.4);
            color:var(--text-primary); transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,0.3);
        }
        .quick-action-icon {
            width:40px; height:40px; border-radius:10px;
            display:flex; align-items:center; justify-content:center;
            font-size:1rem; font-weight:700; flex-shrink:0;
        }
        .quick-action-icon.gold   { background:rgba(212,175,55,0.12); color:var(--accent); }
        .quick-action-icon.red    { background:rgba(248,81,73,0.12);  color:var(--danger-text); }
        .quick-action-icon.green  { background:rgba(63,185,80,0.12);  color:var(--success-text); }
        .quick-action-icon.purple { background:rgba(167,139,250,0.12); color:#A78BFA; }
        .quick-action-icon.orange { background:rgba(212,175,55,0.12); color:var(--warning-text); }
        .quick-action-label { font-size:0.875rem; font-weight:500; color:inherit; }
        .quick-action-desc  { font-size:0.75rem; color:var(--text-muted); margin-top:2px; }
    </style>
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Dashboard</a>
        <a href="incidents.php">Incidents</a>
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
    <h2>Tableau de bord</h2>

    <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
        <button onclick="window.location.reload()" class="btn btn-secondary">↻ Actualiser</button>
    </div>

    <div class="dashboard-welcome">
        <div class="dashboard-welcome-text">
            <p>Connecté en tant qu'<span style="color:var(--accent); font-weight:600;">Administrateur</span>. Voici l'état du système.</p>
        </div>
        <div class="dashboard-date"><?= date('d/m/Y — H:i') ?></div>
    </div>

    <div class="section-title">Vue d'ensemble</div>
    <div class="stats-grid">
        <div class="carte-stat">
            <div class="stat-nombre"><?= $totalIncidents ?></div>
            <div class="stat-label">Incidents actifs</div>
        </div>
        <div class="carte-stat ouvert">
            <div class="stat-nombre"><?= $ouverts ?></div>
            <div class="stat-label">Ouverts</div>
        </div>
        <div class="carte-stat en-cours">
            <div class="stat-nombre"><?= $enCours ?></div>
            <div class="stat-label">En cours</div>
        </div>
        <div class="carte-stat resolu">
            <div class="stat-nombre"><?= $resolus ?></div>
            <div class="stat-label">Résolus</div>
        </div>
        <div class="carte-stat">
            <div class="stat-nombre"><?= $totalUsers ?></div>
            <div class="stat-label">En ligne</div>
        </div>
    </div>

    <div class="section-title">Actions rapides</div>
    <div class="quick-actions-grid">
        <a href="utilisateurs.php" class="quick-action-card">
            <div class="quick-action-icon gold">+</div>
            <div>
                <div class="quick-action-label">Créer un compte</div>
                <div class="quick-action-desc">Ajouter technicien ou client</div>
            </div>
        </a>
        <a href="incidents.php" class="quick-action-card">
            <div class="quick-action-icon red">!</div>
            <div>
                <div class="quick-action-label">Voir les incidents</div>
                <div class="quick-action-desc">Gérer et assigner</div>
            </div>
        </a>
        <a href="incidents.php?statut=ouvert" class="quick-action-card">
            <div class="quick-action-icon purple">~</div>
            <div>
                <div class="quick-action-label">Incidents ouverts</div>
                <div class="quick-action-desc"><?= $ouverts ?> en attente d'assignation</div>
            </div>
        </a>
        <a href="utilisateurs.php" class="quick-action-card">
            <div class="quick-action-icon green">U</div>
            <div>
                <div class="quick-action-label">Utilisateurs en ligne</div>
                <div class="quick-action-desc"><?= $totalUsers ?> connecté<?= $totalUsers > 1 ? 's' : '' ?> actuellement</div>
            </div>
        </a>
        <?php if ($nbDemandes > 0): ?>
        <a href="demandes_mdp.php" class="quick-action-card" style="border-color:rgba(248,81,73,0.3);">
            <div class="quick-action-icon red">!</div>
            <div>
                <div class="quick-action-label">Demandes MDP</div>
                <div class="quick-action-desc"><?= $nbDemandes ?> demande<?= $nbDemandes > 1 ? 's' : '' ?> en attente</div>
            </div>
        </a>
        <?php endif; ?>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>