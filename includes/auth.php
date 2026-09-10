<?php
/**
 * Fonctions d'authentification et de protection des pages
 */

// Démarre la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Vérifie si un utilisateur est connecté
 */
function estConnecte(): bool
{
    return isset($_SESSION['id_utilisateur']);
}

/**
 * Bloque l'accès à la page si l'utilisateur n'est pas connecté
 */
function requireConnexion(): void
{
    if (!estConnecte()) {
        header('Location: /auth/login.php');
        exit;
    }
}

/**
 * Bloque l'accès si l'utilisateur n'a pas un des rôles autorisés
 * Exemple : requireRole(['admin', 'pharmacien']);
 */
function requireRole(array $rolesAutorises): void
{
    requireConnexion();

    if (!in_array($_SESSION['role'], $rolesAutorises, true)) {
        http_response_code(403);
        die('Accès refusé : vous n\'avez pas les permissions nécessaires pour cette page.');
    }
}