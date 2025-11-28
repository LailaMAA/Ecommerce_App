<?php
require_once 'config/config.php';
require_once 'classes/Security.php';

// Fonctions utilitaires
function getProducts($pdo, $category = null, $search = null, $page = 1) {
    $limit = ITEMS_PER_PAGE;
    $offset = ($page - 1) * $limit;
    
    $whereConditions = ["p.active = 1"];
    $params = [];
    
    if ($category) {
        $whereConditions[] = "c.slug = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT p.*, c.name as category_name, c.slug as category_slug, pi.file_path
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        LEFT JOIN product_images pi ON p.id = pi.product_id AND pi.is_primary = 1
        WHERE $whereClause
        ORDER BY p.featured DESC, p.created_at DESC
        LIMIT $limit OFFSET $offset
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
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

function getTotalProducts($pdo, $category = null, $search = null) {
    $whereConditions = ["p.active = 1"];
    $params = [];
    
    if ($category) {
        $whereConditions[] = "c.slug = ?";
        $params[] = $category;
    }
    
    if ($search) {
        $whereConditions[] = "(p.name LIKE ? OR p.description LIKE ? OR p.short_description LIKE ?)";
        $searchTerm = "%$search%";
        $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
    }
    
    $whereClause = implode(' AND ', $whereConditions);
    
    $sql = "
        SELECT COUNT(*) as total
        FROM products p
        LEFT JOIN categories c ON p.category_id = c.id
        WHERE $whereClause
    ";
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetch()['total'];
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

// Récupération des paramètres
$category = $_GET['category'] ?? null;
$search = $_GET['search'] ?? null;
$page = max(1, (int)($_GET['page'] ?? 1));

// Récupération des données
$products = getProducts($pdo, $category, $search, $page);
$totalProducts = getTotalProducts($pdo, $category, $search);
$categories = getCategories($pdo);

// Calcul de la pagination
$totalPages = ceil($totalProducts / ITEMS_PER_PAGE);
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Produits - <?php echo SITE_NAME; ?></title>
    <meta name="description" content="Découvrez notre gamme complète de produits multimédias au Maroc. Smartphones, ordinateurs, accessoires et plus encore.">
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
                        <a class="nav-link active" href="products.php">Produits</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="categoriesDropdown" role="button" data-bs-toggle="dropdown">
                            Catégories
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="products.php">Toutes les catégories</a></li>
                            <?php foreach ($categories as $cat): ?>
                            <li><a class="dropdown-item" href="products.php?category=<?php echo $cat['slug']; ?>">
                                <?php echo htmlspecialchars($cat['name']); ?>
                                <span class="badge bg-secondary ms-2"><?php echo $cat['product_count']; ?></span>
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

    <!-- Header -->
    <section class="py-5 mt-5 bg-light">
        <div class="container">
            <div class="row">
                <div class="col-12 text-center">
                    <h1 class="display-5 fw-bold">Nos Produits</h1>
                    <p class="lead text-muted">Découvrez notre gamme complète de produits multimédias</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Filtres et recherche -->
    <section class="py-4 bg-white border-bottom">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <form method="GET" class="d-flex gap-3">
                        <div class="flex-grow-1">
                            <input type="text" class="form-control" name="search" 
                                   placeholder="Rechercher un produit..." 
                                   value="<?php echo htmlspecialchars($search ?? ''); ?>">
                        </div>
                        <div>
                            <select name="category" class="form-select">
                                <option value="">Toutes les catégories</option>
                                <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['slug']; ?>" 
                                        <?php echo ($category === $cat['slug']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-search"></i> Rechercher
                        </button>
                        <?php if ($search || $category): ?>
                        <a href="products.php" class="btn btn-outline-secondary">
                            <i class="fas fa-times"></i> Effacer
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
                <div class="col-lg-4 text-lg-end mt-3 mt-lg-0">
                    <p class="mb-0 text-muted">
                        <?php echo $totalProducts; ?> produit(s) trouvé(s)
                        <?php if ($search): ?>
                            pour "<?php echo htmlspecialchars($search); ?>"
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- Produits -->
    <section class="py-5">
        <div class="container">
            <?php if (empty($products)): ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card text-center py-5">
                            <div class="card-body">
                                <i class="fas fa-search fa-5x text-muted mb-4"></i>
                                <h3>Aucun produit trouvé</h3>
                                <p class="text-muted mb-4">Essayez de modifier vos critères de recherche</p>
                                <a href="products.php" class="btn btn-primary">
                                    <i class="fas fa-list"></i> Voir tous les produits
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($products as $product): ?>
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
                                <?php if ($product['featured']): ?>
                                <div class="product-badge">
                                    <span class="badge bg-warning">En vedette</span>
                                </div>
                                <?php endif; ?>
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
                                <div class="product-stock">
                                    <?php if ($product['stock_quantity'] > 0): ?>
                                        <small class="text-success">
                                            <i class="fas fa-check-circle"></i> En stock (<?php echo $product['stock_quantity']; ?>)
                                        </small>
                                    <?php else: ?>
                                        <small class="text-danger">
                                            <i class="fas fa-times-circle"></i> Rupture de stock
                                        </small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <nav aria-label="Pagination des produits" class="mt-5">
                    <ul class="pagination justify-content-center">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>">
                                <i class="fas fa-chevron-left"></i> Précédent
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <li class="page-item <?php echo ($i === $page) ? 'active' : ''; ?>">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>">
                                Suivant <i class="fas fa-chevron-right"></i>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </section>

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
</body>
</html>
