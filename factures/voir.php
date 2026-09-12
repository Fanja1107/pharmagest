<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT v.*, c.nom AS client_nom, c.prenom AS client_prenom, c.telephone AS client_telephone,
            u.nom AS vendeur_nom, u.prenom AS vendeur_prenom
     FROM ventes v
     LEFT JOIN clients c ON c.id_client = v.id_client
     JOIN utilisateurs u ON u.id_utilisateur = v.id_utilisateur
     WHERE v.id_vente = ?"
);
$stmt->execute([$id]);
$vente = $stmt->fetch();

if (!$vente) {
    $_SESSION['flash_error'] = 'Facture introuvable.';
    header('Location: /ventes/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT vd.*, m.nom AS medicament_nom, c.libelle AS conditionnement_libelle
     FROM vente_details vd
     JOIN medicaments m ON m.id_medicament = vd.id_medicament
     JOIN conditionnements c ON c.id_conditionnement = vd.id_conditionnement
     WHERE vd.id_vente = ?"
);
$stmt->execute([$id]);
$lignes = $stmt->fetchAll();

$labelsPaiement = [
    'especes' => 'Espèces',
    'mobile_money' => 'Mobile Money',
    'carte' => 'Carte',
    'virement' => 'Virement',
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Facture <?= htmlspecialchars($vente['numero_vente']) ?> - PharmaGest</title>
    <style>
        :root {
            --color-primary: #2563eb;
            --color-border: #e2e8f0;
            --color-text: #1e293b;
            --color-text-muted: #64748b;
        }
        * { box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background: #f4f6f9;
            color: var(--color-text);
            margin: 0;
            padding: 24px;
        }
        .facture-wrapper {
            max-width: 760px;
            margin: 0 auto;
            background: #fff;
            border-radius: 8px;
            padding: 40px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.08);
        }
        .facture-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2px solid var(--color-primary);
            padding-bottom: 20px;
            margin-bottom: 24px;
        }
        .facture-header .brand {
            font-size: 1.5rem;
            font-weight: 700;
            color: var(--color-primary);
        }
        .facture-header .brand small {
            display: block;
            font-size: 0.8rem;
            font-weight: 400;
            color: var(--color-text-muted);
        }
        .facture-header .meta {
            text-align: right;
            font-size: 0.9rem;
        }
        .facture-header .meta .numero {
            font-size: 1.1rem;
            font-weight: 700;
            color: var(--color-text);
        }
        .facture-infos {
            display: flex;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 28px;
            font-size: 0.9rem;
        }
        .facture-infos .bloc { flex: 1; }
        .facture-infos .bloc h4 {
            margin: 0 0 6px;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: var(--color-text-muted);
        }
        table.facture-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        table.facture-table th {
            background: #f8fafc;
            text-align: left;
            padding: 10px;
            font-size: 0.78rem;
            text-transform: uppercase;
            color: var(--color-text-muted);
            border-bottom: 2px solid var(--color-border);
        }
        table.facture-table td {
            padding: 10px;
            border-bottom: 1px solid var(--color-border);
            font-size: 0.92rem;
        }
        table.facture-table td.montant, table.facture-table th.montant { text-align: right; }
        .facture-total-row td {
            font-size: 1.15rem;
            font-weight: 700;
            border-top: 2px solid var(--color-text);
            border-bottom: none;
        }
        .facture-footer {
            margin-top: 30px;
            text-align: center;
            font-size: 0.85rem;
            color: var(--color-text-muted);
        }
        .facture-actions {
            max-width: 760px;
            margin: 16px auto 0;
            display: flex;
            gap: 10px;
        }
        .btn {
            display: inline-block;
            padding: 9px 16px;
            border-radius: 8px;
            border: none;
            font-size: 0.88rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
        }
        .btn-primary { background: var(--color-primary); color: #fff; }
        .btn-outline { background: #fff; border: 1px solid var(--color-border); color: var(--color-text); }

        /* ---------- IMPRESSION ---------- */
        @media print {
            body { background: #fff; padding: 0; }
            .facture-wrapper { box-shadow: none; border-radius: 0; max-width: 100%; padding: 0; }
            .facture-actions { display: none; }
        }
    </style>
</head>
<body>
    <div class="facture-wrapper">
        <div class="facture-header">
            <div class="brand">
                💊 PharmaGest
                <small>Gestion de pharmacie</small>
            </div>
            <div class="meta">
                <div class="numero">Facture <?= htmlspecialchars($vente['numero_vente']) ?></div>
                <div>Date : <?= date('d/m/Y', strtotime($vente['date_vente'])) ?></div>
            </div>
        </div>

        <div class="facture-infos">
            <div class="bloc">
                <h4>Client</h4>
                <?php if ($vente['client_nom']): ?>
                    <div><?= htmlspecialchars($vente['client_nom'] . ' ' . $vente['client_prenom']) ?></div>
                    <?php if ($vente['client_telephone']): ?>
                        <div><?= htmlspecialchars($vente['client_telephone']) ?></div>
                    <?php endif; ?>
                <?php else: ?>
                    <div>Vente comptoir</div>
                <?php endif; ?>
            </div>
            <div class="bloc">
                <h4>Vendeur</h4>
                <div><?= htmlspecialchars($vente['vendeur_nom'] . ' ' . $vente['vendeur_prenom']) ?></div>
            </div>
            <div class="bloc">
                <h4>Paiement</h4>
                <div><?= htmlspecialchars($labelsPaiement[$vente['mode_paiement']] ?? $vente['mode_paiement']) ?></div>
            </div>
        </div>

        <table class="facture-table">
            <thead>
                <tr>
                    <th>Produit</th>
                    <th class="montant">Qté</th>
                    <th class="montant">Prix unit.</th>
                    <th class="montant">Sous-total</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($lignes as $l): ?>
                    <tr>
                        <td><?= htmlspecialchars($l['medicament_nom']) ?> — <?= htmlspecialchars($l['conditionnement_libelle']) ?></td>
                        <td class="montant"><?= (int)$l['quantite'] ?></td>
                        <td class="montant"><?= number_format($l['prix_unitaire'], 0, ',', ' ') ?> Ar</td>
                        <td class="montant"><?= number_format($l['sous_total'], 0, ',', ' ') ?> Ar</td>
                    </tr>
                <?php endforeach; ?>
                <tr class="facture-total-row">
                    <td colspan="3" style="text-align:right;">TOTAL</td>
                    <td class="montant"><?= number_format($vente['total'], 0, ',', ' ') ?> Ar</td>
                </tr>
            </tbody>
        </table>

        <div class="facture-footer">
            Merci pour votre confiance.
        </div>
    </div>

    <div class="facture-actions">
        <button type="button" class="btn btn-primary" onclick="window.print()">🖨️ Imprimer</button>
        <a href="/ventes/voir.php?id=<?= $id ?>" class="btn btn-outline">← Retour au détail</a>
    </div>
</body>
</html>