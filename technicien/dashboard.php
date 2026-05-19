<?php
session_start();
require '../includes/db.php';
require '../includes/auth.php';
verifierConnexion();
verifierRole('technicien');

$userConnecte = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$userConnecte->execute([$_SESSION['user_id']]);
$userConnecte = $userConnecte->fetch();
$photoSrc = $userConnecte['photo'] ?? null;

$stmt = $pdo->prepare("SELECT i.*, u.nom AS nom_client, u.prenom AS prenom_client
                        FROM incidents i JOIN utilisateurs u ON i.id_client = u.id
                        WHERE i.id_technicien = ? AND i.archive = 0
                        ORDER BY i.date_creation DESC");
$stmt->execute([$_SESSION['user_id']]);
$incidents = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Espace Technicien</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php" class="active">Mes interventions</a>
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
    <h2>Mes interventions</h2>

    <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
        <button onclick="window.location.reload()" class="btn btn-secondary">↻ Actualiser</button>
    </div>

    <?php if (empty($incidents)): ?>
        <p class="vide">Aucun incident assigné pour le moment.</p>
    <?php else: ?>

    <div style="display:flex; align-items:center; gap:16px; margin-bottom:16px; flex-wrap:wrap;">
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:11px; color:rgba(212,175,55,0.5); text-transform:uppercase; letter-spacing:0.5px;">Priorité</span>
            <select id="triPriorite" style="margin:0; width:180px; padding:8px 12px;">
                <option value="">— Aucun tri —</option>
                <option value="desc">Critique → Faible</option>
                <option value="asc">Faible → Critique</option>
            </select>
        </div>
        <div style="display:flex; align-items:center; gap:6px;">
            <span style="font-size:11px; color:rgba(212,175,55,0.5); text-transform:uppercase; letter-spacing:0.5px;">Statut</span>
            <select id="triStatutTech" style="margin:0; width:200px; padding:8px 12px;">
                <option value="">— Aucun tri —</option>
                <option value="en_cours">En cours en premier</option>
                <option value="resolu">Résolus en premier</option>
            </select>
        </div>
    </div>

    <p style="font-size:11px; color:var(--text-muted); margin-bottom:12px;">
        Cliquez sur une ligne pour l'agrandir
    </p>

    <table>
        <thead>
            <tr>
                <th>#</th><th>Titre</th><th>Client</th>
                <th>Priorité</th><th>Statut</th><th>Date</th><th>Actions</th>
            </tr>
        </thead>
        <tbody id="techIncidentsTbody">
            <?php foreach ($incidents as $inc): ?>
            <tr data-priorite="<?= $inc['priorite'] ?>" data-statut="<?= $inc['statut'] ?>">
                <td><?= $inc['id'] ?></td>
                <td><?= htmlspecialchars($inc['titre']) ?></td>
                <td><?= htmlspecialchars($inc['prenom_client'] . ' ' . $inc['nom_client']) ?></td>
                <td><span class="badge badge-<?= $inc['priorite'] ?>"><?= $inc['priorite'] ?></span></td>
                <td><span class="statut statut-<?= $inc['statut'] ?>"><?= $inc['statut'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($inc['date_creation'])) ?></td>
                <td>
                    <a href="incident_detail.php?id=<?= $inc['id'] ?>"
                       class="btn btn-primary"
                       onclick="event.stopPropagation()">
                        Gérer
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
    const prioriteOrdre = { critique: 3, moyen: 2, faible: 1 };

    const triPriorite = document.getElementById('triPriorite');
    if (triPriorite) {
        triPriorite.addEventListener('change', function() {
            const ordre = this.value;
            const tbody = document.getElementById('techIncidentsTbody');
            if (!tbody || !ordre) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b) {
                const pa = prioriteOrdre[a.getAttribute('data-priorite')] || 0;
                const pb = prioriteOrdre[b.getAttribute('data-priorite')] || 0;
                return ordre === 'desc' ? pb - pa : pa - pb;
            });
            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    }

    const triStatutTech = document.getElementById('triStatutTech');
    if (triStatutTech) {
        triStatutTech.addEventListener('change', function() {
            const statutCible = this.value;
            const tbody = document.getElementById('techIncidentsTbody');
            if (!tbody || !statutCible) return;
            const rows = Array.from(tbody.querySelectorAll('tr'));
            rows.sort(function(a, b) {
                const sa = a.getAttribute('data-statut') === statutCible ? 1 : 0;
                const sb = b.getAttribute('data-statut') === statutCible ? 1 : 0;
                return sb - sa;
            });
            rows.forEach(function(row) { tbody.appendChild(row); });
        });
    }

    setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>