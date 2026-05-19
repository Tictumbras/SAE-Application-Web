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

$succes = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['accepter'])) {
    $idDemande  = (int)$_POST['id_demande'];
    $login      = $_POST['login'];
    $nouveauMdp = $_POST['nouveau_mdp'];
    $mdpHash    = hash('sha256', $nouveauMdp);

    $pdo->prepare("UPDATE utilisateurs SET mot_de_passe = ? WHERE login = ?")->execute([$mdpHash, $login]);
    $pdo->prepare("UPDATE demandes_mdp SET statut = 'acceptee' WHERE id = ?")->execute([$idDemande]);
    $succes = "Mot de passe réinitialisé pour <strong>$login</strong>. Nouveau mot de passe : <strong>$nouveauMdp</strong>";
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['refuser'])) {
    $idDemande = (int)$_POST['id_demande'];
    $pdo->prepare("UPDATE demandes_mdp SET statut = 'refusee' WHERE id = ?")->execute([$idDemande]);
    $succes = "Demande refusée.";
}

$demandes   = $pdo->query("SELECT * FROM demandes_mdp WHERE statut = 'en_attente' ORDER BY date_demande DESC")->fetchAll();
$historique = $pdo->query("SELECT * FROM demandes_mdp WHERE statut != 'en_attente' ORDER BY date_demande DESC LIMIT 20")->fetchAll();
$nbDemandes = count($demandes);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Demandes MDP</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .mdp-form { display:flex; gap:8px; align-items:center; flex-wrap:wrap; }
        .mdp-form input {
            padding:7px 12px;
            background:rgba(4,10,24,0.8);
            border:1px solid rgba(212,175,55,0.2);
            border-radius:6px; color:#E8EEF8;
            font-family:'IBM Plex Sans',sans-serif;
            font-size:0.85rem; width:160px; margin:0;
        }
        .mdp-form input:focus { outline:none; border-color:rgba(212,175,55,0.45); }
        .notif-badge {
            display:inline-flex; align-items:center; justify-content:center;
            width:18px; height:18px; background:var(--danger-text);
            color:#fff; border-radius:50%; font-size:10px; font-weight:700; margin-left:6px;
        }
    </style>
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="incidents.php">Incidents</a>
        <a href="utilisateurs.php">Utilisateurs</a>
        <a href="demandes_mdp.php" class="active">
            Demandes MDP
            <?php if ($nbDemandes > 0): ?>
                <span class="notif-badge"><?= $nbDemandes ?></span>
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
    <h2>Demandes de réinitialisation</h2>

    <?php if ($succes): ?>
        <p class="alert succes"><?= $succes ?></p>
    <?php endif; ?>

    <?php if (empty($demandes)): ?>
        <p class="vide">Aucune demande en attente.</p>
    <?php else: ?>
    <h3>En attente (<?= $nbDemandes ?>)</h3>
    <table>
        <thead>
            <tr><th>Login</th><th>Date</th><th>Action</th></tr>
        </thead>
        <tbody>
            <?php foreach ($demandes as $d): ?>
            <tr>
                <td style="font-weight:600; color:var(--accent);"><?= htmlspecialchars($d['login']) ?></td>
                <td><?= date('d/m/Y à H:i', strtotime($d['date_demande'])) ?></td>
                <td>
                    <form method="POST" class="mdp-form">
                        <input type="hidden" name="id_demande" value="<?= $d['id'] ?>">
                        <input type="hidden" name="login" value="<?= htmlspecialchars($d['login']) ?>">
                        <input type="text" name="nouveau_mdp" placeholder="Nouveau mot de passe" required autocomplete="off">
                        <button type="submit" name="accepter" class="btn btn-primary">Accepter</button>
                        <button type="submit" name="refuser" class="btn btn-danger"
                                onclick="return confirm('Refuser cette demande ?')">Refuser</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>

    <h3>Historique</h3>
    <?php if (empty($historique)): ?>
        <p class="vide">Aucun historique.</p>
    <?php else: ?>
    <table>
        <thead>
            <tr><th>Login</th><th>Date</th><th>Statut</th></tr>
        </thead>
        <tbody>
            <?php foreach ($historique as $h): ?>
            <tr style="opacity:0.6;">
                <td><?= htmlspecialchars($h['login']) ?></td>
                <td><?= date('d/m/Y à H:i', strtotime($h['date_demande'])) ?></td>
                <td>
                    <span class="badge <?= $h['statut']==='acceptee' ? 'badge-actif' : 'badge-inactif' ?>">
                        <?= $h['statut'] === 'acceptee' ? 'Acceptée' : 'Refusée' ?>
                    </span>
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