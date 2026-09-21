<?php
/**
 * Fonctions utilitaires réutilisables dans tout le projet
 */

/**
 * Met à jour automatiquement le statut des lots expirés.
 * Un lot dont la date d'expiration est dépassée passe automatiquement en "expire".
 * À appeler en début de page sur toute page qui affiche des lots ou du stock.
 */
function mettreAJourStatutsLots(PDO $pdo): void
{
    // Passe en "expire" les lots dont la date est dépassée
    $pdo->exec(
        "UPDATE lots
         SET statut = 'expire'
         WHERE date_expiration < CURDATE()
         AND statut != 'expire'"
    );

    // Repasse en "actif" les lots dont la date a été corrigée vers le futur
    $pdo->exec(
        "UPDATE lots
         SET statut = 'actif'
         WHERE date_expiration >= CURDATE()
         AND statut = 'expire'"
    );
}

/**
 * Génère un numéro d'approvisionnement unique du type APPRO-2026-0001
 */
function genererNumeroAppro(PDO $pdo): string
{
    $annee = date('Y');
    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM approvisionnements WHERE numero_appro LIKE ?"
    );
    $stmt->execute(["APPRO-$annee-%"]);
    $compteur = (int)$stmt->fetchColumn() + 1;

    return sprintf('APPRO-%s-%04d', $annee, $compteur);
}

/**
 * Retourne les lots actifs qui expirent bientôt (par défaut, dans les 30 prochains jours).
 */
function getLotsExpirationProche(PDO $pdo, int $joursSeuil = 30): array
{
    $stmt = $pdo->prepare(
        "SELECT l.*, m.nom AS medicament_nom,
                DATEDIFF(l.date_expiration, CURDATE()) AS jours_restants
         FROM lots l
         JOIN medicaments m ON m.id_medicament = l.id_medicament
         WHERE l.statut = 'actif'
         AND DATEDIFF(l.date_expiration, CURDATE()) <= ?
         ORDER BY l.date_expiration ASC"
    );
    $stmt->execute([$joursSeuil]);
    return $stmt->fetchAll();
}

/**
 * Génère un numéro de vente unique du type VENTE-2026-0001
 */
function genererNumeroVente(PDO $pdo): string
{
    $annee = date('Y');
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM ventes WHERE numero_vente LIKE ?");
    $stmt->execute(["VENTE-$annee-%"]);
    $compteur = (int)$stmt->fetchColumn() + 1;

    return sprintf('VENTE-%s-%04d', $annee, $compteur);
}

/**
 * Retourne l'id_utilisateur du tout premier compte admin actif encore existant
 * (le plus petit id parmi les admins actifs). Ce compte est considéré comme
 * le "propriétaire" du système et ne peut être désactivé ou rétrogradé par
 * un autre admin — seulement par lui-même.
 */
function getIdAdminPrincipal(PDO $pdo): ?int
{
    $stmt = $pdo->query(
        "SELECT id_utilisateur FROM utilisateurs
         WHERE role = 'admin' AND statut = 'actif'
         ORDER BY id_utilisateur ASC
         LIMIT 1"
    );
    $id = $stmt->fetchColumn();
    return $id !== false ? (int)$id : null;
}

/**
 * Retourne le SVG inline d'un avatar prédéfini (8 avatars disponibles).
 */
function avatarSvg(string $avatarId, int $size = 36): string
{
    $avatars = [
        'avatar-1' => ['fill' => '#2563eb', 'icon' => 'star'],
        'avatar-2' => ['fill' => '#16a34a', 'icon' => 'leaf'],
        'avatar-3' => ['fill' => '#f59e0b', 'icon' => 'sun'],
        'avatar-4' => ['fill' => '#dc2626', 'icon' => 'drop'],
        'avatar-5' => ['fill' => '#7c3aed', 'icon' => 'moon'],
        'avatar-6' => ['fill' => '#0891b2', 'icon' => 'wave'],
        'avatar-7' => ['fill' => '#db2777', 'icon' => 'bolt'],
        'avatar-8' => ['fill' => '#4338ca', 'icon' => 'mountain'],
    ];
    $a = $avatars[$avatarId] ?? $avatars['avatar-1'];

    $icons = [
        'star'     => '<polygon points="18,6 20.5,13 28,13 22,17.5 24,25 18,20.5 12,25 14,17.5 8,13 15.5,13" fill="white"/>',
        'leaf'     => '<path d="M10 26C10 14 22 10 28 10C28 20 22 26 12 26C11 26 10 26 10 26Z" fill="white"/><line x1="10" y1="26" x2="20" y2="16" stroke="' . $a['fill'] . '" stroke-width="1.6"/>',
        'sun'      => '<circle cx="18" cy="18" r="6" fill="white"/><g stroke="white" stroke-width="2" stroke-linecap="round"><line x1="18" y1="4" x2="18" y2="8"/><line x1="18" y1="28" x2="18" y2="32"/><line x1="4" y1="18" x2="8" y2="18"/><line x1="28" y1="18" x2="32" y2="18"/><line x1="7.5" y1="7.5" x2="10.3" y2="10.3"/><line x1="25.7" y1="25.7" x2="28.5" y2="28.5"/><line x1="7.5" y1="28.5" x2="10.3" y2="25.7"/><line x1="25.7" y1="10.3" x2="28.5" y2="7.5"/></g>',
        'drop'     => '<path d="M18 6C18 6 9 17 9 23C9 28 13 31 18 31C23 31 27 28 27 23C27 17 18 6 18 6Z" fill="white"/>',
        'moon'     => '<path d="M24 8C18 8 13 13 13 19C13 25 18 30 24 30C19 30 15 25 15 19C15 13 19 8 24 8Z" fill="white"/>',
        'wave'     => '<path d="M6 20C10 14 14 26 18 20C22 14 26 26 30 20" stroke="white" stroke-width="2.5" fill="none" stroke-linecap="round"/>',
        'bolt'     => '<polygon points="20,4 10,20 17,20 14,32 26,15 19,15" fill="white"/>',
        'mountain' => '<polygon points="6,26 14,12 20,20 24,14 30,26" fill="white"/>',
    ];
    $icon = $icons[$a['icon']] ?? '';

    return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 36 36" xmlns="http://www.w3.org/2000/svg" style="border-radius:50%; display:block;">'
         . '<circle cx="18" cy="18" r="18" fill="' . $a['fill'] . '"/>'
         . $icon
         . '</svg>';
}
/**
 * Génère un mot de passe temporaire aléatoire lisible (8 caractères alphanumériques).
 */
function genererMotDePasseTemporaire(): string
{
    $caracteres = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $mdp = '';
    for ($i = 0; $i < 8; $i++) {
        $mdp .= $caracteres[random_int(0, strlen($caracteres) - 1)];
    }
    return $mdp;
}