document.addEventListener('DOMContentLoaded', () => {
    const passwordToggles = document.querySelectorAll('[data-password-toggle]');

    passwordToggles.forEach((button) => {
        button.addEventListener('click', () => {
            const field = button.closest('.password-field');

            if (!field) {
                return;
            }

            const input = field.querySelector('.password-input');

            if (!input) {
                return;
            }

            const isPasswordHidden = input.type === 'password';

            input.type = isPasswordHidden ? 'text' : 'password';

            button.classList.toggle('is-visible', isPasswordHidden);

            button.setAttribute(
                'aria-label',
                isPasswordHidden ? 'Masquer le mot de passe' : 'Afficher le mot de passe'
            );
        });
    });
});