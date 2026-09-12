<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('DELETE FROM clients WHERE id_client = ?');
$stmt->execute([$id]);

$_SESSION['flash_success'] = 'Client supprimé avec succès.';
header('Location: /clients/index.php');
exit;