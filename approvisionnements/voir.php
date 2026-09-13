<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/database.php';
requireRole(['admin', 'pharmacien']);

$id = (int)($_GET['id'] ?? 0);

$stmt = $pdo->prepare(
    "SELECT a.*, f.raison_sociale, u.nom AS utilisateur_nom
     FROM approvisionnements a
     JOIN fournisseurs f ON f.id_fournisseur = a.id_fournisseur
     JOIN utilisateurs u ON u.id_utilisateur = a.id_utilisateur
     WHERE a.id_approvisionnement = ?"
);
$stmt->execute([$id]);
$appro = $stmt->fetch();

if (!$appro) {
    $_SESSION['flash_error'] = 'Approvisionnement introuvable.';
    header('Location: /approvisionnements/index.php');
    exit;
}

$stmt = $pdo->prepare(
    "SELECT d.*, m.nom AS medicament_nom, c.libelle AS conditionnement_libelle
     FROM approvisionnement_details d
     JOIN medicaments m ON m.id_medicament = d.id_medicament
     JOIN conditionnements c ON c.id_conditionnement = d.id_conditionnement
     WHERE d.id_approvisionnement = ?"
);
$stmt->execute([$id]);
$lignes = $stmt->fetchAll();

$erreur = '';

// Traitement de la validation
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['valider'])) {
    if ($appro['statut'] !== 'brouillon') {
        $erreur = 'Cet approvisionnement a déjà été traité.';
    } else {
        try {
            $pdo->beginTransaction();

            // Pour chaque ligne : créer le lot correspondant
            $stmtLot = $pdo->prepare(
                'INSERT INTO lots (id_medicament, numero_lot, date_fabrication, date_expiration, quantite_base, prix_achat_base)
                 VALUES (?, ?, ?, ?, ?, ?)'
            );

            foreach ($lignes as $ligne) {
                // Prix d'achat "par unité de base" = prix d'achat de la ligne / quantité de base d'un conditionnement
                $stmt2 = $pdo->prepare('SELECT quantite_base FROM conditionnements WHERE id_conditionnement = ?');
                $stmt2->execute([$ligne['id_conditionnement']]);
                $qteBaseConditionnement = (int)$stmt2->fetchColumn();

                $prixAchatParUniteBase = $qteBaseConditionnement > 0
                    ? round($ligne['prix_achat'] / $qteBaseConditionnement, 2)
                    : $ligne['prix_achat'];

                $stmtLot->execute([
                    $ligne['id_medicament'],
                    $ligne['numero_lot'],
                    $ligne['date_fabrication'],
                    $ligne['date_expiration'],
                    $ligne['quantite_base'],
                    $prixAchatParUniteBase,
                ]);
            }

            // On passe l'approvisionnement en "validé"
            $stmtMaj = $pdo->prepare("UPDATE approvisionnements SET statut = 'valide' WHERE id_approvisionnement = ?");
            $stmtMaj->execute([$id]);

            $pdo->commit();

            $_SESSION['flash_success'] = 'Approvisionnement validé : le stock a été mis à jour.';
            header('Location: /approvisionnements/voir.php?id=' . $id);
            exit;
        } catch (PDOException $e) {
            $pdo->rollBack();
            $erreur = 'Une erreur est survenue lors de la validation. Veuillez réessayer.';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approvisionnement <?= htmlspecialchars($appro['numero_appro']) ?> - PharmaGest</title>
    <link rel="stylesheet" href="/assets/css/style.css">
    <link rel="stylesheet" href="/assets/css/responsive.css">
</head>
<body>
    <div class="app-layout">
        <?php include __DIR__ . '/../includes/sidebar.php'; ?>

        <div class="main-content">
            <?php include __DIR__ . '/../includes/header.php'; ?>

            <div class="page-content">
                <h1 class="page-title">Approvisionnement <?= htmlspecialchars($appro['numero_appro']) ?></h1>

                <?php if ($erreur): ?>
                    <div class="alert-error" style="background:#fee2e2;color:#dc2626;padding:10px 12px;border-radius:8px;margin-bottom:16px;">
                        <?= htmlspecialchars($erreur) ?>
                    </div>
                <?php endif; ?>

                <div class="card" style="margin-bottom:20px;">
                    <p><strong>Fournisseur :</strong> <?= htmlspecialchars($appro['raison_sociale']) ?></p>
                    <p><strong>Date :</strong> <?= date('d/m/Y H:i', strtotime($appro['date_approvisionnement'])) ?></p>
                    <p><strong>Créé par :</strong> <?= htmlspecialchars($appro['utilisateur_nom']) ?></p>
                    <p>
                        <strong>Statut :</strong>
                        <?php
                            $badgeClass = match($appro['statut']) {
                                'brouillon' => 'badge-warning',
                                'valide' => 'badge-success',
                                'annule' => 'badge-danger',
                                default => 'badge-warning',
                            };
                        ?>
                        <span class="badge <?= $badgeClass ?>"><?= ucfirst($appro['statut']) ?></span>
                    </p>
                </div>

                <div class="table-wrapper">
                    <table class="data-table">
                        <thead>
                            <tr>
                                <th>Médicament</th>
                                <th>Conditionnement</th>
                                <th>N° lot</th>
                                <th>Expiration</th>
                                <th>Qté</th>
                                <th>Prix/u</th>
                                <th>Sous-total</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($lignes as $l): ?>
                                <tr>
                                    <td><?= htmlspecialchars($l['medicament_nom']) ?></td>
                                    <td><?= htmlspecialchars($l['conditionnement_libelle']) ?></td>
                                    <td><?= htmlspecialchars($l['numero_lot']) ?></td>
                                    <td><?= date('d/m/Y', strtotime($l['date_expiration'])) ?></td>
                                    <td><?= (int)$l['quantite'] ?></td>
                                    <td><?= number_format($l['prix_achat'], 0, ',', ' ') ?> Ar</td>
                                    <td><?= number_format($l['sous_total'], 0, ',', ' ') ?> Ar</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                            <tr>
                                <td colspan="6" style="text-align:right; font-weight:700;">Total</td>
                                <td style="font-weight:700;"><?= number_format($appro['total_achat'], 0, ',', ' ') ?> Ar</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                                <div style="margin-top:20px; display:flex; gap:10px; flex-wrap:wrap; align-items:center;">
                    <?php if ($appro['statut'] === 'brouillon'): ?>
                        <form method="POST" id="formValidation">
                            <button type="button" class="btn btn-primary" onclick="confirmerValidation()">✅ Valider et mettre à jour le stock</button>
                        </form>
                        <a href="/approvisionnements/modifier.php?id=<?= $appro['id_approvisionnement'] ?>" class="btn btn-outline">✏️ Modifier</a>
                        <button type="button" class="btn-danger-text"
                            onclick="confirmerSuppression('/approvisionnements/annuler.php?id=<?= $appro['id_approvisionnement'] ?>', '<?= htmlspecialchars($appro['numero_appro'], ENT_QUOTES) ?>')">
                            Annuler cet achat
                        </button>
                    <?php else: ?>
                        <p style="color:var(--color-text-muted);">Cet approvisionnement a déjà été traité, il n'est plus modifiable.</p>
                    <?php endif; ?>
                </div>
                <div style="margin-top:14px;">
                    <a href="/approvisionnements/index.php" class="btn btn-outline">← Retour à la liste</a>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script>
        function confirmerValidation() {
            Swal.fire({
                title: 'Valider cet approvisionnement ?',
                text: 'Cette action va créer les lots et augmenter le stock. Elle est irréversible.',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Oui, valider',
                cancelButtonText: 'Annuler',
                confirmButtonColor: '#16a34a'
            }).then((result) => {
                if (result.isConfirmed) {
                    const form = document.getElementById('formValidation');
                    const champCache = document.createElement('input');
                    champCache.type = 'hidden';
                    champCache.name = 'valider';
                    champCache.value = '1';
                    form.appendChild(champCache);
                    form.submit();
                }
            });
        }
    </script>
</body>
</html>