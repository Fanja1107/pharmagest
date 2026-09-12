<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT v.*, c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone,
            u.nom AS vendeur_nom, u.prenom AS vendeur_prenom
     FROM ventes v
     LEFT JOIN clients c ON c.id_client = v.id_client
     JOIN utilisateurs u ON u.id_utilisateur = v.id_utilisateur
     WHERE v.id_vente = ?"
);
$stmt->execute([$id]);
$vente = $stmt->fetch();

if (!$vente) {
    $_SESSION['flash_error'] = 'Vente introuvable.';
    header('Location: /ventes/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT vd.*, m.nom AS medicament_nom, c.libelle AS conditionnement_libelle
     FROM vente_details vd
     JOIN medicaments m ON m.id_medicament = vd.id_medicament
     JOIN conditionnements c ON c.id_conditionnement = vd.id_conditionnement
     WHERE vd.id_vente = ?"
);
$stmt->execute([$id]);
$lignes = $stmt->fetchAll();

// Pour chaque ligne, on récupère la répartition par lot (traçabilité FEFO)
foreach ($lignes as &$ligne) {
    $stmtLots = $pdo->prepare(
        "SELECT vl.*, l.numero_lot, l.date_expiration
         FROM vente_lots vl
         JOIN lots l ON l.id_lot = vl.id_lot
         WHERE vl.id_vente_detail = ?"
    );
    $stmtLots->execute([$ligne['id_vente_detail']]);
    $ligne['lots_utilises'] = $stmtLots->fetchAll();
}
unset($ligne);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vente <?= htmlspecialchars($vente['numero_vente']) ?> - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .lots-detail {
            font-size: 0.75rem;
            color: var(--color-text-muted);
            margin-top: 4px;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Vente <?= htmlspecialchars($vente['numero_vente']) ?></h1>

                <div class="card" style="margin-bottom:20px;">
                    <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($vente['date_vente'])) ?></p>
                    <p><strong>Client :</strong> <?= $vente['client_nom'] ? htmlspecialchars($vente['client_nom'] . ' ' . $vente['client_prenom']) . ($vente['client_telephone'] ? ' — ' . htmlspecialchars($vente['client_telephone']) : '') : 'Vente comptoir (sans client)' ?></p>
                    <p><strong>Vendeur :</strong> <?= htmlspecialchars($vente['vendeur_nom'] . ' ' . $vente['vendeur_prenom']) ?></p>
                    <p><strong>Mode de paiement :</strong> <?= htmlspecialchars(str_replace('_', ' ', $vente['mode_paiement'])) ?></p>
                    <p>
                        <strong>Statut :</strong>
                        <?php
                            $badgeClass = match($vente['statut']) {
                                'validee' => 'badge-success',
                                'annulee' => 'badge-danger',
                                default => 'badge-warning',
                            };
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($vente['statut']) ?></span>
                    </p>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Produit</th>
                                <th>Qté</th>
                                <th>Prix unit.</th>
                                <th>Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $l): ?>
                                <tr>
                                    <td>
                                        <?= htmlspecialchars($l['medicament_nom']) ?> — <?= htmlspecialchars($l['conditionnement_libelle']) ?>
                                        <div class="lots-detail">
                                            Lots utilisés (FEFO) :
                                            <?php foreach ($l['lots_utilises'] as $lu): ?>
                                                <?= htmlspecialchars($lu['numero_lot']) ?> (<?= (int)$lu['quantite_base'] ?> u., exp. <?= date('d/m/Y', strtotime($lu['date_expiration'])) ?>)<?= end($l['lots_utilises']) !== $lu ? ', ' : '' ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </td>
                                    <td><?= (int)$l['quantite'] ?></td>
                                    <td><?= number_format($l['prix_unitaire'], 0, ',', ' ') ?> Ar</td>
                                    <td><?= number_format($l['sous_total'], 0, ',', ' ') ?> Ar</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="3" style="text-align:right; font-weight:700;">Total</td>
                                <td style="font-weight:700;"><?= number_format($vente['total'], 0, ',', ' ') ?> Ar</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div style="margin-top:20px; display:flex; gap:10px;">
                    <a href="/factures/voir.php?id=<?= $vente['id_vente'] ?>" class="btn btn-primary" target="_blank">🧾 Voir la facture</a>
                    <a href="/ventes/index.php" class="btn btn-outline">← Retour à la liste</a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>