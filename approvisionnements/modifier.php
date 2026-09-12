<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare('SELECT * FROM approvisionnements WHERE id_approvisionnement = ?');
$stmt->execute([$id]);
$appro = $stmt->fetch();

if (!$appro) {
    $_SESSION['flash_error'] = 'Approvisionnement introuvable.';
    header('Location: /approvisionnements/index.php');
    exit;
}

if ($appro['statut'] !== 'brouillon') {
    $_SESSION['flash_error'] = 'Seul un brouillon peut être modifié.';
    header('Location: /approvisionnements/voir.php?id=' . $id);
    exit;
}

$stmt = $pdo->prepare('SELECT * FROM approvisionnement_details WHERE id_approvisionnement = ?');
$stmt->execute([$id]);
$lignesExistantes = $stmt->fetchAll();

$fournisseurs = $pdo->query("SELECT * FROM fournisseurs WHERE statut = 'actif' ORDER BY raison_sociale")->fetchAll();
$medicaments = $pdo->query("SELECT * FROM medicaments WHERE statut = 'actif' ORDER BY nom")->fetchAll();
$conditionnementsData = $pdo->query(
    "SELECT c.id_conditionnement, c.id_medicament, c.libelle, c.quantite_base
     FROM conditionnements c
     JOIN medicaments m ON m.id_medicament = c.id_medicament
     WHERE c.achetable = 1 AND c.statut = 'actif' AND m.statut = 'actif'
     ORDER BY c.libelle"
)->fetchAll();

$erreur = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $idFournisseur = (int)($_POST['id_fournisseur'] ?? 0);
    $idsMedicament = $_POST['id_medicament'] ?? [];
    $idsConditionnement = $_POST['id_conditionnement'] ?? [];
    $numerosLot = $_POST['numero_lot'] ?? [];
    $datesFabrication = $_POST['date_fabrication'] ?? [];
    $datesExpiration = $_POST['date_expiration'] ?? [];
    $quantites = $_POST['quantite'] ?? [];
    $prixAchats = $_POST['prix_achat'] ?? [];

    if ($idFournisseur === 0) {
        $erreur = 'Veuillez choisir un fournisseur.';
    } elseif (empty($idsMedicament)) {
        $erreur = 'Ajoutez au moins une ligne de produit.';
    } else {
        $conditionnementsById = [];
        foreach ($conditionnementsData as $c) {
            $conditionnementsById[$c['id_conditionnement']] = $c;
        }

        $lignesValides = [];
        $totalAchat = 0;

        foreach ($idsMedicament as $i => $idMed) {
            $idMed = (int)$idMed;
            $idCond = (int)($idsConditionnement[$i] ?? 0);
            $numLot = trim($numerosLot[$i] ?? '');
            $dateFab = trim($datesFabrication[$i] ?? '');
            $dateExp = trim($datesExpiration[$i] ?? '');
            $qte = (int)($quantites[$i] ?? 0);
            $prix = (float)($prixAchats[$i] ?? 0);

            if ($idMed === 0 || $idCond === 0 || $numLot === '' || $dateExp === '' || $qte <= 0 || $prix <= 0) {
                $erreur = 'Chaque ligne doit avoir un médicament, un conditionnement, un n° de lot, une date d\'expiration, une quantité et un prix valides.';
                break;
            }

            if (!isset($conditionnementsById[$idCond])) {
                $erreur = 'Conditionnement invalide.';
                break;
            }

            $quantiteBase = $qte * (int)$conditionnementsById[$idCond]['quantite_base'];
            $sousTotal = $qte * $prix;
            $totalAchat += $sousTotal;

            $lignesValides[] = [
                'id_medicament' => $idMed,
                'id_conditionnement' => $idCond,
                'numero_lot' => $numLot,
                'date_fabrication' => $dateFab ?: null,
                'date_expiration' => $dateExp,
                'quantite' => $qte,
                'prix_achat' => $prix,
                'quantite_base' => $quantiteBase,
                'sous_total' => $sousTotal,
            ];
        }

        if ($erreur === '' && !empty($lignesValides)) {
            try {
                $pdo->beginTransaction();

                // On supprime les anciennes lignes, puis on réinsère les nouvelles (plus simple et sûr que du update ligne par ligne)
                $stmtDel = $pdo->prepare('DELETE FROM approvisionnement_details WHERE id_approvisionnement = ?');
                $stmtDel->execute([$id]);

                $stmtDetail = $pdo->prepare(
                    'INSERT INTO approvisionnement_details
                        (id_approvisionnement, id_medicament, id_conditionnement, quantite, prix_achat, quantite_base, numero_lot, date_fabrication, date_expiration, sous_total)
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );
                foreach ($lignesValides as $ligne) {
                    $stmtDetail->execute([
                        $id,
                        $ligne['id_medicament'],
                        $ligne['id_conditionnement'],
                        $ligne['quantite'],
                        $ligne['prix_achat'],
                        $ligne['quantite_base'],
                        $ligne['numero_lot'],
                        $ligne['date_fabrication'],
                        $ligne['date_expiration'],
                        $ligne['sous_total'],
                    ]);
                }

                $stmtMaj = $pdo->prepare('UPDATE approvisionnements SET id_fournisseur = ?, total_achat = ? WHERE id_approvisionnement = ?');
                $stmtMaj->execute([$idFournisseur, $totalAchat, $id]);

                $pdo->commit();

                $_SESSION['flash_success'] = 'Brouillon modifié avec succès.';
                header('Location: /approvisionnements/voir.php?id=' . $id);
                exit;
            } catch (PDOException $e) {
                $pdo->rollBack();
                $erreur = 'Une erreur est survenue lors de la modification.';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Modifier <?= htmlspecialchars($appro['numero_appro']) ?> - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .lignes-table { width: 100%; border-collapse: collapse; margin-bottom: 16px; }
        .lignes-table th, .lignes-table td { padding: 8px; border-bottom: 1px solid var(--color-border); font-size: 0.85rem; }
        .lignes-table input, .lignes-table select { width: 100%; padding: 6px 8px; border: 1px solid var(--color-border); border-radius: 6px; font-size: 0.85rem; }
        .total-box { text-align: right; font-size: 1.1rem; font-weight: 700; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Modifier <?= htmlspecialchars($appro['numero_appro']) ?></h1>

                <div class="card" style="max-width: 100%;">
                    <?php if ($erreur): ?>
                        <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                            <?= htmlspecialchars($erreur) ?>
                        </div>
                    <?php endif; ?>

                    <form method="POST" id="formAppro">
                        <div class="form-group" style="max-width:400px;">
                            <label for="id_fournisseur">Fournisseur *</label>
                            <select id="id_fournisseur" name="id_fournisseur" required>
                                <option value="">-- Choisir --</option>
                                <?php foreach ($fournisseurs as $f): ?>
                                    <option value="<?= $f['id_fournisseur'] ?>" <?= $f['id_fournisseur'] == $appro['id_fournisseur'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($f['raison_sociale']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <h3 style="margin-top:20px;">Lignes de produits</h3>

                        <table class="lignes-table" id="tableLignes">
                            <thead>
                                <tr>
                                    <th>Médicament</th>
                                    <th>Conditionnement</th>
                                    <th>N° lot</th>
                                    <th>Fabrication</th>
                                    <th>Expiration *</th>
                                    <th>Qté</th>
                                    <th>Prix/u</th>
                                    <th>Sous-total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>

                        <button type="button" class="btn btn-outline btn-sm" id="btnAjouterLigne">+ Ajouter une ligne</button>

                        <div class="total-box">Total : <span id="totalAffiche">0</span> Ar</div>

                        <div class="form-actions">
                            <button type="submit" class="btn btn-primary">Enregistrer les modifications</button>
                            <a href="/approvisionnements/voir.php?id=<?= $id ?>" class="btn btn-outline">Annuler</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        const medicaments = <?= json_encode($medicaments) ?>;
        const conditionnements = <?= json_encode($conditionnementsData) ?>;
        const lignesExistantes = <?= json_encode($lignesExistantes) ?>;

        function optionsMedicaments(idSelectionne) {
            return medicaments.map(m =>
                `<option value="${m.id_medicament}" ${m.id_medicament == idSelectionne ? 'selected' : ''}>${m.nom}${m.dosage ? ' (' + m.dosage + ')' : ''}</option>`
            ).join('');
        }

        function optionsConditionnements(idMedicament, idSelectionne) {
            const filtres = conditionnements.filter(c => c.id_medicament == idMedicament);
            if (filtres.length === 0) {
                return '<option value="">-- Aucun --</option>';
            }
            return '<option value="">-- Choisir --</option>' +
                filtres.map(c =>
                    `<option value="${c.id_conditionnement}" data-qb="${c.quantite_base}" ${c.id_conditionnement == idSelectionne ? 'selected' : ''}>${c.libelle}</option>`
                ).join('');
        }

        function ajouterLigne(donnees = null) {
            const tbody = document.querySelector('#tableLignes tbody');
            const tr = document.createElement('tr');

            const idMedSelectionne = donnees ? donnees.id_medicament : '';
            const idCondSelectionne = donnees ? donnees.id_conditionnement : '';

            tr.innerHTML = `
                <td>
                    <select name="id_medicament[]" class="select-medicament" required>
                        <option value="">-- Choisir --</option>
                        ${optionsMedicaments(idMedSelectionne)}
                    </select>
                </td>
                <td><select name="id_conditionnement[]" class="select-conditionnement" required>${optionsConditionnements(idMedSelectionne, idCondSelectionne)}</select></td>
                <td><input type="text" name="numero_lot[]" required value="${donnees ? donnees.numero_lot : ''}"></td>
                <td><input type="date" name="date_fabrication[]" value="${donnees && donnees.date_fabrication ? donnees.date_fabrication : ''}"></td>
                <td><input type="date" name="date_expiration[]" required value="${donnees ? donnees.date_expiration : ''}"></td>
                <td><input type="number" name="quantite[]" class="input-quantite" min="1" required value="${donnees ? donnees.quantite : ''}"></td>
                <td><input type="number" name="prix_achat[]" class="input-prix" min="0" step="0.01" required value="${donnees ? donnees.prix_achat : ''}"></td>
                <td class="sous-total">${donnees ? Number(donnees.sous_total).toLocaleString('fr-FR') : '0'}</td>
                <td><button type="button" class="btn-danger-text" onclick="this.closest('tr').remove(); recalculerTotal();">✕</button></td>
            `;
            tbody.appendChild(tr);

            const selectMed = tr.querySelector('.select-medicament');
            const selectCond = tr.querySelector('.select-conditionnement');
            const inputQte = tr.querySelector('.input-quantite');
            const inputPrix = tr.querySelector('.input-prix');
            const sousTotalCell = tr.querySelector('.sous-total');

            selectMed.addEventListener('change', () => {
                selectCond.innerHTML = optionsConditionnements(selectMed.value, '');
            });

            function recalculerLigne() {
                const qte = parseFloat(inputQte.value) || 0;
                const prix = parseFloat(inputPrix.value) || 0;
                const sousTotal = qte * prix;
                sousTotalCell.textContent = sousTotal.toLocaleString('fr-FR');
                recalculerTotal();
            }

            inputQte.addEventListener('input', recalculerLigne);
            inputPrix.addEventListener('input', recalculerLigne);
        }

        function recalculerTotal() {
            let total = 0;
            document.querySelectorAll('#tableLignes .input-quantite').forEach((inputQte, i) => {
                const inputPrix = document.querySelectorAll('#tableLignes .input-prix')[i];
                total += (parseFloat(inputQte.value) || 0) * (parseFloat(inputPrix.value) || 0);
            });
            document.getElementById('totalAffiche').textContent = total.toLocaleString('fr-FR');
        }

        document.getElementById('btnAjouterLigne')?.addEventListener('click', () => ajouterLigne());

        // Pré-remplissage avec les lignes existantes
        if (lignesExistantes.length > 0) {
            lignesExistantes.forEach(ligne => ajouterLigne(ligne));
        } else {
            ajouterLigne();
        }
        recalculerTotal();
    </script>
</body>
</html>