<?php
require_once __DIR__ . '/bootstrap.php';
require_once ROOT_PATH . '/classes/Security.php';

// Vérification de l'authentification
requireLogin();

// Vérification biométrique si nécessaire
if (isset($_SESSION['biometric_required']) && !isset($_SESSION['biometric_verified'])) {
    $_SESSION['redirect_after_biometric'] = 'cart.php';
    redirect('biometric-auth.php');
}

$error = '';
$success = '';

// Traitement des actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Get POST data (support both form and JSON)
        $postData = [];
        $rawInput = file_get_contents('php://input');
        if (!empty($rawInput) && strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false) {
            $postData = json_decode($rawInput, true) ?? [];
        } else {
            $postData = $_POST;
        }

        // Vérification du token CSRF (from POST or header)
        $csrfToken = $postData['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCSRFToken($csrfToken)) {
            throw new Exception("Token de sécurité invalide");
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'update') {
            $itemId = $_POST['item_id'] ?? null;
            $quantity = (int)($_POST['quantity'] ?? 0);
            
            if (!$itemId || $quantity < 0) {
                throw new Exception("Données invalides");
            }
            
            if ($quantity === 0) {
                // Suppression de l'article
                $stmt = $pdo->prepare("DELETE FROM cart WHERE id = ? AND user_id = ?");
                $stmt->execute([$itemId, $_SESSION['user_id']]);
                $success = "Article supprimé du panier";
            } else {
                // Mise à jour de la quantité
                $stmt = $pdo->prepare("UPDATE cart SET quantity = ? WHERE id = ? AND user_id = ?");
                $stmt->execute([$quantity, $itemId, $_SESSION['user_id']]);
                $success = "Quantité mise à jour";
            }
            
        } elseif ($action === 'clear') {
            // Vider le panier
            $stmt = $pdo->prepare("DELETE FROM cart WHERE user_id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $success = "Panier vidé";
            
        } elseif ($action === 'checkout') {
            // Redirection vers le checkout
            redirect('checkout.php');
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

}

$stmt = $pdo->prepare(<<<'SQL'
SELECT c.*, p.name, p.sku, pi.file_path AS image_url, p.stock_quantity, p.weight
FROM cart c
JOIN products p ON c.product_id = p.id
LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
WHERE c.user_id = ?
ORDER BY c.created_at DESC
SQL
);
$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Calcul du total
$subtotal = 0;
$totalWeight = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['price'] * $item['quantity'];
    $totalWeight += $item['weight'] * $item['quantity'];
}

$taxRate = 0.20; // TVA 20%
$taxAmount = $subtotal * $taxRate;
$shippingCost = $totalWeight > 5 ? 150 : 99; // Frais de port selon le poids (DH)
$total = $subtotal + $taxAmount + $shippingCost;

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Panier - <?php echo SITE_NAME; ?></title>
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
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="index.php">Accueil</a>
                <a class="nav-link" href="products.php">Produits</a>
                <a class="nav-link" href="dashboard.php">Tableau de bord</a>
                <a class="nav-link" href="logout.php">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container mt-4">
        <div class="row">
            <div class="col-12">
                <h1 class="mb-4">
                    <i class="fas fa-shopping-cart"></i> Mon Panier
                    <?php if (count($cartItems) > 0): ?>
                        <span class="badge bg-primary"><?php echo count($cartItems); ?> article(s)</span>
                    <?php endif; ?>
                </h1>
            </div>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
            </div>
        <?php endif; ?>

        <?php if (empty($cartItems)): ?>
            <!-- Panier vide -->
            <div class="row">
                <div class="col-12">
                    <div class="card text-center py-5">
                        <div class="card-body">
                            <i class="fas fa-shopping-cart fa-5x text-muted mb-4"></i>
                            <h3>Votre panier est vide</h3>
                            <p class="text-muted mb-4">Découvrez nos produits et ajoutez-les à votre panier</p>
                            <a href="products.php" class="btn btn-primary btn-lg">
                                <i class="fas fa-shopping-bag"></i> Découvrir nos produits
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <!-- Articles du panier -->
            <div class="row">
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Articles dans votre panier</h5>
                        </div>
                        <div class="card-body p-0">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item border-bottom p-3">
                                <div class="row align-items-center">
                                    <div class="col-md-2">
                                        <?php if ($item['image_url']): ?>
                                            <img src="<?php echo htmlspecialchars($item['image_url']); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['name']); ?>" 
                                                 class="img-fluid rounded">
                                        <?php else: ?>
                                            <div class="no-image bg-light rounded d-flex align-items-center justify-content-center" style="height: 80px;">
                                                <i class="fas fa-image text-muted"></i>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col-md-4">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($item['name']); ?></h6>
                                        <small class="text-muted">SKU: <?php echo htmlspecialchars($item['sku']); ?></small>
                                    </div>
                                    <div class="col-md-2">
                                        <span class="fw-bold"><?php echo formatPrice($item['price']); ?></span>
                                    </div>
                                    <div class="col-md-2">
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                            <input type="hidden" name="action" value="update">
                                            <input type="hidden" name="item_id" value="<?php echo $item['id']; ?>">
                                            <div class="input-group input-group-sm">
                                                <input type="number" name="quantity" value="<?php echo $item['quantity']; ?>" 
                                                       min="0" max="<?php echo $item['stock_quantity']; ?>" 
                                                       class="form-control">
                                                <button type="submit" class="btn btn-outline-primary">
                                                    <i class="fas fa-sync"></i>
                                                </button>
                                            </div>
                                        </form>
                                    </div>
                                    <div class="col-md-2 text-end">
                                        <span class="fw-bold text-primary">
                                            <?php echo formatPrice($item['price'] * $item['quantity']); ?>
                                        </span>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <div class="card-footer">
                            <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir vider votre panier ?')">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="clear">
                                <button type="submit" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-trash"></i> Vider le panier
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Résumé de la commande -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Résumé de la commande</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex justify-content-between mb-2">
                                <span>Sous-total:</span>
                                <span><?php echo formatPrice($subtotal); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>TVA (20%):</span>
                                <span><?php echo formatPrice($taxAmount); ?></span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span>Frais de port:</span>
                                <span><?php echo formatPrice($shippingCost); ?></span>
                            </div>
                            <hr>
                            <div class="d-flex justify-content-between mb-3">
                                <strong>Total:</strong>
                                <strong class="text-primary"><?php echo formatPrice($total); ?></strong>
                            </div>
                            
                            <form method="POST">
                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                <input type="hidden" name="action" value="checkout">
                                <button type="submit" class="btn btn-primary btn-lg w-100 mb-3">
                                    <i class="fas fa-credit-card"></i> Passer la commande
                                </button>
                            </form>
                            
                            <div class="text-center">
                                <small class="text-muted">
                                    <i class="fas fa-shield-alt"></i> Paiement sécurisé<br>
                                    <i class="fas fa-truck"></i> Livraison sous 2-3 jours ouvrés
                                </small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
