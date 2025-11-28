<?php
require_once __DIR__ . '/bootstrap.php';
require_once ROOT_PATH . '/classes/Security.php';
require_once ROOT_PATH . '/classes/PaymentManager.php';

// Vérification de l'authentification
requireLogin();

// Vérification biométrique si nécessaire
if (isset($_SESSION['biometric_required']) && !isset($_SESSION['biometric_verified'])) {
    $_SESSION['redirect_after_biometric'] = 'checkout.php';
    redirect('biometric-auth.php');
}

// Récupération du panier
$stmt = $pdo->prepare(<<<'SQL'
    SELECT c.*, p.name, p.sku, pi.file_path AS image_url, p.stock_quantity, p.weight,
           p.price as unit_price, (p.price * c.quantity) as total_price
    FROM cart c
    JOIN products p ON c.product_id = p.id
    LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1
    WHERE c.user_id = ?
    ORDER BY c.created_at DESC
SQL);

$stmt->execute([$_SESSION['user_id']]);
$cartItems = $stmt->fetchAll();

// Calcul des totaux
$subtotal = 0;
$totalWeight = 0;
foreach ($cartItems as $item) {
    $subtotal += $item['total_price'];
    $totalWeight += ($item['weight'] * $item['quantity']);
}

// Frais de livraison basés sur le poids total
$shippingCost = calculateShippingCost($totalWeight);
$taxRate = 0.20; // TVA 20%
$taxAmount = $subtotal * $taxRate;
$total = $subtotal + $taxAmount + $shippingCost;

// Récupération des adresses de l'utilisateur
$stmt = $pdo->prepare("
    SELECT * FROM addresses 
    WHERE user_id = ?
    ORDER BY type ASC, is_default DESC
");
$stmt->execute([$_SESSION['user_id']]);
$allAddresses = $stmt->fetchAll();

// Réorganiser les adresses par type
$addresses = [
    'shipping' => [],
    'billing' => []
];
foreach ($allAddresses as $address) {
    $addresses[$address['type']][] = $address;
}

// Traitement du formulaire de commande
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCSRFToken($csrfToken)) {
            throw new Exception("Token de sécurité invalide");
        }

        // Validation du stock
        foreach ($cartItems as $item) {
            if ($item['quantity'] > $item['stock_quantity']) {
                throw new Exception("Stock insuffisant pour {$item['name']}");
            }
        }

        // Création de la commande
        $paymentManager = new PaymentManager($pdo);
        $orderId = $paymentManager->createOrder([
            'user_id' => $_SESSION['user_id'],
            'items' => $cartItems,
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_amount' => $shippingCost,
            'total_amount' => $total,
            'shipping_address' => $_POST['shipping_address'] ?? null,
            'billing_address' => $_POST['billing_address'] ?? null,
            'payment_method' => $_POST['payment_method'] ?? 'card'
        ]);

        // Redirection vers le paiement
        redirect("payment.php?order_id=" . $orderId);

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Fonction pour calculer les frais de livraison
function calculateShippingCost($weight) {
    // Tarifs de base
    if ($weight <= 1) return 50; // Jusqu'à 1kg
    if ($weight <= 3) return 75; // Jusqu'à 3kg
    if ($weight <= 5) return 100; // Jusqu'à 5kg
    return 150; // Plus de 5kg
}

// Génération du token CSRF
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paiement - <?php echo SITE_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?php echo isLoggedIn() ? 'logged-in' : ''; ?>">
    <?php require_once ROOT_PATH . '/includes/header.php'; ?>

    <main class="container py-4">
        <div class="page-header mb-4">
            <h1 class="display-4">Finaliser la commande</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/cart.php">Panier</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Paiement</li>
                </ol>
            </nav>
        </div>

        <?php if (empty($cartItems)): ?>
            <div class="alert alert-info">
                Votre panier est vide. <a href="products.php">Continuer vos achats</a>
            </div>
        <?php else: ?>

            <?php if ($error): ?>
                <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>

            <div class="row">
                <div class="col-md-8">
                    <!-- Récapitulatif de la commande -->
                    <div class="card shadow-sm rounded-3 mb-4">
                        <div class="card-header bg-gradient">
                            <h2 class="h4 mb-0"><i class="fas fa-shopping-cart me-2"></i>Récapitulatif de la commande</h2>
                        </div>
                        <div class="card-body">
                            <?php foreach ($cartItems as $item): ?>
                            <div class="cart-item mb-3 p-3 bg-light rounded-3">
                                <div class="row align-items-center">
                                    <div class="col-2">
                                        <?php if ($item['image_url']): ?>
                                            <div class="product-image-container">
                                                <img src="<?php echo SITE_URL . '/assets/images/products/' . basename($item['image_url']); ?>" 
                                                     alt="<?php echo htmlspecialchars($item['name']); ?>"
                                                     class="img-fluid rounded">
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="col">
                                        <h5 class="product-title"><?php echo htmlspecialchars($item['name']); ?></h5>
                                        <p class="text-muted mb-0">
                                            <span class="badge bg-primary me-2">
                                                <i class="fas fa-times me-1"></i><?php echo $item['quantity']; ?>
                                            </span>
                                            <?php echo formatPrice($item['unit_price']); ?>
                                        </p>
                                    </div>
                                    <div class="col-3 text-end">
                                        <strong class="text-primary fs-5"><?php echo formatPrice($item['total_price']); ?></strong>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Formulaire de commande -->
                    <form action="checkout.php" method="POST" id="checkout-form">
                        <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                        
                        <!-- Adresse de livraison -->
                        <div class="card shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-gradient">
                                <h2 class="h4 mb-0"><i class="fas fa-shipping-fast me-2"></i>Adresse de livraison</h2>
                            </div>
                            <div class="card-body">
                                <?php if (!empty($addresses['shipping'])): ?>
                                    <?php foreach ($addresses['shipping'] as $address): ?>
                                        <div class="address-card mb-3">
                                            <div class="form-check custom-radio">
                                                <input class="form-check-input" type="radio" 
                                                       name="shipping_address" 
                                                       value="<?php echo $address['id']; ?>"
                                                       <?php echo $address['is_default'] ? 'checked' : ''; ?>>
                                                <label class="form-check-label p-3 bg-light rounded-3 w-100">
                                                    <strong class="d-block mb-2">
                                                        <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                                                    </strong>
                                                    <span class="text-muted d-block">
                                                        <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                                        <?php if ($address['address_line_2']): ?>
                                                            <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                                        <?php endif; ?>
                                                        <?php echo htmlspecialchars($address['postal_code'] . ' ' . $address['city']); ?><br>
                                                        <?php echo htmlspecialchars($address['country']); ?>
                                                    </span>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <div class="text-center py-4">
                                        <i class="fas fa-home fa-3x text-muted mb-3"></i>
                                        <p class="text-muted">Aucune adresse de livraison enregistrée.</p>
                                        <a href="address.php?type=shipping&redirect=checkout.php" 
                                           class="btn btn-primary">
                                            <i class="fas fa-plus me-2"></i>Ajouter une adresse
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Méthode de paiement -->
                        <div class="card shadow-sm rounded-3 mb-4">
                            <div class="card-header bg-gradient">
                                <h2 class="h4 mb-0"><i class="fas fa-credit-card me-2"></i>Méthode de paiement</h2>
                            </div>
                            <div class="card-body">
                                <div class="payment-methods">
                                    <div class="payment-method-card mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                   name="payment_method" value="card" checked>
                                            <label class="form-check-label p-3 bg-light rounded-3 w-100">
                                                <span class="d-flex align-items-center">
                                                    <i class="fas fa-credit-card fa-2x text-primary me-3"></i>
                                                    <span>
                                                        <strong class="d-block">Carte bancaire</strong>
                                                        <small class="text-muted">Visa, Mastercard, CMI</small>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="payment-method-card mb-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="radio" 
                                                   name="payment_method" value="paypal">
                                            <label class="form-check-label p-3 bg-light rounded-3 w-100">
                                                <span class="d-flex align-items-center">
                                                    <i class="fab fa-paypal fa-2x text-primary me-3"></i>
                                                    <span>
                                                        <strong class="d-block">PayPal</strong>
                                                        <small class="text-muted">Paiement sécurisé via PayPal</small>
                                                    </span>
                                                </span>
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary btn-lg w-100 mb-4 py-3 shadow-sm">
                            <i class="fas fa-lock me-2"></i>Procéder au paiement sécurisé
                        </button>
                    </form>
                </div>

                <!-- Résumé de la commande -->
                <div class="col-md-4">
                    <div class="card shadow-sm rounded-3 sticky-md-top" style="top: 2rem;">
                        <div class="card-header bg-gradient">
                            <h2 class="h4 mb-0"><i class="fas fa-receipt me-2"></i>Résumé</h2>
                        </div>
                        <div class="card-body">
                            <div class="price-details">
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">Sous-total</span>
                                    <strong><?php echo formatPrice($subtotal); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">TVA (20%)</span>
                                    <strong><?php echo formatPrice($taxAmount); ?></strong>
                                </div>
                                <div class="d-flex justify-content-between mb-3">
                                    <span class="text-muted">
                                        <i class="fas fa-truck me-1"></i>Livraison
                                    </span>
                                    <strong><?php echo formatPrice($shippingCost); ?></strong>
                                </div>
                                <hr class="my-4">
                                <div class="d-flex justify-content-between align-items-center">
                                    <span class="h5 mb-0">Total TTC</span>
                                    <strong class="h4 mb-0 text-primary">
                                        <?php echo formatPrice($total); ?>
                                    </strong>
                                </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <?php require_once ROOT_PATH . '/includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>