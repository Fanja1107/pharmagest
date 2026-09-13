<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT statut FROM approvisionnements WHERE id_approvisionnement = ?');
$stmt->execute([$id]);
$statutActuel = $stmt->fetchColumn();

if ($statutActuel !== 'brouillon') {
    $_SESSION['flash_error'] = 'Seul un brouillon peut être annulé.';
} else {
    $stmt = $pdo->prepare("UPDATE approvisionnements SET statut = 'annule' WHERE id_approvisionnement = ?");
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = 'Approvisionnement annulé avec succès.';
}

header('Location: /approvisionnements/index.php');
exit;