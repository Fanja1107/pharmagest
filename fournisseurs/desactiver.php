<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'desactiver';

$nouveauStatut = ($action === 'activer') ? 'actif' : 'inactif';

$stmt = $pdo->prepare('UPDATE fournisseurs SET statut = ? WHERE id_fournisseur = ?');
$stmt->execute([$nouveauStatut, $id]);

$_SESSION['flash_success'] = ($nouveauStatut === 'actif')
    ? 'Fournisseur réactivé avec succès.'
    : 'Fournisseur désactivé avec succès.';

header('Location: /fournisseurs/index.php');
exit;