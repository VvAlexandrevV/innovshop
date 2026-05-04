import './stimulus_bootstrap.js';
import './styles/app.css';

/**
 * MENU BURGER
 *
 * Fonctionnalité InnovShop :
 * Navigation responsive.
 *
 * Cette fonction :
 * - récupère le bouton burger
 * - récupère la navigation
 * - ouvre ou ferme le menu mobile au clic
 * - referme le menu quand l'utilisateur clique sur un lien
 */
function initBurgerMenu() {
    const burgerButton = document.querySelector('.burger-btn');
    const navLinks = document.querySelector('.nav-links');

    if (!burgerButton || !navLinks) {
        return;
    }

    if (burgerButton.dataset.initialized === 'true') {
        return;
    }

    burgerButton.dataset.initialized = 'true';

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
 *
 * Fonctionnalité InnovShop :
 * Catalogue produits dynamique.
 *
 * Cette fonction permet de filtrer les produits sans recharger toute la page :
 * - recherche texte
 * - prix minimum
 * - prix maximum
 * - tri
 * - catégories
 * - pagination AJAX
 */
function initCatalogAjaxSearch() {
    const form = document.getElementById('catalog-search-form');
    const results = document.getElementById('catalog-results');

    if (!form || !results) {
        return;
    }

    if (form.dataset.initialized === 'true') {
        return;
    }

    form.dataset.initialized = 'true';

    const searchUrl = results.dataset.searchUrl;

    const searchInput = document.getElementById('catalog-search-input');
    const minPriceInput = document.getElementById('catalog-min-price');
    const maxPriceInput = document.getElementById('catalog-max-price');
    const triSelect = document.getElementById('catalog-tri-select');
    const categoryTabs = document.querySelectorAll('.category-tab');

    if (!searchInput || !minPriceInput || !maxPriceInput || !triSelect || !searchUrl) {
        return;
    }

    let selectedCategory = document.querySelector('.category-tab.active')?.dataset.categoryId || '0';
    let debounceTimer;

    /**
     * Recharge la grille produits via AJAX.
     *
     * Le header X-Requested-With permet à Symfony de savoir
     * que la requête vient de JavaScript.
     */
    function fetchProducts(page = null) {
        const params = new URLSearchParams();

        if (searchInput.value) params.set('q', searchInput.value);
        if (minPriceInput.value) params.set('minPrice', minPriceInput.value);
        if (maxPriceInput.value) params.set('maxPrice', maxPriceInput.value);
        if (triSelect.value) params.set('tri', triSelect.value);
        if (selectedCategory !== '0') params.set('category', selectedCategory);
        if (page) params.set('page', page);

        results.classList.add('catalog-results-loading');

        fetch(`${searchUrl}?${params.toString()}`, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        })
            .then((res) => {
                if (!res.ok) {
                    throw new Error('Erreur AJAX catalogue');
                }

                return res.text();
            })
            .then((html) => {
                results.innerHTML = html;
            })
            .catch((error) => {
                console.error(error);
            })
            .finally(() => {
                results.classList.remove('catalog-results-loading');
            });
    }

    /**
     * Retarde la recherche pendant la saisie.
     *
     * Objectif :
     * éviter d'envoyer une requête AJAX à chaque frappe clavier.
     */
    function debounceFetch() {
        clearTimeout(debounceTimer);

        debounceTimer = setTimeout(() => {
            fetchProducts();
        }, 300);
    }

    results.addEventListener('click', (e) => {
        const paginationLink = e.target.closest('.pagination a');

        if (!paginationLink) {
            return;
        }

        e.preventDefault();

        const url = new URL(paginationLink.href);
        const page = url.searchParams.get('page');

        fetchProducts(page);

        window.scrollTo({
            top: form.offsetTop - 120,
            behavior: 'smooth',
        });
    });

    searchInput.addEventListener('input', debounceFetch);
    minPriceInput.addEventListener('input', debounceFetch);
    maxPriceInput.addEventListener('input', debounceFetch);

    triSelect.addEventListener('change', () => {
        fetchProducts();
    });

    categoryTabs.forEach((tab) => {
        tab.addEventListener('click', (e) => {
            e.preventDefault();

            selectedCategory = tab.dataset.categoryId;

            categoryTabs.forEach((t) => t.classList.remove('active'));
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
 * PRIX DYNAMIQUE FICHE PRODUIT
 *
 * Fonctionnalité InnovShop :
 * Fiche produit avec variantes.
 *
 * Cette fonction :
 * - lit le prix de base du produit
 * - ajoute les modificateurs de prix des variantes cochées
 * - met à jour le prix affiché
 * - active ou désactive le bouton panier selon le stock
 */
function initProductDynamicPrice() {
    const form = document.querySelector('#product-add-form');
    const priceElement = document.querySelector('#product-dynamic-price');
    const addToCartButton = document.querySelector('#add-to-cart-button');
    const variantInputs = document.querySelectorAll('.variant-price-input');

    if (!form || !priceElement || !addToCartButton) {
        return;
    }

    if (form.dataset.priceInitialized === 'true') {
        return;
    }

    form.dataset.priceInitialized = 'true';

    const basePrice = parseFloat(
        String(priceElement.dataset.basePrice || '0').replace(',', '.')
    );

    const baseStock = parseInt(form.dataset.baseStock || '0', 10);

    /**
     * Calcule le prix final du produit.
     *
     * Règles :
     * - prix final = prix de base + variantes sélectionnées
     * - si le produit de base a du stock, le bouton reste actif
     * - sinon, il faut au moins une variante disponible sélectionnée
     */
    function updateProductPriceAndButton() {
        let finalPrice = basePrice;
        let hasSelectedAvailableVariant = false;

        variantInputs.forEach((variantInput) => {
            if (!variantInput.checked) {
                return;
            }

            const modifier = parseFloat(
                String(variantInput.dataset.priceModifier || '0').replace(',', '.')
            );

            const variantStock = parseInt(variantInput.dataset.stock || '0', 10);

            finalPrice += modifier;

            if (variantStock > 0) {
                hasSelectedAvailableVariant = true;
            }
        });

        priceElement.textContent = finalPrice.toFixed(2).replace('.', ',') + ' €';

        if (baseStock > 0) {
            addToCartButton.disabled = false;
            return;
        }

        addToCartButton.disabled = !hasSelectedAvailableVariant;
    }

    variantInputs.forEach((variantInput) => {
        variantInput.addEventListener('change', updateProductPriceAndButton);
        variantInput.addEventListener('click', updateProductPriceAndButton);
    });

    updateProductPriceAndButton();
}

/**
 * INIT GLOBAL
 *
 * Lance les scripts du site.
 *
 * Chaque fonction vérifie elle-même si les éléments existent.
 * Donc ce fichier peut être chargé sur toutes les pages sans erreur.
 */
function initApp() {
    initBurgerMenu();
    initCatalogAjaxSearch();
    initProductDynamicPrice();
}

document.addEventListener('DOMContentLoaded', initApp);

/**
 * INIT TURBO
 *
 * Relance les scripts si Turbo recharge une page
 * sans rechargement complet.
 */
document.addEventListener('turbo:load', initApp);