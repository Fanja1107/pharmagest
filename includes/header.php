<?php
require_once __DIR__ . '/functions.php';

$stmtHeaderUser = $pdo->prepare('SELECT nom, prenom, email, role, avatar FROM utilisateurs WHERE id_utilisateur = ?');
$stmtHeaderUser->execute([$_SESSION['id_utilisateur']]);
$utilisateurCourant = $stmtHeaderUser->fetch();

$avatarsDisponibles = ['avatar-1', 'avatar-2', 'avatar-3', 'avatar-4', 'avatar-5', 'avatar-6', 'avatar-7', 'avatar-8'];
$avatarActuel = $utilisateurCourant['avatar'] ?? 'avatar-1';
?>
<header class="topbar">
    <button class="topbar-toggle" id="sidebarToggle">☰</button>

    <div class="topbar-user" id="btnMonCompte" role="button" tabindex="0">
        <span class="topbar-avatar"><?= avatarSvg($avatarActuel, 32) ?></span>
        <span class="topbar-name"><?= htmlspecialchars(trim($utilisateurCourant['prenom'] . ' ' . $utilisateurCourant['nom'])) ?></span>
        <span class="role-badge"><?= htmlspecialchars(ucfirst($utilisateurCourant['role'])) ?></span>
    </div>
</header>

<!-- MODALE MON COMPTE -->
<div class="modal-overlay" id="modalMonCompte">
    <div class="modal-box">
        <button type="button" class="modal-close" id="btnFermerMonCompte">&times;</button>
        <h3>Mon compte</h3>

        <div class="mc-tabs">
            <button type="button" class="mc-tab active" data-tab="profil">Profil</button>
            <button type="button" class="mc-tab" data-tab="securite">Sécurité</button>
        </div>

        <!-- ONGLET PROFIL -->
        <div class="mc-tab-content active" id="mc-tab-profil">
            <div class="toggle-row">
                <span>Modifier mes informations</span>
                <label class="toggle-switch">
                    <input type="checkbox" id="toggleModifierProfil">
                    <span class="toggle-slider"></span>
                </label>
            </div>

            <div id="alertProfil" style="display:none; margin-bottom:14px; padding:10px 12px; border-radius:8px; font-size:0.85rem;"></div>

            <div class="avatar-picker" id="avatarPicker">
                <?php foreach ($avatarsDisponibles as $av): ?>
                    <div class="avatar-option disabled <?= $avatarActuel === $av ? 'selected' : '' ?>" data-avatar="<?= $av ?>">
                        <?= avatarSvg($av, 40) ?>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="form-group">
                <label for="mc_nom">Nom</label>
                <input type="text" id="mc_nom" value="<?= htmlspecialchars($utilisateurCourant['nom']) ?>" disabled>
            </div>
            <div class="form-group">
                <label for="mc_prenom">Prénom</label>
                <input type="text" id="mc_prenom" value="<?= htmlspecialchars($utilisateurCourant['prenom'] ?? '') ?>" disabled>
            </div>
            <div class="form-group">
                <label for="mc_email">Email</label>
                <input type="email" id="mc_email" value="<?= htmlspecialchars($utilisateurCourant['email']) ?>" disabled>
            </div>

            <div class="form-actions" id="actionsProfil" style="display:none;">
                <button type="button" class="btn btn-primary" id="btnEnregistrerProfil">Enregistrer</button>
            </div>
        </div>

        <!-- ONGLET SÉCURITÉ -->
        <div class="mc-tab-content" id="mc-tab-securite">
            <div id="alertMotDePasse" style="display:none; margin-bottom:14px; padding:10px 12px; border-radius:8px; font-size:0.85rem;"></div>

            <div class="form-group">
                <label for="mc_mdp_actuel">Mot de passe actuel</label>
                <input type="password" id="mc_mdp_actuel" autocomplete="current-password">
            </div>
            <div class="form-group">
                <label for="mc_mdp_nouveau">Nouveau mot de passe (min. 6 caractères)</label>
                <input type="password" id="mc_mdp_nouveau" minlength="6" autocomplete="new-password">
            </div>
            <div class="form-group">
                <label for="mc_mdp_confirmation">Confirmer le nouveau mot de passe</label>
                <input type="password" id="mc_mdp_confirmation" minlength="6" autocomplete="new-password">
            </div>
            <div class="form-actions">
                <button type="button" class="btn btn-primary" id="btnChangerMdp">Changer le mot de passe</button>
            </div>
        </div>
    </div>
</div>