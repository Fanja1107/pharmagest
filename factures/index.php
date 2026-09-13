<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$recherche = trim($_GET['q'] ?? '');

$sql = "SELECT v.*, c.nom AS client_nom, c.prenom AS client_prenom
        FROM ventes v
        LEFT JOIN clients c ON c.id_client = v.id_client
        WHERE v.statut = 'validee'";
$params = [];

if ($recherche !== '') {
    $sql .= ' AND (v.numero_vente LIKE ? OR c.nom LIKE ? OR c.prenom LIKE ?)';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
    $params[] = '%' . $recherche . '%';
}

$sql .= ' ORDER BY v.date_vente DESC';

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$factures = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factures - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Factures</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par n° facture ou client..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <div></div>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>N° facture</th>
                                <th>Date</th>
                                <th>Client</th>
                                <th>Total</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($factures)): ?>
                                <tr><td colspan="5">Aucune facture trouvée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($factures as $f): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($f['numero_vente']) ?></td>
                                        <td><?= date('d/m/Y H:i', strtotime($f['date_vente'])) ?></td>
                                        <td><?= $f['client_nom'] ? htmlspecialchars($f['client_nom'] . ' ' . $f['client_prenom']) : 'Comptoir' ?></td>
                                        <td><?= number_format($f['total'], 0, ',', ' ') ?> Ar</td>
                                        <td class="actions-cell">
                                            <a href="/factures/voir.php?id=<?= $f['id_vente'] ?>" class="btn btn-outline btn-sm" target="_blank">Voir</a>
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