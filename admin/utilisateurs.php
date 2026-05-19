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

$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom    = trim($_POST['nom']);
    $prenom = trim($_POST['prenom']);
    $login  = trim($_POST['login']);
    $mdp    = hash('sha256', $_POST['mot_de_passe']);
    $role   = $_POST['role'];

    $check = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ?");
    $check->execute([$login]);

    if ($check->fetch()) {
        $erreur = "Ce login est déjà utilisé.";
    } else {
        $pdo->prepare("INSERT INTO utilisateurs (nom, prenom, login, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)")
            ->execute([$nom, $prenom, $login, $mdp, $role]);
        $succes = "Compte créé avec succès !";
    }
}

$utilisateurs = $pdo->query("SELECT * FROM utilisateurs ORDER BY role, nom")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Utilisateurs</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="incidents.php">Incidents</a>
        <a href="utilisateurs.php" class="active">Utilisateurs</a>
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
    <h2>Gestion des utilisateurs</h2>

    <?php if ($succes): ?>
        <p class="alert succes"><?= $succes ?></p>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <p class="alert erreur"><?= $erreur ?></p>
    <?php endif; ?>

    <div class="form-card">
        <h3>Créer un compte</h3>
        <form method="POST" autocomplete="off">
            <div class="form-row">
                <input type="text" name="nom" placeholder="Nom" required autocomplete="off">
                <input type="text" name="prenom" placeholder="Prénom" required autocomplete="off">
            </div>
            <input type="text" name="login" placeholder="Login" required autocomplete="off">
            <input type="password" name="mot_de_passe" placeholder="Mot de passe" required autocomplete="new-password">
            <div class="select-role-wrapper">
                <select name="role" id="selectRole" required>
                    <option value="">-- Choisir un rôle --</option>
                    <option value="technicien">Technicien</option>
                    <option value="client">Client</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Créer le compte</button>
        </form>
    </div>

    <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px;">
        <span style="font-size:11px; color:rgba(212,175,55,0.5); text-transform:uppercase; letter-spacing:0.5px;">Filtrer</span>
        <select id="filtreStatutUser" style="margin:0; width:auto; padding:8px 12px; width:180px;">
            <option value="tous">Tous les utilisateurs</option>
            <option value="enligne">En ligne</option>
            <option value="horsligne">Hors ligne</option>
        </select>
    </div>

    <h3>Liste des utilisateurs</h3>
    <table>
        <thead>
            <tr><th>Photo</th><th>Nom</th><th>Prénom</th><th>Login</th><th>Rôle</th><th>Statut</th></tr>
        </thead>
        <tbody id="usersTbody">
            <?php foreach ($utilisateurs as $u): ?>
            <?php $enLigne = estActif($u['derniere_activite'] ?? null); ?>
            <tr data-statut="<?= $enLigne ? 'enligne' : 'horsligne' ?>">
                <td>
                    <?php if ($u['photo']): ?>
                        <img src="<?= htmlspecialchars($u['photo']) ?>" class="avatar" alt="">
                    <?php else: ?>
                        <div class="avatar-placeholder"></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($u['nom']) ?></td>
                <td><?= htmlspecialchars($u['prenom']) ?></td>
                <td><?= htmlspecialchars($u['login']) ?></td>
                <td><span class="badge badge-<?= $u['role'] ?>"><?= $u['role'] ?></span></td>
                <td>
                    <span class="badge <?= $enLigne ? 'badge-actif' : 'badge-inactif' ?>">
                        <?= $enLigne ? 'En ligne' : 'Hors ligne' ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<script src="../assets/js/main.js"></script>
<script>
    const selectRole = document.getElementById('selectRole');
    if (selectRole) {
        function updateRoleColor() {
            if (selectRole.value === 'technicien')  selectRole.style.color = '#A78BFA';
            else if (selectRole.value === 'client') selectRole.style.color = '#3FB950';
            else                                    selectRole.style.color = '';
        }
        selectRole.addEventListener('change', updateRoleColor);
        updateRoleColor();
    }

    const filtreStatutUser = document.getElementById('filtreStatutUser');
    if (filtreStatutUser) {
        filtreStatutUser.addEventListener('change', function() {
            const val  = this.value;
            const rows = document.querySelectorAll('#usersTbody tr');
            rows.forEach(function(row) {
                row.style.display = (val === 'tous' || row.getAttribute('data-statut') === val) ? '' : 'none';
            });
        });
    }

    setInterval(function() { fetch('../includes/ping.php', { credentials:'include' }); }, 60000);
</script>
</body>
</html>