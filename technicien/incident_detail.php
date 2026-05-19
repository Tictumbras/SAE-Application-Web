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

$id = $_GET['id'] ?? null;
if (!$id) { header('Location: dashboard.php'); exit(); }

$stmt = $pdo->prepare("SELECT i.*, u.nom AS nom_client, u.prenom AS prenom_client
                        FROM incidents i JOIN utilisateurs u ON i.id_client = u.id
                        WHERE i.id = ? AND i.id_technicien = ?");
$stmt->execute([$id, $_SESSION['user_id']]);
$incident = $stmt->fetch();
if (!$incident) { header('Location: dashboard.php'); exit(); }

$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['statut'])) {
    $nouveauStatut         = $_POST['statut'];
    $commentaireResolution = trim($_POST['commentaire_resolution'] ?? '');

    $pdo->prepare("UPDATE incidents SET statut = ?, commentaire_resolution = ? WHERE id = ?")
        ->execute([$nouveauStatut, $commentaireResolution, $id]);

    // Ajouter dans l'historique avec la description de résolution
    $actionLog = 'Statut mis à jour : ' . $nouveauStatut;
    if (!empty($commentaireResolution)) {
        $pdo->prepare("INSERT INTO historique (id_incident, id_utilisateur, action, commentaire) VALUES (?, ?, ?, ?)")
            ->execute([$id, $_SESSION['user_id'], $actionLog, $commentaireResolution]);
    } else {
        $pdo->prepare("INSERT INTO historique (id_incident, id_utilisateur, action) VALUES (?, ?, ?)")
            ->execute([$id, $_SESSION['user_id'], $actionLog]);
    }

    $succes = "Statut mis à jour !";
    $incident['statut'] = $nouveauStatut;
    $incident['commentaire_resolution'] = $commentaireResolution;
}

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
    <title>TrackInsi — Gérer l'incident</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Mes interventions</a>
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

    <?php if ($succes): ?>
        <p class="alert succes"><?= $succes ?></p>
    <?php endif; ?>

    <h2>Incident #<?= $incident['id'] ?> — <?= htmlspecialchars($incident['titre']) ?></h2>

    <div class="form-card">
        <p><strong>Client :</strong> <?= htmlspecialchars($incident['prenom_client'].' '.$incident['nom_client']) ?></p>
        <p><strong>Description :</strong> <?= htmlspecialchars($incident['description']) ?></p>
        <p><strong>Priorité :</strong> <span class="badge badge-<?= $incident['priorite'] ?>"><?= $incident['priorite'] ?></span></p>
        <p><strong>Statut :</strong> <span class="statut statut-<?= $incident['statut'] ?>"><?= $incident['statut'] ?></span></p>
        <p><strong>Date :</strong> <?= date('d/m/Y à H:i', strtotime($incident['date_creation'])) ?></p>
    </div>

    <div class="form-card">
        <h3>Mettre à jour le statut</h3>
        <form method="POST">
            <select name="statut">
                <option value="en_cours" <?= $incident['statut']==='en_cours'?'selected':'' ?>>
                    🟡 En cours
                </option>
                <option value="resolu" <?= $incident['statut']==='resolu'?'selected':'' ?>>
                    🟢 Résolu
                </option>
            </select>
            <label style="font-size:11px; color:rgba(212,175,55,0.5); text-transform:uppercase; letter-spacing:0.8px; display:block; margin-bottom:6px; margin-top:12px;">
                Description de la résolution (visible par l'admin et dans l'historique)
            </label>
            <textarea name="commentaire_resolution"
                      placeholder="Décrivez comment vous avez résolu l'incident..."
                      rows="4"><?= htmlspecialchars($incident['commentaire_resolution'] ?? '') ?></textarea>
            <button type="submit" class="btn btn-primary">Mettre à jour</button>
        </form>
    </div>

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