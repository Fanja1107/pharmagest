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

    // Affiche les messages transmis en session (succès/erreur après redirection)
    <?php if (!empty($_SESSION['flash_success'])): ?>
        alertSuccess(<?= json_encode($_SESSION['flash_success']) ?>);
        <?php unset($_SESSION['flash_success']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        alertError(<?= json_encode($_SESSION['flash_error']) ?>);
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>
</script>