<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';

requireConnexion();

$fournisseurs = $pdo->query(
    "SELECT * 
     FROM fournisseurs 
     WHERE statut = 'actif' 
     ORDER BY raison_sociale"
)->fetchAll();

// Conditionnements achetables, groupés par médicament (pour le filtrage en JS)
$conditionnementsData = $pdo->query(
    "SELECT 
        c.id_conditionnement,
        c.id_medicament,
        c.libelle,
        c.quantite_base
     FROM conditionnements c
     JOIN medicaments m 
        ON m.id_medicament = c.id_medicament
     WHERE c.achetable = 1
       AND c.statut = 'actif'
       AND m.statut = 'actif'
     ORDER BY c.libelle"
)->fetchAll();

$medicaments = $pdo->query(
    "SELECT * 
     FROM medicaments 
     WHERE statut = 'actif' 
     ORDER BY nom"
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

        // Récupération des conditionnements
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

            if (
                $idMed === 0 ||
                $idCond === 0 ||
                $numLot === '' ||
                $dateExp === '' ||
                $qte <= 0 ||
                $prix <= 0
            ) {
                $erreur = 'Chaque ligne doit avoir un médicament, un conditionnement, un n° de lot, une date d\'expiration, une quantité et un prix valides.';
                break;
            }

            if (!isset($conditionnementsById[$idCond])) {
                $erreur = 'Conditionnement invalide.';
                break;
            }

            $quantiteBase =
                $qte * (int)$conditionnementsById[$idCond]['quantite_base'];

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

                $numeroAppro = genererNumeroAppro($pdo);

                // Création de l'approvisionnement
                $stmt = $pdo->prepare(
                    'INSERT INTO approvisionnements
                        (
                            id_fournisseur,
                            id_utilisateur,
                            numero_appro,
                            total_achat,
                            statut
                        )
                     VALUES (?, ?, ?, ?, ?)'
                );

                $stmt->execute([
                    $idFournisseur,
                    $_SESSION['id_utilisateur'],
                    $numeroAppro,
                    $totalAchat,
                    'brouillon'
                ]);

                $idAppro = (int)$pdo->lastInsertId();

                // Création des détails
                $stmtDetail = $pdo->prepare(
                    'INSERT INTO approvisionnement_details
                        (
                            id_approvisionnement,
                            id_medicament,
                            id_conditionnement,
                            quantite,
                            prix_achat,
                            quantite_base,
                            numero_lot,
                            date_fabrication,
                            date_expiration,
                            sous_total
                        )
                     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
                );

                foreach ($lignesValides as $ligne) {

                    $stmtDetail->execute([
                        $idAppro,
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

                $pdo->commit();

                $_SESSION['flash_success'] =
                    "Brouillon $numeroAppro créé avec succès. Pensez à le valider pour mettre à jour le stock.";

                header('Location: /approvisionnements/index.php');
                exit;

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                $erreur = 'Une erreur est survenue lors de l\'enregistrement.';
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

    <title>Nouvel approvisionnement - PharmaGest</title>

    <link rel="stylesheet" href="/assets/css/style.css">

    <link rel="stylesheet" href="/assets/css/responsive.css">

    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>

        .lignes-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
        }

        .lignes-table th,
        .lignes-table td {
            padding: 8px;
            border-bottom: 1px solid var(--color-border);
            font-size: 0.85rem;
        }

        .lignes-table input,
        .lignes-table select {
            width: 100%;
            padding: 6px 8px;
            border: 1px solid var(--color-border);
            border-radius: 6px;
            font-size: 0.85rem;
        }

        .total-box {
            text-align: right;
            font-size: 1.1rem;
            font-weight: 700;
            margin-top: 10px;
        }

        /* ================================
           BOUTON SUPPRESSION
        ================================= */

        .btn-delete-row {
            width: 38px;
            height: 38px;

            display: inline-flex;
            align-items: center;
            justify-content: center;

            border: none;
            border-radius: 6px;

            background: transparent;
            color: #dc2626;

            cursor: pointer;

            transition:
                background-color 0.2s ease,
                color 0.2s ease,
                transform 0.2s ease;
        }

        .btn-delete-row:hover {
            background-color: #fee2e2;
            color: #b91c1c;
            transform: scale(1.05);
        }

        .btn-delete-row:active {
            transform: scale(0.95);
        }

        .btn-delete-row:focus {
            outline: 2px solid rgba(220, 38, 38, 0.25);
            outline-offset: 2px;
        }

        .btn-delete-row svg {
            width: 18px;
            height: 18px;
            stroke: currentColor;
        }

        /* Évite que la colonne suppression soit trop large */
        .col-action {
            width: 55px;
            text-align: center;
        }

    </style>

</head>

<body>

    <div class="app-layout">

        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">

            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">

                <h1 class="page-title">
                    Nouvel approvisionnement
                </h1>

                <div class="card" style="max-width: 100%;">

                    <?php if ($erreur): ?>

                        <div
                            class="alert-error"
                            style="
                                background:#fee2e2;
                                color:#dc2626;
                                padding:10px 12px;
                                border-radius:8px;
                                margin-bottom:16px;
                            "
                        >
                            <?= htmlspecialchars($erreur) ?>
                        </div>

                    <?php endif; ?>


                    <?php if (
                        empty($fournisseurs) ||
                        empty($medicaments) ||
                        empty($conditionnementsData)
                    ): ?>

                        <p>
                            ⚠️ Il faut au moins un fournisseur actif,
                            un médicament et un conditionnement achetable
                            pour créer un approvisionnement.
                        </p>

                    <?php else: ?>


                    <form method="POST" id="formAppro">

                        <!-- FOURNISSEUR -->

                        <div class="form-group" style="max-width:400px;">

                            <label for="id_fournisseur">
                                Fournisseur *
                            </label>

                            <select
                                id="id_fournisseur"
                                name="id_fournisseur"
                                required
                            >

                                <option value="">
                                    -- Choisir --
                                </option>

                                <?php foreach ($fournisseurs as $f): ?>

                                    <option
                                        value="<?= $f['id_fournisseur'] ?>"
                                    >
                                        <?= htmlspecialchars($f['raison_sociale']) ?>
                                    </option>

                                <?php endforeach; ?>

                            </select>

                        </div>


                        <h3 style="margin-top:20px;">
                            Lignes de produits
                        </h3>


                        <!-- TABLEAU -->

                        <table
                            class="lignes-table"
                            id="tableLignes"
                        >

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

                                    <th class="col-action"></th>

                                </tr>

                            </thead>

                            <tbody></tbody>

                        </table>


                        <!-- AJOUT LIGNE -->

                        <button
                            type="button"
                            class="btn btn-outline btn-sm"
                            id="btnAjouterLigne"
                        >
                            + Ajouter une ligne
                        </button>


                        <!-- TOTAL -->

                        <div class="total-box">

                            Total :
                            <span id="totalAffiche">0</span>
                            Ar

                        </div>


                        <!-- ACTIONS -->

                        <div class="form-actions">

                            <button
                                type="submit"
                                class="btn btn-primary"
                            >
                                Enregistrer le brouillon
                            </button>

                            <a
                                href="/approvisionnements/index.php"
                                class="btn btn-outline"
                            >
                                Annuler
                            </a>

                        </div>

                    </form>

                    <?php endif; ?>

                </div>

            </div>

        </div>

    </div>


    <?php include __DIR__ . '/../includes/footer.php'; ?>


    <script>

        const medicaments =
            <?= json_encode($medicaments, JSON_UNESCAPED_UNICODE) ?>;

        const conditionnements =
            <?= json_encode($conditionnementsData, JSON_UNESCAPED_UNICODE) ?>;


        let compteurLigne = 0;


        /*
         * ==========================================================
         * OPTIONS MÉDICAMENTS
         * ==========================================================
         */

        function optionsMedicaments() {

            return medicaments
                .map(m => {

                    return `
                        <option value="${m.id_medicament}">
                            ${escapeHtml(m.nom)}
                            ${m.dosage
                                ? ' (' + escapeHtml(m.dosage) + ')'
                                : ''
                            }
                        </option>
                    `;

                })
                .join('');
        }


        /*
         * ==========================================================
         * OPTIONS CONDITIONNEMENTS
         * ==========================================================
         */

        function optionsConditionnements(idMedicament) {

            const filtres = conditionnements.filter(
                c => c.id_medicament == idMedicament
            );

            if (filtres.length === 0) {

                return `
                    <option value="">
                        -- Aucun --
                    </option>
                `;
            }

            return `
                <option value="">
                    -- Choisir --
                </option>
            ` +

            filtres
                .map(c => {

                    return `
                        <option
                            value="${c.id_conditionnement}"
                            data-qb="${c.quantite_base}"
                        >
                            ${escapeHtml(c.libelle)}
                        </option>
                    `;

                })
                .join('');
        }


        /*
         * ==========================================================
         * AJOUTER UNE LIGNE
         * ==========================================================
         */

        function ajouterLigne() {

            compteurLigne++;

            const tbody =
                document.querySelector('#tableLignes tbody');

            const tr =
                document.createElement('tr');

            tr.innerHTML = `

                <td>

                    <select
                        name="id_medicament[]"
                        class="select-medicament"
                        required
                    >

                        <option value="">
                            -- Choisir --
                        </option>

                        ${optionsMedicaments()}

                    </select>

                </td>


                <td>

                    <select
                        name="id_conditionnement[]"
                        class="select-conditionnement"
                        required
                    >

                        <option value="">
                            -- Médicament d'abord --
                        </option>

                    </select>

                </td>


                <td>

                    <input
                        type="text"
                        name="numero_lot[]"
                        required
                    >

                </td>


                <td>

                    <input
                        type="date"
                        name="date_fabrication[]"
                    >

                </td>


                <td>

                    <input
                        type="date"
                        name="date_expiration[]"
                        required
                    >

                </td>


                <td>

                    <input
                        type="number"
                        name="quantite[]"
                        class="input-quantite"
                        min="1"
                        required
                    >

                </td>


                <td>

                    <input
                        type="number"
                        name="prix_achat[]"
                        class="input-prix"
                        min="0"
                        step="0.01"
                        required
                    >

                </td>


                <td class="sous-total">
                    0
                </td>


                <td class="col-action">

                    <button
                        type="button"
                        class="btn-delete-row"
                        onclick="supprimerLigne(this)"
                        title="Supprimer la ligne"
                        aria-label="Supprimer la ligne"
                    >

                        <!-- Icône poubelle SVG -->

                        <svg
                            viewBox="0 0 24 24"
                            fill="none"
                            stroke="currentColor"
                            stroke-width="2"
                            stroke-linecap="round"
                            stroke-linejoin="round"
                            aria-hidden="true"
                        >

                            <polyline points="3 6 5 6 21 6"></polyline>

                            <path
                                d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6"
                            ></path>

                            <path
                                d="M8 6V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"
                            ></path>

                            <line
                                x1="10"
                                y1="11"
                                x2="10"
                                y2="17"
                            ></line>

                            <line
                                x1="14"
                                y1="11"
                                x2="14"
                                y2="17"
                            ></line>

                        </svg>

                    </button>

                </td>

            `;

            tbody.appendChild(tr);


            /*
             * Récupération des champs
             */

            const selectMed =
                tr.querySelector('.select-medicament');

            const selectCond =
                tr.querySelector('.select-conditionnement');

            const inputQte =
                tr.querySelector('.input-quantite');

            const inputPrix =
                tr.querySelector('.input-prix');

            const sousTotalCell =
                tr.querySelector('.sous-total');


            /*
             * Changement du médicament
             */

            selectMed.addEventListener('change', () => {

                selectCond.innerHTML =
                    optionsConditionnements(selectMed.value);

            });


            /*
             * Calcul du sous-total
             */

            function recalculerLigne() {

                const qte =
                    parseFloat(inputQte.value) || 0;

                const prix =
                    parseFloat(inputPrix.value) || 0;

                const sousTotal =
                    qte * prix;

                sousTotalCell.textContent =
                    sousTotal.toLocaleString('fr-FR');

                recalculerTotal();
            }


            inputQte.addEventListener(
                'input',
                recalculerLigne
            );

            inputPrix.addEventListener(
                'input',
                recalculerLigne
            );
        }


        /*
         * ==========================================================
         * SUPPRIMER UNE LIGNE
         * ==========================================================
         *
         * Ligne vide :
         *      suppression immédiate
         *
         * Ligne remplie :
         *      demande de confirmation SweetAlert2
         */

        function supprimerLigne(button) {

            const ligne =
                button.closest('tr');

            if (!ligne) {
                return;
            }


            /*
             * Vérifier si la ligne contient des données
             */

            const champs =
                ligne.querySelectorAll('input, select');

            let ligneRemplie = false;


            champs.forEach(champ => {

                if (champ.value.trim() !== '') {

                    ligneRemplie = true;

                }

            });


            /*
             * ==========================================
             * LIGNE VIDE
             * ==========================================
             */

            if (!ligneRemplie) {

                ligne.remove();

                recalculerTotal();

                return;
            }


            /*
             * ==========================================
             * LIGNE REMPLIE
             * ==========================================
             */

            Swal.fire({

                title: 'Supprimer cette ligne ?',

                text:
                    'Les informations saisies dans cette ligne seront perdues.',

                icon: 'warning',

                showCancelButton: true,

                confirmButtonColor: '#dc2626',

                cancelButtonColor: '#6b7280',

                confirmButtonText:
                    'Oui, supprimer',

                cancelButtonText:
                    'Annuler',

                reverseButtons: true,

                focusCancel: true

            }).then((result) => {

                if (result.isConfirmed) {

                    ligne.remove();

                    recalculerTotal();


                    /*
                     * Message de confirmation
                     */

                    Swal.fire({

                        icon: 'success',

                        title: 'Ligne supprimée',

                        text:
                            'La ligne a été supprimée avec succès.',

                        timer: 1500,

                        showConfirmButton: false

                    });

                }

            });
        }


        /*
         * ==========================================================
         * CALCUL DU TOTAL
         * ==========================================================
         */

        function recalculerTotal() {

            let total = 0;

            const quantites =
                document.querySelectorAll(
                    '#tableLignes .input-quantite'
                );

            const prix =
                document.querySelectorAll(
                    '#tableLignes .input-prix'
                );


            quantites.forEach((inputQte, i) => {

                const qte =
                    parseFloat(inputQte.value) || 0;

                const prixUnitaire =
                    parseFloat(prix[i]?.value) || 0;

                total +=
                    qte * prixUnitaire;

            });


            document.getElementById(
                'totalAffiche'
            ).textContent =
                total.toLocaleString('fr-FR');
        }


        /*
         * ==========================================================
         * PROTECTION HTML
         * ==========================================================
         *
         * Évite d'injecter directement certaines valeurs
         * provenant de la base de données dans innerHTML.
         */

        function escapeHtml(value) {

            if (value === null || value === undefined) {
                return '';
            }

            return String(value)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }


        /*
         * ==========================================================
         * BOUTON AJOUTER UNE LIGNE
         * ==========================================================
         */

        document
            .getElementById('btnAjouterLigne')
            ?.addEventListener(
                'click',
                ajouterLigne
            );


        /*
         * ==========================================================
         * LIGNE DE DÉPART
         * ==========================================================
         */

        ajouterLigne();

    </script>

</body>

</html>