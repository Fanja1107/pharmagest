<?php
// Détermine la page active pour surligner le bon lien du menu
$current_page = basename($_SERVER['PHP_SELF']);
$peutGererStock = in_array($_SESSION['role'] ?? '', ['admin', 'pharmacien'], true);
$estAdmin = ($_SESSION['role'] ?? '') === 'admin';
?>
<aside class="sidebar" id="sidebar">
    <div>
        <span class="sidebar-brand">💊 PharmaGest</span>
        <ul class="sidebar-nav">
            <li><a href="/dashboard/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/dashboard/') ? 'active' : '' ?>">📊 Dashboard</a></li>
            <li><a href="/medicaments/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/medicaments/') ? 'active' : '' ?>">💊 Médicaments</a></li>
            <?php if ($peutGererStock): ?>
                <li><a href="/categories/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/categories/') ? 'active' : '' ?>">🗂️ Catégories</a></li>
                <li><a href="/unites/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/unites/') ? 'active' : '' ?>">📏 Unités</a></li>
            <?php endif; ?>
            <li><a href="/conditionnements/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/conditionnements/') ? 'active' : '' ?>">🏷️ Conditionnements</a></li>
            <li><a href="/lots/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/lots/') ? 'active' : '' ?>">📋 Lots</a></li>
            <li><a href="/stock/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/stock/') ? 'active' : '' ?>">📦 Stock</a></li>
            <?php if ($peutGererStock): ?>
                <li><a href="/fournisseurs/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/fournisseurs/') ? 'active' : '' ?>">🚚 Fournisseurs</a></li>
                <li><a href="/approvisionnements/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/approvisionnements/') ? 'active' : '' ?>">📥 Achats</a></li>
            <?php endif; ?>
            <li><a href="/ventes/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/ventes/') ? 'active' : '' ?>">🧾 Ventes</a></li>
            <li><a href="/factures/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/factures/') ? 'active' : '' ?>">📄 Factures</a></li>
            <li><a href="/clients/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/clients/') ? 'active' : '' ?>">👥 Clients</a></li>
            <?php if ($estAdmin): ?>
                <li><a href="/utilisateurs/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/utilisateurs/') ? 'active' : '' ?>">🔐 Utilisateurs</a></li>
            <?php endif; ?>
            <li><a href="/rapports/index.php" class="<?= str_contains($_SERVER['REQUEST_URI'], '/rapports/') ? 'active' : '' ?>">📈 Rapports</a></li>
        </ul>
    </div>

    <div class="sidebar-bottom">
        <a href="/auth/logout.php" class="sidebar-logout">🚪 Déconnexion</a>
    </div>
</aside>