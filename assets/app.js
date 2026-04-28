import './stimulus_bootstrap.js';
import './styles/app.css';

/**
 * MENU BURGER
 */
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

/**
 * RECHERCHE AJAX CATALOGUE
 */
function initCatalogAjaxSearch() {
    console.log('AJAX catalogue actif');

    const form = document.getElementById('catalog-search-form');
    const results = document.getElementById('catalog-results');

    if (!form || !results) {
        return;
    }

    const searchUrl = results.dataset.searchUrl;

    const searchInput = document.getElementById('catalog-search-input');
    const minPriceInput = document.getElementById('catalog-min-price');
    const maxPriceInput = document.getElementById('catalog-max-price');
    const triSelect = document.getElementById('catalog-tri-select');
    const categoryTabs = document.querySelectorAll('.category-tab');

    let selectedCategory = document.querySelector('.category-tab.active')?.dataset.categoryId || '0';

    let debounceTimer;

    function fetchProducts() {
        const params = new URLSearchParams();

        if (searchInput.value) params.set('q', searchInput.value);
        if (minPriceInput.value) params.set('minPrice', minPriceInput.value);
        if (maxPriceInput.value) params.set('maxPrice', maxPriceInput.value);
        if (triSelect.value) params.set('tri', triSelect.value);
        if (selectedCategory !== '0') params.set('category', selectedCategory);

        fetch(`${searchUrl}?${params.toString()}`)
            .then(res => res.text())
            .then(html => {
                results.innerHTML = html;
            });
    }

    function debounceFetch() {
        clearTimeout(debounceTimer);
        debounceTimer = setTimeout(fetchProducts, 300);
    }

    // EVENTS
    searchInput.addEventListener('input', debounceFetch);
    minPriceInput.addEventListener('input', debounceFetch);
    maxPriceInput.addEventListener('input', debounceFetch);
    triSelect.addEventListener('change', fetchProducts);

    categoryTabs.forEach(tab => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();

            selectedCategory = tab.dataset.categoryId;

            categoryTabs.forEach(t => t.classList.remove('active'));
            tab.classList.add('active');

            fetchProducts();
        });
    });

    form.addEventListener('submit', (e) => {
        e.preventDefault();
        fetchProducts();
    });
}

/**
 * INIT GLOBAL
 */
document.addEventListener('DOMContentLoaded', () => {
    initBurgerMenu();
    initCatalogAjaxSearch();
});

document.addEventListener('turbo:load', () => {
    initBurgerMenu();
    initCatalogAjaxSearch();
});