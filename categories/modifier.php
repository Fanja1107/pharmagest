<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM categories WHERE id_categorie = ?');
$stmt->execute([$id]);
$categorie = $stmt->fetch();

if (!$categorie) {
    $_SESSION['flash_error'] = 'Catégorie introuvable.';
    header('Location: /categories/index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = trim($_POST['libelle'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($libelle === '') {
        $erreur = 'Le libellé est obligatoire.';
    } else {
        $stmt = $pdo->prepare('UPDATE categories SET libelle = ?, description = ? WHERE id_categorie = ?');
        $stmt->execute([$libelle, $description ?: null, $id]);

        $_SESSION['flash_success'] = 'Catégorie modifiée avec succès.';
        header('Location: /categories/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier une catégorie - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier une catégorie</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="libelle">Libellé *</label>
                            <input type="text" id="libelle" name="libelle" required
                                   value="<?= htmlspecialchars($_POST['libelle'] ?? $categorie['libelle']) ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? $categorie['description']) ?></textarea>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/categories/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>