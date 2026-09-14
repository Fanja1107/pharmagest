<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM lots WHERE id_lot = ?');
$stmt->execute([$id]);
$lot = $stmt->fetch();

if (!$lot) {
    $_SESSION['flash_error'] = 'Lot introuvable.';
    header('Location: /lots/index.php');
    exit;
}

$medicaments = $pdo->query("SELECT * FROM medicaments WHERE statut = 'actif' ORDER BY nom")->fetchAll();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMedicament = (int)($_POST['id_medicament'] ?? 0);
    $numeroLot = trim($_POST['numero_lot'] ?? '');
    $dateFabrication = trim($_POST['date_fabrication'] ?? '');
    $dateExpiration = trim($_POST['date_expiration'] ?? '');
    $quantiteBase = (int)($_POST['quantite_base'] ?? 0);
    $prixAchatBase = (float)($_POST['prix_achat_base'] ?? 0);

    if ($idMedicament === 0 || $numeroLot === '' || $dateExpiration === '' || $quantiteBase < 0 || $prixAchatBase <= 0) {
        $erreur = 'Médicament, numéro de lot, date d\'expiration et prix sont obligatoires.';
    } elseif ($dateFabrication !== '' && strtotime($dateFabrication) > strtotime(date('Y-m-d'))) {
        $erreur = 'La date de fabrication ne peut pas être dans le futur.';
    } elseif ($dateFabrication !== '' && strtotime($dateFabrication) > strtotime($dateExpiration)) {
        $erreur = 'La date de fabrication ne peut pas être postérieure à la date d\'expiration.';
    } else {
        $stmt = $pdo->prepare(
            'UPDATE lots
             SET id_medicament = ?, numero_lot = ?, date_fabrication = ?, date_expiration = ?, quantite_base = ?, prix_achat_base = ?
             WHERE id_lot = ?'
        );
        $stmt->execute([$idMedicament, $numeroLot, $dateFabrication ?: null, $dateExpiration, $quantiteBase, $prixAchatBase, $id]);

        $_SESSION['flash_success'] = 'Lot modifié avec succès.';
        header('Location: /lots/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un lot - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier un lot</h1>

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
                                        <?= (($_POST['id_medicament'] ?? $lot['id_medicament']) == $m['id_medicament']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nom']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="numero_lot">Numéro de lot *</label>
                            <input type="text" id="numero_lot" name="numero_lot" required
                                   value="<?= htmlspecialchars($_POST['numero_lot'] ?? $lot['numero_lot'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_fabrication">Date de fabrication</label>
                            <input type="date" id="date_fabrication" name="date_fabrication"
                                   value="<?= htmlspecialchars($_POST['date_fabrication'] ?? $lot['date_fabrication'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_expiration">Date d'expiration *</label>
                            <input type="date" id="date_expiration" name="date_expiration" required
                                   value="<?= htmlspecialchars($_POST['date_expiration'] ?? $lot['date_expiration'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="quantite_base">Quantité (unités de base) *</label>
                            <input type="number" id="quantite_base" name="quantite_base" min="0" required
                                   value="<?= htmlspecialchars($_POST['quantite_base'] ?? $lot['quantite_base'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="prix_achat_base">Prix d'achat (par unité de base, Ar) *</label>
                            <input type="number" id="prix_achat_base" name="prix_achat_base" min="0" step="0.01" required
                                   value="<?= htmlspecialchars($_POST['prix_achat_base'] ?? $lot['prix_achat_base'] ?? '') ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/lots/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>