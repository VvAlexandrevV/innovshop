console.log('password-toggle.js chargé');

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');

    if (!button) {
        return;
    }

    const field = button.closest('.password-field');

    if (!field) {
        console.warn('Aucun .password-field trouvé');
        return;
    }

    const input = field.querySelector('.password-input');

    if (!input) {
        console.warn('Aucun .password-input trouvé');
        return;
    }

    const passwordIsHidden = input.type === 'password';

    input.type = passwordIsHidden ? 'text' : 'password';

    button.classList.toggle('is-visible', passwordIsHidden);

    button.setAttribute(
        'aria-label',
        passwordIsHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
    );
});