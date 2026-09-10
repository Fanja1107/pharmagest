<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $libelle = trim($_POST['libelle'] ?? '');
    $description = trim($_POST['description'] ?? '');

    if ($libelle === '') {
        $erreur = 'Le libellé est obligatoire.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO categories (libelle, description) VALUES (?, ?)');
        $stmt->execute([$libelle, $description ?: null]);

        $_SESSION['flash_success'] = 'Catégorie ajoutée avec succès.';
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
    <title>Ajouter une catégorie - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Ajouter une catégorie</h1>

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
                                   value="<?= htmlspecialchars($_POST['libelle'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="description">Description</label>
                            <textarea id="description" name="description" rows="3"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
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