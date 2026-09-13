<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin']);

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';
    $role = $_POST['role'] ?? 'vendeur';

    $rolesValides = ['admin', 'pharmacien', 'vendeur'];

    if ($nom === '' || $email === '' || $motDePasse === '') {
        $erreur = 'Le nom, l\'email et le mot de passe sont obligatoires.';
    } elseif (strlen($motDePasse) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif (!in_array($role, $rolesValides, true)) {
        $erreur = 'Rôle invalide.';
    } else {
        try {
            $hash = password_hash($motDePasse, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare(
                'INSERT INTO utilisateurs (nom, prenom, email, mot_de_passe, role) VALUES (?, ?, ?, ?, ?)'
            );
            $stmt->execute([$nom, $prenom ?: null, $email, $hash, $role]);

            $_SESSION['flash_success'] = 'Utilisateur ajouté avec succès.';
            header('Location: /utilisateurs/index.php');
            exit;
        } catch (PDOException $e) {
            $erreur = 'Cet email est déjà utilisé par un autre utilisateur.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter un utilisateur - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Ajouter un utilisateur</h1>

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
                                   value="<?= htmlspecialchars($_POST['nom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom"
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="mot_de_passe">Mot de passe * (min. 6 caractères)</label>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" required minlength="6">
                        </div>
                        <div class="form-group">
                            <label for="role">Rôle *</label>
                            <select id="role" name="role" required>
                                <option value="vendeur" <?= ($_POST['role'] ?? '') === 'vendeur' ? 'selected' : '' ?>>Vendeur</option>
                                <option value="pharmacien" <?= ($_POST['role'] ?? '') === 'pharmacien' ? 'selected' : '' ?>>Pharmacien</option>
                                <option value="admin" <?= ($_POST['role'] ?? '') === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                        </div>
                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer</button>
                            <a href="/utilisateurs/index.php" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>