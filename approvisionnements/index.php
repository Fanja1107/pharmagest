<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$sql = "SELECT a.*, f.raison_sociale, u.nom AS utilisateur_nom
        FROM approvisionnements a
        JOIN fournisseurs f ON f.id_fournisseur = a.id_fournisseur
        JOIN utilisateurs u ON u.id_utilisateur = a.id_utilisateur
        ORDER BY a.date_approvisionnement DESC";
$approvisionnements = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvisionnements - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Approvisionnements</h1>

                <div class="table-toolbar">
                    <div></div>
                    <a href="/approvisionnements/ajouter.php" class="btn btn-primary">+ Nouvel approvisionnement</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                                                        <tr>
                                <th>N° achat</th>
                                <th>Fournisseur</th>
                                <th>Date</th>
                                <th>Total</th>
                                <th>Créé par</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($approvisionnements)): ?>
                                <tr><td colspan="7">Aucun approvisionnement trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($approvisionnements as $a): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($a['numero_appro']) ?></td>
                                        <td><?= htmlspecialchars($a['raison_sociale']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($a['date_approvisionnement'])) ?></td>
                                        <td><?= number_format($a['total_achat'], 0, ',', ' ') ?> Ar</td>
                                        <td><?= htmlspecialchars($a['utilisateur_nom']) ?></td>
                                        <td>
                                            <?php
                                                $badgeClass = match($a['statut']) {
                                                    'brouillon' => 'badge-warning',
                                                    'valide' => 'badge-success',
                                                    'annule' => 'badge-danger',
                                                    default => 'badge-warning',
                                                };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($a['statut']) ?></span>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="/approvisionnements/voir.php?id=<?= $a['id_approvisionnement'] ?>" class="btn btn-outline btn-sm">Voir</a>
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
</body>
</html>