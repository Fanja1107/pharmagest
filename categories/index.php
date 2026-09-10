<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$recherche = trim($_GET['q'] ?? '');

if ($recherche !== '') {
    $stmt = $pdo->prepare('SELECT * FROM categories WHERE libelle LIKE ? ORDER BY libelle');
    $stmt->execute(['%' . $recherche . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM categories ORDER BY libelle');
}
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Catégories - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Catégories</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher une catégorie..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <a href="/categories/ajouter.php" class="btn btn-primary">+ Ajouter une catégorie</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Libellé</th>
                                <th>Description</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($categories)): ?>
                                <tr><td colspan="3">Aucune catégorie trouvée.</td></tr>
                            <?php else: ?>
                                <?php foreach ($categories as $cat): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($cat['libelle']) ?></td>
                                        <td><?= htmlspecialchars($cat['description'] ?? '—') ?></td>
                                        <td class="actions-cell">
                                            <a href="/categories/modifier.php?id=<?= $cat['id_categorie'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <button type="button" class="btn-danger-text"
                                                onclick="confirmerSuppression('/categories/supprimer.php?id=<?= $cat['id_categorie'] ?>', '<?= htmlspecialchars($cat['libelle'], ENT_QUOTES) ?>')">
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
        // Recherche en direct : soumet le formulaire 400ms après la dernière frappe
        const champRecherche = document.getElementById('champRecherche');
        let minuteur;

        champRecherche.addEventListener('input', function () {
            clearTimeout(minuteur);
            minuteur = setTimeout(() => {
                document.getElementById('formRecherche').submit();
            }, 400);
        });

        // Remet le focus sur le champ après le rechargement, curseur à la fin
        champRecherche.focus();
        const valeur = champRecherche.value;
        champRecherche.value = '';
        champRecherche.value = valeur;
    </script>
</body>
</html>