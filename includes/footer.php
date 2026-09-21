<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="/assets/js/alerts.js"></script>
<script>
    const toggleBtn = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    if (toggleBtn && sidebar) {
        toggleBtn.addEventListener('click', () => {
            sidebar.classList.toggle('open');
        });
    }

    <?php if (!empty($_SESSION['flash_success'])): ?>
        alertSuccess(<?= json_encode($_SESSION['flash_success']) ?>);
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        alertError(<?= json_encode($_SESSION['flash_error']) ?>);
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_warning'])): ?>
        alertWarning(<?= json_encode($_SESSION['flash_warning']) ?>);
        <?php unset($_SESSION['flash_warning']); ?>
    <?php endif; ?>
</script>

<!-- ========== MON COMPTE ========== -->
<script>
(function () {
    const btnOuvrir = document.getElementById('btnMonCompte');
    const modal = document.getElementById('modalMonCompte');
    const btnFermer = document.getElementById('btnFermerMonCompte');
    if (!btnOuvrir || !modal) return;

    const toggleModifier = document.getElementById('toggleModifierProfil');
    const champsProfil = [document.getElementById('mc_nom'), document.getElementById('mc_prenom'), document.getElementById('mc_email')];
    const actionsProfil = document.getElementById('actionsProfil');
    const avatarOptions = document.querySelectorAll('.avatar-option');
    let avatarSelectionne = document.querySelector('.avatar-option.selected')?.dataset.avatar || 'avatar-1';

    function ouvrirModal() {
        modal.classList.add('open');
    }
    function fermerModal() {
        modal.classList.remove('open');
    }

    btnOuvrir.addEventListener('click', ouvrirModal);
    btnOuvrir.addEventListener('keydown', (e) => {
        if (e.key === 'Enter' || e.key === ' ') { e.preventDefault(); ouvrirModal(); }
    });
    btnFermer.addEventListener('click', fermerModal);

    // Gestion des onglets
    document.querySelectorAll('.mc-tab').forEach(tab => {
        tab.addEventListener('click', () => {
            document.querySelectorAll('.mc-tab').forEach(t => t.classList.remove('active'));
            document.querySelectorAll('.mc-tab-content').forEach(c => c.classList.remove('active'));
            tab.classList.add('active');
            document.getElementById('mc-tab-' + tab.dataset.tab).classList.add('active');
        });
    });

    modal.addEventListener('click', (e) => { if (e.target === modal) fermerModal(); });

    // Toggle Modifier
    toggleModifier.addEventListener('change', () => {
        const actif = toggleModifier.checked;
        champsProfil.forEach(champ => champ.disabled = !actif);
        actionsProfil.style.display = actif ? 'flex' : 'none';
        avatarOptions.forEach(opt => opt.classList.toggle('disabled', !actif));
    });

    // Sélection avatar
    avatarOptions.forEach(opt => {
        opt.addEventListener('click', () => {
            if (opt.classList.contains('disabled')) return;
            avatarOptions.forEach(o => o.classList.remove('selected'));
            opt.classList.add('selected');
            avatarSelectionne = opt.dataset.avatar;
        });
    });

    function afficherAlerte(idAlerte, message, type) {
        const el = document.getElementById(idAlerte);
        el.textContent = message;
        el.style.display = 'block';
        el.style.background = type === 'success' ? '#dcfce7' : '#fee2e2';
        el.style.color = type === 'success' ? '#16a34a' : '#dc2626';
    }

    // Enregistrer le profil
    document.getElementById('btnEnregistrerProfil').addEventListener('click', () => {
        const nom = document.getElementById('mc_nom').value.trim();
        const prenom = document.getElementById('mc_prenom').value.trim();
        const email = document.getElementById('mc_email').value.trim();

        fetch('/mon-compte/mettre-a-jour.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ nom, prenom, email, avatar: avatarSelectionne }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    fermerModal();
                    Swal.fire({ icon: 'success', title: 'Succès', text: data.message, confirmButtonText: 'OK' })
                        .then(() => location.reload());
                } else {
                    afficherAlerte('alertProfil', data.message, 'error');
                }
            })
            .catch(() => afficherAlerte('alertProfil', 'Erreur de connexion au serveur.', 'error'));
    });

    // Changer le mot de passe
    document.getElementById('btnChangerMdp').addEventListener('click', () => {
        const mdpActuel = document.getElementById('mc_mdp_actuel').value;
        const mdpNouveau = document.getElementById('mc_mdp_nouveau').value;
        const mdpConfirmation = document.getElementById('mc_mdp_confirmation').value;

        fetch('/mon-compte/changer-mot-de-passe.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ mdp_actuel: mdpActuel, mdp_nouveau: mdpNouveau, mdp_confirmation: mdpConfirmation }),
        })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    afficherAlerte('alertMotDePasse', data.message, 'success');
                    document.getElementById('mc_mdp_actuel').value = '';
                    document.getElementById('mc_mdp_nouveau').value = '';
                    document.getElementById('mc_mdp_confirmation').value = '';
                } else {
                    afficherAlerte('alertMotDePasse', data.message, 'error');
                }
            })
            .catch(() => afficherAlerte('alertMotDePasse', 'Erreur de connexion au serveur.', 'error'));
    });
})();
</script>

<!-- BOUTON RETOUR EN HAUT -->
<button type="button" id="btnRetourHaut" title="Retour en haut" aria-label="Retour en haut">
    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
        <line x1="12" y1="19" x2="12" y2="5"></line>
        <polyline points="5 12 12 5 19 12"></polyline>
    </svg>
</button>

<script>
    (function () {
        const btn = document.getElementById('btnRetourHaut');
        if (!btn) return;

        const SEUIL = 150; // px avant que le bouton apparaisse

        function verifierScroll() {
            btn.classList.toggle('visible', window.scrollY > SEUIL);
        }

        window.addEventListener('scroll', verifierScroll);
        window.addEventListener('resize', verifierScroll);
        verifierScroll(); // vérifie tout de suite au chargement (si la page arrive déjà scrollée)

        btn.addEventListener('click', () => {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        });
    })();
</script>