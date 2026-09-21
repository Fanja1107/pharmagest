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

function alertWarning(message) {
    Swal.fire({
        icon: 'warning',
        title: 'Attention',
        html: message,
        confirmButtonText: 'Compris'
    });
}

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
/**
 * Confirmation générique pour une action non destructive (ex. réinitialisation).
 */
function confirmerAction(url, titre, texte) {
    Swal.fire({
        title: titre,
        text: texte,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: 'Oui, continuer',
        cancelButtonText: 'Annuler',
        confirmButtonColor: '#2563eb'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = url;
        }
    });
}