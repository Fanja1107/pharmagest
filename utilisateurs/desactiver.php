<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'desactiver';

if ($id == $_SESSION['id_utilisateur']) {
    $_SESSION['flash_error'] = 'Vous ne pouvez pas désactiver votre propre compte.';
    header('Location: /utilisateurs/index.php');
    exit;
}

$nouveauStatut = ($action === 'activer') ? 'actif' : 'inactif';

$stmt = $pdo->prepare('UPDATE utilisateurs SET statut = ? WHERE id_utilisateur = ?');
$stmt->execute([$nouveauStatut, $id]);

$_SESSION['flash_success'] = ($nouveauStatut === 'actif')
    ? 'Utilisateur réactivé avec succès.'
    : 'Utilisateur désactivé avec succès.';

header('Location: /utilisateurs/index.php');
exit;