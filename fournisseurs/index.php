<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$recherche = trim($_GET['q'] ?? '');

if ($recherche !== '') {
    $stmt = $pdo->prepare('SELECT * FROM fournisseurs WHERE raison_sociale LIKE ? OR telephone LIKE ? ORDER BY raison_sociale');
    $stmt->execute(['%' . $recherche . '%', '%' . $recherche . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM fournisseurs ORDER BY raison_sociale');
}
$fournisseurs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fournisseurs - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Fournisseurs</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher un fournisseur..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <a href="/fournisseurs/ajouter.php" class="btn btn-primary">+ Ajouter un fournisseur</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Raison sociale</th>
                                <th>Téléphone</th>
                                <th>Email</th>
                                <th>NIF</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($fournisseurs)): ?>
                                <tr><td colspan="6">Aucun fournisseur trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($fournisseurs as $f): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($f['raison_sociale']) ?></td>
                                        <td><?= htmlspecialchars($f['telephone'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($f['email'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($f['nif'] ?? '—') ?></td>
                                        <td>
                                            <?php if ($f['statut'] === 'actif'): ?>
                                                <span class="badge badge-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="/fournisseurs/modifier.php?id=<?= $f['id_fournisseur'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <?php if ($f['statut'] === 'actif'): ?>
                                                <button type="button" class="btn-danger-text"
                                                    onclick="confirmerSuppression('/fournisseurs/desactiver.php?id=<?= $f['id_fournisseur'] ?>&action=desactiver', '<?= htmlspecialchars($f['raison_sociale'], ENT_QUOTES) ?>')">
                                                    Désactiver
                                                </button>
                                            <?php else: ?>
                                                <a href="/fournisseurs/desactiver.php?id=<?= $f['id_fournisseur'] ?>&action=activer" class="btn-danger-text" style="color:var(--color-success);">
                                                    Réactiver
                                                </a>
                                            <?php endif; ?>
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