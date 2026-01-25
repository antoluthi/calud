// Chargement et affichage des produits
async function loadProducts() {
    try {
        const response = await fetch('api/produits.php');
        const data = await response.json();

        if (data.success && data.produits) {
            // Remplir la variable globale products pour le panier
            window.products = data.produits;
            displayProducts(data.produits);
        } else {
            throw new Error('Erreur lors de la récupération des produits');
        }
    } catch (error) {
        console.error('Erreur lors du chargement des produits:', error);
        const grid = document.getElementById('productsGrid') || document.getElementById('products-grid');
        if (grid) {
            grid.innerHTML = '<p style="color: var(--text-secondary); text-align: center;">Erreur lors du chargement des produits.</p>';
        }
    }
}

// Affichage des produits dans la grille
function displayProducts(productsData) {
    const grid = document.getElementById('productsGrid') || document.getElementById('products-grid');
    if (!grid) return;

    grid.innerHTML = '';

    productsData.forEach(product => {
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
        ? `<img src="${product.image}" alt="${product.name}">`
        : `<div class="product-image">🧗</div>`;

    // Features list
    const featuresHTML = product.features && product.features.length > 0
        ? `<ul class="product-features">
            ${product.features.map(feature => `<li>${feature}</li>`).join('')}
           </ul>`
        : '';

    // Size selector
    const sizesHTML = product.sizes && product.sizes.length > 0
        ? `<div class="size-selector">
            <label>Taille</label>
            <select id="size-${product.id}">
                ${product.sizes.map(size => `<option value="${size}">${size}</option>`).join('')}
            </select>
           </div>`
        : '';

    card.innerHTML = `
        ${product.image
            ? `<div class="product-image"><img src="${product.image}" alt="${product.name}"></div>`
            : `<div class="product-image"></div>`
        }
        <div class="product-info">
            <h3>${product.name}</h3>
            <div class="price">${product.price.toFixed(2)} €</div>
            <p>${product.description}</p>
            ${featuresHTML}
            ${sizesHTML}
            <button class="add-to-cart-btn" onclick="addToCart(${product.id})">Ajouter au panier</button>
        </div>
    `;

    return card;
}

// Vérifier l'état d'authentification
async function checkAuthStatus() {
    try {
        const response = await fetch('api/auth/status.php');
        const data = await response.json();

        const loginBtn = document.getElementById('login-btn');
        const userProfile = document.getElementById('user-profile');
        const userAvatar = document.getElementById('user-avatar');
        const userName = document.getElementById('user-name');

        if (data.authenticated && data.user) {
            // Utilisateur connecté - afficher le profil
            loginBtn.style.display = 'none';
            userProfile.style.display = 'flex';
            userAvatar.src = data.user.picture;
            userName.textContent = data.user.name;

            // Mettre à jour currentUser pour le reste de l'app (panier, contact)
            if (typeof window.currentUser !== 'undefined') {
                window.currentUser = {
                    name: data.user.name,
                    email: data.user.email,
                    avatar: data.user.picture
                };
            }

            // Mettre à jour le formulaire de contact
            if (typeof window.updateContactForm === 'function') {
                window.updateContactForm();
            }
        } else {
            // Utilisateur non connecté - afficher le bouton de connexion
            loginBtn.style.display = 'flex';
            userProfile.style.display = 'none';

            if (typeof window.currentUser !== 'undefined') {
                window.currentUser = null;
            }

            if (typeof window.updateContactForm === 'function') {
                window.updateContactForm();
            }
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'authentification:', error);
        // En cas d'erreur, afficher le bouton de connexion
        document.getElementById('login-btn').style.display = 'flex';
        if (typeof window.currentUser !== 'undefined') {
            window.currentUser = null;
        }
    }
}

// Gérer la connexion
function handleLogin() {
    window.location.href = 'api/auth/login.php';
}

// Gérer la déconnexion
async function handleLogout() {
    try {
        const response = await fetch('api/auth/logout.php');
        const data = await response.json();

        if (data.success) {
            // Recharger la page pour mettre à jour l'état
            window.location.reload();
        }
    } catch (error) {
        console.error('Erreur lors de la déconnexion:', error);
    }
}

// Smooth scroll pour la navigation
document.addEventListener('DOMContentLoaded', () => {
    // Charger les produits
    loadProducts();

    // Vérifier l'état d'authentification
    checkAuthStatus();

    // Event listeners pour les boutons d'authentification
    const loginBtn = document.getElementById('login-btn');
    const logoutBtn = document.getElementById('logout-btn');

    if (loginBtn) {
        loginBtn.addEventListener('click', handleLogin);
    }

    if (logoutBtn) {
        logoutBtn.addEventListener('click', handleLogout);
    }

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
