<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'desactiver';

$nouveauStatut = ($action === 'activer') ? 'actif' : 'inactif';

$stmt = $pdo->prepare('UPDATE conditionnements SET statut = ? WHERE id_conditionnement = ?');
$stmt->execute([$nouveauStatut, $id]);

$_SESSION['flash_success'] = ($nouveauStatut === 'actif')
    ? 'Conditionnement réactivé avec succès.'
    : 'Conditionnement désactivé avec succès.';

header('Location: /conditionnements/index.php');
exit;