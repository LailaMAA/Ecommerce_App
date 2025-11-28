<?php
require_once __DIR__ . '/../bootstrap.php';

try {
    require_once ROOT_PATH . '/classes/Security.php';

    header('Content-Type: application/json');

    error_log("Debug add-to-cart: Starting request");

    // Read JSON input early so we can validate CSRF from AJAX payload
    $rawInput = file_get_contents('php://input');
    error_log("Debug add-to-cart: Raw input: " . $rawInput);
    $input = json_decode($rawInput, true) ?? [];

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
        exit;
    }

    // Vérification de l'authentification
    if (!isLoggedIn()) {
        error_log("Debug add-to-cart: Non authentifié");
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }

    // Vérification du token CSRF
    // Accept CSRF token from JSON payload (AJAX) or from X-CSRF-Token header
    $csrfToken = $input['csrf_token'] ?? null;
    if (!$csrfToken) {
        $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    }
    error_log("Debug add-to-cart: CSRF token present: " . ($csrfToken ? 'yes' : 'no'));

    if (!Security::verifyCSRFToken($csrfToken ?? '')) {
        error_log("Debug add-to-cart: CSRF invalide");
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }

    // Process the cart request
    try {
        $productId = $input['product_id'] ?? null;
        $quantity = $input['quantity'] ?? 1;
        error_log("Debug add-to-cart: Product ID: $productId, Quantity: $quantity");
        
        if (!$productId) {
            throw new Exception('ID produit requis');
        }
        
        // Vérification de l'existence du produit
        $stmt = $pdo->prepare("SELECT * FROM products WHERE id = ? AND active = 1");
        $stmt->execute([$productId]);
        $product = $stmt->fetch();
        
        if (!$product) {
            throw new Exception('Produit non trouvé');
        }
        
        // Vérification du stock
        if ($product['stock_quantity'] < $quantity) {
            throw new Exception('Stock insuffisant');
        }
        
        // Vérification si le produit est déjà dans le panier
        $stmt = $pdo->prepare("SELECT * FROM cart WHERE user_id = ? AND product_id = ?");
        $stmt->execute([$_SESSION['user_id'], $productId]);
        $existingItem = $stmt->fetch();
        
        if ($existingItem) {
            // Mise à jour de la quantité
            $newQuantity = $existingItem['quantity'] + $quantity;
            
            if ($newQuantity > $product['stock_quantity']) {
                throw new Exception('Quantité demandée supérieure au stock disponible');
            }
            
            $stmt = $pdo->prepare("UPDATE cart SET quantity = ?, updated_at = NOW() WHERE id = ?");
            $stmt->execute([$newQuantity, $existingItem['id']]);
        } else {
            // Ajout d'un nouvel article
            $stmt = $pdo->prepare("
                INSERT INTO cart (user_id, product_id, quantity, price) 
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'],
                $productId,
                $quantity,
                $product['sale_price'] ?: $product['price']
            ]);
        }
        
        // Log de l'activité
        Security::logActivity('add_to_cart', "Produit ajouté au panier: {$product['name']}", $_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Produit ajouté au panier',
            'product_name' => $product['name']
        ]);
        
    } catch (Throwable $e) {
        error_log("Debug add-to-cart inner error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
        throw $e; // Re-throw to outer handler
    }
    
} catch (Throwable $e) {
    error_log("Erreur add-to-cart: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur: ' . $e->getMessage(),
        'file' => str_replace($_SERVER['DOCUMENT_ROOT'], '', $e->getFile()),
        'line' => $e->getLine(),
        'debug' => [
            'post' => $_POST,
            'raw_input' => $rawInput ?? null,
            'session_active' => session_status() === PHP_SESSION_ACTIVE,
            'user_logged_in' => isset($_SESSION['user_id']),
            'csrf_token_present' => isset($csrfToken),
            'request_method' => $_SERVER['REQUEST_METHOD']
        ]
    ]);
}
?>
