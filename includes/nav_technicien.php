<?php
$photoSrc = isset($userConnecte['photo']) && $userConnecte['photo']
    ? '/~DUMONT-Tom/trackInsi/assets/img/profils/' . $userConnecte['photo']
    : null;
?>
<nav>
    <div class="nav-brand"><span>TrackInsi</span></div>
    <div class="nav-links">
        <a href="/~DUMONT-Tom/trackInsi/technicien/dashboard.php"
           class="<?= basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : '' ?>">
            🔧 Mes interventions
        </a>
    </div>
    <div class="nav-bottom">
        <a href="/~DUMONT-Tom/trackInsi/logout.php" class="nav-logout">⏻ Déconnexion</a>
        <a href="/~DUMONT-Tom/trackInsi/profil.php" class="nav-user">
            <?php if ($photoSrc): ?>
                <img src="<?= $photoSrc ?>" class="avatar" alt="photo">
            <?php else: ?>
                <div class="avatar-placeholder"></div>
            <?php endif; ?>
            <span><?= htmlspecialchars($_SESSION['prenom'] . ' ' . $_SESSION['nom']) ?></span>
        </a>
    </div>
</nav>