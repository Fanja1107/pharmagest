<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE id_utilisateur = ?');
$stmt->execute([$id]);
$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    $_SESSION['flash_error'] = 'Utilisateur introuvable.';
    header('Location: /utilisateurs/index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nom = trim($_POST['nom'] ?? '');
    $prenom = trim($_POST['prenom'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $role = $_POST['role'] ?? 'vendeur';
    $nouveauMotDePasse = $_POST['mot_de_passe'] ?? '';

    $rolesValides = ['admin', 'pharmacien', 'vendeur'];

    require_once __DIR__ . '/../includes/functions.php';
    $idAdminPrincipal = getIdAdminPrincipal($pdo);

    if ($nom === '' || $email === '') {
        $erreur = 'Le nom et l\'email sont obligatoires.';
    } elseif (!in_array($role, $rolesValides, true)) {
        $erreur = 'Rôle invalide.';
    } elseif ($nouveauMotDePasse !== '' && strlen($nouveauMotDePasse) < 6) {
        $erreur = 'Le nouveau mot de passe doit contenir au moins 6 caractères.';
    } elseif ($id == $_SESSION['id_utilisateur'] && $role !== 'admin') {
        $erreur = 'Vous ne pouvez pas retirer votre propre rôle admin.';
    } elseif ($id === $idAdminPrincipal && $role !== 'admin' && $id != $_SESSION['id_utilisateur']) {
        $erreur = 'Ce compte est le propriétaire du système : son rôle ne peut être modifié que par lui-même.';
    } else {
        try {
            if ($nouveauMotDePasse !== '') {
                $hash = password_hash($nouveauMotDePasse, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare(
                    'UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ?, mot_de_passe = ?, doit_changer_mdp = 1 WHERE id_utilisateur = ?'
                );
                $stmt->execute([$nom, $prenom ?: null, $email, $role, $hash, $id]);
            } else {
                $stmt = $pdo->prepare(
                    'UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, role = ? WHERE id_utilisateur = ?'
                );
                $stmt->execute([$nom, $prenom ?: null, $email, $role, $id]);
            }

            $_SESSION['flash_success'] = 'Utilisateur modifié avec succès.';
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
    <title>Modifier un utilisateur - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier un utilisateur</h1>

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
                                   value="<?= htmlspecialchars($_POST['nom'] ?? $utilisateur['nom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="prenom">Prénom</label>
                            <input type="text" id="prenom" name="prenom"
                                   value="<?= htmlspecialchars($_POST['prenom'] ?? $utilisateur['prenom'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="email">Email *</label>
                            <input type="email" id="email" name="email" required
                                   value="<?= htmlspecialchars($_POST['email'] ?? $utilisateur['email'] ?? '') ?>">
                        </div>
                        <div class="form-group">
                            <label for="role">Rôle *</label>
                            <select id="role" name="role" required <?= $id == $_SESSION['id_utilisateur'] ? 'disabled' : '' ?>>
                                <option value="vendeur" <?= ($_POST['role'] ?? $utilisateur['role']) === 'vendeur' ? 'selected' : '' ?>>Vendeur</option>
                                <option value="pharmacien" <?= ($_POST['role'] ?? $utilisateur['role']) === 'pharmacien' ? 'selected' : '' ?>>Pharmacien</option>
                                <option value="admin" <?= ($_POST['role'] ?? $utilisateur['role']) === 'admin' ? 'selected' : '' ?>>Admin</option>
                            </select>
                            <?php if ($id == $_SESSION['id_utilisateur']): ?>
                                <input type="hidden" name="role" value="admin">
                                <small style="color:var(--color-text-muted);">Vous ne pouvez pas changer votre propre rôle.</small>
                            <?php endif; ?>
                        </div>
                        <div class="form-group">
                            <label for="mot_de_passe">Nouveau mot de passe (laisser vide pour ne pas changer)</label>
                            <input type="password" id="mot_de_passe" name="mot_de_passe" minlength="6">
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