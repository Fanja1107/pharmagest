<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM conditionnements WHERE id_conditionnement = ?');
$stmt->execute([$id]);
$conditionnement = $stmt->fetch();

if (!$conditionnement) {
    $_SESSION['flash_error'] = 'Conditionnement introuvable.';
    header('Location: /conditionnements/index.php');
    exit;
}

$medicaments = $pdo->query("SELECT * FROM medicaments WHERE statut = 'actif' ORDER BY nom")->fetchAll();
$unites = $pdo->query('SELECT * FROM unites ORDER BY nom_unite')->fetchAll();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMedicament = (int)($_POST['id_medicament'] ?? 0);
    $idUnite = (int)($_POST['id_unite'] ?? 0);
    $libelle = trim($_POST['libelle'] ?? '');
    $quantiteBase = (int)($_POST['quantite_base'] ?? 0);
    $prixVente = (float)($_POST['prix_vente'] ?? 0);
    $vendable = isset($_POST['vendable']) ? 1 : 0;
    $achetable = isset($_POST['achetable']) ? 1 : 0;

    if ($idMedicament === 0 || $idUnite === 0 || $libelle === '' || $quantiteBase <= 0 || $prixVente <= 0) {
        $erreur = 'Tous les champs sont obligatoires. La quantité de base et le prix doivent être supérieurs à 0.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE conditionnements
             SET id_medicament = ?, id_unite = ?, libelle = ?, quantite_base = ?, prix_vente = ?, vendable = ?, achetable = ?
             WHERE id_conditionnement = ?'
        );
        $stmt->execute([$idMedicament, $idUnite, $libelle, $quantiteBase, $prixVente, $vendable, $achetable, $id]);

        $_SESSION['flash_success'] = 'Conditionnement modifié avec succès.';
        header('Location: /conditionnements/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un conditionnement - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier un conditionnement</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="id_medicament">Médicament *</label>
                            <select id="id_medicament" name="id_medicament" required>
                                <?php foreach ($medicaments as $m): ?>
                                    <option value="<?= $m['id_medicament'] ?>"
                                        <?= (($_POST['id_medicament'] ?? $conditionnement['id_medicament']) == $m['id_medicament']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nom']) ?> <?= $m['dosage'] ? '(' . htmlspecialchars($m['dosage']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="id_unite">Unité *</label>
                            <select id="id_unite" name="id_unite" required>
                                <?php foreach ($unites as $u): ?>
                                    <option value="<?= $u['id_unite'] ?>"
                                        <?= (($_POST['id_unite'] ?? $conditionnement['id_unite']) == $u['id_unite']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($u['nom_unite']) ?> (<?= htmlspecialchars($u['symbole']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="libelle">Libellé *</label>
                            <input type="text" id="libelle" name="libelle" required
                                   value="<?= htmlspecialchars($_POST['libelle'] ?? $conditionnement['libelle'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="quantite_base">Quantité de base *</label>
                            <input type="number" id="quantite_base" name="quantite_base" min="1" required
                                   value="<?= htmlspecialchars($_POST['quantite_base'] ?? $conditionnement['quantite_base'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="prix_vente">Prix de vente (Ar) *</label>
                            <input type="number" id="prix_vente" name="prix_vente" min="0" step="0.01" required
                                   value="<?= htmlspecialchars($_POST['prix_vente'] ?? $conditionnement['prix_vente'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="vendable" style="width:auto;display:inline-block;"
                                    <?= (isset($_POST['vendable']) || (!isset($_POST['libelle']) && $conditionnement['vendable'])) ? 'checked' : '' ?>>
                                Vendable (proposé à la vente)
                            </label>
                        </div>
                        <div class="form-group">
                            <label>
                                <input type="checkbox" name="achetable" style="width:auto;display:inline-block;"
                                    <?= (isset($_POST['achetable']) || (!isset($_POST['libelle']) && $conditionnement['achetable'])) ? 'checked' : '' ?>>
                                Achetable (utilisable lors d'un approvisionnement)
                            </label>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/conditionnements/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>