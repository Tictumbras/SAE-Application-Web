<?php
session_start();
require 'includes/db.php';

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $login = $_POST['login'];
    $mdp   = hash('sha256', $_POST['mot_de_passe']);

    $stmt = $pdo->prepare("SELECT * FROM utilisateurs WHERE login = ? AND mot_de_passe = ? AND actif = 1");
    $stmt->execute([$login, $mdp]);
    $user = $stmt->fetch();

    if ($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role']    = $user['role'];
        $_SESSION['nom']     = $user['nom'];
        $_SESSION['prenom']  = $user['prenom'];
        header('Location: ' . $user['role'] . '/dashboard.php');
        exit();
    } else {
        $erreur = "Login ou mot de passe incorrect.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>TrackInsi — Connexion</title>
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
                radial-gradient(ellipse at 75% 85%, rgba(212,175,55,0.05) 0%, transparent 45%),
                radial-gradient(ellipse at 50% 50%, rgba(10,40,100,0.35) 0%, transparent 70%),
                linear-gradient(160deg, #0B1A35 0%, #071022 45%, #0C1830 100%);
        }

        .bg-grid {
            position:fixed; inset:0; z-index:0;
            background-image:
                linear-gradient(rgba(212,175,55,0.025) 1px, transparent 1px),
                linear-gradient(90deg, rgba(212,175,55,0.025) 1px, transparent 1px);
            background-size:50px 50px;
        }

        .particles { position:fixed; inset:0; z-index:0; overflow:hidden; }
        .particle { position:absolute; border-radius:50%; animation:floatParticle linear infinite; will-change:transform; }

        @keyframes floatParticle {
            0%   { transform:translateY(100vh); opacity:0; }
            10%  { opacity:1; }
            90%  { opacity:0.4; }
            100% { transform:translateY(-10vh); opacity:0; }
        }

        #iceCanvas { position:fixed; inset:0; z-index:1; pointer-events:none; }

        .center-glow {
            position:fixed; top:50%; left:50%;
            transform:translate(-50%,-50%);
            width:600px; height:600px;
            background:radial-gradient(circle, rgba(212,175,55,0.06) 0%, rgba(10,50,120,0.07) 40%, transparent 70%);
            border-radius:50%; z-index:1;
            animation:glowPulse 6s ease-in-out infinite;
        }

        @keyframes glowPulse {
            0%,100% { opacity:0.5; transform:translate(-50%,-50%) scale(1); }
            50%      { opacity:1;   transform:translate(-50%,-50%) scale(1.06); }
        }

        .login-wrapper {
            position:relative; z-index:10;
            display:flex; flex-direction:column;
            align-items:center; gap:20px;
        }

        .typewriter-container { text-align:center; }

        .typewriter-text {
            font-size:1.3rem; font-weight:300;
            color:rgba(200,215,240,0.75);
            letter-spacing:1.5px; min-height:2rem;
        }

        .typewriter-text .highlight {
            color:#D4AF37; font-weight:600;
            text-shadow:0 0 10px rgba(212,175,55,0.4);
        }

        .cursor {
            display:inline-block; width:2px; height:1.2em;
            background:#D4AF37; margin-left:3px;
            vertical-align:middle;
            animation:blink 0.8s step-end infinite;
        }

        @keyframes blink { 0%,100% { opacity:1; } 50% { opacity:0; } }

        .login-card {
            background:rgba(8,18,40,0.88);
            backdrop-filter:blur(20px);
            border:1px solid rgba(212,175,55,0.2);
            border-radius:20px; padding:48px 44px; width:420px;
            box-shadow:0 0 0 1px rgba(212,175,55,0.03), 0 25px 70px rgba(0,0,0,0.55), inset 0 1px 0 rgba(212,175,55,0.1);
            position:relative;
        }

        .login-card::before {
            content:''; position:absolute; top:0; left:15%; right:15%; height:1px;
            background:linear-gradient(90deg, transparent, rgba(212,175,55,0.6), transparent);
        }

        .login-card::after {
            content:''; position:absolute; inset:-1px; border-radius:20px;
            background:linear-gradient(135deg, rgba(212,175,55,0.07), transparent 40%, transparent 60%, rgba(212,175,55,0.04));
            pointer-events:none;
        }

        .login-logo { text-align:center; margin-bottom:32px; }

        .logo-title {
            font-size:2.6rem; font-weight:700; color:#E8EEF8;
            letter-spacing:-1px; line-height:1;
            animation:titleGlow 4s ease-in-out infinite;
        }

        .logo-title span { color:#D4AF37; }

        @keyframes titleGlow {
            0%,100% { text-shadow:0 0 20px rgba(212,175,55,0.35), 0 0 40px rgba(212,175,55,0.15); }
            50%      { text-shadow:0 0 28px rgba(212,175,55,0.6), 0 0 55px rgba(212,175,55,0.3); }
        }

        .logo-sub {
            font-size:0.72rem; color:rgba(212,175,55,0.5);
            margin-top:10px; letter-spacing:3px;
            text-transform:uppercase; font-weight:400;
        }

        .login-divider {
            height:1px;
            background:linear-gradient(90deg, transparent, rgba(212,175,55,0.2), transparent);
            margin-bottom:26px;
        }

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
            outline:none;
            border-color:rgba(212,175,55,0.45);
            box-shadow:0 0 0 3px rgba(212,175,55,0.08);
        }

        .field input::placeholder { color:#2A3A5A; }

        .mdp-oublie {
            display:block; text-align:right;
            font-size:0.78rem; color:rgba(212,175,55,0.45);
            text-decoration:none; margin-bottom:14px;
            transition:color 0.2s;
        }

        .mdp-oublie:hover { color:rgba(212,175,55,0.9); }

        .login-btn {
            width:100%; padding:13px; margin-top:6px;
            background:linear-gradient(135deg, #C9A227, #D4AF37, #B8941F);
            border:none; border-radius:10px; color:#071022;
            font-family:'IBM Plex Sans',sans-serif;
            font-size:1rem; font-weight:700; cursor:pointer;
            letter-spacing:0.5px; transition:all 0.25s;
            box-shadow:0 4px 20px rgba(212,175,55,0.28), inset 0 1px 0 rgba(255,255,255,0.18);
            position:relative; overflow:hidden;
        }

        .login-btn::before {
            content:''; position:absolute; top:0; left:-100%;
            width:100%; height:100%;
            background:linear-gradient(90deg, transparent, rgba(255,255,255,0.18), transparent);
            transition:left 0.5s;
        }

        .login-btn:hover::before { left:100%; }
        .login-btn:hover { transform:translateY(-2px); box-shadow:0 8px 28px rgba(212,175,55,0.42); }
        .login-btn:active { transform:translateY(0); }

        .login-erreur {
            background:rgba(40,10,10,0.9);
            border:1px solid rgba(248,81,73,0.3);
            color:#F85149; padding:11px 16px; border-radius:8px;
            font-size:0.875rem; margin-bottom:16px; text-align:center;
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
<div class="particles" id="particles"></div>
<div class="center-glow"></div>
<canvas id="iceCanvas"></canvas>

<div class="login-wrapper">
    <div class="typewriter-container">
        <div class="typewriter-text" id="typewriterText">
            <span class="cursor"></span>
        </div>
    </div>

    <div class="login-card">
        <div class="login-logo">
            <div class="logo-title">Track<span>Insi</span></div>
            <div class="logo-sub">Gestion des incidents réseau</div>
        </div>

        <div class="login-divider"></div>

        <?php if ($erreur): ?>
            <div class="login-erreur">⚠ <?= htmlspecialchars($erreur) ?></div>
        <?php endif; ?>

        <form method="POST" autocomplete="off">
            <div class="field">
                <label>Identifiant</label>
                <input type="text" name="login" placeholder="Votre login" required autocomplete="off" value="">
            </div>
            <div class="field">
                <label>Mot de passe</label>
                <input type="password" name="mot_de_passe" placeholder="••••••••" required autocomplete="new-password" value="">
            </div>
            <a href="mot_de_passe_oublie.php" class="mdp-oublie">Mot de passe oublié ?</a>
            <button type="submit" class="login-btn">Se connecter →</button>
        </form>
    </div>

    <div class="login-footer">TrackInsi &middot; IUT de Béziers &middot; Département R&T</div>
</div>

<script>
const phrases = [
    "Bienvenue sur TrackInsi",
    "Suivez vos incidents en temps réel",
    "Une gestion réseau simplifiée",
    "Connectez-vous pour continuer",
];
let phraseIndex = 0, charIndex = 0, isDeleting = false;
const el = document.getElementById('typewriterText');

function type() {
    const current = phrases[phraseIndex];
    if (!isDeleting) charIndex++; else charIndex--;
    const displayed = current.substring(0, charIndex);
    el.innerHTML = displayed.replace('TrackInsi', '<span class="highlight">TrackInsi</span>') + '<span class="cursor"></span>';
    let delay = isDeleting ? 40 : 70;
    if (!isDeleting && charIndex === current.length) { delay = 2200; isDeleting = true; }
    else if (isDeleting && charIndex === 0) { isDeleting = false; phraseIndex = (phraseIndex + 1) % phrases.length; delay = 400; }
    setTimeout(type, delay);
}
setTimeout(type, 600);

const container = document.getElementById('particles');
for (let i = 0; i < 10; i++) {
    const p = document.createElement('div');
    p.className = 'particle';
    const size = Math.random() * 3 + 1;
    const isGold = Math.random() > 0.4;
    p.style.cssText = `width:${size}px;height:${size}px;left:${Math.random()*100}%;background:${isGold?'rgba(212,175,55,0.2)':'rgba(100,150,220,0.12)'};animation-duration:${Math.random()*14+10}s;animation-delay:${Math.random()*14}s;`;
    container.appendChild(p);
}

const canvas = document.getElementById('iceCanvas');
const ctx = canvas.getContext('2d');

function resize() { canvas.width = window.innerWidth; canvas.height = window.innerHeight; drawIce(); }
window.addEventListener('resize', resize);

function rand(a, b) { return a + Math.random() * (b - a); }

function drawBranch(x, y, angle, length, depth, alpha) {
    if (depth === 0 || length < 6) return;
    const endX = x + Math.cos(angle) * length;
    const endY = y + Math.sin(angle) * length;
    const grad = ctx.createLinearGradient(x, y, endX, endY);
    grad.addColorStop(0, `rgba(212,175,55,${alpha})`);
    grad.addColorStop(1, `rgba(212,175,55,0)`);
    ctx.beginPath(); ctx.moveTo(x, y); ctx.lineTo(endX, endY);
    ctx.strokeStyle = grad; ctx.lineWidth = Math.max(depth * 0.28, 0.3);
    ctx.lineCap = 'round'; ctx.stroke();
    const newLen = length * rand(0.55, 0.72);
    const spread = rand(0.28, 0.62);
    drawBranch(endX, endY, angle - spread, newLen, depth - 1, alpha * 0.65);
    drawBranch(endX, endY, angle + spread, newLen, depth - 1, alpha * 0.65);
    if (Math.random() > 0.5) drawBranch(endX, endY, angle + rand(-0.8, 0.8), newLen * 0.45, depth - 2, alpha * 0.35);
}

function drawIce() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    const cx = canvas.width / 2, cy = canvas.height / 2;
    for (let i = 0; i < 14; i++) {
        const angle = (i / 14) * Math.PI * 2 + rand(-0.1, 0.1);
        drawBranch(cx, cy, angle, rand(140, 260), Math.floor(rand(6, 9)), rand(0.28, 0.55));
    }
}

setTimeout(function() { resize(); }, 300);
</script>
</body>
</html>