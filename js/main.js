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

        const profileIconDefault = document.getElementById('profileIconDefault');
        const profileIconAvatar = document.getElementById('profileIconAvatar');
        const profileDropdown = document.getElementById('profileDropdown');

        if (data.authenticated && data.user) {
            // Utilisateur connecté - afficher l'avatar
            profileIconDefault.style.display = 'none';
            profileIconAvatar.style.display = 'block';
            profileIconAvatar.src = data.user.picture;

            // Contenu du dropdown pour utilisateur connecté
            profileDropdown.innerHTML = `
                <div class="profile-dropdown-header">
                    <img src="${data.user.picture}" alt="Avatar" class="profile-dropdown-avatar">
                    <div class="profile-dropdown-info">
                        <div class="profile-dropdown-name">${data.user.name}</div>
                        <div class="profile-dropdown-email">${data.user.email}</div>
                    </div>
                </div>
                <button class="profile-dropdown-item" onclick="handleLogout()">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17 7l-1.41 1.41L18.17 11H8v2h10.17l-2.58 2.58L17 17l5-5zM4 5h8V3H4c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h8v-2H4V5z"/>
                    </svg>
                    Déconnexion
                </button>
            `;

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
            // Utilisateur non connecté - afficher l'icône par défaut
            profileIconDefault.style.display = 'block';
            profileIconAvatar.style.display = 'none';

            // Contenu du dropdown pour utilisateur non connecté
            profileDropdown.innerHTML = `
                <button class="profile-dropdown-item" onclick="handleLogin()">
                    <svg width="20" height="20" viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                        <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                        <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.836.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.438 15.983 5.482 18 9.003 18z" fill="#34A853"/>
                        <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                        <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.48 0 2.438 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                    </svg>
                    Se connecter avec Google
                </button>
            `;

            if (typeof window.currentUser !== 'undefined') {
                window.currentUser = null;
            }

            if (typeof window.updateContactForm === 'function') {
                window.updateContactForm();
            }
        }
    } catch (error) {
        console.error('Erreur lors de la vérification de l\'authentification:', error);
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

// Toggle profile dropdown
function toggleProfileDropdown() {
    const dropdown = document.getElementById('profileDropdown');
    dropdown.classList.toggle('active');
}

// Fermer le dropdown si on clique ailleurs
document.addEventListener('click', (e) => {
    const profileIcon = document.getElementById('profileIcon');
    const dropdown = document.getElementById('profileDropdown');

    if (profileIcon && !profileIcon.contains(e.target) && !dropdown.contains(e.target)) {
        dropdown.classList.remove('active');
    }
});

// Smooth scroll pour la navigation
document.addEventListener('DOMContentLoaded', () => {
    // Charger les produits
    loadProducts();

    // Vérifier l'état d'authentification
    checkAuthStatus();

    // Event listener pour l'icône de profil
    const profileIcon = document.getElementById('profileIcon');
    if (profileIcon) {
        profileIcon.addEventListener('click', toggleProfileDropdown);
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
