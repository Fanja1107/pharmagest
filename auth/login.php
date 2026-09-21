<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';

// Si déjà connecté, on redirige directement vers le dashboard
if (estConnecte()) {
    header('Location: /dashboard/index.php');
    exit;
}

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $motDePasse = $_POST['mot_de_passe'] ?? '';

    if ($email === '' || $motDePasse === '') {
        $erreur = 'Veuillez remplir tous les champs.';
    } else {
        $stmt = $pdo->prepare('SELECT * FROM utilisateurs WHERE email = ? AND statut = "actif"');
        $stmt->execute([$email]);
        $utilisateur = $stmt->fetch();

            if ($utilisateur && password_verify($motDePasse, $utilisateur['mot_de_passe'])) {
            // Connexion réussie : on régénère l'ID de session (sécurité)
            session_regenerate_id(true);

            $_SESSION['id_utilisateur']  = $utilisateur['id_utilisateur'];
            $_SESSION['nom']             = $utilisateur['nom'];
            $_SESSION['prenom']          = $utilisateur['prenom'];
            $_SESSION['role']            = $utilisateur['role'];
            $_SESSION['doit_changer_mdp'] = (bool)$utilisateur['doit_changer_mdp'];

            // Vérifie les lots proches de l'expiration à l'instant précis de la connexion
            require_once __DIR__ . '/../includes/functions.php';
            mettreAJourStatutsLots($pdo);
            $lotsProches = getLotsExpirationProche($pdo, 30);

            if (!empty($lotsProches)) {
                $lignes = array_map(
                    fn($l) => '• ' . htmlspecialchars($l['medicament_nom']) . ' (lot ' . htmlspecialchars($l['numero_lot']) . ') — J-' . (int)$l['jours_restants'],
                    array_slice($lotsProches, 0, 5)
                );
                $message = 'Des lots arrivent bientôt à expiration :<br><br>' . implode('<br>', $lignes);
                if (count($lotsProches) > 5) {
                    $message .= '<br><br>… et ' . (count($lotsProches) - 5) . ' autre(s).';
                }
                $_SESSION['flash_warning'] = $message;
            }

            header('Location: /dashboard/index.php');
            exit;
        } else {
            $erreur = 'Email ou mot de passe incorrect.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - PharmaGest</title>
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
        .login-box {
            width: 100%;
            max-width: 360px;
            padding: 32px;
        }
        .login-box h1 {
            font-size: 1.3rem;
            margin-bottom: 24px;
            text-align: center;
        }
        .form-group {
            margin-bottom: 16px;
        }
        .form-group label {
            display: block;
            margin-bottom: 6px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .form-group input {
            width: 100%;
            padding: 10px 12px;
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            font-size: 0.95rem;
        }
        .btn-primary {
            width: 100%;
            padding: 11px;
            background: var(--color-primary);
            color: #fff;
            border: none;
            border-radius: var(--radius);
            font-size: 0.95rem;
            font-weight: 600;
            cursor: pointer;
        }
        .btn-primary:hover {
            background: var(--color-primary-dark);
        }
        .alert-error {
            background: #fee2e2;
            color: var(--color-danger);
            padding: 10px 12px;
            border-radius: var(--radius);
            font-size: 0.88rem;
            margin-bottom: 16px;
        }
    </style>
</head>
<body>
    <div class="login-wrapper">
        <div class="card login-box">
            <h1>💊 PharmaGest — Connexion</h1>

            <?php if ($erreur): ?>
                <div class="alert-error"><?= htmlspecialchars($erreur) ?></div>
            <?php endif; ?>

            <form method="POST" novalidate>
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="mot_de_passe">Mot de passe</label>
                    <input type="password" id="mot_de_passe" name="mot_de_passe" required>
                </div>
                <button type="submit" class="btn-primary">Se connecter</button>
            </form>
        </div>
    </div>
</body>
</html>