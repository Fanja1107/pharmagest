<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nomUnite = trim($_POST['nom_unite'] ?? '');
    $symbole = trim($_POST['symbole'] ?? '');

    if ($nomUnite === '' || $symbole === '') {
        $erreur = 'Le nom et le symbole sont obligatoires.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO unites (nom_unite, symbole) VALUES (?, ?)');
        $stmt->execute([$nomUnite, $symbole]);

        $_SESSION['flash_success'] = 'Unité ajoutée avec succès.';
        header('Location: /unites/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter une unité - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Ajouter une unité</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="nom_unite">Nom de l'unité *</label>
                            <input type="text" id="nom_unite" name="nom_unite" required
                                   placeholder="Ex. Comprimé"
                                   value="<?= htmlspecialchars($_POST['nom_unite'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="symbole">Symbole *</label>
                            <input type="text" id="symbole" name="symbole" required
                                   placeholder="Ex. cp"
                                   value="<?= htmlspecialchars($_POST['symbole'] ?? '') ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/unites/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>