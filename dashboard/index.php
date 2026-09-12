<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireConnexion();

mettreAJourStatutsLots($pdo);

// Nombre de médicaments actifs
$nbMedicaments = (int)$pdo->query("SELECT COUNT(*) FROM medicaments WHERE statut = 'actif'")->fetchColumn();

// Stock total (somme de toutes les unités de base actives, tous médicaments confondus)
$stockTotal = (int)$pdo->query("SELECT COALESCE(SUM(stock_total), 0) FROM vue_stock_medicaments")->fetchColumn();

// Médicaments en stock faible ou en rupture
$nbStockFaible = (int)$pdo->query("SELECT COUNT(*) FROM vue_stock_medicaments WHERE statut_stock = 'faible'")->fetchColumn();
$nbRupture = (int)$pdo->query("SELECT COUNT(*) FROM vue_stock_medicaments WHERE statut_stock = 'rupture'")->fetchColumn();

// Lots proches de l'expiration (<= 30 jours, non expirés) et lots expirés
$nbExpirationProche = (int)$pdo->query("SELECT COUNT(*) FROM vue_expirations WHERE jours_restants BETWEEN 0 AND 30")->fetchColumn();
$nbExpires = (int)$pdo->query("SELECT COUNT(*) FROM vue_expirations WHERE statut = 'expire'")->fetchColumn();

// Ventes / achats — pas encore développés (Phases 6 et 7), on affiche 0 pour l'instant
$ventesDuJour = 0;
$ventesDuMois = 0;
$achatsDuMois = 0;
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 16px;
            margin-bottom: 24px;
        }
        .stat-card {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 18px;
        }
        .stat-card .stat-label {
            font-size: 0.82rem;
            color: var(--color-text-muted);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .stat-card .stat-value {
            font-size: 1.8rem;
            font-weight: 700;
        }
        .stat-card.stat-danger .stat-value { color: var(--color-danger); }
        .stat-card.stat-warning .stat-value { color: var(--color-warning); }
        .stat-card.stat-success .stat-value { color: var(--color-success); }
        .stat-card.stat-primary .stat-value { color: var(--color-primary); }

        a.stat-card {
            display: block;
            transition: box-shadow 0.15s ease, transform 0.15s ease;
        }
        a.stat-card:hover {
            box-shadow: 0 2px 8px rgba(0,0,0,0.08);
            transform: translateY(-2px);
        }

        .section-title {
            font-size: 1.05rem;
            font-weight: 700;
            margin: 28px 0 14px;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Tableau de bord</h1>

                <div class="section-title">📦 Médicaments &amp; Stock</div>
                <div class="stats-grid">
                    <a href="/medicaments/index.php" class="stat-card stat-primary">
                        <div class="stat-label">Médicaments actifs</div>
                        <div class="stat-value"><?= $nbMedicaments ?></div>
                    </a>
                    <a href="/stock/index.php" class="stat-card stat-primary">
                        <div class="stat-label">Stock total (unités)</div>
                        <div class="stat-value"><?= number_format($stockTotal, 0, ',', ' ') ?></div>
                    </a>
                    <a href="/stock/index.php?statut=faible" class="stat-card stat-warning">
                        <div class="stat-label">Stock faible</div>
                        <div class="stat-value"><?= $nbStockFaible ?></div>
                    </a>
                    <a href="/stock/index.php?statut=rupture" class="stat-card stat-danger">
                        <div class="stat-label">En rupture</div>
                        <div class="stat-value"><?= $nbRupture ?></div>
                    </a>
                </div>

                <div class="section-title">⏳ Expirations</div>
                <div class="stats-grid">
                    <a href="/lots/index.php?statut=proche_expiration" class="stat-card stat-warning">
                        <div class="stat-label">Proches de l'expiration (≤30j)</div>
                        <div class="stat-value"><?= $nbExpirationProche ?></div>
                    </a>
                    <a href="/lots/index.php?statut=expire" class="stat-card stat-danger">
                        <div class="stat-label">Lots expirés</div>
                        <div class="stat-value"><?= $nbExpires ?></div>
                    </a>
                </div>

                <div class="section-title">💰 Ventes &amp; Achats</div>
                <div class="stats-grid">
                    <div class="stat-card stat-success">
                        <div class="stat-label">Ventes du jour</div>
                        <div class="stat-value"><?= $ventesDuJour ?> Ar</div>
                    </div>
                    <div class="stat-card stat-success">
                        <div class="stat-label">Ventes du mois</div>
                        <div class="stat-value"><?= $ventesDuMois ?> Ar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Achats du mois</div>
                        <div class="stat-value"><?= $achatsDuMois ?> Ar</div>
                    </div>
                </div>
                <p style="color:var(--color-text-muted); font-size:0.85rem;">
                    ℹ️ Les statistiques de ventes et d'achats seront actives une fois les modules correspondants développés (Phases 6 et 7).
                </p>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>