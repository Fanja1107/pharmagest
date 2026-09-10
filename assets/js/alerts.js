/**
 * Helpers SweetAlert2 réutilisables sur tout le projet
 */

function alertSuccess(message) {
    Swal.fire({
        icon: 'success',
        title: 'Succès',
        text: message,
        confirmButtonText: 'OK'
    });
}

function alertError(message) {
    Swal.fire({
        icon: 'error',
        title: 'Erreur',
        text: message,
        confirmButtonText: 'OK'
    });
}

/**
 * Affiche une confirmation avant suppression.
 * Si confirmé, redirige vers l'URL fournie.
 */
function confirmerSuppression(url, nomElement) {
    Swal.fire({
        title: 'Êtes-vous sûr ?',
        text: `Supprimer "${nomElement}" ? Cette action est irréversible.`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Oui, supprimer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#dc2626'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}