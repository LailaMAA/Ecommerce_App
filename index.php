<?php
require_once 'config/config.php';
require_once 'classes/Security.php';

// Fonctions utilitaires pour la page d'accueil
function getFeaturedProducts($pdo) {
    $stmt = $pdo->prepare("
        SELECT p.*, c.name as category_name, pi.file_path
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE p.featured = 1 AND p.active = 1
        ORDER BY p.created_at DESC
        LIMIT 6
    ");
    $stmt->execute();
    $products = $stmt->fetchAll();
    
    // Construire le chemin complet des images
    foreach ($products as &$product) {
        if ($product['file_path']) {
            $product['image_url'] = 'assets/images/products/' . $product['file_path'];
        } else {
            $product['image_url'] = null;
        }
    }
    
    return $products;
}

function getCategories($pdo) {
    $stmt = $pdo->prepare("
        SELECT c.*, COUNT(p.id) as product_count
        FROM categories c
        LEFT JOIN products p ON c.id = p.category_id AND p.active = 1
        WHERE c.active = 1
        GROUP BY c.id
        ORDER BY c.sort_order ASC, c.name ASC
    ");
    $stmt->execute();
    return $stmt->fetchAll();
}

$featuredProducts = getFeaturedProducts($pdo);
$categories = getCategories($pdo);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo SITE_NAME; ?> - Votre boutique en ligne sécurisée</title>
    <meta name="description" content="Découvrez notre boutique en ligne avec des fonctionnalités avancées de sécurité, paiement sécurisé et gestion d'images professionnelle.">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <?php if (isLoggedIn()): ?>
    <meta name="user-logged-in" content="true">
    <?php endif; ?>
    <meta name="csrf-token" content="<?php echo generateCSRFToken(); ?>">
</head>
<body<?php echo isLoggedIn() ? ' class="logged-in"' : ''; ?>>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> <?php echo SITE_NAME; ?>
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produits</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                            Catégories
                        </a>
                        <ul class="dropdown-menu">
                            <?php foreach ($categories as $category): ?>
                            <li><a class="dropdown-item" href="products.php?category=<?php echo $category['slug']; ?>">
                                <?php echo htmlspecialchars($category['name']); ?>
                                <span class="badge bg-secondary ms-2"><?php echo $category['product_count']; ?></span>
                            </a></li>
                            <?php endforeach; ?>
                        </ul>
                    </li>
                </ul>
                <ul class="navbar-nav">
                    <?php if (isLoggedIn()): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="cart.php">
                                <i class="fas fa-shopping-cart"></i> Panier
                                <span class="badge bg-danger" id="cart-count">0</span>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="dashboard.php">
                                <i class="fas fa-tachometer-alt"></i> Tableau de bord
                            </a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user"></i> <?php echo $_SESSION['user_name']; ?>
                            </a>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="profile.php">Mon profil</a></li>
                                <li><a class="dropdown-item" href="orders.php">Mes commandes</a></li>
                                <li><a class="dropdown-item" href="biometric-auth.php">Sécurité</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="logout.php">Déconnexion</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Connexion</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="register.php">Inscription</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section">
        <div class="container">
            <div class="row align-items-center min-vh-100">
                <div class="col-lg-6">
                    <h1 class="display-4 fw-bold text-white mb-4">
                        Bienvenue sur <?php echo SITE_NAME; ?>
                    </h1>
                    <p class="lead text-white mb-4">
                        Découvrez notre boutique en ligne marocaine avec des fonctionnalités avancées de sécurité, 
                        paiement sécurisé et gestion d'images professionnelle. Livraison partout au Maroc !
                    </p>
                    <div class="d-flex gap-3">
                        <a href="products.php" class="btn btn-light btn-lg">
                            <i class="fas fa-shopping-bag"></i> Découvrir nos produits
                        </a>
                        <?php if (!isLoggedIn()): ?>
                        <a href="register.php" class="btn btn-outline-light btn-lg">
                            <i class="fas fa-user-plus"></i> S'inscrire
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="hero-image">
                        <i class="fas fa-shopping-cart fa-10x text-white opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Fonctionnalités -->
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Fonctionnalités Avancées</h2>
                    <p class="lead text-muted">Notre plateforme e-commerce intègre les dernières technologies de sécurité</p>
                </div>
            </div>
            <div class="row g-4">
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-shield-alt"></i>
                        </div>
                        <h4>Sécurité Renforcée</h4>
                        <p>Authentification biométrique, captcha reCAPTCHA et chiffrement des données pour protéger vos informations.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-credit-card"></i>
                        </div>
                        <h4>Paiement Sécurisé</h4>
                        <p>PayPal, cartes bancaires et paiement mobile avec trousseau pour une expérience de paiement optimale.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-images"></i>
                        </div>
                        <h4>Gestion d'Images</h4>
                        <p>Conversion automatique en plusieurs formats, protection par watermark et optimisation des performances.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-chart-bar"></i>
                        </div>
                        <h4>Tableau de Bord</h4>
                        <p>Statistiques détaillées avec graphiques JpGraph pour suivre les performances de votre boutique.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-mobile-alt"></i>
                        </div>
                        <h4>Design Responsive</h4>
                        <p>Interface adaptée à tous les écrans pour une expérience utilisateur optimale sur mobile et desktop.</p>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="feature-card">
                        <div class="feature-icon">
                            <i class="fas fa-fingerprint"></i>
                        </div>
                        <h4>Authentification Biométrique</h4>
                        <p>Accès sécurisé à votre espace client et panier grâce à l'authentification par empreinte digitale.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Produits en vedette -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center mb-5">
                    <h2 class="display-5 fw-bold">Produits en Vedette</h2>
                    <p class="lead text-muted">Découvrez notre sélection de produits multimédias de qualité</p>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($featuredProducts as $product): ?>
                <div class="col-lg-4 col-md-6">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($product['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="img-fluid">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <a href="product.php?id=<?php echo $product['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Voir le produit
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h5 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h5>
                            <p class="product-category"><?php echo htmlspecialchars($product['category_name']); ?></p>
                            <p class="product-description"><?php echo htmlspecialchars(substr($product['short_description'], 0, 100)); ?>...</p>
                            <div class="product-price">
                                <span class="price"><?php echo formatPrice($product['price']); ?></span>
                                <?php if ($product['sale_price']): ?>
                                    <span class="sale-price"><?php echo formatPrice($product['sale_price']); ?></span>
                                <?php endif; ?>
                            </div>
                            <div class="product-actions">
                                <button class="btn btn-outline-primary btn-sm add-to-cart" 
                                        data-product-id="<?php echo $product['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div class="text-center mt-5">
                <a href="products.php" class="btn btn-primary btn-lg">
                    <i class="fas fa-th-large"></i> Voir tous les produits
                </a>
            </div>
        </div>
    </section>

    <!-- Newsletter -->
    <section class="py-5 bg-primary text-white">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h3 class="mb-3">Restez informé de nos nouveautés</h3>
                    <p class="mb-0">Recevez nos offres exclusives et les dernières actualités de notre boutique.</p>
                </div>
                <div class="col-lg-6">
                    <form class="newsletter-form">
                        <div class="input-group">
                            <input type="email" class="form-control" placeholder="Votre adresse email" required>
                            <button class="btn btn-light" type="submit">
                                <i class="fas fa-paper-plane"></i> S'abonner
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p>Votre boutique en ligne marocaine sécurisée avec des fonctionnalités avancées de sécurité et de gestion d'images. Livraison partout au Maroc !</p>
                    <div class="social-links">
                        <a href="#" class="text-light me-3"><i class="fab fa-facebook-f"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-twitter"></i></a>
                        <a href="#" class="text-light me-3"><i class="fab fa-instagram"></i></a>
                        <a href="#" class="text-light"><i class="fab fa-linkedin-in"></i></a>
                    </div>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Navigation</h6>
                    <ul class="list-unstyled">
                        <li><a href="index.php" class="text-light-50">Accueil</a></li>
                        <li><a href="products.php" class="text-light-50">Produits</a></li>
                        <li><a href="about.php" class="text-light-50">À propos</a></li>
                        <li><a href="contact.php" class="text-light-50">Contact</a></li>
                    </ul>
                </div>
                <div class="col-lg-2 col-md-6 mb-4">
                    <h6>Compte</h6>
                    <ul class="list-unstyled">
                        <li><a href="login.php" class="text-light-50">Connexion</a></li>
                        <li><a href="register.php" class="text-light-50">Inscription</a></li>
                        <li><a href="profile.php" class="text-light-50">Mon profil</a></li>
                        <li><a href="orders.php" class="text-light-50">Mes commandes</a></li>
                    </ul>
                </div>
                <div class="col-lg-4 mb-4">
                    <h6>Contact</h6>
                    <ul class="list-unstyled">
                        <li><i class="fas fa-envelope me-2"></i> contact@ecommerce-multimedia.ma</li>
                        <li><i class="fas fa-phone me-2"></i> +212 5 22 12 34 56</li>
                        <li><i class="fas fa-map-marker-alt me-2"></i> 123 Avenue Mohammed V, Casablanca, Maroc</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="row align-items-center">
                <div class="col-md-6">
                    <p class="mb-0">&copy; <?php echo date('Y'); ?> <?php echo SITE_NAME; ?>. Tous droits réservés.</p>
                </div>
                <div class="col-md-6 text-md-end">
                    <a href="privacy.php" class="text-light-50 me-3">Confidentialité</a>
                    <a href="terms.php" class="text-light-50">Conditions</a>
                </div>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>