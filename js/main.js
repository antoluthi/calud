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
            <button class="add-to-cart-btn" onclick="event.stopPropagation(); addToCart(${product.id})">Ajouter au panier</button>
        </div>
    `;

    // Ajouter le click pour ouvrir la modal
    card.addEventListener('click', (e) => {
        // Ne pas ouvrir si on clique sur le select ou le bouton
        if (e.target.tagName === 'SELECT' || e.target.tagName === 'OPTION' || e.target.tagName === 'BUTTON') {
            return;
        }
        openProductModal(product.id);
    });

    return card;
}

// ========== Product Modal Functions ==========

// Variable pour stocker le produit actuellement affiché dans la modal
let currentModalProduct = null;

// Ouvrir la modal produit
function openProductModal(productId) {
    const product = window.products.find(p => p.id === productId);
    if (!product) return;

    currentModalProduct = product;

    // Titre et prix
    document.getElementById('modalProductTitle').textContent = product.name;
    document.getElementById('modalProductPrice').textContent = `${product.price.toFixed(2)} €`;
    document.getElementById('modalProductDescription').textContent = product.description;

    // Image principale
    const mainImageContainer = document.getElementById('modalMainImage');
    const allImages = [];

    // Ajouter l'image principale si elle existe
    if (product.image) {
        allImages.push(product.image);
    }

    // Ajouter les images supplémentaires
    if (product.images && product.images.length > 0) {
        product.images.forEach(img => {
            if (img && !allImages.includes(img)) {
                allImages.push(img);
            }
        });
    }

    // Afficher l'image principale
    if (allImages.length > 0) {
        mainImageContainer.innerHTML = `<img src="${allImages[0]}" alt="${product.name}" id="modalMainImg">`;
    } else {
        mainImageContainer.innerHTML = '<span class="placeholder">🧗</span>';
    }

    // Afficher les miniatures
    const thumbnailsContainer = document.getElementById('modalThumbnails');
    if (allImages.length > 1) {
        thumbnailsContainer.innerHTML = allImages.map((img, index) => `
            <div class="thumbnail ${index === 0 ? 'active' : ''}" onclick="setMainImage('${img}', this)">
                <img src="${img}" alt="Image ${index + 1}">
            </div>
        `).join('');
        thumbnailsContainer.style.display = 'flex';
    } else {
        thumbnailsContainer.innerHTML = '';
        thumbnailsContainer.style.display = 'none';
    }

    // Specifications
    const specsSection = document.getElementById('modalSpecs');
    if (product.dimensions || product.poids) {
        document.getElementById('modalDimensions').textContent = product.dimensions || '-';
        document.getElementById('modalPoids').textContent = product.poids || '-';
        specsSection.style.display = 'block';
    } else {
        specsSection.style.display = 'none';
    }

    // Materiaux
    const materialsSection = document.getElementById('modalMaterials');
    if (product.materiaux) {
        document.getElementById('modalMateriauxText').textContent = product.materiaux;
        materialsSection.style.display = 'block';
    } else {
        materialsSection.style.display = 'none';
    }

    // Guide des tailles
    const sizeGuideSection = document.getElementById('modalSizeGuide');
    if (product.guide_tailles) {
        document.getElementById('modalSizeGuideText').textContent = product.guide_tailles;
        sizeGuideSection.style.display = 'block';
    } else {
        sizeGuideSection.style.display = 'none';
    }

    // Video YouTube
    const videoSection = document.getElementById('modalVideo');
    if (product.video_url) {
        const videoId = extractYouTubeId(product.video_url);
        if (videoId) {
            document.getElementById('modalVideoFrame').src = `https://www.youtube.com/embed/${videoId}`;
            videoSection.style.display = 'block';
        } else {
            videoSection.style.display = 'none';
        }
    } else {
        videoSection.style.display = 'none';
        document.getElementById('modalVideoFrame').src = '';
    }

    // Guide PDF
    const guidePdfSection = document.getElementById('modalGuidePdf');
    const guidePdfLink = document.getElementById('modalGuidePdfLink');
    if (product.guide_pdf) {
        guidePdfLink.href = product.guide_pdf;
        guidePdfSection.style.display = 'block';
    } else {
        guidePdfSection.style.display = 'none';
    }

    // Selecteur de taille
    const sizeSelect = document.getElementById('modalSizeSelect');
    if (product.sizes && product.sizes.length > 0) {
        sizeSelect.innerHTML = product.sizes.map(size =>
            `<option value="${size}">${size}</option>`
        ).join('');
    } else {
        sizeSelect.innerHTML = '<option value="Unique">Taille unique</option>';
    }

    // Afficher la modal
    document.getElementById('productModalOverlay').classList.add('active');
    document.body.style.overflow = 'hidden';
}

// Fermer la modal produit
function closeProductModal() {
    document.getElementById('productModalOverlay').classList.remove('active');
    document.body.style.overflow = '';
    // Arrêter la vidéo si elle est en cours
    document.getElementById('modalVideoFrame').src = '';
    currentModalProduct = null;
}

// Changer l'image principale au clic sur une miniature
function setMainImage(imageUrl, thumbnailElement) {
    // Mettre à jour l'image principale
    const mainImg = document.getElementById('modalMainImg');
    if (mainImg) {
        mainImg.src = imageUrl;
    }

    // Mettre à jour la classe active des miniatures
    document.querySelectorAll('.thumbnail').forEach(thumb => {
        thumb.classList.remove('active');
    });
    if (thumbnailElement) {
        thumbnailElement.classList.add('active');
    }
}

// Extraire l'ID YouTube d'une URL
function extractYouTubeId(url) {
    if (!url) return null;

    const patterns = [
        /(?:youtube\.com\/watch\?v=|youtu\.be\/|youtube\.com\/embed\/)([^&\n?#]+)/,
        /youtube\.com\/watch\?.*v=([^&\n?#]+)/
    ];

    for (const pattern of patterns) {
        const match = url.match(pattern);
        if (match && match[1]) {
            return match[1];
        }
    }
    return null;
}

// Ajouter au panier depuis la modal
function addToCartFromModal() {
    if (!currentModalProduct) return;

    const sizeSelect = document.getElementById('modalSizeSelect');
    const selectedSize = sizeSelect.value;

    const existingItem = window.cart.find(item =>
        item.id === currentModalProduct.id && item.size === selectedSize
    );

    if (existingItem) {
        existingItem.quantity++;
    } else {
        window.cart.push({
            id: currentModalProduct.id,
            name: currentModalProduct.name,
            price: currentModalProduct.price,
            size: selectedSize,
            quantity: 1
        });
    }

    updateCart();

    // Visual feedback
    const btn = document.getElementById('modalAddToCart');
    const originalText = btn.textContent;
    btn.textContent = '✓ Ajouté !';
    btn.style.backgroundColor = '#34a853';
    setTimeout(() => {
        btn.textContent = originalText;
        btn.style.backgroundColor = '';
    }, 1000);
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
            const adminButton = data.user.is_admin ? `
                <a href="admin/" class="profile-dropdown-item">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm-2 15l-5-5 1.41-1.41L10 14.17l7.59-7.59L19 8l-9 9z"/>
                    </svg>
                    Administration
                </a>
            ` : '';

            profileDropdown.innerHTML = `
                <div class="profile-dropdown-header">
                    <img src="${data.user.picture}" alt="Avatar" class="profile-dropdown-avatar">
                    <div class="profile-dropdown-info">
                        <div class="profile-dropdown-name">${data.user.name}</div>
                        <div class="profile-dropdown-email">${data.user.email}</div>
                    </div>
                </div>
                ${adminButton}
                <a href="mes-commandes.html" class="profile-dropdown-item">
                    <svg viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                        <path d="M19 3H5c-1.1 0-2 .9-2 2v14c0 1.1.9 2 2 2h14c1.1 0 2-.9 2-2V5c0-1.1-.9-2-2-2zm-2 10H7v-2h10v2zm0-4H7V7h10v2z"/>
                    </svg>
                    Mes commandes
                </a>
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

    // Event listeners pour la modal produit
    const productModalOverlay = document.getElementById('productModalOverlay');
    const closeProductModalBtn = document.getElementById('closeProductModal');
    const modalAddToCartBtn = document.getElementById('modalAddToCart');

    if (closeProductModalBtn) {
        closeProductModalBtn.addEventListener('click', closeProductModal);
    }

    if (productModalOverlay) {
        productModalOverlay.addEventListener('click', (e) => {
            if (e.target === productModalOverlay) {
                closeProductModal();
            }
        });
    }

    if (modalAddToCartBtn) {
        modalAddToCartBtn.addEventListener('click', addToCartFromModal);
    }

    // Fermer la modal avec Escape
    document.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            const modal = document.getElementById('productModalOverlay');
            if (modal && modal.classList.contains('active')) {
                closeProductModal();
            }
        }
    });

    // --- Easter egg: triple-clic sur le logo cycle la couleur d'accent ---
    (function () {
        const logoEl = document.querySelector('a.logo');
        if (!logoEl) return;
        const themes = [
            { accent: '#ffffff', hover: '#cccccc' },
            { accent: '#e75480', hover: '#c44570' },
            { accent: '#60a5fa', hover: '#4b8bd4' }
        ];
        let idx = 0;
        let clicks = 0;
        let timer = null;
        logoEl.addEventListener('click', function () {
            clicks++;
            clearTimeout(timer);
            timer = setTimeout(function () { clicks = 0; }, 500);
            if (clicks >= 3) {
                clicks = 0;
                idx = (idx + 1) % themes.length;
                var t = themes[idx];
                document.documentElement.style.setProperty('--accent', t.accent);
                document.documentElement.style.setProperty('--accent-hover', t.hover);
            }
        });
    })();

    // Typewriter animation for hero "Train ___."
    (function () {
        var el = document.getElementById('typewriter');
        if (!el) return;
        var words = ['At Home.', 'Outside.', 'At the Gym.', 'On the Go.', 'Anywhere.'];
        var wordIdx = 0;
        var charIdx = 0;
        var deleting = false;
        var typeSpeed = 100;
        var deleteSpeed = 60;
        var pauseEnd = 1500;
        var pauseDelete = 800;

        function tick() {
            var current = words[wordIdx];
            if (!deleting) {
                el.textContent = current.substring(0, charIdx + 1);
                charIdx++;
                if (charIdx === current.length) {
                    if (wordIdx === words.length - 1) return; // stop on "Anywhere."
                    setTimeout(function () { deleting = true; tick(); }, pauseEnd);
                    return;
                }
                setTimeout(tick, typeSpeed);
            } else {
                el.textContent = current.substring(0, charIdx - 1);
                charIdx--;
                if (charIdx === 0) {
                    deleting = false;
                    wordIdx++;
                    setTimeout(tick, pauseDelete);
                    return;
                }
                setTimeout(tick, deleteSpeed);
            }
        }

        setTimeout(tick, 600);
    })();

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
