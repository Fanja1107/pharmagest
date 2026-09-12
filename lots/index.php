<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireConnexion();

mettreAJourStatutsLots($pdo);

$recherche = trim($_GET['q'] ?? '');
$statutFiltre = trim($_GET['statut'] ?? '');

$sql = "SELECT l.*, m.nom AS medicament_nom,
        DATEDIFF(l.date_expiration, CURDATE()) AS jours_restants
        FROM lots l
        JOIN medicaments m ON m.id_medicament = l.id_medicament
        WHERE 1=1";
$params = [];

if ($recherche !== '') {
    $sql .= ' AND (m.nom LIKE ? OR l.numero_lot LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}

if ($statutFiltre !== '' && in_array($statutFiltre, ['actif', 'expire', 'epuise'], true)) {
    $sql .= ' AND l.statut = ?';
    $params[] = $statutFiltre;
}

$sql .= ' ORDER BY l.date_expiration ASC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$lots = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lots - PharmaGest</title>
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
                <h1 class="page-title">Lots</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche" style="display:flex; gap:10px; flex-wrap:wrap;">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par médicament ou n° de lot..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">

                        <select name="statut" id="filtreStatut" class="filtre-statut" onchange="document.getElementById('formRecherche').submit()">
                            <option value="">Tous les statuts</option>
                            <option value="actif" <?= $statutFiltre === 'actif' ? 'selected' : '' ?>>Actif</option>
                            <option value="expire" <?= $statutFiltre === 'expire' ? 'selected' : '' ?>>Expiré</option>
                            <option value="epuise" <?= $statutFiltre === 'epuise' ? 'selected' : '' ?>>Épuisé</option>
                        </select>
                    </form>
                    <a href="/lots/ajouter.php" class="btn btn-primary">+ Ajouter un lot</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>N° de lot</th>
                                <th>Expiration</th>
                                <th>Quantité</th>
                                <th>Prix d'achat</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($lots)): ?>
                                <tr><td colspan="7">Aucun lot trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($lots as $l): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($l['medicament_nom']) ?></td>
                                        <td><?= htmlspecialchars($l['numero_lot']) ?></td>
                                        <td>
                                            <?= date('d/m/Y', strtotime($l['date_expiration'])) ?>
                                            <?php if ($l['jours_restants'] < 0): ?>
                                                <span class="badge badge-danger">Expiré</span>
                                            <?php elseif ($l['jours_restants'] <= 30): ?>
                                                <span class="badge badge-warning">J-<?= (int)$l['jours_restants'] ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= (int)$l['quantite_base'] ?></td>
                                        <td><?= number_format($l['prix_achat_base'], 0, ',', ' ') ?> Ar</td>
                                        <td>
                                            <?php
                                                $badgeClass = match($l['statut']) {
                                                    'actif' => 'badge-success',
                                                    'epuise' => 'badge-warning',
                                                    'expire' => 'badge-danger',
                                                    default => 'badge-warning',
                                                };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($l['statut']) ?></span>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="/lots/modifier.php?id=<?= $l['id_lot'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <button type="button" class="btn-danger-text"
                                                onclick="confirmerSuppression('/lots/supprimer.php?id=<?= $l['id_lot'] ?>', '<?= htmlspecialchars($l['numero_lot'], ENT_QUOTES) ?>')">
                                                Supprimer
                                            </button>
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