<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$idAdminPrincipal = getIdAdminPrincipal($pdo);

$recherche = trim($_GET['q'] ?? '');

if ($recherche !== '') {
    $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE nom LIKE ? OR prenom LIKE ? OR email LIKE ? ORDER BY nom');
    $stmt->execute(['%' . $recherche . '%', '%' . $recherche . '%', '%' . $recherche . '%']);
} else {
    $stmt = $pdo->query('SELECT * FROM utilisateurs ORDER BY nom');
}
$utilisateurs = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilisateurs - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Utilisateurs</h1>

                <div class="table-toolbar">
                    <form method="GET" id="formRecherche">
                        <input type="text" name="q" id="champRecherche" placeholder="Rechercher par nom ou email..."
                               value="<?= htmlspecialchars($recherche) ?>" autocomplete="off">
                    </form>
                    <a href="/utilisateurs/ajouter.php" class="btn btn-primary">+ Ajouter un utilisateur</a>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Nom</th>
                                <th>Prénom</th>
                                <th>Email</th>
                                <th>Rôle</th>
                                <th>Statut</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($utilisateurs)): ?>
                                <tr><td colspan="6">Aucun utilisateur trouvé.</td></tr>
                            <?php else: ?>
                                <?php foreach ($utilisateurs as $u): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($u['nom']) ?></td>
                                        <td><?= htmlspecialchars($u['prenom']) ?></td>
                                        <td><?= htmlspecialchars($u['email']) ?></td>
                                        <td><?= htmlspecialchars(ucfirst($u['role'])) ?></td>
                                        <td>
                                            <?php if ($u['statut'] === 'actif'): ?>
                                                <span class="badge badge-success">Actif</span>
                                            <?php else: ?>
                                                <span class="badge badge-danger">Inactif</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="actions-cell">
                                            <a href="/utilisateurs/modifier.php?id=<?= $u['id_utilisateur'] ?>" class="btn btn-outline btn-sm">Modifier</a>
                                            <?php if ($u['id_utilisateur'] == $_SESSION['id_utilisateur']): ?>
                                                <span style="color:var(--color-text-muted); font-size:0.8rem;">(vous)</span>
                                            <?php elseif ($u['id_utilisateur'] === $idAdminPrincipal): ?>
                                                <span style="color:var(--color-text-muted); font-size:0.8rem;" title="Ce compte est le propriétaire du système, protégé contre la désactivation par un autre admin.">🔒 Protégé</span>
                                            <?php else: ?>
                                                <button type="button" class="btn-outline btn-sm"
                                                    onclick="confirmerAction('/utilisateurs/reinitialiser-mdp.php?id=<?= $u['id_utilisateur'] ?>', 'Réinitialiser le mot de passe ?', 'Un nouveau mot de passe temporaire sera généré et affiché une seule fois. L\'utilisateur devra le changer à sa prochaine connexion.')">
                                                    Réinitialiser MDP
                                                </button>
                                                <?php if ($u['statut'] === 'actif'): ?>
                                                    <button type="button" class="btn-danger-text"
                                                        onclick="confirmerSuppression('/utilisateurs/desactiver.php?id=<?= $u['id_utilisateur'] ?>&action=desactiver', '<?= htmlspecialchars($u['nom'], ENT_QUOTES) ?>')">
                                                        Désactiver
                                                    </button>
                                                <?php else: ?>
                                                    <a href="/utilisateurs/desactiver.php?id=<?= $u['id_utilisateur'] ?>&action=activer" class="btn-danger-text" style="color:var(--color-success);">
                                                        Réactiver
                                                    </a>
                                                <?php endif; ?>
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