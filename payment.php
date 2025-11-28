<?php
require_once __DIR__ . '/bootstrap.php';
require_once ROOT_PATH . '/classes/Security.php';
require_once ROOT_PATH . '/classes/PaymentManager.php';

// Vérification de l'authentification
requireLogin();

// Récupération de l'ID de la commande
$orderId = (int)($_GET['order_id'] ?? 0);
if (!$orderId) {
    redirect('checkout.php');
}

// Initialisation du gestionnaire de paiement
$paymentManager = new PaymentManager($pdo);

// Récupération des informations de la commande
try {
    $order = $paymentManager->getOrderById($orderId);
    if (!$order || $order['user_id'] != $_SESSION['user_id']) {
        throw new Exception("Commande introuvable");
    }
} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
    redirect('checkout.php');
}

// Traitement du paiement
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification CSRF
        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!Security::verifyCSRFToken($csrfToken)) {
            throw new Exception("Token de sécurité invalide");
        }

        // Traitement du paiement selon la méthode choisie
        $result = $paymentManager->processCreditCardPayment($orderId);
        
        if ($result['success']) {
            $_SESSION['success'] = "Paiement effectué avec succès";
            redirect('order-confirmation.php?order_id=' . $orderId);
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
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
            <h1 class="display-4">Paiement sécurisé</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/cart.php">Panier</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/checkout.php">Commande</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Paiement</li>
                </ol>
            </nav>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Formulaire de paiement -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-gradient">
                        <h2 class="h4 mb-0">
                            <i class="fas fa-credit-card me-2"></i>Informations de paiement
                        </h2>
                    </div>
                    <div class="card-body">
                        <form action="payment.php?order_id=<?php echo $orderId; ?>" method="POST" id="payment-form">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <!-- En développement : simulation du paiement -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Mode de développement : Le paiement sera simulé
                            </div>

                            <div class="text-center mt-4">
                                <button type="submit" class="btn btn-primary btn-lg py-3 px-5">
                                    <i class="fas fa-lock me-2"></i>Confirmer le paiement
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Récapitulatif de la commande -->
                <div class="card shadow-sm rounded-3 sticky-md-top" style="top: 2rem;">
                    <div class="card-header bg-gradient">
                        <h2 class="h4 mb-0">
                            <i class="fas fa-receipt me-2"></i>Récapitulatif
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="price-details">
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">Sous-total</span>
                                <strong><?php echo formatPrice($order['subtotal']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">TVA (20%)</span>
                                <strong><?php echo formatPrice($order['tax_amount']); ?></strong>
                            </div>
                            <div class="d-flex justify-content-between mb-3">
                                <span class="text-muted">
                                    <i class="fas fa-truck me-1"></i>Livraison
                                </span>
                                <strong><?php echo formatPrice($order['shipping_amount']); ?></strong>
                            </div>
                            <hr class="my-4">
                            <div class="d-flex justify-content-between align-items-center">
                                <span class="h5 mb-0">Total TTC</span>
                                <strong class="h4 mb-0 text-primary">
                                    <?php echo formatPrice($order['total_amount']); ?>
                                </strong>
                            </div>
                        </div>

                        <div class="address-details mt-4">
                            <h3 class="h5 mb-3">Adresse de livraison</h3>
                            <address class="mb-4">
                                <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_postal_code'] . ' ' . $order['shipping_city']); ?><br>
                                <?php echo htmlspecialchars($order['shipping_country']); ?>
                            </address>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once ROOT_PATH . '/includes/footer.php'; ?>
    <script src="assets/js/main.js"></script>
</body>
</html>