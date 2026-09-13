<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$medicaments = $pdo->query("SELECT * FROM medicaments WHERE statut = 'actif' ORDER BY nom")->fetchAll();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idMedicament = (int)($_POST['id_medicament'] ?? 0);
    $numeroLot = trim($_POST['numero_lot'] ?? '');
    $dateFabrication = trim($_POST['date_fabrication'] ?? '');
    $dateExpiration = trim($_POST['date_expiration'] ?? '');
    $quantiteBase = (int)($_POST['quantite_base'] ?? 0);
    $prixAchatBase = (float)($_POST['prix_achat_base'] ?? 0);

    if ($idMedicament === 0 || $numeroLot === '' || $dateExpiration === '' || $quantiteBase <= 0 || $prixAchatBase <= 0) {
        $erreur = 'Médicament, numéro de lot, date d\'expiration, quantité et prix sont obligatoires (quantité et prix > 0).';
    } elseif (strtotime($dateExpiration) < strtotime(date('Y-m-d'))) {
        $erreur = 'La date d\'expiration ne peut pas être dans le passé.';
    } else {
        $stmt = $pdo->prepare(
            'INSERT INTO lots (id_medicament, numero_lot, date_fabrication, date_expiration, quantite_base, prix_achat_base)
             VALUES (?, ?, ?, ?, ?, ?)'
        );
        $stmt->execute([$idMedicament, $numeroLot, $dateFabrication ?: null, $dateExpiration, $quantiteBase, $prixAchatBase]);

        $_SESSION['flash_success'] = 'Lot ajouté avec succès.';
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
    <title>Ajouter un lot - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Ajouter un lot</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($medicaments)): ?>
                        <p>⚠️ Aucun médicament actif disponible. Ajoutez-en un avant de créer un lot.</p>
                    <?php else: ?>
                    <form method="POST">
                        <div class="form-group">
                            <label for="id_medicament">Médicament *</label>
                            <select id="id_medicament" name="id_medicament" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($medicaments as $m): ?>
                                    <option value="<?= $m['id_medicament'] ?>"
                                        <?= (($_POST['id_medicament'] ?? '') == $m['id_medicament']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($m['nom']) ?> <?= $m['dosage'] ? '(' . htmlspecialchars($m['dosage']) . ')' : '' ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="numero_lot">Numéro de lot *</label>
                            <input type="text" id="numero_lot" name="numero_lot" required placeholder="Ex. PARA-2609-A"
                                   value="<?= htmlspecialchars($_POST['numero_lot'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_fabrication">Date de fabrication</label>
                            <input type="date" id="date_fabrication" name="date_fabrication"
                                   value="<?= htmlspecialchars($_POST['date_fabrication'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="date_expiration">Date d'expiration *</label>
                            <input type="date" id="date_expiration" name="date_expiration" required
                                   value="<?= htmlspecialchars($_POST['date_expiration'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="quantite_base">Quantité (unités de base) *</label>
                            <input type="number" id="quantite_base" name="quantite_base" min="1" required
                                   value="<?= htmlspecialchars($_POST['quantite_base'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="prix_achat_base">Prix d'achat (par unité de base, Ar) *</label>
                            <input type="number" id="prix_achat_base" name="prix_achat_base" min="0" step="0.01" required
                                   value="<?= htmlspecialchars($_POST['prix_achat_base'] ?? '') ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/lots/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>