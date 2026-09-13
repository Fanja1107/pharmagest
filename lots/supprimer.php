<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM lots WHERE id_lot = ?');
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = 'Lot supprimé avec succès.';
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Impossible de supprimer ce lot : il est lié à un approvisionnement ou une vente.';
}

header('Location: /lots/index.php');
exit;