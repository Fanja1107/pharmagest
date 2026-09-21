<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

// Période : par défaut, le mois en cours
$dateDebut = trim($_GET['debut'] ?? date('Y-m-01'));
$dateFin = trim($_GET['fin'] ?? date('Y-m-t'));

// Validation basique des dates (si format invalide, on retombe sur le mois en cours)
if (!strtotime($dateDebut) || !strtotime($dateFin) || strtotime($dateDebut) > strtotime($dateFin)) {
    $dateDebut = date('Y-m-01');
    $dateFin = date('Y-m-t');
}

// --- Indicateurs généraux sur la période ---
$stmt = $pdo->prepare(
    "SELECT COUNT(*) AS nb_ventes, COALESCE(SUM(total), 0) AS chiffre_affaires
     FROM ventes
     WHERE statut = 'validee' AND DATE(date_vente) BETWEEN ? AND ?"
);
$stmt->execute([$dateDebut, $dateFin]);
$stats = $stmt->fetch();

$panierMoyen = $stats['nb_ventes'] > 0 ? $stats['chiffre_affaires'] / $stats['nb_ventes'] : 0;

// --- Top 10 médicaments vendus sur la période ---
$stmt = $pdo->prepare(
    "SELECT m.nom, SUM(vd.quantite_base) AS quantite_totale, SUM(vd.sous_total) AS montant_total
     FROM vente_details vd
     JOIN ventes v ON v.id_vente = vd.id_vente
     JOIN medicaments m ON m.id_medicament = vd.id_medicament
     WHERE v.statut = 'validee' AND DATE(v.date_vente) BETWEEN ? AND ?
     GROUP BY m.id_medicament, m.nom
     ORDER BY quantite_totale DESC
     LIMIT 10"
);
$stmt->execute([$dateDebut, $dateFin]);
$topMedicaments = $stmt->fetchAll();

// --- Répartition par mode de paiement sur la période ---
$stmt = $pdo->prepare(
    "SELECT mode_paiement, COUNT(*) AS nb, COALESCE(SUM(total), 0) AS montant
     FROM ventes
     WHERE statut = 'validee' AND DATE(date_vente) BETWEEN ? AND ?
     GROUP BY mode_paiement"
);
$stmt->execute([$dateDebut, $dateFin]);
$paiements = $stmt->fetchAll();

// --- Valeur totale du stock actuel (indépendant de la période) ---
$stmt = $pdo->query(
    "SELECT COALESCE(SUM(l.quantite_base * l.prix_achat_base), 0) AS valeur_stock
     FROM lots l
     WHERE l.statut = 'actif'"
);
$valeurStock = (float)$stmt->fetchColumn();

$labelsPaiement = [
    'especes' => 'Espèces',
    'mobile_money' => 'Mobile Money',
    'carte' => 'Carte',
    'virement' => 'Virement',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Rapports - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
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
            font-size: 0.8rem;
            color: var(--color-text-muted);
            font-weight: 600;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        .stat-card .stat-value {
            font-size: 1.6rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .filtre-periode {
            display: flex;
            gap: 10px;
            align-items: end;
            flex-wrap: wrap;
            margin-bottom: 24px;
        }
        .filtre-periode .form-group { margin: 0; }
        .filtre-periode label { display: block; font-size: 0.82rem; font-weight: 600; margin-bottom: 6px; }
        .filtre-periode input {
            padding: 9px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
        }
        .rapport-grid {
            display: grid;
            grid-template-columns: 1.3fr 1fr;
            gap: 20px;
        }
        @media (max-width: 900px) {
            .rapport-grid { grid-template-columns: 1fr; }
        }
        .section-title { font-size: 1.05rem; font-weight: 700; margin: 28px 0 14px; }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Rapports</h1>

                <form method="GET" class="filtre-periode">
                    <div class="form-group">
                        <label for="debut">Du</label>
                        <input type="date" id="debut" name="debut" value="<?= htmlspecialchars($dateDebut) ?>">
                    </div>
                    <div class="form-group">
                        <label for="fin">Au</label>
                        <input type="date" id="fin" name="fin" value="<?= htmlspecialchars($dateFin) ?>">
                    </div>
                    <button type="submit" class="btn btn-primary">Filtrer</button>
                </form>

                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-label">Chiffre d'affaires</div>
                        <div class="stat-value"><?= number_format($stats['chiffre_affaires'], 0, ',', ' ') ?> Ar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Nombre de ventes</div>
                        <div class="stat-value"><?= (int)$stats['nb_ventes'] ?></div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Panier moyen</div>
                        <div class="stat-value"><?= number_format($panierMoyen, 0, ',', ' ') ?> Ar</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-label">Valeur du stock actuel</div>
                        <div class="stat-value"><?= number_format($valeurStock, 0, ',', ' ') ?> Ar</div>
                    </div>
                </div>

                <div class="rapport-grid">
                    <div class="card">
                        <h3 style="margin-top:0;">Top 10 médicaments vendus (période sélectionnée)</h3>
                        <?php if (empty($topMedicaments)): ?>
                            <p style="color:var(--color-text-muted); font-size:0.85rem;">Aucune vente sur cette période.</p>
                        <?php else: ?>
                            <div class="table-wrapper">
                                <table class="data-table">
                                    <thead>
                                        <tr>
                                            <th>Médicament</th>
                                            <th>Qté vendue</th>
                                            <th>Montant</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($topMedicaments as $tm): ?>
                                            <tr>
                                                <td><?= htmlspecialchars($tm['nom']) ?></td>
                                                <td><?= (int)$tm['quantite_totale'] ?></td>
                                                <td><?= number_format($tm['montant_total'], 0, ',', ' ') ?> Ar</td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div class="card">
                        <h3 style="margin-top:0;">Répartition par mode de paiement</h3>
                        <?php if (empty($paiements)): ?>
                            <p style="color:var(--color-text-muted); font-size:0.85rem;">Aucune vente sur cette période.</p>
                        <?php else: ?>
                            <canvas id="graphPaiements" height="220"></canvas>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <?php if (!empty($paiements)): ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
    <script>
        const labels = <?= json_encode(array_map(fn($p) => $labelsPaiement[$p['mode_paiement']] ?? $p['mode_paiement'], $paiements)) ?>;
        const montants = <?= json_encode(array_map('floatval', array_column($paiements, 'montant'))) ?>;

        new Chart(document.getElementById('graphPaiements'), {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: montants,
                    backgroundColor: ['#2563eb', '#16a34a', '#f59e0b', '#dc2626'],
                }]
            },
            options: {
                responsive: true,
                plugins: { legend: { position: 'bottom' } }
            }
        });
    </script>
    <?php endif; ?>
</body>
</html>