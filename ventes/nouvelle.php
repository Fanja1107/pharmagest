<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireConnexion();

$clients = $pdo->query('SELECT * FROM clients ORDER BY nom')->fetchAll();

$conditionnementsData = $pdo->query(
    "SELECT c.id_conditionnement, c.id_medicament, c.libelle, c.prix_vente, c.quantite_base,
            m.nom AS medicament_nom,
            COALESCE((SELECT SUM(l.quantite_base) FROM lots l WHERE l.id_medicament = c.id_medicament AND l.statut = 'actif'), 0) AS stock_disponible
     FROM conditionnements c
     JOIN medicaments m ON m.id_medicament = c.id_medicament
     WHERE c.vendable = 1 AND c.statut = 'actif' AND m.statut = 'actif'
     ORDER BY m.nom, c.quantite_base"
)->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Nouvelle vente - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
    <style>
        .caisse-layout {
            display: grid;
            grid-template-columns: 1fr 380px;
            gap: 20px;
        }
        @media (max-width: 900px) {
            .caisse-layout { grid-template-columns: 1fr; }
        }

        .panier-table { width: 100%; border-collapse: collapse; }
        .panier-table th, .panier-table td { padding: 10px 8px; border-bottom: 1px solid var(--color-border); font-size: 0.88rem; text-align: left; }

        .panier-box {
            background: var(--color-surface);
            border: 1px solid var(--color-border);
            border-radius: var(--radius);
            padding: 18px;
            position: sticky;
            top: 20px;
        }
        .panier-total {
            font-size: 1.4rem;
            font-weight: 700;
            text-align: right;
            margin: 14px 0;
        }
        .stock-info { font-size: 0.8rem; color: var(--color-text-muted); margin-top: 6px; }
        .stock-insuffisant { color: var(--color-danger); font-weight: 600; }

        /* ---------- MODALE ---------- */
        .modal-overlay {
            display: none;
            position: fixed;
            inset: 0;
            background: rgba(15, 23, 42, 0.5);
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }
        .modal-overlay.open { display: flex; }
        .modal-box {
            background: #fff;
            border-radius: 12px;
            width: 100%;
            max-width: 440px;
            padding: 24px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
        }
        .modal-box h3 { margin-top: 0; margin-bottom: 18px; }
        .modal-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
        }
        .modal-close {
            background: none;
            border: none;
            font-size: 1.3rem;
            cursor: pointer;
            float: right;
            color: var(--color-text-muted);
            line-height: 1;
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Nouvelle vente</h1>

                <div class="caisse-layout">
                    <!-- Colonne principale : panier -->
                    <div class="card">
                        <div style="display:flex; justify-content:space-between; align-items:center;">
                            <h3 style="margin:0;">Panier</h3>
                            <button type="button" class="btn btn-primary" id="btnOuvrirModal">+ Ajouter un produit</button>
                        </div>

                        <table class="panier-table" style="margin-top:16px;">
                            <thead>
                                <tr>
                                    <th>Produit</th>
                                    <th>Qté</th>
                                    <th>Prix unit.</th>
                                    <th>Sous-total</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody id="corpsPanier">
                                <tr id="ligneVide"><td colspan="5" style="color:var(--color-text-muted);">Le panier est vide. Cliquez sur "Ajouter un produit" pour commencer.</td></tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- Colonne latérale : client, paiement, total -->
                    <div class="panier-box">
                        <div class="form-group">
                            <label for="id_client">Client (facultatif)</label>
                            <select id="id_client">
                                <option value="">Vente comptoir (sans client)</option>
                                <?php foreach ($clients as $cl): ?>
                                    <option value="<?= $cl['id_client'] ?>"><?= htmlspecialchars($cl['nom'] . ' ' . ($cl['prenom'] ?? '')) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="form-group">
                            <label for="mode_paiement">Mode de paiement</label>
                            <select id="mode_paiement">
                                <option value="especes">Espèces</option>
                                <option value="mobile_money">Mobile Money</option>
                                <option value="carte">Carte</option>
                                <option value="virement">Virement</option>
                            </select>
                        </div>

                        <div class="panier-total">Total : <span id="totalPanier">0</span> Ar</div>

                        <button type="button" class="btn btn-primary" style="width:100%;" id="btnValiderVente">
                            Valider la vente
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODALE D'AJOUT DE PRODUIT -->
    <div class="modal-overlay" id="modalOverlay">
        <div class="modal-box">
            <button type="button" class="modal-close" id="btnFermerModal">&times;</button>
            <h3>Ajouter un produit</h3>

            <div class="form-group">
                <label for="selectConditionnement">Médicament / conditionnement</label>
                <select id="selectConditionnement">
                    <option value="">-- Choisir --</option>
                    <?php foreach ($conditionnementsData as $c): ?>
                        <option value="<?= $c['id_conditionnement'] ?>"
                            data-nom="<?= htmlspecialchars($c['medicament_nom'] . ' - ' . $c['libelle'], ENT_QUOTES) ?>"
                            data-prix="<?= $c['prix_vente'] ?>"
                            data-qb="<?= $c['quantite_base'] ?>"
                            data-stock="<?= $c['stock_disponible'] ?>">
                            <?= htmlspecialchars($c['medicament_nom']) ?> — <?= htmlspecialchars($c['libelle']) ?>
                            (<?= number_format($c['prix_vente'], 0, ',', ' ') ?> Ar)
                        </option>
                    <?php endforeach; ?>
                </select>
                <p class="stock-info" id="infoStock"></p>
            </div>

            <div class="form-group">
                <label for="quantiteAAjouter">Quantité</label>
                <input type="number" id="quantiteAAjouter" min="1" value="1">
            </div>

            <div class="form-group">
                <label>Sous-total</label>
                <div style="font-size:1.2rem; font-weight:700;"><span id="sousTotalModal">0</span> Ar</div>
            </div>

            <div class="modal-actions">
                <button type="button" class="btn btn-primary" id="btnConfirmerAjout" style="flex:1;">Ajouter au panier</button>
                <button type="button" class="btn btn-outline" id="btnAnnulerModal">Annuler</button>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        let panier = [];

        const modalOverlay = document.getElementById('modalOverlay');
        const selectConditionnement = document.getElementById('selectConditionnement');
        const quantiteAAjouter = document.getElementById('quantiteAAjouter');
        const infoStock = document.getElementById('infoStock');
        const sousTotalModal = document.getElementById('sousTotalModal');

        function ouvrirModal() {
            selectConditionnement.value = '';
            quantiteAAjouter.value = 1;
            infoStock.textContent = '';
            sousTotalModal.textContent = '0';
            modalOverlay.classList.add('open');
        }

        function fermerModal() {
            modalOverlay.classList.remove('open');
        }

        document.getElementById('btnOuvrirModal').addEventListener('click', ouvrirModal);
        document.getElementById('btnFermerModal').addEventListener('click', fermerModal);
        document.getElementById('btnAnnulerModal').addEventListener('click', fermerModal);
        modalOverlay.addEventListener('click', (e) => {
            if (e.target === modalOverlay) fermerModal();
        });

        function mettreAJourApercuModal() {
            const option = selectConditionnement.selectedOptions[0];
            if (!option || !option.value) {
                infoStock.textContent = '';
                sousTotalModal.textContent = '0';
                return;
            }
            const stock = parseInt(option.dataset.stock);
            const qb = parseInt(option.dataset.qb);
            const prix = parseFloat(option.dataset.prix);
            const qte = parseInt(quantiteAAjouter.value) || 0;
            const qteBaseDemandee = qte * qb;

            if (qteBaseDemandee > stock) {
                infoStock.innerHTML = `<span class="stock-insuffisant">⚠️ Stock insuffisant : ${stock} unité(s) de base disponible(s)</span>`;
            } else {
                infoStock.textContent = `Stock disponible : ${stock} unité(s) de base`;
            }

            sousTotalModal.textContent = (qte * prix).toLocaleString('fr-FR');
        }

        selectConditionnement.addEventListener('change', mettreAJourApercuModal);
        quantiteAAjouter.addEventListener('input', mettreAJourApercuModal);

        document.getElementById('btnConfirmerAjout').addEventListener('click', () => {
            const option = selectConditionnement.selectedOptions[0];
            if (!option || !option.value) {
                alertError('Veuillez choisir un produit.');
                return;
            }
            const qte = parseInt(quantiteAAjouter.value) || 0;
            if (qte <= 0) {
                alertError('La quantité doit être supérieure à 0.');
                return;
            }

            const idConditionnement = option.value;
            const qb = parseInt(option.dataset.qb);
            const stock = parseInt(option.dataset.stock);
            const qteBaseDemandee = qte * qb;

            const ligneExistante = panier.find(l => l.idConditionnement === idConditionnement);
            const qteBaseTotaleApresAjout = (ligneExistante ? ligneExistante.quantite * qb : 0) + qteBaseDemandee;

            if (qteBaseTotaleApresAjout > stock) {
                alertError(`Stock insuffisant. Disponible : ${stock} unité(s) de base.`);
                return;
            }

            if (ligneExistante) {
                ligneExistante.quantite += qte;
            } else {
                panier.push({
                    idConditionnement: idConditionnement,
                    nom: option.dataset.nom,
                    prixUnitaire: parseFloat(option.dataset.prix),
                    quantite: qte,
                    quantiteBase: qb,
                    stockDisponible: stock,
                });
            }

            afficherPanier();
            fermerModal();
        });

        function afficherPanier() {
            const corpsPanier = document.getElementById('corpsPanier');
            corpsPanier.innerHTML = '';

            if (panier.length === 0) {
                corpsPanier.innerHTML = '<tr id="ligneVide"><td colspan="5" style="color:var(--color-text-muted);">Le panier est vide. Cliquez sur "Ajouter un produit" pour commencer.</td></tr>';
                document.getElementById('totalPanier').textContent = '0';
                return;
            }

            let total = 0;
            panier.forEach((ligne, index) => {
                const sousTotal = ligne.quantite * ligne.prixUnitaire;
                total += sousTotal;

                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td>${ligne.nom}</td>
                    <td>${ligne.quantite}</td>
                    <td>${ligne.prixUnitaire.toLocaleString('fr-FR')} Ar</td>
                    <td>${sousTotal.toLocaleString('fr-FR')} Ar</td>
                    <td><button type="button" class="btn-danger-text" onclick="retirerDuPanier(${index})">✕</button></td>
                `;
                corpsPanier.appendChild(tr);
            });

            document.getElementById('totalPanier').textContent = total.toLocaleString('fr-FR');
        }

        function retirerDuPanier(index) {
            panier.splice(index, 1);
            afficherPanier();
        }

                function retirerDuPanier(index) {
            panier.splice(index, 1);
            afficherPanier();
        }

        document.getElementById('btnValiderVente').addEventListener('click', () => {
            if (panier.length === 0) {
                alertError('Le panier est vide.');
                return;
            }

            const idClient = document.getElementById('id_client').value;
            const modePaiement = document.getElementById('mode_paiement').value;

            const lignes = panier.map(l => ({
                id_conditionnement: l.idConditionnement,
                quantite: l.quantite,
            }));

            Swal.fire({
                title: 'Confirmer la vente ?',
                text: 'Cette action va diminuer le stock de façon définitive.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonText: 'Oui, valider',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#16a34a'
            }).then((result) => {
                if (!result.isConfirmed) return;

                fetch('/ventes/enregistrer.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({
                        id_client: idClient,
                        mode_paiement: modePaiement,
                        lignes: lignes,
                    }),
                })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            Swal.fire({
                                icon: 'success',
                                title: 'Vente enregistrée !',
                                text: `Numéro de vente : ${data.numero_vente} — Total : ${data.total.toLocaleString('fr-FR')} Ar`,
                                confirmButtonText: 'OK'
                            }).then(() => {
                                window.location.href = '/ventes/index.php';
                            });
                        } else {
                            alertError(data.message || 'Une erreur est survenue.');
                        }
                    })
                    .catch(() => {
                        alertError('Erreur de connexion au serveur.');
                    });
            });
        });
    </script>
</body>
</html>