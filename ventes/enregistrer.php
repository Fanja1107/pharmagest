<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
requireConnexion();

header('Content-Type: application/json');

$donnees = json_decode(file_get_contents('php://input'), true);

$idClient = !empty($donnees['id_client']) ? (int)$donnees['id_client'] : null;
$modePaiement = $donnees['mode_paiement'] ?? 'especes';
$lignesPanier = $donnees['lignes'] ?? [];

$modesValides = ['especes', 'mobile_money', 'carte', 'virement'];
if (!in_array($modePaiement, $modesValides, true)) {
    echo json_encode(['success' => false, 'message' => 'Mode de paiement invalide.']);
    exit;
}

if (empty($lignesPanier)) {
    echo json_encode(['success' => false, 'message' => 'Le panier est vide.']);
    exit;
}

try {
    $pdo->beginTransaction();

    $totalVente = 0;
    $lignesAEnregistrer = []; // Contiendra le détail complet, recalculé serveur

    foreach ($lignesPanier as $ligne) {
        $idConditionnement = (int)($ligne['id_conditionnement'] ?? 0);
        $quantiteDemandee = (int)($ligne['quantite'] ?? 0);

        if ($idConditionnement <= 0 || $quantiteDemandee <= 0) {
            throw new Exception('Ligne de panier invalide.');
        }

        // On recharge le conditionnement depuis la base — jamais confiance au prix/quantité venus du client
        $stmt = $pdo->prepare(
            "SELECT c.*, m.nom AS medicament_nom
             FROM conditionnements c
             JOIN medicaments m ON m.id_medicament = c.id_medicament
             WHERE c.id_conditionnement = ? AND c.vendable = 1 AND c.statut = 'actif'"
        );
        $stmt->execute([$idConditionnement]);
        $conditionnement = $stmt->fetch();

        if (!$conditionnement) {
            throw new Exception("Un produit du panier n'est plus disponible à la vente.");
        }

        $quantiteBaseNecessaire = $quantiteDemandee * (int)$conditionnement['quantite_base'];
        $prixUnitaire = (float)$conditionnement['prix_vente'];
        $sousTotal = $quantiteDemandee * $prixUnitaire;
        $totalVente += $sousTotal;

        // Récupération des lots actifs, triés par date d'expiration croissante (FEFO)
        $stmtLots = $pdo->prepare(
            "SELECT * FROM lots
             WHERE id_medicament = ? AND statut = 'actif' AND date_expiration >= CURDATE() AND quantite_base > 0
             ORDER BY date_expiration ASC
             FOR UPDATE"
        );
        $stmtLots->execute([$conditionnement['id_medicament']]);
        $lots = $stmtLots->fetchAll();

        $stockDisponible = array_sum(array_column($lots, 'quantite_base'));

        if ($stockDisponible < $quantiteBaseNecessaire) {
            throw new Exception(
                "Stock insuffisant pour \"{$conditionnement['medicament_nom']} - {$conditionnement['libelle']}\". " .
                "Disponible : $stockDisponible unité(s) de base, demandé : $quantiteBaseNecessaire."
            );
        }

        // Répartition FEFO : on consomme les lots dans l'ordre jusqu'à couvrir la quantité
        $quantiteRestante = $quantiteBaseNecessaire;
        $repartitionLots = [];

        foreach ($lots as $lot) {
            if ($quantiteRestante <= 0) {
                break;
            }
            $quantitePrelevee = min($lot['quantite_base'], $quantiteRestante);
            $repartitionLots[] = [
                'id_lot' => $lot['id_lot'],
                'quantite_base' => $quantitePrelevee,
                'nouvelle_quantite_lot' => $lot['quantite_base'] - $quantitePrelevee,
            ];
            $quantiteRestante -= $quantitePrelevee;
        }

        $lignesAEnregistrer[] = [
            'id_medicament' => $conditionnement['id_medicament'],
            'id_conditionnement' => $idConditionnement,
            'quantite' => $quantiteDemandee,
            'prix_unitaire' => $prixUnitaire,
            'quantite_base' => $quantiteBaseNecessaire,
            'sous_total' => $sousTotal,
            'repartition_lots' => $repartitionLots,
        ];
    }

    // Création de la vente
    $numeroVente = genererNumeroVente($pdo);
    $stmt = $pdo->prepare(
        'INSERT INTO ventes (id_client, id_utilisateur, numero_vente, total, statut, mode_paiement)
         VALUES (?, ?, ?, ?, ?, ?)'
    );
    $stmt->execute([$idClient, $_SESSION['id_utilisateur'], $numeroVente, $totalVente, 'validee', $modePaiement]);
    $idVente = (int)$pdo->lastInsertId();

    $stmtDetail = $pdo->prepare(
        'INSERT INTO vente_details (id_vente, id_medicament, id_conditionnement, quantite, prix_unitaire, quantite_base, sous_total)
         VALUES (?, ?, ?, ?, ?, ?, ?)'
    );
    $stmtVenteLot = $pdo->prepare(
        'INSERT INTO vente_lots (id_vente_detail, id_lot, quantite_base) VALUES (?, ?, ?)'
    );
    $stmtMajLot = $pdo->prepare(
        "UPDATE lots SET quantite_base = ?, statut = IF(? <= 0, 'epuise', statut) WHERE id_lot = ?"
    );

    foreach ($lignesAEnregistrer as $ligne) {
        $stmtDetail->execute([
            $idVente,
            $ligne['id_medicament'],
            $ligne['id_conditionnement'],
            $ligne['quantite'],
            $ligne['prix_unitaire'],
            $ligne['quantite_base'],
            $ligne['sous_total'],
        ]);
        $idVenteDetail = (int)$pdo->lastInsertId();

        foreach ($ligne['repartition_lots'] as $rep) {
            $stmtVenteLot->execute([$idVenteDetail, $rep['id_lot'], $rep['quantite_base']]);
            $stmtMajLot->execute([$rep['nouvelle_quantite_lot'], $rep['nouvelle_quantite_lot'], $rep['id_lot']]);
        }
    }

    $pdo->commit();

    echo json_encode([
        'success' => true,
        'numero_vente' => $numeroVente,
        'id_vente' => $idVente,
        'total' => $totalVente,
    ]);
} catch (Exception $e) {
    $pdo->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}