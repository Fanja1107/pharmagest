<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$recherche = trim($_GET['q'] ?? '');

if ($recherche !== '') {
    $stmt = $pdo->prepare('SELECT * FROM unites WHERE nom_unite LIKE ? ORDER BY nom_unite');
    $stmt->execute(['%' . $recherche . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM unites ORDER BY nom_unite');
}
$unites = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Unités - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Unités</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher une unité..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <a href="/unites/ajouter.php" class="btn btn-primary">+ Ajouter une unité</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Symbole</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($unites)): ?>
                                <tr><td colspan="3">Aucune unité trouvée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($unites as $unite): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($unite['nom_unite']) ?></td>
                                        <td><?= htmlspecialchars($unite['symbole']) ?></td>
                                        <td class="actions-cell">
                                            <a href="/unites/modifier.php?id=<?= $unite['id_unite'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <button type="button" class="btn-danger-text"
                                                onclick="confirmerSuppression('/unites/supprimer.php?id=<?= $unite['id_unite'] ?>', '<?= htmlspecialchars($unite['nom_unite'], ENT_QUOTES) ?>')">
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