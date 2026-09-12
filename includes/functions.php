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