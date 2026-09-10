<header class="topbar">
    <button class="topbar-toggle" id="sidebarToggle">☰</button>
    <div class="topbar-user">
        <span>Bonjour, <strong><?= htmlspecialchars($_SESSION['prenom'] ?? '') ?></strong></span>
        <span class="role-badge"><?= htmlspecialchars($_SESSION['role'] ?? '') ?></span>
        <a href="/auth/logout.php" style="margin-left:10px; color: var(--color-danger); font-size:0.85rem;">Déconnexion</a>
    </div>
</header>