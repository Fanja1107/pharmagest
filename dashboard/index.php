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
// Ventes du jour et du mois (uniquement les ventes validées)
$ventesDuJour = (float)$pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM ventes WHERE statut = 'validee' AND DATE(date_vente) = CURDATE()"
)->fetchColumn();

$ventesDuMois = (float)$pdo->query(
    "SELECT COALESCE(SUM(total), 0) FROM ventes WHERE statut = 'validee'
     AND MONTH(date_vente) = MONTH(CURDATE()) AND YEAR(date_vente) = YEAR(CURDATE())"
)->fetchColumn();

// Achats du mois (uniquement les approvisionnements validés)
$achatsDuMois = (float)$pdo->query(
    "SELECT COALESCE(SUM(total_achat), 0) FROM approvisionnements WHERE statut = 'valide'
     AND MONTH(date_approvisionnement) = MONTH(CURDATE()) AND YEAR(date_approvisionnement) = YEAR(CURDATE())"
)->fetchColumn();
// Ventes des 7 derniers jours (pour le graphique d'évolution)
$stmt = $pdo->query(
    "SELECT DATE(date_vente) AS jour, SUM(total) AS total_jour
     FROM ventes
     WHERE statut = 'validee' AND date_vente >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY DATE(date_vente)
     ORDER BY jour ASC"
);
$ventesParJourBrut = $stmt->fetchAll();

// On complète les jours sans vente avec 0, pour avoir toujours 7 points sur le graphique
$ventesParJour = [];
for ($i = 6; $i >= 0; $i--) {
    $jour = date('Y-m-d', strtotime("-$i day"));
    $ventesParJour[$jour] = 0;
}
foreach ($ventesParJourBrut as $ligne) {
    $ventesParJour[$ligne['jour']] = (float)$ligne['total_jour'];
}

// Top 5 des médicaments les plus vendus (en quantité de base), toutes ventes validées confondues
$stmt = $pdo->query(
    "SELECT m.nom, SUM(vd.quantite_base) AS quantite_totale
     FROM vente_details vd
     JOIN ventes v ON v.id_vente = vd.id_vente
     JOIN medicaments m ON m.id_medicament = vd.id_medicament
     WHERE v.statut = 'validee'
     GROUP BY m.id_medicament, m.nom
     ORDER BY quantite_totale DESC
     LIMIT 5"
);
$topMedicaments = $stmt->fetchAll();
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
                    <a href="/ventes/index.php" class="stat-card stat-success">
                        <div class="stat-label">Ventes du jour</div>
                        <div class="stat-value"><?= number_format($ventesDuJour, 0, ',', ' ') ?> Ar</div>
                    </a>
                    <a href="/ventes/index.php" class="stat-card stat-success">
                        <div class="stat-label">Ventes du mois</div>
                        <div class="stat-value"><?= number_format($ventesDuMois, 0, ',', ' ') ?> Ar</div>
                    </a>
                    <a href="/approvisionnements/index.php" class="stat-card">
                        <div class="stat-label">Achats du mois</div>
                        <div class="stat-value"><?= number_format($achatsDuMois, 0, ',', ' ') ?> Ar</div>
                    </a>
                </div>

                <div class="section-title">📈 Graphiques</div>
                <div class="stats-grid" style="grid-template-columns: 1fr 1fr;">
                    <div class="stat-card">
                        <div class="stat-label" style="margin-bottom:14px;">Ventes des 7 derniers jours</div>
                        <canvas id="graphVentes" height="180"></canvas>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label" style="margin-bottom:14px;">Top 5 médicaments les plus vendus</div>
                        <?php if (empty($topMedicaments)): ?>
                            <p style="color:var(--color-text-muted); font-size:0.85rem;">Aucune vente enregistrée pour le moment.</p>
                        <?php else: ?>
                            <canvas id="graphTopMedicaments" height="180"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

        <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        // Graphique 1 : évolution des ventes sur 7 jours
        const joursVentes = <?= json_encode(array_keys($ventesParJour)) ?>;
        const montantsVentes = <?= json_encode(array_values($ventesParJour)) ?>;

        const joursFormates = joursVentes.map(j => {
            const d = new Date(j);
            return d.toLocaleDateString('fr-FR', { day: '2-digit', month: '2-digit' });
        });

        new Chart(document.getElementById('graphVentes'), {
            type: 'line',
            data: {
                labels: joursFormates,
                datasets: [{
                    label: 'Ventes (Ar)',
                    data: montantsVentes,
                    borderColor: '#2563eb',
                    backgroundColor: 'rgba(37, 99, 235, 0.1)',
                    fill: true,
                    tension: 0.3,
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { display: false } },
                scales: { y: { beginAtZero: true } }
            }
        });

        <?php if (!empty($topMedicaments)): ?>
        // Graphique 2 : top médicaments les plus vendus
        const nomsMedicaments = <?= json_encode(array_column($topMedicaments, 'nom')) ?>;
        const quantitesMedicaments = <?= json_encode(array_map('intval', array_column($topMedicaments, 'quantite_totale'))) ?>;

        new Chart(document.getElementById('graphTopMedicaments'), {
            type: 'bar',
            data: {
                labels: nomsMedicaments,
                datasets: [{
                    label: 'Unités vendues',
                    data: quantitesMedicaments,
                    backgroundColor: '#16a34a',
                }]
            },
            options: {
                responsive: true,
                indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: { x: { beginAtZero: true } }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>