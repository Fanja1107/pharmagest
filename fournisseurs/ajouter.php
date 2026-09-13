<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $raisonSociale = trim($_POST['raison_sociale'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');
    $nif = trim($_POST['nif'] ?? '');
    $stat = trim($_POST['stat'] ?? '');

    if ($raisonSociale === '') {
        $erreur = 'La raison sociale est obligatoire.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO fournisseurs (raison_sociale, telephone, email, adresse, nif, stat)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([
            $raisonSociale,
            $telephone ?: null,
            $email ?: null,
            $adresse ?: null,
            $nif ?: null,
            $stat ?: null,
        ]);

        $_SESSION['flash_success'] = 'Fournisseur ajouté avec succès.';
        header('Location: /fournisseurs/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un fournisseur - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Ajouter un fournisseur</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="raison_sociale">Raison sociale *</label>
                            <input type="text" id="raison_sociale" name="raison_sociale" required
                                   value="<?= htmlspecialchars($_POST['raison_sociale'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="text" id="telephone" name="telephone"
                                   value="<?= htmlspecialchars($_POST['telephone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="adresse">Adresse</label>
                            <input type="text" id="adresse" name="adresse"
                                   value="<?= htmlspecialchars($_POST['adresse'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="nif">NIF</label>
                            <input type="text" id="nif" name="nif"
                                   value="<?= htmlspecialchars($_POST['nif'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="stat">STAT</label>
                            <input type="text" id="stat" name="stat"
                                   value="<?= htmlspecialchars($_POST['stat'] ?? '') ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/fournisseurs/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>