<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);
$action = $_GET['action'] ?? 'desactiver';

$nouveauStatut = ($action === 'activer') ? 'actif' : 'inactif';

$stmt = $pdo->prepare('UPDATE medicaments SET statut = ? WHERE id_medicament = ?');
$stmt->execute([$nouveauStatut, $id]);

$_SESSION['flash_success'] = ($nouveauStatut === 'actif')
    ? 'Médicament réactivé avec succès.'
    : 'Médicament désactivé avec succès.';

header('Location: /medicaments/index.php');
exit;