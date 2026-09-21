<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nouveau = $_POST['nouveau'] ?? '';
    $confirmation = $_POST['confirmation'] ?? '';

    if ($nouveau === '' || $confirmation === '') {
        $erreur = 'Veuillez remplir les deux champs.';
    } elseif (strlen($nouveau) < 6) {
        $erreur = 'Le mot de passe doit contenir au moins 6 caractères.';
    } elseif ($nouveau !== $confirmation) {
        $erreur = 'Les deux mots de passe ne correspondent pas.';
    } else {
        $hash = password_hash($nouveau, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 0 WHERE id_utilisateur = ?');
        $stmt->execute([$hash, $_SESSION['id_utilisateur']]);

        $_SESSION['doit_changer_mdp'] = false;
        $_SESSION['flash_success'] = 'Mot de passe changé avec succès.';
        header('Location: /dashboard/index.php');
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Changement de mot de passe requis - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .login-wrapper {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: var(--color-bg);
        }
        .login-box { width: 100%; max-width: 400px; padding: 32px; }
        .login-box h1 { font-size: 1.2rem; margin-bottom: 8px; text-align: center; }
        .login-box p.subtitle { text-align: center; color: var(--color-text-muted); font-size: 0.88rem; margin-bottom: 24px; }
        .form-group { margin-bottom: 16px; }
        .form-group label { display: block; margin-bottom: 6px; font-size: 0.9rem; font-weight: 600; }
        .form-group input {
            width: 100%; padding: 10px 12px; border: 1px solid var(--color-border);
            border-radius: var(--radius); font-size: 0.95rem;
        }
        .btn-primary {
            width: 100%; padding: 11px; background: var(--color-primary); color: #fff;
            border: none; border-radius: var(--radius); font-size: 0.95rem; font-weight: 600; cursor: pointer;
        }
        .btn-primary:hover { background: var(--color-primary-dark); }
        .alert-error { background: #fee2e2; color: var(--color-danger); padding: 10px 12px; border-radius: var(--radius); font-size: 0.88rem; margin-bottom: 16px; }
        .lien-deconnexion { display: block; text-align: center; margin-top: 16px; font-size: 0.85rem; color: var(--color-text-muted); }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-box">
            <h1>🔒 Changement de mot de passe requis</h1>
            <p class="subtitle">Pour des raisons de sécurité, vous devez définir un nouveau mot de passe avant de continuer.</p>

            <?php if ($erreur): ?>
                <div class="alert-error"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="nouveau">Nouveau mot de passe (min. 6 caractères)</label>
                    <input type="password" id="nouveau" name="nouveau" required minlength="6" autocomplete="new-password">
                </div>
                <div class="form-group">
                    <label for="confirmation">Confirmer le nouveau mot de passe</label>
                    <input type="password" id="confirmation" name="confirmation" required minlength="6" autocomplete="new-password">
                </div>
                <button type="submit" class="btn-primary">Valider</button>
            </form>

            <a href="/auth/logout.php" class="lien-deconnexion">Se déconnecter</a>
        </div>
    </div>
</body>
</html>