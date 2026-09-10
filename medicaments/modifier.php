<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM medicaments WHERE id_medicament = ?');
$stmt->execute([$id]);
$medicament = $stmt->fetch();

if (!$medicament) {
    $_SESSION['flash_error'] = 'Médicament introuvable.';
    header('Location: /medicaments/index.php');
    exit;
}

$categories = $pdo->query('SELECT * FROM categories ORDER BY libelle')->fetchAll();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idCategorie = (int)($_POST['id_categorie'] ?? 0);
    $reference = trim($_POST['reference'] ?? '');
    $nom = trim($_POST['nom'] ?? '');
    $dosage = trim($_POST['dosage'] ?? '');
    $forme = trim($_POST['forme'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $seuilAlerte = (int)($_POST['seuil_alerte'] ?? 10);

    if ($idCategorie === 0 || $reference === '' || $nom === '') {
        $erreur = 'La catégorie, la référence et le nom sont obligatoires.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE medicaments
                 SET id_categorie = ?, reference = ?, nom = ?, dosage = ?, forme = ?, description = ?, seuil_alerte = ?
                 WHERE id_medicament = ?'
            );
            $stmt->execute([$idCategorie, $reference, $nom, $dosage ?: null, $forme ?: null, $description ?: null, $seuilAlerte, $id]);

            $_SESSION['flash_success'] = 'Médicament modifié avec succès.';
            header('Location: /medicaments/index.php');
            exit;
        } catch (PDOException $e) {
            $erreur = 'Cette référence existe déjà. Choisissez-en une autre.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un médicament - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier un médicament</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="id_categorie">Catégorie *</label>
                            <select id="id_categorie" name="id_categorie" required>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= $cat['id_categorie'] ?>"
                                        <?= (($_POST['id_categorie'] ?? $medicament['id_categorie']) == $cat['id_categorie']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($cat['libelle']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="reference">Référence *</label>
                            <input type="text" id="reference" name="reference" required
                                   value="<?= htmlspecialchars($_POST['reference'] ?? $medicament['reference'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required
                                   value="<?= htmlspecialchars($_POST['nom'] ?? $medicament['nom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="dosage">Dosage</label>
                            <input type="text" id="dosage" name="dosage"
                                   value="<?= htmlspecialchars($_POST['dosage'] ?? $medicament['dosage'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="forme">Forme</label>
                            <input type="text" id="forme" name="forme"
                                   value="<?= htmlspecialchars($_POST['forme'] ?? $medicament['forme'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? $medicament['description'] ?? '') ?></textarea>
                        </div>
                        <div class="form-group">
                            <label for="seuil_alerte">Seuil d'alerte (unités)</label>
                            <input type="number" id="seuil_alerte" name="seuil_alerte" min="0"
                                   value="<?= htmlspecialchars($_POST['seuil_alerte'] ?? $medicament['seuil_alerte'] ?? '10') ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/medicaments/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>