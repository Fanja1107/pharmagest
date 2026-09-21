<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);

if ($id === (int)$_SESSION['id_utilisateur']) {
    $_SESSION['flash_error'] = 'Utilisez "Mon compte" pour changer votre propre mot de passe.';
    header('Location: /utilisateurs/index.php');
    exit;
}

$idAdminPrincipal = getIdAdminPrincipal($pdo);
if ($id === $idAdminPrincipal) {
    $_SESSION['flash_error'] = 'Ce compte est le propriétaire du système : son mot de passe ne peut être réinitialisé que par lui-même.';
    header('Location: /utilisateurs/index.php');
    exit;
}

$stmt = $pdo->prepare('SELECT nom, prenom FROM utilisateurs WHERE id_utilisateur = ?');
$stmt->execute([$id]);
$utilisateur = $stmt->fetch();

if (!$utilisateur) {
    $_SESSION['flash_error'] = 'Utilisateur introuvable.';
    header('Location: /utilisateurs/index.php');
    exit;
}

$mdpTemporaire = genererMotDePasseTemporaire();
$hash = password_hash($mdpTemporaire, PASSWORD_DEFAULT);

$stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 1 WHERE id_utilisateur = ?');
$stmt->execute([$hash, $id]);

$_SESSION['flash_success'] =
    'Mot de passe réinitialisé pour ' . htmlspecialchars($utilisateur['prenom'] . ' ' . $utilisateur['nom']) . '. ' .
    'Mot de passe temporaire : ' . $mdpTemporaire . ' — communiquez-le à l\'utilisateur, il devra le changer à sa prochaine connexion.';

header('Location: /utilisateurs/index.php');
exit;