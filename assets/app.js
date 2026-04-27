import './stimulus_bootstrap.js';
import './styles/app.css';

function initBurgerMenu() {
    const burgerButton = document.querySelector('.burger-btn');
    const navLinks = document.querySelector('.nav-links');

    if (!burgerButton || !navLinks) {
        return;
    }

    burgerButton.addEventListener('click', () => {
        burgerButton.classList.toggle('is-active');
        navLinks.classList.toggle('is-open');
    });

    navLinks.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            burgerButton.classList.remove('is-active');
            navLinks.classList.remove('is-open');
        });
    });
}

document.addEventListener('DOMContentLoaded', initBurgerMenu);
document.addEventListener('turbo:load', initBurgerMenu);