// ========== 3D Viewer State ==========
let viewer3DState = null;
let threeJSLoaded = false;
let THREE_MODULE = null;
let GLTFLoaderClass = null;
let OrbitControlsClass = null;
let threeJSLoadingPromise = null;

// Charger Three.js dynamiquement (resolu via l'importmap dans index.html)
async function loadThreeJS() {
    // Si déjà chargé, retourner immédiatement
    if (threeJSLoaded) return Promise.resolve();

    // Si un chargement est en cours, attendre sa fin (évite les race conditions)
    if (threeJSLoadingPromise) return threeJSLoadingPromise;

    // Créer une Promise singleton pour le chargement
    threeJSLoadingPromise = (async () => {
        try {
            THREE_MODULE = await import('three');
            const gltfMod = await import('three/addons/loaders/GLTFLoader.js');
            GLTFLoaderClass = gltfMod.GLTFLoader;
            const orbitMod = await import('three/addons/controls/OrbitControls.js');
            OrbitControlsClass = orbitMod.OrbitControls;
            threeJSLoaded = true;
        } catch (e) {
            console.error('Erreur chargement Three.js:', e);
            threeJSLoadingPromise = null; // Reset pour permettre une nouvelle tentative
            throw e; // Propager l'erreur
        }
    })();

    return threeJSLoadingPromise;
}

// Initialiser le viewer 3D dans le conteneur d'image principale
function init3DViewer(modelUrl) {
    const container = document.getElementById('modalMainImage');
    if (!container || !THREE_MODULE) return;

    const T = THREE_MODULE;

    // Spinner
    const spinner = document.createElement('div');
    spinner.className = 'viewer3d-spinner';
    container.appendChild(spinner);

    // Scene
    const scene = new T.Scene();
    scene.background = new T.Color(0x181818);

    // Camera
    const width = container.clientWidth;
    const height = container.clientHeight;
    const camera = new T.PerspectiveCamera(45, width / height, 0.1, 100);
    camera.position.set(0, 0.5, 2.5);

    // Renderer
    const renderer = new T.WebGLRenderer({ antialias: true });
    renderer.setSize(width, height);
    renderer.setPixelRatio(Math.min(window.devicePixelRatio, 2));
    renderer.outputColorSpace = T.SRGBColorSpace;
    container.appendChild(renderer.domElement);

    // Controls
    const controls = new OrbitControlsClass(camera, renderer.domElement);
    controls.enableDamping = true;
    controls.dampingFactor = 0.08;
    controls.enableZoom = true;
    controls.enablePan = false;
    controls.autoRotate = true;
    controls.autoRotateSpeed = 2;

    // Lights
    const ambientLight = new T.AmbientLight(0xffffff, 0.8);
    scene.add(ambientLight);
    const dirLight = new T.DirectionalLight(0xffffff, 1.2);
    dirLight.position.set(2, 3, 2);
    scene.add(dirLight);
    const dirLight2 = new T.DirectionalLight(0xffffff, 0.4);
    dirLight2.position.set(-2, 1, -1);
    scene.add(dirLight2);

    // Load model
    const loader = new GLTFLoaderClass();
    loader.load(modelUrl, (gltf) => {
        const model = gltf.scene;

        // Center and scale model
        const box = new T.Box3().setFromObject(model);
        const center = box.getCenter(new T.Vector3());
        const size = box.getSize(new T.Vector3());
        const maxDim = Math.max(size.x, size.y, size.z);
        const scale = 1.5 / maxDim;
        model.scale.setScalar(scale);
        model.position.sub(center.multiplyScalar(scale));

        scene.add(model);
        spinner.remove();
    }, undefined, (error) => {
        console.error('Erreur chargement modele 3D:', error);
        spinner.remove();
        // Afficher un message d'erreur à l'utilisateur
        const errorMsg = document.createElement('div');
        errorMsg.style.cssText = 'position:absolute;top:50%;left:50%;transform:translate(-50%,-50%);color:#888;text-align:center;padding:20px;';
        errorMsg.innerHTML = '<div style="font-size:2rem;margin-bottom:10px;">⚠️</div><div>Impossible de charger le modèle 3D</div><div style="font-size:0.8rem;margin-top:5px;opacity:0.7;">Vérifiez votre connexion ou le chemin du fichier</div>';
        container.appendChild(errorMsg);
    });

    // Animation loop
    let animId;
    function animate() {
        animId = requestAnimationFrame(animate);
        controls.update();
        renderer.render(scene, camera);
    }
    animate();

    // Resize observer
    const resizeObserver = new ResizeObserver(() => {
        const w = container.clientWidth;
        const h = container.clientHeight;
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
        renderer.setSize(w, h);
    });
    resizeObserver.observe(container);

    // Store state for cleanup
    viewer3DState = { scene, camera, renderer, controls, animId, resizeObserver, container };
}

// Detruire le viewer 3D proprement
function destroy3DViewer() {
    if (!viewer3DState) return;
    const { scene, renderer, controls, animId, resizeObserver, container } = viewer3DState;

    cancelAnimationFrame(animId);
    resizeObserver.disconnect();
    controls.dispose();

    // Dispose all scene objects
    scene.traverse((obj) => {
        if (obj.geometry) obj.geometry.dispose();
        if (obj.material) {
            if (Array.isArray(obj.material)) {
                obj.material.forEach(m => m.dispose());
            } else {
                obj.material.dispose();
            }
        }
    });

    renderer.dispose();
    if (renderer.domElement && renderer.domElement.parentNode) {
        renderer.domElement.parentNode.removeChild(renderer.domElement);
    }

    // Remove spinner if still present
    const spinner = container.querySelector('.viewer3d-spinner');
    if (spinner) spinner.remove();

    viewer3DState = null;
}

// Activer le viewer 3D dans le conteneur principal
function setMain3DViewer(modelUrl, thumbElement) {
    // Destroy previous viewer if any
    destroy3DViewer();

    const container = document.getElementById('modalMainImage');
    if (!container) return;

    // Clear image content
    container.innerHTML = '';

    // Update active thumbnail
    document.querySelectorAll('.thumbnail, .thumbnail-3d').forEach(t => t.classList.remove('active'));
    if (thumbElement) thumbElement.classList.add('active');

    // Init viewer
    init3DViewer(modelUrl);
}

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

// Création d'une carte produit (design minimaliste)
function createProductCard(product) {
    const card = document.createElement('div');
    card.className = 'product-card';

    // Badge: first feature or category
    const badgeText = (product.features && product.features.length > 0) ? product.features[0] : '';

    card.innerHTML = `
        <div class="card-image">
            ${product.image
            ? `<img src="${product.image}" alt="${product.name}">`
            : `<div class="card-image-placeholder"></div>`
        }
            ${badgeText ? `<span class="card-badge">${badgeText}</span>` : ''}
        </div>
        <div class="card-body">
            <span class="card-name">${product.name}</span>
            <span class="card-price">${product.price.toFixed(2)} €</span>
            <button class="card-cta" onclick="event.stopPropagation(); openProductModal(${product.id})">Voir</button>
        </div>
    `;

    // Click on card opens modal
    card.addEventListener('click', (e) => {
        if (e.target.tagName === 'BUTTON') return;
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
    const has3D = product.model_3d && product.model_3d.trim() !== '';
    if (allImages.length > 1 || has3D) {
        let thumbsHTML = allImages.map((img, index) => `
            <div class="thumbnail ${index === 0 ? 'active' : ''}" onclick="setMainImage('${img}', this)">
                <img src="${img}" alt="Image ${index + 1}">
            </div>
        `).join('');

        if (has3D) {
            thumbsHTML += `
                <div class="thumbnail thumbnail-3d" onclick="(async()=>{try{await loadThreeJS();setMain3DViewer('${product.model_3d}',this)}catch(e){console.error('Erreur viewer 3D:',e);alert('Erreur lors du chargement du viewer 3D')}})()">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" width="28" height="28">
                        <path d="M12 2L2 7l10 5 10-5-10-5z"/>
                        <path d="M2 17l10 5 10-5"/>
                        <path d="M2 12l10 5 10-5"/>
                    </svg>
                    <span class="thumbnail-3d-label">3D</span>
                </div>
            `;
        }

        thumbnailsContainer.innerHTML = thumbsHTML;
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
    destroy3DViewer();
    document.getElementById('productModalOverlay').classList.remove('active');
    document.body.style.overflow = '';
    // Arrêter la vidéo si elle est en cours
    document.getElementById('modalVideoFrame').src = '';
    currentModalProduct = null;
}

// Changer l'image principale au clic sur une miniature
function setMainImage(imageUrl, thumbnailElement) {
    // Destroy 3D viewer if active
    destroy3DViewer();

    const container = document.getElementById('modalMainImage');
    if (container) {
        container.innerHTML = `<img src="${imageUrl}" alt="${currentModalProduct ? currentModalProduct.name : ''}" id="modalMainImg">`;
    }

    // Mettre à jour la classe active des miniatures
    document.querySelectorAll('.thumbnail, .thumbnail-3d').forEach(thumb => {
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
            // Utilisateur connecte - afficher l'avatar ou l'icone par defaut
            if (data.user.picture) {
                profileIconDefault.style.display = 'none';
                profileIconAvatar.style.display = 'block';
                profileIconAvatar.src = data.user.picture;
            } else {
                // Pas de photo (compte email) - garder l'icone SVG par defaut
                profileIconDefault.style.display = 'block';
                profileIconAvatar.style.display = 'none';
            }

            // Avatar ou initiale dans le dropdown header
            const avatarHTML = data.user.picture
                ? '<img src="' + data.user.picture + '" alt="Avatar" class="profile-dropdown-avatar">'
                : '<div class="profile-dropdown-avatar-initial">' + (data.user.name ? data.user.name.charAt(0).toUpperCase() : '?') + '</div>';

            // Contenu du dropdown pour utilisateur connecte
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
                    ${avatarHTML}
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
                    Deconnexion
                </button>
            `;

            // Mettre a jour currentUser pour le reste de l'app (panier, contact)
            if (typeof window.currentUser !== 'undefined') {
                window.currentUser = {
                    name: data.user.name,
                    email: data.user.email,
                    avatar: data.user.picture
                };
            }

            // Mettre a jour le formulaire de contact
            if (typeof window.updateContactForm === 'function') {
                window.updateContactForm();
            }
        } else {
            // Utilisateur non connecte - afficher l'icone par defaut
            profileIconDefault.style.display = 'block';
            profileIconAvatar.style.display = 'none';

            // Contenu du dropdown avec tabs Connexion / Inscription
            profileDropdown.innerHTML = `
                <div class="auth-tabs">
                    <button class="auth-tab active" onclick="switchAuthTab('login', this)">Connexion</button>
                    <button class="auth-tab" onclick="switchAuthTab('register', this)">Inscription</button>
                </div>
                <div class="auth-panel active" id="authPanelLogin">
                    <div class="auth-error" id="authLoginError"></div>
                    <div class="auth-form-group">
                        <input type="email" id="authLoginEmail" placeholder="Email" autocomplete="email">
                    </div>
                    <div class="auth-form-group">
                        <input type="password" id="authLoginPassword" placeholder="Mot de passe" autocomplete="current-password">
                    </div>
                    <button class="auth-submit-btn" onclick="handleEmailLogin()">Se connecter</button>
                    <div class="auth-divider">ou</div>
                    <button class="auth-google-btn" onclick="handleLogin()">
                        <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.836.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.438 15.983 5.482 18 9.003 18z" fill="#34A853"/>
                            <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.48 0 2.438 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                        </svg>
                        Google
                    </button>
                </div>
                <div class="auth-panel" id="authPanelRegister">
                    <div class="auth-error" id="authRegisterError"></div>
                    <div class="auth-form-group">
                        <input type="text" id="authRegisterName" placeholder="Nom" autocomplete="name">
                    </div>
                    <div class="auth-form-group">
                        <input type="email" id="authRegisterEmail" placeholder="Email" autocomplete="email">
                    </div>
                    <div class="auth-form-group">
                        <input type="password" id="authRegisterPassword" placeholder="Mot de passe (min. 8 car.)" autocomplete="new-password">
                    </div>
                    <button class="auth-submit-btn" onclick="handleEmailRegister()">Creer un compte</button>
                    <div class="auth-divider">ou</div>
                    <button class="auth-google-btn" onclick="handleLogin()">
                        <svg viewBox="0 0 18 18" xmlns="http://www.w3.org/2000/svg">
                            <path d="M17.64 9.2c0-.637-.057-1.251-.164-1.84H9v3.481h4.844c-.209 1.125-.843 2.078-1.796 2.717v2.258h2.908c1.702-1.567 2.684-3.874 2.684-6.615z" fill="#4285F4"/>
                            <path d="M9.003 18c2.43 0 4.467-.806 5.956-2.18L12.05 13.56c-.806.54-1.836.86-3.047.86-2.344 0-4.328-1.584-5.036-3.711H.96v2.332C2.438 15.983 5.482 18 9.003 18z" fill="#34A853"/>
                            <path d="M3.964 10.712c-.18-.54-.282-1.117-.282-1.71 0-.593.102-1.17.282-1.71V4.96H.957C.347 6.175 0 7.55 0 9.002c0 1.452.348 2.827.957 4.042l3.007-2.332z" fill="#FBBC05"/>
                            <path d="M9.003 3.58c1.321 0 2.508.454 3.44 1.345l2.582-2.58C13.464.891 11.428 0 9.002 0 5.48 0 2.438 2.017.96 4.958L3.967 7.29c.708-2.127 2.692-3.71 5.036-3.71z" fill="#EA4335"/>
                        </svg>
                        Google
                    </button>
                </div>
            `;

            // Enter key support pour les champs login/register
            setTimeout(function() {
                var loginPassword = document.getElementById('authLoginPassword');
                if (loginPassword) {
                    loginPassword.addEventListener('keydown', function(e) { if (e.key === 'Enter') handleEmailLogin(); });
                }
                var loginEmail = document.getElementById('authLoginEmail');
                if (loginEmail) {
                    loginEmail.addEventListener('keydown', function(e) { if (e.key === 'Enter') handleEmailLogin(); });
                }
                var registerPassword = document.getElementById('authRegisterPassword');
                if (registerPassword) {
                    registerPassword.addEventListener('keydown', function(e) { if (e.key === 'Enter') handleEmailRegister(); });
                }
            }, 0);

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

// Switcher entre les onglets Connexion / Inscription
function switchAuthTab(tab, btnElement) {
    document.querySelectorAll('.auth-tab').forEach(function(t) { t.classList.remove('active'); });
    document.querySelectorAll('.auth-panel').forEach(function(p) { p.classList.remove('active'); });
    btnElement.classList.add('active');
    if (tab === 'login') {
        document.getElementById('authPanelLogin').classList.add('active');
    } else {
        document.getElementById('authPanelRegister').classList.add('active');
    }
}

// Connexion par email
async function handleEmailLogin() {
    var email = document.getElementById('authLoginEmail').value.trim();
    var password = document.getElementById('authLoginPassword').value;
    var errorEl = document.getElementById('authLoginError');

    errorEl.classList.remove('visible');

    if (!email || !password) {
        errorEl.textContent = 'Veuillez remplir tous les champs';
        errorEl.classList.add('visible');
        return;
    }

    try {
        var response = await fetch('api/auth/login-email.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: password })
        });
        var data = await response.json();

        if (data.success) {
            window.location.reload();
        } else {
            errorEl.textContent = data.error || 'Erreur de connexion';
            errorEl.classList.add('visible');
        }
    } catch (e) {
        errorEl.textContent = 'Erreur de connexion au serveur';
        errorEl.classList.add('visible');
    }
}

// Inscription par email
async function handleEmailRegister() {
    var name = document.getElementById('authRegisterName').value.trim();
    var email = document.getElementById('authRegisterEmail').value.trim();
    var password = document.getElementById('authRegisterPassword').value;
    var errorEl = document.getElementById('authRegisterError');

    errorEl.classList.remove('visible');

    if (!name || !email || !password) {
        errorEl.textContent = 'Veuillez remplir tous les champs';
        errorEl.classList.add('visible');
        return;
    }

    if (password.length < 8) {
        errorEl.textContent = 'Le mot de passe doit contenir au moins 8 caracteres';
        errorEl.classList.add('visible');
        return;
    }

    try {
        var response = await fetch('api/auth/register.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ email: email, password: password, name: name })
        });
        var data = await response.json();

        if (data.success) {
            window.location.reload();
        } else {
            errorEl.textContent = data.error || 'Erreur lors de l\'inscription';
            errorEl.classList.add('visible');
        }
    } catch (e) {
        errorEl.textContent = 'Erreur de connexion au serveur';
        errorEl.classList.add('visible');
    }
}

// Gerer la connexion Google
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

    // Typewriter animation for hero "Entraîne toi ___."
    (function () {
        var el = document.getElementById('typewriter');
        if (!el) return;
        var cursor = document.querySelector('.typewriter-cursor');
        var words = ['chez Toi.', 'dehors.', 'à la Salle.', "n'importe où."];
        var wordIdx = 0;
        var charIdx = 0;
        var deleting = false;
        var typeSpeed = 90;
        var deleteSpeed = 54;
        var pauseEnd = 1350;
        var pauseDelete = 720;

        function tick() {
            var current = words[wordIdx];
            if (!deleting) {
                el.textContent = current.substring(0, charIdx + 1);
                charIdx++;
                if (charIdx === current.length) {
                    if (wordIdx === words.length - 1) {
                        if (cursor) cursor.style.display = 'none';
                        return;
                    }
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

        setTimeout(tick, 540);
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
