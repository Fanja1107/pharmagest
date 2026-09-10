<?php
// Détermine la page active pour surligner le bon lien du menu
$current_page = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar" id="sidebar">
    <span class="sidebar-brand">💊 PharmaGest</span>
    <ul class="sidebar-nav">
        <li><a href="/dashboard/index.php" class="<?= $current_page === 'index.php' ? 'active' : '' ?>">📊 Dashboard</a></li>
        <li><a href="/medicaments/index.php">💊 Médicaments</a></li>
        <li><a href="/stock/index.php">📦 Stock</a></li>
        <li><a href="/fournisseurs/index.php">🚚 Fournisseurs</a></li>
        <li><a href="/approvisionnements/index.php">📥 Achats</a></li>
        <li><a href="/ventes/index.php">🧾 Ventes</a></li>
        <li><a href="/clients/index.php">👥 Clients</a></li>
        <li><a href="/utilisateurs/index.php">🔐 Utilisateurs</a></li>
        <li><a href="/rapports/index.php">📈 Rapports</a></li>
    </ul>
</aside>