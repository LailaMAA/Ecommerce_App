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

// Génération du token CSRF
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation de commande - <?php echo SITE_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?php echo isLoggedIn() ? 'logged-in' : ''; ?>">
    <?php require_once ROOT_PATH . '/includes/header.php'; ?>

    <main class="container py-4">
        <div class="page-header mb-4">
            <h1 class="display-4">Confirmation de commande</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Confirmation</li>
                </ol>
            </nav>
        </div>

        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-body text-center py-5">
                        <i class="fas fa-check-circle text-success fa-5x mb-4"></i>
                        <h2 class="h3 mb-4">Merci pour votre commande !</h2>
                        <p class="lead mb-4">
                            Votre commande n°<?php echo htmlspecialchars($order['order_number']); ?> a été confirmée.
                        </p>
                        <p class="text-muted mb-4">
                            Un email de confirmation vous sera envoyé prochainement.
                        </p>
                        <div class="d-flex justify-content-center gap-3">
                            <a href="<?php echo SITE_URL; ?>" class="btn btn-primary">
                                <i class="fas fa-home me-2"></i>Retour à l'accueil
                            </a>
                            <a href="<?php echo SITE_URL; ?>/profile.php?tab=orders" class="btn btn-outline-primary">
                                <i class="fas fa-box me-2"></i>Voir mes commandes
                            </a>
                        </div>
                    </div>
                </div>

                <!-- Détails de la commande -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-gradient">
                        <h2 class="h4 mb-0">
                            <i class="fas fa-receipt me-2"></i>Détails de la commande
                        </h2>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h3 class="h5 mb-3">Adresse de livraison</h3>
                                <address>
                                    <?php echo htmlspecialchars($order['shipping_address']); ?><br>
                                    <?php echo htmlspecialchars($order['shipping_postal_code'] . ' ' . $order['shipping_city']); ?><br>
                                    <?php echo htmlspecialchars($order['shipping_country']); ?>
                                </address>
                            </div>
                            <div class="col-md-6">
                                <h3 class="h5 mb-3">Récapitulatif</h3>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Sous-total</span>
                                    <span><?php echo formatPrice($order['subtotal']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">TVA (20%)</span>
                                    <span><?php echo formatPrice($order['tax_amount']); ?></span>
                                </div>
                                <div class="d-flex justify-content-between mb-2">
                                    <span class="text-muted">Livraison</span>
                                    <span><?php echo formatPrice($order['shipping_amount']); ?></span>
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between fw-bold">
                                    <span>Total</span>
                                    <span class="text-primary">
                                        <?php echo formatPrice($order['total_amount']); ?>
                                    </span>
                                </div>
                            </div>
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