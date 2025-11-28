// JavaScript principal pour le site e-commerce

// Enregistrement du Service Worker
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js', { scope: '/' })
            .then(registration => {
                console.log('ServiceWorker enregistré avec succès:', registration.scope);
            })
            .catch(error => {
                console.error('Échec enregistrement ServiceWorker:', error);
            });
    });
}

document.addEventListener('DOMContentLoaded', function() {
    // Initialisation des composants
    initCart();
    initBiometric();
    initForms();
    initAnimations();
    initTooltips();
});

// Gestion du panier
function initCart() {
    updateCartCount();
    
    // Ajouter au panier
    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
            e.preventDefault();
            const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
            const productId = button.dataset.productId;
            addToCart(productId);
        }
});

function addToCart(productId) {
    if (!isLoggedIn()) {
        showAlert('Veuillez vous connecter pour ajouter des produits au panier', 'warning');
        return;
    }
    
    const button = document.querySelector(`[data-product-id="${productId}"]`);
    const originalText = button.innerHTML;
    
    // Animation de chargement
    button.innerHTML = '<span class="loading"></span> Ajout...';
    button.disabled = true;
    
    // Build headers and include CSRF token if available
    const csrfMeta = document.querySelector('meta[name="csrf-token"]');
    const headers = {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    };
    if (csrfMeta) headers['X-CSRF-Token'] = csrfMeta.content;

    fetch('/ajax/add-to-cart.php', {
        method: 'POST',
        credentials: 'same-origin',
        headers: headers,
        body: JSON.stringify({
            product_id: productId,
            quantity: 1,
            csrf_token: csrfMeta ? csrfMeta.content : undefined
        })
    })
    .then(response => response.text())
    .then(text => {
        try {
            const data = JSON.parse(text);
            if (data.success) {
                showAlert('Produit ajouté au panier !', 'success');
                updateCartCount();
                button.innerHTML = '<i class="fas fa-check"></i> Ajouté';
                setTimeout(() => {
                    button.innerHTML = originalText;
                    button.disabled = false;
                }, 2000);
                return;
            }
            throw new Error(data.message || 'Erreur lors de l\'ajout au panier');
        } catch (err) {
            console.error('Réponse serveur non-JSON ou erreur:', text, err);
            throw err;
        }
    })
    .then(() => {
        // Success already handled in previous then block
    })
    .catch(error => {
        console.error('Erreur:', error);
        showAlert(error.message, 'danger');
        button.innerHTML = originalText;
        button.disabled = false;
    });
}

// Fonction pour récupérer le nombre d'articles dans le panier
function updateCartCount() {
    const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
    fetch('/ajax/get-cart-count.php', {
        credentials: 'same-origin',
        headers: {
            'X-CSRF-Token': csrfToken
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const cartCount = document.getElementById('cart-count');
            if (cartCount) {
                cartCount.textContent = data.count;
                cartCount.style.display = data.count > 0 ? 'inline' : 'none';
            }
        }
    })
    .catch(console.error);
}

// Authentification biométrique
function initBiometric() {
    const enableBiometricBtn = document.getElementById('enableBiometric');
    if (enableBiometricBtn) {
        enableBiometricBtn.addEventListener('click', enableBiometric);
    }
    
    // Vérification de la compatibilité WebAuthn
    if (window.PublicKeyCredential) {
        console.log('WebAuthn API disponible');
        checkBiometricSupport();
    }
}

function enableBiometric() {
    if (!window.PublicKeyCredential) {
        showAlert('L\'authentification biométrique n\'est pas supportée par votre navigateur', 'warning');
        return;
    }
    
    // Simulation de l'enregistrement biométrique
    showBiometricModal('enable');
}

function checkBiometricSupport() {
    PublicKeyCredential.isUserVerifyingPlatformAuthenticatorAvailable()
    .then(available => {
        if (available) {
            console.log('Authentificateur biométrique disponible');
        } else {
            console.log('Authentificateur biométrique non disponible');
        }
    })
    .catch(error => {
        console.error('Erreur vérification biométrique:', error);
    });
}

function showBiometricModal(action) {
    const modal = document.createElement('div');
    modal.className = 'modal fade';
    modal.innerHTML = `
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="fas fa-fingerprint"></i> Authentification Biométrique
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center">
                    <div class="biometric-scanner mb-3">
                        <i class="fas fa-fingerprint fa-4x text-primary"></i>
                    </div>
                    <p>Placez votre doigt sur le scanner</p>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" style="width: 0%"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Annuler</button>
                </div>
            </div>
        </div>
    `;
    
    document.body.appendChild(modal);
    const bsModal = new bootstrap.Modal(modal);
    bsModal.show();
    
    // Simulation du scan
    simulateBiometricScan(modal, action);
    
    modal.addEventListener('hidden.bs.modal', () => {
        document.body.removeChild(modal);
    });
}

function simulateBiometricScan(modal, action) {
    const progressBar = modal.querySelector('.progress-bar');
    const scanner = modal.querySelector('.biometric-scanner');
    
    let progress = 0;
    const interval = setInterval(() => {
        progress += 10;
        progressBar.style.width = progress + '%';
        
        if (progress >= 100) {
            clearInterval(interval);
            scanner.innerHTML = '<i class="fas fa-check fa-4x text-success"></i>';
            
            // Génération de données biométriques simulées
            const biometricData = generateBiometricData();
            
            // Envoi des données
            fetch('ajax/biometric-auth.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest'
                },
                body: JSON.stringify({
                    action: action,
                    biometric_data: biometricData
                })
            })
            .then(response => response.text())
            .then(text => {
                try {
                    const data = JSON.parse(text);
                    if (data.success) {
                        showAlert(data.message, 'success');
                        setTimeout(() => {
                            bootstrap.Modal.getInstance(modal).hide();
                            if (action === 'verify') {
                                window.location.href = data.redirect || 'dashboard.php';
                            }
                        }, 1000);
                        return;
                    }
                    throw new Error(data.message || 'Erreur biométrique');
                } catch (err) {
                    console.error('Réponse serveur non-JSON pour biometric-auth:', text, err);
                    throw err;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert(error.message, 'danger');
                bootstrap.Modal.getInstance(modal).hide();
            });
        }
    }, 200);
}

function generateBiometricData() {
    const timestamp = Date.now();
    const randomData = Math.random().toString(36).substring(2, 15);
    return btoa(timestamp + '_' + randomData + '_' + Math.random().toString(36).substring(2, 15));
}

// Gestion des formulaires
function initForms() {
    // Validation en temps réel
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', validateForm);
        
        // Validation des champs en temps réel
        const inputs = form.querySelectorAll('input, textarea, select');
        inputs.forEach(input => {
            input.addEventListener('blur', () => validateField(input));
            input.addEventListener('input', () => clearFieldError(input));
        });
    });
    
    // Toggle mot de passe
    const togglePasswordBtn = document.getElementById('togglePassword');
    if (togglePasswordBtn) {
        togglePasswordBtn.addEventListener('click', togglePassword);
    }
}

function validateForm(e) {
    const form = e.target;
    const inputs = form.querySelectorAll('input[required], textarea[required], select[required]');
    let isValid = true;
    
    inputs.forEach(input => {
        if (!validateField(input)) {
            isValid = false;
        }
    });
    
    if (!isValid) {
        e.preventDefault();
        showAlert('Veuillez corriger les erreurs dans le formulaire', 'danger');
    }
}

function validateField(field) {
    const value = field.value.trim();
    const type = field.type;
    const required = field.hasAttribute('required');
    
    clearFieldError(field);
    
    if (required && !value) {
        showFieldError(field, 'Ce champ est requis');
        return false;
    }
    
    if (value) {
        switch (type) {
            case 'email':
                if (!isValidEmail(value)) {
                    showFieldError(field, 'Adresse email invalide');
                    return false;
                }
                break;
            case 'password':
                if (!isValidPassword(value)) {
                    showFieldError(field, 'Le mot de passe doit contenir au moins 8 caractères avec une majuscule, une minuscule et un chiffre');
                    return false;
                }
                break;
            case 'tel':
                if (!isValidPhone(value)) {
                    showFieldError(field, 'Numéro de téléphone invalide');
                    return false;
                }
                break;
        }
    }
    
    return true;
}

function showFieldError(field, message) {
    field.classList.add('is-invalid');
    
    let errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (!errorDiv) {
        errorDiv = document.createElement('div');
        errorDiv.className = 'invalid-feedback';
        field.parentNode.appendChild(errorDiv);
    }
    errorDiv.textContent = message;
}

function clearFieldError(field) {
    field.classList.remove('is-invalid');
    const errorDiv = field.parentNode.querySelector('.invalid-feedback');
    if (errorDiv) {
        errorDiv.remove();
    }
}

function togglePassword() {
    const passwordField = document.getElementById('password');
    const toggleBtn = document.getElementById('togglePassword');
    const icon = toggleBtn.querySelector('i');
    
    if (passwordField.type === 'password') {
        passwordField.type = 'text';
        icon.classList.remove('fa-eye');
        icon.classList.add('fa-eye-slash');
    } else {
        passwordField.type = 'password';
        icon.classList.remove('fa-eye-slash');
        icon.classList.add('fa-eye');
    }
}

// Animations
function initAnimations() {
    // Animation au scroll
    const observerOptions = {
        threshold: 0.1,
        rootMargin: '0px 0px -50px 0px'
    };
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('fade-in-up');
            }
        });
    }, observerOptions);
    
    // Observer les éléments à animer
    const animatedElements = document.querySelectorAll('.feature-card, .product-card, .stat-card');
    animatedElements.forEach(el => observer.observe(el));
}

// Tooltips
function initTooltips() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
}

// Utilitaires
function isLoggedIn() {
    return document.body.classList.contains('logged-in') || 
           document.querySelector('meta[name="user-logged-in"]')?.content === 'true';
}

function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type} alert-dismissible fade show`;
    alertDiv.innerHTML = `
        ${message}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;
    
    // Insérer en haut de la page
    const container = document.querySelector('.container') || document.body;
    container.insertBefore(alertDiv, container.firstChild);
    
    // Auto-suppression après 5 secondes
    setTimeout(() => {
        if (alertDiv.parentNode) {
            alertDiv.remove();
        }
    }, 5000);
}

function isValidEmail(email) {
    const re = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    return re.test(email);
}

function isValidPassword(password) {
    const re = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/;
    return re.test(password);
}

function isValidPhone(phone) {
    const re = /^[\+]?[0-9\s\-\(\)]{10,}$/;
    return re.test(phone);
}

// Newsletter
document.addEventListener('DOMContentLoaded', function() {
    const newsletterForm = document.querySelector('.newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            if (!isValidEmail(email)) {
                showAlert('Adresse email invalide', 'danger');
                return;
            }
            
            // Simulation de l'inscription à la newsletter
            const button = this.querySelector('button');
            const originalText = button.innerHTML;
            
            button.innerHTML = '<span class="loading"></span> Inscription...';
            button.disabled = true;
            
            setTimeout(() => {
                showAlert('Inscription à la newsletter réussie !', 'success');
                this.reset();
                button.innerHTML = originalText;
                button.disabled = false;
            }, 2000);
        });
    }
});

// Gestion des erreurs globales
window.addEventListener('error', function(e) {
    console.error('Erreur JavaScript:', e.error);
});

// Service Worker pour le cache (PWA)
if ('serviceWorker' in navigator) {
    navigator.serviceWorker.register('/sw.js')
        .then(registration => {
            console.log('ServiceWorker enregistré avec succès:', registration.scope);
        })
        .catch(error => {
            console.log('ServiceWorker enregistré avec succès, scope:', registration.scope);
        })
        .catch(function(error) {
            console.log('Échec enregistrement ServiceWorker:', error);
        });
    });
}