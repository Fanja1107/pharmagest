<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$recherche = trim($_GET['q'] ?? '');

if ($recherche !== '') {
    $stmt = $pdo->prepare('SELECT * FROM clients WHERE nom LIKE ? OR prenom LIKE ? OR telephone LIKE ? ORDER BY nom');
    $stmt->execute(['%' . $recherche . '%', '%' . $recherche . '%', '%' . $recherche . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM clients ORDER BY nom');
}
$clients = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Clients - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Clients</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par nom ou téléphone..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <a href="/clients/ajouter.php" class="btn btn-primary">+ Ajouter un client</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($clients)): ?>
                                <tr><td colspan="5">Aucun client trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($clients as $cl): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cl['nom']) ?></td>
                                        <td><?= htmlspecialchars($cl['prenom'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($cl['telephone'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($cl['email'] ?? '—') ?></td>
                                        <td class="actions-cell">
                                            <a href="/clients/modifier.php?id=<?= $cl['id_client'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <button type="button" class="btn-danger-text"
                                                onclick="confirmerSuppression('/clients/supprimer.php?id=<?= $cl['id_client'] ?>', '<?= htmlspecialchars($cl['nom'], ENT_QUOTES) ?>')">
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