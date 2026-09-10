<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM unites WHERE id_unite = ?');
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = 'Unité supprimée avec succès.';
} catch (PDOException $e) {
    $_SESSION['flash_error'] = 'Impossible de supprimer cette unité : elle est utilisée par des conditionnements.';
}

header('Location: /unites/index.php');
exit;