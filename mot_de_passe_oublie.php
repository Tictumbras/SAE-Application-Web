<?php
session_start();
require 'includes/db.php';

$succes = '';
$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = trim($_POST['login']);

    $stmt = $pdo->prepare("SELECT id FROM utilisateurs WHERE login = ?");
    $stmt->execute([$login]);

    if (!$stmt->fetch()) {
        $erreur = "Ce login n'existe pas.";
    } else {
        $check = $pdo->prepare("SELECT id FROM demandes_mdp WHERE login = ? AND statut = 'en_attente'");
        $check->execute([$login]);

        if ($check->fetch()) {
            $erreur = "Une demande est déjà en attente pour ce compte.";
        } else {
            $insert = $pdo->prepare("INSERT INTO demandes_mdp (login) VALUES (?)");
            $insert->execute([$login]);
            $succes = "Votre demande a été envoyée à l'administrateur.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Mot de passe oublié</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        *, *::before, *::after { margin:0; padding:0; box-sizing:border-box; }
        html, body { width:100%; height:100%; overflow:hidden; font-family:'IBM Plex Sans',sans-serif; }
        body { background:#071022; display:flex; align-items:center; justify-content:center; }

        .bg-gradient {
            position:fixed; inset:0; z-index:0;
            background:
                radial-gradient(ellipse at 25% 15%, rgba(212,175,55,0.07) 0%, transparent 50%),
                linear-gradient(160deg, #0B1A35 0%, #071022 45%, #0C1830 100%);
        }

        .bg-grid {
            position:fixed; inset:0; z-index:0;
            background-image:
                linear-gradient(rgba(212,175,55,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,175,55,0.025) 1px, transparent 1px);
            background-size:50px 50px;
        }

        .wrapper { position:relative; z-index:10; display:flex; flex-direction:column; align-items:center; gap:20px; }

        .card {
            background:rgba(8,18,40,0.88);
            backdrop-filter:blur(20px);
            border:1px solid rgba(212,175,55,0.2);
            border-radius:20px; padding:48px 44px; width:420px;
            box-shadow:0 25px 70px rgba(0,0,0,0.55), inset 0 1px 0 rgba(212,175,55,0.1);
            position:relative;
        }

        .card::before {
            content:''; position:absolute; top:0; left:15%; right:15%; height:1px;
            background:linear-gradient(90deg, transparent, rgba(212,175,55,0.6), transparent);
        }

        .card-title { font-size:1.6rem; font-weight:700; color:#E8EEF8; margin-bottom:6px; }
        .card-sub { font-size:0.82rem; color:rgba(212,175,55,0.5); margin-bottom:28px; }

        .field { margin-bottom:16px; }

        .field label {
            display:block; font-size:10px; font-weight:600;
            color:rgba(212,175,55,0.55); text-transform:uppercase;
            letter-spacing:1.2px; margin-bottom:7px;
        }

        .field input {
            width:100%; padding:12px 16px;
            background:rgba(4,10,24,0.85);
            border:1px solid rgba(212,175,55,0.12);
            border-radius:10px; color:#E8EEF8;
            font-family:'IBM Plex Sans',sans-serif;
            font-size:0.95rem; transition:all 0.2s;
        }

        .field input:focus {
            outline:none; border-color:rgba(212,175,55,0.45);
            box-shadow:0 0 0 3px rgba(212,175,55,0.08);
        }

        .field input::placeholder { color:#2A3A5A; }

        .btn-submit {
            width:100%; padding:13px; margin-top:6px;
            background:linear-gradient(135deg, #C9A227, #D4AF37, #B8941F);
            border:none; border-radius:10px; color:#071022;
            font-family:'IBM Plex Sans',sans-serif;
            font-size:1rem; font-weight:700; cursor:pointer;
            transition:all 0.25s;
            box-shadow:0 4px 20px rgba(212,175,55,0.28);
        }

        .btn-submit:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(212,175,55,0.42); }

        .retour {
            display:block; text-align:center; margin-top:18px;
            font-size:0.85rem; color:rgba(212,175,55,0.45);
            text-decoration:none; transition:color 0.2s;
        }

        .retour:hover { color:rgba(212,175,55,0.9); }

        .alert-succes {
            background:rgba(15,42,24,0.9); border:1px solid rgba(63,185,80,0.3);
            color:#3FB950; padding:12px 16px; border-radius:8px;
            font-size:0.875rem; margin-bottom:18px; text-align:center;
        }

        .alert-erreur {
            background:rgba(40,10,10,0.9); border:1px solid rgba(248,81,73,0.3);
            color:#F85149; padding:12px 16px; border-radius:8px;
            font-size:0.875rem; margin-bottom:18px; text-align:center;
        }

        .login-footer {
            color:rgba(212,175,55,0.08); font-size:0.7rem;
            letter-spacing:0.8px; text-transform:uppercase;
        }
    </style>
</head>
<body>
<div class="bg-gradient"></div>
<div class="bg-grid"></div>

<div class="wrapper">
    <div class="card">
        <div class="card-title">Mot de passe oublié</div>
        <div class="card-sub">Entrez votre login — l'administrateur recevra votre demande</div>

        <?php if ($succes): ?>
            <div class="alert-succes"><?= $succes ?></div>
            <a href="index.php" class="retour">← Retour à la connexion</a>
        <?php else: ?>
            <?php if ($erreur): ?>
                <div class="alert-erreur"><?= $erreur ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="field">
                    <label>Votre login</label>
                    <input type="text" name="login" placeholder="Votre login" required autocomplete="off">
                </div>
                <button type="submit" class="btn-submit">Envoyer la demande</button>
            </form>
            <a href="index.php" class="retour">← Retour à la connexion</a>
        <?php endif; ?>
    </div>
    <div class="login-footer">TrackInsi &middot; IUT de Béziers &middot; Département R&T</div>
</div>
</body>
</html>