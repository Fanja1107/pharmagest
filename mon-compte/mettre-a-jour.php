<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

header('Content-Type: application/json');

$donnees = json_decode(file_get_contents('php://input'), true);

$nom = trim($donnees['nom'] ?? '');
$prenom = trim($donnees['prenom'] ?? '');
$email = trim($donnees['email'] ?? '');
$avatar = trim($donnees['avatar'] ?? 'avatar-1');

$avatarsValides = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-4', 'avatar-5', 'avatar-6', 'avatar-7', 'avatar-8'];

if ($nom === '' || $email === '') {
    echo json_encode(['success' => false, 'message' => 'Le nom et l\'email sont obligatoires.']);
    exit;
}

if (!in_array($avatar, $avatarsValides, true)) {
    $avatar = 'avatar-1';
}

try {
    $stmt = $pdo->prepare('UPDATE utilisateurs SET nom = ?, prenom = ?, email = ?, avatar = ? WHERE id_utilisateur = ?');
    $stmt->execute([$nom, $prenom ?: null, $email, $avatar, $_SESSION['id_utilisateur']]);

    $_SESSION['nom'] = $nom;
    $_SESSION['prenom'] = $prenom;

    echo json_encode(['success' => true, 'message' => 'Profil mis à jour avec succès.']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Cet email est déjà utilisé par un autre utilisateur.']);
}