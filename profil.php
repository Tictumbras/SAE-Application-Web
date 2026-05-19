<?php
session_start();
require 'includes/db.php';
require 'includes/auth.php';
verifierConnexion();

$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_FILES['photo'])) {
    $file    = $_FILES['photo'];
    $ext     = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp'];

    if (!in_array($ext, $allowed)) {
        $erreur = "Format non autorisé. Utilisez JPG, PNG ou GIF.";
    } elseif ($file['size'] > 2 * 1024 * 1024) {
        $erreur = "Fichier trop lourd (max 2Mo).";
    } elseif ($file['error'] !== UPLOAD_ERR_OK) {
        $erreur = "Erreur lors de l'upload.";
    } else {
        $imageData = file_get_contents($file['tmp_name']);
        $base64    = 'data:image/' . $ext . ';base64,' . base64_encode($imageData);
        $stmt = $pdo->prepare("UPDATE utilisateurs SET photo = ? WHERE id = ?");
        $stmt->execute([$base64, $_SESSION['user_id']]);
        $succes = "Photo mise à jour !";
    }
}

$user = $pdo->prepare("SELECT * FROM utilisateurs WHERE id = ?");
$user->execute([$_SESSION['user_id']]);
$user = $user->fetch();

$role          = $_SESSION['role'];
$dashboardLink = $role . '/dashboard.php';
$photoSrc      = $user['photo'] ?? null;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Mon profil</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .photo-upload-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 10px 22px;
            background: linear-gradient(135deg, #C9A227, #D4AF37, #B8941F);
            color: #071022;
            font-family: 'IBM Plex Sans', sans-serif;
            font-size: 0.875rem;
            font-weight: 700;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.2s;
            box-shadow: 0 2px 10px rgba(212,175,55,0.3);
            margin-top: 14px;
        }

        .photo-upload-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 18px rgba(212,175,55,0.5);
        }

        .file-chosen-name {
            display: none;
            margin-top: 8px;
            font-size: 0.78rem;
            color: var(--accent);
            font-style: italic;
        }

        .file-chosen-name.visible { display: block; }
    </style>
</head>
<body>

<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="<?= $dashboardLink ?>">← Retour au dashboard</a>
    </div>
    <div class="nav-bottom">
        <a href="logout.php" class="nav-logout">Deconnexion</a>
        <a href="profil.php" class="nav-user">
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
    <h2>Mon profil</h2>

    <?php if ($succes): ?>
        <p class="alert succes"><?= $succes ?></p>
    <?php endif; ?>
    <?php if ($erreur): ?>
        <p class="alert erreur"><?= $erreur ?></p>
    <?php endif; ?>

    <div class="form-card">
        <h3>Photo de profil</h3>
        <div class="profil-photo-section">
            <?php if ($photoSrc): ?>
                <img src="<?= htmlspecialchars($photoSrc) ?>" class="avatar-large" alt="photo de profil">
            <?php else: ?>
                <div class="avatar-large-placeholder">?</div>
            <?php endif; ?>

            <div>
                <form method="POST" enctype="multipart/form-data" id="photoForm">
                    <div class="input-file-wrapper">
                        <div class="input-file-label">
                            <span class="file-icon">&#128193;</span>
                            <span>Parcourir — JPG, PNG, max 2Mo</span>
                        </div>
                        <input type="file" name="photo" accept="image/*" id="photoInput">
                    </div>
                    <div class="file-chosen-name" id="fileName"></div>
                    <button type="submit" class="photo-upload-btn">
                        Mettre à jour la photo
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="form-card">
        <p><strong>Nom :</strong> <?= htmlspecialchars($user['nom']) ?></p>
        <p><strong>Prénom :</strong> <?= htmlspecialchars($user['prenom']) ?></p>
        <p><strong>Login :</strong> <?= htmlspecialchars($user['login']) ?></p>
        <p><strong>Rôle :</strong> <span class="badge badge-<?= $user['role'] ?>"><?= $user['role'] ?></span></p>
    </div>
</div>

<script src="assets/js/main.js"></script>
<script>
    document.getElementById('photoInput').addEventListener('change', function() {
        const nameEl = document.getElementById('fileName');
        if (this.files[0]) {
            nameEl.textContent = this.files[0].name;
            nameEl.classList.add('visible');
        } else {
            nameEl.classList.remove('visible');
        }
    });
</script>
</body>
</html>