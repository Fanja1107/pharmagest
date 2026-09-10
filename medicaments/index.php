<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$recherche = trim($_GET['q'] ?? '');

$sql = 'SELECT m.*, c.libelle AS categorie_libelle
        FROM medicaments m
        JOIN categories c ON c.id_categorie = m.id_categorie';

if ($recherche !== '') {
    $sql .= ' WHERE m.nom LIKE ? OR m.reference LIKE ?';
    $sql .= ' ORDER BY m.nom';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $recherche . '%', '%' . $recherche . '%']);
} else {
    $sql .= ' ORDER BY m.nom';
    $stmt = $pdo->query($sql);
}
$medicaments = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Médicaments - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Médicaments</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par nom ou référence..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <a href="/medicaments/ajouter.php" class="btn btn-primary">+ Ajouter un médicament</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Réf.</th>
                                <th>Nom</th>
                                <th>Dosage</th>
                                <th>Catégorie</th>
                                <th>Seuil alerte</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($medicaments)): ?>
                                <tr><td colspan="7">Aucun médicament trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($medicaments as $m): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($m['reference']) ?></td>
                                        <td><?= htmlspecialchars($m['nom']) ?></td>
                                        <td><?= htmlspecialchars($m['dosage'] ?? '—') ?></td>
                                        <td><?= htmlspecialchars($m['categorie_libelle']) ?></td>
                                        <td><?= (int)$m['seuil_alerte'] ?></td>
                                        <td>
                                            <?php if ($m['statut'] === 'actif'): ?>
                                                <span class="badge badge-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="/medicaments/modifier.php?id=<?= $m['id_medicament'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <?php if ($m['statut'] === 'actif'): ?>
                                                <button type="button" class="btn-danger-text"
                                                    onclick="confirmerSuppression('/medicaments/desactiver.php?id=<?= $m['id_medicament'] ?>&action=desactiver', '<?= htmlspecialchars($m['nom'], ENT_QUOTES) ?>')">
                                                    Désactiver
                                                </button>
                                            <?php else: ?>
                                                <a href="/medicaments/desactiver.php?id=<?= $m['id_medicament'] ?>&action=activer" class="btn-danger-text" style="color:var(--color-success);">
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