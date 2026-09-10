<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

try {
    $stmt = $pdo->prepare('DELETE FROM categories WHERE id_categorie = ?');
    $stmt->execute([$id]);
    $_SESSION['flash_success'] = 'Catégorie supprimée avec succès.';
} catch (PDOException $e) {
    // Si la catégorie est utilisée par des médicaments, la contrainte de clé étrangère empêchera la suppression
    $_SESSION['flash_error'] = 'Impossible de supprimer cette catégorie : elle est utilisée par des médicaments.';
}

header('Location: /categories/index.php');
exit;