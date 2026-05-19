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
$nbDemandes = $pdo->query("SELECT COUNT(*) FROM demandes_mdp WHERE statut = 'en_attente'")->fetchColumn();

$filtreStatut = $_GET['statut'] ?? 'tous';
$sql = "SELECT i.*, u_client.nom AS nom_client, u_client.prenom AS prenom_client,
               u_tech.nom AS nom_tech, u_tech.prenom AS prenom_tech
        FROM incidents i
        JOIN utilisateurs u_client ON i.id_client = u_client.id
        LEFT JOIN utilisateurs u_tech ON i.id_technicien = u_tech.id
        WHERE i.archive = 0";
if ($filtreStatut !== 'tous') $sql .= " AND i.statut = " . $pdo->quote($filtreStatut);
$sql .= " ORDER BY i.date_creation DESC";
$incidents = $pdo->query($sql)->fetchAll();

$techniciens_charge = $pdo->query("
    SELECT u.id, u.nom, u.prenom, u.photo, COUNT(i.id) AS nb_taches
    FROM utilisateurs u
    LEFT JOIN incidents i ON i.id_technicien = u.id AND i.archive = 0 AND i.statut != 'resolu'
    WHERE u.role = 'technicien' AND u.actif = 1
    GROUP BY u.id ORDER BY nb_taches ASC
")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['assigner'])) {
    $idIncident   = (int)$_POST['id_incident'];
    $idTechnicien = (int)$_POST['id_technicien'];
    $pdo->prepare("UPDATE incidents SET id_technicien = ?, statut = 'en_cours' WHERE id = ?")->execute([$idTechnicien, $idIncident]);
    $pdo->prepare("INSERT INTO historique (id_incident, id_utilisateur, action) VALUES (?, ?, ?)")->execute([$idIncident, $_SESSION['user_id'], 'Incident assigné à un technicien']);
    header('Location: incidents.php');
    exit();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Incidents</title>
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
    <h2>Tous les incidents</h2>

    <div style="display:flex; justify-content:flex-end; margin-bottom:16px;">
        <button onclick="window.location.reload()" class="btn btn-secondary">↻ Actualiser</button>
    </div>

    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-bottom:18px;">
        <div class="filtres" style="margin:0;">
            <a href="?statut=tous"     class="btn <?= $filtreStatut==='tous'     ?'btn-primary':'btn-secondary'?>">Tous</a>
            <a href="?statut=ouvert"   class="btn <?= $filtreStatut==='ouvert'   ?'btn-primary':'btn-secondary'?>">Ouverts</a>
            <a href="?statut=en_cours" class="btn <?= $filtreStatut==='en_cours' ?'btn-primary':'btn-secondary'?>">En cours</a>
            <a href="?statut=resolu"   class="btn <?= $filtreStatut==='resolu'   ?'btn-primary':'btn-secondary'?>">Résolus</a>
        </div>
        <div style="display:flex; align-items:center; gap:10px; flex-wrap:wrap;">
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:11px; color:rgba(212,175,55,0.5); text-transform:uppercase; letter-spacing:0.5px;">Priorité</span>
                <select id="triIncidentPriorite" style="margin:0; width:180px; padding:8px 12px;">
                    <option value="">— Aucun tri —</option>
                    <option value="desc">Critique → Faible</option>
                    <option value="asc">Faible → Critique</option>
                </select>
            </div>
            <div style="display:flex; align-items:center; gap:6px;">
                <span style="font-size:11px; color:rgba(212,175,55,0.5); text-transform:uppercase; letter-spacing:0.5px;">Statut</span>
                <select id="triIncidentStatut" style="margin:0; width:180px; padding:8px 12px;">
                    <option value="">— Aucun tri —</option>
                    <option value="resolu">Résolus en premier</option>
                    <option value="en_cours">En cours en premier</option>
                    <option value="ouvert">Ouverts en premier</option>
                </select>
            </div>
        </div>
    </div>

    <p style="font-size:11px; color:var(--text-muted); margin-bottom:12px;">
        Cliquez sur une ligne pour l'agrandir · Bouton Assigner pour affecter un technicien
    </p>

    <?php if (empty($incidents)): ?>
        <p class="vide">Aucun incident actif.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr>
                <th>#</th><th>Titre</th><th>Client</th>
                <th>Priorité</th><th>Statut</th><th>Technicien</th>
                <th>Date</th><th>Actions</th>
            </tr>
        </thead>
        <tbody id="incidentsTbody">
            <?php foreach ($incidents as $inc): ?>
            <tr data-priorite="<?= $inc['priorite'] ?>" data-statut="<?= $inc['statut'] ?>">
                <td><?= $inc['id'] ?></td>
                <td><?= htmlspecialchars($inc['titre']) ?></td>
                <td><?= htmlspecialchars($inc['prenom_client'].' '.$inc['nom_client']) ?></td>
                <td><span class="badge badge-<?= $inc['priorite'] ?>"><?= $inc['priorite'] ?></span></td>
                <td><span class="statut statut-<?= $inc['statut'] ?>"><?= $inc['statut'] ?></span></td>
                <td>
                    <?php if ($inc['nom_tech']): ?>
                        <?= htmlspecialchars($inc['prenom_tech'].' '.$inc['nom_tech']) ?>
                    <?php else: ?>
                        <em style="color:var(--text-muted)">Non assigné</em>
                    <?php endif; ?>
                </td>
                <td><?= date('d/m/Y', strtotime($inc['date_creation'])) ?></td>
                <td>
                    <div style="display:flex; gap:6px; align-items:center; flex-wrap:wrap;" onclick="event.stopPropagation()">
                        <button class="btn btn-primary btn-assigner"
                                data-id="<?= $inc['id'] ?>"
                                data-titre="<?= htmlspecialchars($inc['titre']) ?>">
                            Assigner
                        </button>
                        <?php if ($inc['statut'] === 'resolu'): ?>
                        <a href="detail_incident.php?id=<?= $inc['id'] ?>"
                           class="btn btn-secondary"
                           onclick="event.stopPropagation()"
                           style="border-color:var(--success-text); color:var(--success-text);">
                            Voir détails
                        </a>
                        <?php endif; ?>
                        <a href="archiver.php?id=<?= $inc['id'] ?>"
                           class="btn btn-danger btn-archiver"
                           onclick="event.stopPropagation()">
                            Archiver
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3>Incidents archivés</h3>
    <?php
    $archives = $pdo->query("
        SELECT i.*, u_client.nom AS nom_client, u_client.prenom AS prenom_client
        FROM incidents i JOIN utilisateurs u_client ON i.id_client = u_client.id
        WHERE i.archive = 1 ORDER BY i.date_creation DESC
    ")->fetchAll();
    ?>
    <?php if (empty($archives)): ?>
        <p class="vide">Aucun incident archivé.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>#</th><th>Titre</th><th>Client</th><th>Priorité</th><th>Date</th><th>Détails</th></tr>
        </thead>
        <tbody>
            <?php foreach ($archives as $arc): ?>
            <tr style="opacity:0.6;">
                <td><?= $arc['id'] ?></td>
                <td><?= htmlspecialchars($arc['titre']) ?></td>
                <td><?= htmlspecialchars($arc['prenom_client'].' '.$arc['nom_client']) ?></td>
                <td><span class="badge badge-<?= $arc['priorite'] ?>"><?= $arc['priorite'] ?></span></td>
                <td><?= date('d/m/Y', strtotime($arc['date_creation'])) ?></td>
                <td>
                    <a href="detail_incident.php?id=<?= $arc['id'] ?>"
                       class="btn btn-secondary"
                       onclick="event.stopPropagation()"
                       style="border-color:var(--success-text); color:var(--success-text);">
                        Voir détails
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<div id="modaleAssigner" class="modale-overlay">
    <div class="modale-topbar">
        <div class="modale-topbar-left">
            <h2>Assigner un technicien</h2>
            <div class="modale-incident-label" id="modaleIncidentTitre"></div>
        </div>
        <button class="btn btn-secondary" id="fermerModale">✕ Fermer</button>
    </div>
    <div class="modale-body">
        <div class="modale-filtres">
            <div>
                <label>Trier par charge</label>
                <select id="triBtnNbTaches">
                    <option value="asc" selected>Moins chargé en premier</option>
                    <option value="desc">Plus chargé en premier</option>
                </select>
            </div>
        </div>
        <table>
            <thead>
                <tr><th>Photo</th><th>Nom</th><th>Prénom</th><th>Rôle</th><th>Tâches</th><th>Disponibilité</th><th>Action</th></tr>
            </thead>
            <tbody id="techniciensTbody">
                <?php foreach ($techniciens_charge as $tech):
                    $dispo      = $tech['nb_taches']==0 ? 'Disponible' : ($tech['nb_taches']<=3 ? 'Occupé' : 'Surchargé');
                    $dispoClass = $tech['nb_taches']==0 ? 'libre' : ($tech['nb_taches']<=3 ? 'moyen' : 'charge');
                ?>
                <tr data-taches="<?= $tech['nb_taches'] ?>">
                    <td>
                        <?php if ($tech['photo']): ?>
                            <img src="<?= htmlspecialchars($tech['photo']) ?>" class="avatar" alt="">
                        <?php else: ?>
                            <div class="avatar-placeholder"></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight:500; color:var(--text-primary);"><?= htmlspecialchars($tech['nom']) ?></td>
                    <td><?= htmlspecialchars($tech['prenom']) ?></td>
                    <td><span class="badge badge-technicien">Technicien</span></td>
                    <td>
                        <span style="font-family:'IBM Plex Mono',monospace; font-size:1.1rem; font-weight:700; color:var(--accent);"><?= $tech['nb_taches'] ?></span>
                        <span style="color:var(--text-muted); font-size:0.8rem;"> tâche<?= $tech['nb_taches']>1?'s':'' ?></span>
                    </td>
                    <td><span class="charge-badge <?= $dispoClass ?>"><?= $dispo ?></span></td>
                    <td>
                        <form method="POST">
                            <input type="hidden" name="id_incident" class="hidden-incident-id" value="">
                            <input type="hidden" name="id_technicien" value="<?= $tech['id'] ?>">
                            <button type="submit" name="assigner" class="btn btn-primary">Choisir</button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script src="../assets/js/main.js"></script>
<script>
setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>