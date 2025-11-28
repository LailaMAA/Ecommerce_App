<?php
require_once 'config/config.php';
require_once 'classes/Security.php';

// Récupération de l'ID du produit
$productId = (int)($_GET['id'] ?? 0);

if (!$productId) {
    redirect('products.php');
}

// Récupération du produit
$stmt = $pdo->prepare("
    SELECT p.*, c.name as category_name, c.slug as category_slug, pi.file_path
    FROM products p
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.id = ? AND p.active = 1
");
$stmt->execute([$productId]);
$product = $stmt->fetch();

if (!$product) {
    redirect('products.php');
}

// Construire le chemin de l'image
if ($product['file_path']) {
    $product['image_url'] = 'assets/images/products/' . $product['file_path'];
} else {
    $product['image_url'] = null;
}

// Récupération des images supplémentaires
$stmt = $pdo->prepare("
    SELECT * FROM product_images 
    WHERE product_id = ? AND is_primary = 0
    ORDER BY sort_order ASC
");
$stmt->execute([$productId]);
$additionalImages = $stmt->fetchAll();

// Construire les chemins des images supplémentaires
foreach ($additionalImages as &$img) {
    $img['image_url'] = 'assets/images/products/' . $img['file_path'];
}

// Récupération des produits similaires
$stmt = $pdo->prepare("
    SELECT p.*, pi.file_path
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
    WHERE p.category_id = ? AND p.id != ? AND p.active = 1
    ORDER BY p.featured DESC, RAND()
    LIMIT 4
");
$stmt->execute([$product['category_id'], $productId]);
$relatedProducts = $stmt->fetchAll();

// Construire les chemins des images des produits similaires
foreach ($relatedProducts as &$relatedProduct) {
    if ($relatedProduct['file_path']) {
        $relatedProduct['image_url'] = 'assets/images/products/' . $relatedProduct['file_path'];
    } else {
        $relatedProduct['image_url'] = null;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($product['name']); ?> - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="<?php echo htmlspecialchars($product['short_description']); ?>">
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
                        <a class="nav-link" href="index.php">Accueil</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php">Produits</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="products.php?category=<?php echo $product['category_slug']; ?>">
                            <?php echo htmlspecialchars($product['category_name']); ?>
                        </a>
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

    <!-- Breadcrumb -->
    <section class="py-3 bg-light mt-5">
        <div class="container">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb mb-0">
                    <li class="breadcrumb-item"><a href="index.php">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="products.php">Produits</a></li>
                    <li class="breadcrumb-item"><a href="products.php?category=<?php echo $product['category_slug']; ?>">
                        <?php echo htmlspecialchars($product['category_name']); ?>
                    </a></li>
                    <li class="breadcrumb-item active"><?php echo htmlspecialchars($product['name']); ?></li>
                </ol>
            </nav>
        </div>
    </section>

    <!-- Détail du produit -->
    <section class="py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-6">
                    <div class="product-image-container">
                        <?php if ($product['image_url']): ?>
                            <img src="<?php echo htmlspecialchars($product['image_url']); ?>" 
                                 alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                 class="img-fluid rounded shadow" id="mainImage">
                        <?php else: ?>
                            <div class="no-image-placeholder">
                                <i class="fas fa-image fa-5x text-muted"></i>
                                <p class="text-muted">Image non disponible</p>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($additionalImages)): ?>
                        <div class="row mt-3">
                            <?php foreach ($additionalImages as $img): ?>
                            <div class="col-3">
                                <img src="<?php echo htmlspecialchars($img['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($product['name']); ?>" 
                                     class="img-fluid rounded thumbnail-image" 
                                     onclick="changeMainImage(this.src)">
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                
                <div class="col-lg-6">
                    <div class="product-details">
                        <h1 class="product-title"><?php echo htmlspecialchars($product['name']); ?></h1>
                        
                        <div class="product-meta mb-3">
                            <span class="badge bg-primary"><?php echo htmlspecialchars($product['category_name']); ?></span>
                            <?php if ($product['featured']): ?>
                                <span class="badge bg-warning ms-2">En vedette</span>
                            <?php endif; ?>
                            <span class="badge bg-secondary ms-2">SKU: <?php echo htmlspecialchars($product['sku']); ?></span>
                        </div>
                        
                        <div class="product-price mb-4">
                            <span class="price-main"><?php echo formatPrice($product['price']); ?></span>
                            <?php if ($product['sale_price']): ?>
                                <span class="price-sale"><?php echo formatPrice($product['sale_price']); ?></span>
                                <span class="price-original"><?php echo formatPrice($product['price']); ?></span>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-description mb-4">
                            <h5>Description</h5>
                            <p><?php echo nl2br(htmlspecialchars($product['description'])); ?></p>
                        </div>
                        
                        <div class="product-stock mb-4">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle"></i> En stock (<?php echo $product['stock_quantity']; ?> disponibles)
                                </div>
                            <?php else: ?>
                                <div class="alert alert-danger">
                                    <i class="fas fa-times-circle"></i> Rupture de stock
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-actions">
                            <?php if ($product['stock_quantity'] > 0): ?>
                                <div class="row mb-3">
                                    <div class="col-4">
                                        <label for="quantity" class="form-label">Quantité</label>
                                        <input type="number" class="form-control" id="quantity" value="1" min="1" max="<?php echo $product['stock_quantity']; ?>">
                                    </div>
                                </div>
                                
                                <div class="d-grid gap-2">
                                    <button class="btn btn-primary btn-lg add-to-cart" data-product-id="<?php echo $product['id']; ?>">
                                        <i class="fas fa-cart-plus"></i> Ajouter au panier
                                    </button>
                                    <button class="btn btn-outline-primary btn-lg">
                                        <i class="fas fa-heart"></i> Ajouter aux favoris
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-warning">
                                    <i class="fas fa-bell"></i> Ce produit n'est actuellement pas disponible. 
                                    <a href="#" class="alert-link">Me notifier quand il sera de nouveau en stock</a>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <div class="product-features mt-4">
                            <h6>Caractéristiques</h6>
                            <ul class="list-unstyled">
                                <li><i class="fas fa-check text-success"></i> Livraison gratuite au Maroc</li>
                                <li><i class="fas fa-check text-success"></i> Garantie constructeur</li>
                                <li><i class="fas fa-check text-success"></i> Support client 24/7</li>
                                <li><i class="fas fa-check text-success"></i> Retour sous 30 jours</li>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Produits similaires -->
    <?php if (!empty($relatedProducts)): ?>
    <section class="py-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12">
                    <h3 class="mb-4">Produits similaires</h3>
                </div>
            </div>
            <div class="row g-4">
                <?php foreach ($relatedProducts as $relatedProduct): ?>
                <div class="col-lg-3 col-md-6">
                    <div class="product-card">
                        <div class="product-image">
                            <?php if ($relatedProduct['image_url']): ?>
                                <img src="<?php echo htmlspecialchars($relatedProduct['image_url']); ?>" 
                                     alt="<?php echo htmlspecialchars($relatedProduct['name']); ?>" 
                                     class="img-fluid">
                            <?php else: ?>
                                <div class="no-image">
                                    <i class="fas fa-image fa-3x text-muted"></i>
                                </div>
                            <?php endif; ?>
                            <div class="product-overlay">
                                <a href="product.php?id=<?php echo $relatedProduct['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-eye"></i> Voir le produit
                                </a>
                            </div>
                        </div>
                        <div class="product-info">
                            <h6 class="product-title"><?php echo htmlspecialchars($relatedProduct['name']); ?></h6>
                            <div class="product-price">
                                <span class="price"><?php echo formatPrice($relatedProduct['price']); ?></span>
                            </div>
                            <div class="product-actions">
                                <button class="btn btn-outline-primary btn-sm add-to-cart" 
                                        data-product-id="<?php echo $relatedProduct['id']; ?>">
                                    <i class="fas fa-cart-plus"></i> Ajouter au panier
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Footer -->
    <footer class="bg-dark text-light py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-4 mb-4">
                    <h5><?php echo SITE_NAME; ?></h5>
                    <p>Votre boutique en ligne marocaine sécurisée avec des fonctionnalités avancées de sécurité et de gestion d'images. Livraison partout au Maroc !</p>
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
    <script>
        function changeMainImage(src) {
            document.getElementById('mainImage').src = src;
        }
        
        // Gestion de l'ajout au panier avec quantité
        document.addEventListener('click', function(e) {
            if (e.target.classList.contains('add-to-cart') || e.target.closest('.add-to-cart')) {
                e.preventDefault();
                const button = e.target.classList.contains('add-to-cart') ? e.target : e.target.closest('.add-to-cart');
                const productId = button.dataset.productId;
                const quantity = document.getElementById('quantity') ? document.getElementById('quantity').value : 1;
                
                addToCartWithQuantity(productId, quantity);
            }
        });
        
        function addToCartWithQuantity(productId, quantity) {
            if (!isLoggedIn()) {
                showAlert('Veuillez vous connecter pour ajouter des produits au panier', 'warning');
                return;
            }
            
            const button = document.querySelector(`[data-product-id="${productId}"]`);
            const originalText = button.innerHTML;
            
            button.innerHTML = '<span class="loading"></span> Ajout...';
            button.disabled = true;
            
            // Include CSRF token and credentials so server returns JSON and recognizes the session
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const headers = {
                'Content-Type': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            };
            if (csrfMeta) headers['X-CSRF-Token'] = csrfMeta.content;

            fetch('ajax/add-to-cart.php', {
                method: 'POST',
                credentials: 'same-origin',
                headers: headers,
                body: JSON.stringify({
                    product_id: productId,
                    quantity: parseInt(quantity),
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
                    console.error('Réponse serveur non-JSON pour add-to-cart:', text, err);
                    throw err;
                }
            })
            .catch(error => {
                console.error('Erreur:', error);
                showAlert(error.message, 'danger');
                button.innerHTML = originalText;
                button.disabled = false;
            });
        }
    </script>
</body>
</html>
