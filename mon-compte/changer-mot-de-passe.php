<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

header('Content-Type: application/json');

$donnees = json_decode(file_get_contents('php://input'), true);

$mdpActuel = $donnees['mdp_actuel'] ?? '';
$mdpNouveau = $donnees['mdp_nouveau'] ?? '';
$mdpConfirmation = $donnees['mdp_confirmation'] ?? '';

if ($mdpActuel === '' || $mdpNouveau === '' || $mdpConfirmation === '') {
    echo json_encode(['success' => false, 'message' => 'Tous les champs sont obligatoires.']);
    exit;
}

if (strlen($mdpNouveau) < 6) {
    echo json_encode(['success' => false, 'message' => 'Le nouveau mot de passe doit contenir au moins 6 caractères.']);
    exit;
}

if ($mdpNouveau !== $mdpConfirmation) {
    echo json_encode(['success' => false, 'message' => 'Les deux mots de passe ne correspondent pas.']);
    exit;
}

$stmt = $pdo->prepare('SELECT mot_de_passe FROM utilisateurs WHERE id_utilisateur = ?');
$stmt->execute([$_SESSION['id_utilisateur']]);
$hashActuel = $stmt->fetchColumn();

if (!$hashActuel || !password_verify($mdpActuel, $hashActuel)) {
    echo json_encode(['success' => false, 'message' => 'Le mot de passe actuel est incorrect.']);
    exit;
}

$nouveauHash = password_hash($mdpNouveau, PASSWORD_DEFAULT);
$stmt = $pdo->prepare('UPDATE utilisateurs SET mot_de_passe = ?, doit_changer_mdp = 0 WHERE id_utilisateur = ?');
$stmt->execute([$nouveauHash, $_SESSION['id_utilisateur']]);

$_SESSION['doit_changer_mdp'] = false;

echo json_encode(['success' => true, 'message' => 'Mot de passe changé avec succès.']);