<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$peutGerer = in_array($_SESSION['role'], ['admin', 'pharmacien'], true);

$recherche = trim($_GET['q'] ?? '');

$sql = "SELECT c.*, m.nom AS medicament_nom, u.symbole AS unite_symbole,
        (SELECT AVG(l.prix_achat_base) FROM lots l WHERE l.id_medicament = c.id_medicament AND l.statut = 'actif') AS prix_achat_moyen
        FROM conditionnements c
        JOIN medicaments m ON m.id_medicament = c.id_medicament
        JOIN unites u ON u.id_unite = c.id_unite";

if ($recherche !== '') {
    $sql .= ' WHERE m.nom LIKE ? OR c.libelle LIKE ?';
    $sql .= ' ORDER BY m.nom, c.quantite_base';
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['%' . $recherche . '%', '%' . $recherche . '%']);
} else {
    $sql .= ' ORDER BY m.nom, c.quantite_base';
    $stmt = $pdo->query($sql);
}
$conditionnements = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Conditionnements - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Conditionnements</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par médicament ou libellé..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <?php if ($peutGerer): ?>
                        <a href="/conditionnements/ajouter.php" class="btn btn-primary">+ Ajouter un conditionnement</a>
                    <?php endif; ?>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Libellé</th>
                                <th>Unité</th>
                                <th>Qté de base</th>
                                <th>Prix de vente</th>
                                <th>Coût d'achat estimé</th>
                                <th>Marge estimée</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($conditionnements)): ?>
                                <tr><td colspan="9">Aucun conditionnement trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($conditionnements as $c): ?>
                                    <?php
                                        $coutAchat = null;
                                        $marge = null;
                                        $margePourcent = null;

                                        if ($c['prix_achat_moyen'] !== null) {
                                            $coutAchat = $c['prix_achat_moyen'] * $c['quantite_base'];
                                            $marge = $c['prix_vente'] - $coutAchat;
                                            $margePourcent = $coutAchat > 0 ? ($marge / $coutAchat) * 100 : null;
                                        }
                                    ?>
                                    <tr>
                                        <td><?= htmlspecialchars($c['medicament_nom']) ?></td>
                                        <td><?= htmlspecialchars($c['libelle']) ?></td>
                                        <td><?= htmlspecialchars($c['unite_symbole']) ?></td>
                                        <td><?= (int)$c['quantite_base'] ?></td>
                                        <td><?= number_format($c['prix_vente'], 0, ',', ' ') ?> Ar</td>
                                        <td>
                                            <?php if ($coutAchat !== null): ?>
                                                <?= number_format($coutAchat, 0, ',', ' ') ?> Ar
                                            <?php else: ?>
                                                <span style="color:var(--color-text-muted);">— (aucun lot)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($marge !== null): ?>
                                                <?php
                                                    $classeMarge = $marge > 0 ? 'badge-success' : ($marge === 0 ? 'badge-warning' : 'badge-danger');
                                                ?>
                                                <span class="badge <?= $classeMarge ?>">
                                                    <?= number_format($marge, 0, ',', ' ') ?> Ar
                                                    <?php if ($margePourcent !== null): ?>
                                                        (<?= number_format($margePourcent, 0) ?>%)
                                                    <?php endif; ?>
                                                </span>
                                            <?php else: ?>
                                                <span style="color:var(--color-text-muted);">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <?php if ($c['statut'] === 'actif'): ?>
                                                <span class="badge badge-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <?php if ($peutGerer): ?>
                                                <a href="/conditionnements/modifier.php?id=<?= $c['id_conditionnement'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                                <?php if ($c['statut'] === 'actif'): ?>
                                                    <button type="button" class="btn-danger-text"
                                                        onclick="confirmerSuppression('/conditionnements/desactiver.php?id=<?= $c['id_conditionnement'] ?>&action=desactiver', '<?= htmlspecialchars($c['libelle'], ENT_QUOTES) ?>')">
                                                        Désactiver
                                                    </button>
                                                <?php else: ?>
                                                    <a href="/conditionnements/desactiver.php?id=<?= $c['id_conditionnement'] ?>&action=activer" class="btn-danger-text" style="color:var(--color-success);">
                                                        Réactiver
                                                    </a>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <span style="color:var(--color-text-muted); font-size:0.8rem;">Lecture seule</span>
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