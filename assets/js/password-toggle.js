const togglePasswordVisibility = (button) => {
    const field = button.closest('.password-field');

    if (!field) {
        return;
    }

    const input = field.querySelector('.password-input');

    if (!input) {
        return;
    }

    const passwordIsHidden = input.type === 'password';

    input.type = passwordIsHidden ? 'text' : 'password';

    button.classList.toggle('is-visible', passwordIsHidden);

    button.setAttribute(
        'aria-label',
        passwordIsHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
    );
};

document.addEventListener('click', (event) => {
    const button = event.target.closest('[data-password-toggle]');

    if (!button) {
        return;
    }

    event.preventDefault();

    togglePasswordVisibility(button);
});