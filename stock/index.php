<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireConnexion();

$peutGerer = in_array($_SESSION['role'], ['admin', 'pharmacien'], true);

mettreAJourStatutsLots($pdo);

$recherche = trim($_GET['q'] ?? '');
$statutFiltre = trim($_GET['statut'] ?? '');

$sql = "SELECT * FROM vue_stock_medicaments WHERE 1=1";
$params = [];

if ($recherche !== '') {
    $sql .= ' AND (nom LIKE ? OR reference LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}

if ($statutFiltre !== '' && in_array($statutFiltre, ['normal', 'faible', 'rupture'], true)) {
    $sql .= ' AND statut_stock = ?';
    $params[] = $statutFiltre;
}

$sql .= ' ORDER BY nom';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$stocks = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Stock - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .filtre-statut {
            padding: 9px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            font-size: 0.9rem;
            background: #fff;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Stock</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche" style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par nom ou référence..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">

                        <select name="statut" id="filtreStatut" class="filtre-statut" onchange="document.getElementById('formRecherche').submit()">
                            <option value="">Tous les statuts</option>
                            <option value="normal" <?= $statutFiltre === 'normal' ? 'selected' : '' ?>>Normal</option>
                            <option value="faible" <?= $statutFiltre === 'faible' ? 'selected' : '' ?>>Stock faible</option>
                            <option value="rupture" <?= $statutFiltre === 'rupture' ? 'selected' : '' ?>>Rupture</option>
                        </select>
                    </form>
                    <?php if ($peutGerer): ?>
                        <a href="/lots/ajouter.php" class="btn btn-primary">+ Ajouter un lot</a>
                    <?php endif; ?>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Réf.</th>
                                <th>Médicament</th>
                                <th>Dosage</th>
                                <th>Stock total</th>
                                <th>Seuil d'alerte</th>
                                <th>Statut stock</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($stocks)): ?>
                                <tr><td colspan="6">Aucun médicament trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($stocks as $s): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($s['reference']) ?></td>
                                        <td><?= htmlspecialchars($s['nom']) ?></td>
                                        <td><?= htmlspecialchars($s['dosage'] ?? '—') ?></td>
                                        <td><strong><?= (int)$s['stock_total'] ?></strong></td>
                                        <td><?= (int)$s['seuil_alerte'] ?></td>
                                        <td>
                                            <?php
                                                $badgeClass = match($s['statut_stock']) {
                                                    'normal' => 'badge-success',
                                                    'faible' => 'badge-warning',
                                                    'rupture' => 'badge-danger',
                                                    default => 'badge-warning',
                                                };
                                                $libelle = match($s['statut_stock']) {
                                                    'normal' => 'Normal',
                                                    'faible' => 'Stock faible',
                                                    'rupture' => 'Rupture',
                                                    default => $s['statut_stock'],
                                                };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= $libelle ?></span>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const champRecherche = document.getElementById('champRecherche');
        let minuteur;

        champRecherche.addEventListener('input', function () {
            clearTimeout(minuteur);
            minuteur = setTimeout(() => {
                document.getElementById('formRecherche').submit();
            }, 400);
        });

        champRecherche.focus();
        const valeur = champRecherche.value;
        champRecherche.value = '';
        champRecherche.value = valeur;
    </script>
</body>
</html>