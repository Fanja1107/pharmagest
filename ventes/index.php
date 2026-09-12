<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$sql = "SELECT v.*, c.nom AS client_nom, c.prenom AS client_prenom, u.nom AS vendeur_nom
        FROM ventes v
        LEFT JOIN clients c ON c.id_client = v.id_client
        JOIN utilisateurs u ON u.id_utilisateur = v.id_utilisateur
        ORDER BY v.date_vente DESC";
$ventes = $pdo->query($sql)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ventes - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Ventes</h1>

                <div class="table-toolbar">
                    <div></div>
                    <a href="/ventes/nouvelle.php" class="btn btn-primary">+ Nouvelle vente</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N° vente</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Vendeur</th>
                                <th>Total</th>
                                <th>Paiement</th>
                                <th>Statut</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($ventes)): ?>
                                <tr><td colspan="7">Aucune vente enregistrée pour le moment.</td></tr>
                            <?php else: ?>
                                <?php foreach ($ventes as $v): ?>
                                    <tr onclick="window.location.href='/ventes/voir.php?id=<?= $v['id_vente'] ?>'" style="cursor:pointer;">
                                        <td><a href="/ventes/voir.php?id=<?= $v['id_vente'] ?>"><?= htmlspecialchars($v['numero_vente']) ?></a></td>
                                        <td><?= date('d/m/Y H:i', strtotime($v['date_vente'])) ?></td>
                                        <td><?= $v['client_nom'] ? htmlspecialchars($v['client_nom'] . ' ' . $v['client_prenom']) : 'Comptoir' ?></td>
                                        <td><?= htmlspecialchars($v['vendeur_nom']) ?></td>
                                        <td><?= number_format($v['total'], 0, ',', ' ') ?> Ar</td>
                                        <td><?= htmlspecialchars(str_replace('_', ' ', $v['mode_paiement'])) ?></td>
                                        <td>
                                            <?php
                                                $badgeClass = match($v['statut']) {
                                                    'validee' => 'badge-success',
                                                    'annulee' => 'badge-danger',
                                                    default => 'badge-warning',
                                                };
                                            ?>
                                            <span class="badge <?= $badgeClass ?>"><?= ucfirst($v['statut']) ?></span>
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