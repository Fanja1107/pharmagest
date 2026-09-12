<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM clients WHERE id_client = ?');
$stmt->execute([$id]);
$client = $stmt->fetch();

if (!$client) {
    $_SESSION['flash_error'] = 'Client introuvable.';
    header('Location: /clients/index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $telephone = trim($_POST['telephone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $adresse = trim($_POST['adresse'] ?? '');

    if ($nom === '') {
        $erreur = 'Le nom est obligatoire.';
    } else {
        try {
            $stmt = $pdo->prepare(
                'UPDATE clients SET nom = ?, prenom = ?, telephone = ?, email = ?, adresse = ? WHERE id_client = ?'
            );
            $stmt->execute([$nom, $prenom ?: null, $telephone ?: null, $email ?: null, $adresse ?: null, $id]);

            $_SESSION['flash_success'] = 'Client modifié avec succès.';
            header('Location: /clients/index.php');
            exit;
        } catch (PDOException $e) {
            if (str_contains($e->getMessage(), 'uq_client_email')) {
                $erreur = 'Cet email est déjà utilisé par un autre client.';
            } elseif (str_contains($e->getMessage(), 'uq_client_telephone')) {
                $erreur = 'Ce numéro de téléphone est déjà utilisé par un autre client.';
            } else {
                $erreur = 'Une erreur est survenue lors de l\'enregistrement.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier un client - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier un client</h1>

                <div class="card form-card">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="form-group">
                            <label for="nom">Nom *</label>
                            <input type="text" id="nom" name="nom" required
                                   value="<?= htmlspecialchars($_POST['nom'] ?? $client['nom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom"
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? $client['prenom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="telephone">Téléphone</label>
                            <input type="text" id="telephone" name="telephone"
                                   value="<?= htmlspecialchars($_POST['telephone'] ?? $client['telephone'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email</label>
                            <input type="email" id="email" name="email"
                                   value="<?= htmlspecialchars($_POST['email'] ?? $client['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="adresse">Adresse</label>
                            <input type="text" id="adresse" name="adresse"
                                   value="<?= htmlspecialchars($_POST['adresse'] ?? $client['adresse'] ?? '') ?>">
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/clients/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>