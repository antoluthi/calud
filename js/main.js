// Chargement et affichage des produits
async function loadProducts() {
    try {
        const response = await fetch('data/produits.json');
        const products = await response.json();
        displayProducts(products);
    } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
        document.getElementById('products-grid').innerHTML =
            '<p style="color: var(--text-secondary); text-align: center;">Erreur lors du chargement des produits.</p>';
    }
}

// Affichage des produits dans la grille
function displayProducts(products) {
    const grid = document.getElementById('products-grid');
    grid.innerHTML = '';

    products.forEach(product => {
        const card = createProductCard(product);
        grid.appendChild(card);
    });
}

// Création d'une carte produit
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';

    // Image (ou placeholder si pas d'image)
    const imageHTML = product.image
        ? `<img src="${product.image}" alt="${product.nom}">`
        : `<div class="product-image">🧗</div>`;

    // Features list
    const featuresHTML = product.caracteristiques
        ? `<ul class="product-features">
            ${product.caracteristiques.map(feature => `<li>${feature}</li>`).join('')}
           </ul>`
        : '';

    card.innerHTML = `
        ${product.image
            ? `<div class="product-image"><img src="${product.image}" alt="${product.nom}"></div>`
            : `<div class="product-image">🧗</div>`
        }
        <div class="product-info">
            <h3>${product.nom}</h3>
            <p class="price">${product.prix}</p>
            <p>${product.description}</p>
            ${featuresHTML}
        </div>
    `;

    return card;
}

// Smooth scroll pour la navigation
document.addEventListener('DOMContentLoaded', () => {
    // Charger les produits
    loadProducts();

    // Smooth scroll pour les liens de navigation
    document.querySelectorAll('a[href^="#"]').forEach(anchor => {
        anchor.addEventListener('click', function (e) {
            e.preventDefault();
            const target = document.querySelector(this.getAttribute('href'));
            if (target) {
                target.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start'
                });
            }
        });
    });
});
