<?php
class PaymentManager {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Crée une commande dans la base de données
     */
    public function createOrder($data) {
        try {
            $this->pdo->beginTransaction();
            
            // Validation des données requises
            if (empty($data['shipping_address'])) {
                throw new Exception("L'adresse de livraison est obligatoire");
            }
            
            // Génération du numéro de commande
            $orderNumber = $this->generateOrderNumber();
            
            // Création de la commande
            $stmt = $this->pdo->prepare("
                INSERT INTO orders (
                    user_id, order_number, shipping_address_id, billing_address_id,
                    subtotal, tax_amount, shipping_amount, total_amount, payment_method
                ) VALUES (
                    :user_id, :order_number, :shipping_address_id, :billing_address_id,
                    :subtotal, :tax_amount, :shipping_amount, :total_amount, :payment_method
                )
            ");
            
            $stmt->execute([
                'user_id' => $data['user_id'],
                'order_number' => $orderNumber,
                'shipping_address_id' => $data['shipping_address'],
                'billing_address_id' => $data['billing_address'] ?? $data['shipping_address'],
                'subtotal' => $data['subtotal'],
                'tax_amount' => $data['tax_amount'],
                'shipping_amount' => $data['shipping_amount'],
                'total_amount' => $data['total_amount'],
                'payment_method' => $data['payment_method']
            ]);
            
            $orderId = $this->pdo->lastInsertId();
            
            // Ajout des produits à la commande
            foreach ($data['items'] as $item) {
                $stmt = $this->pdo->prepare("
                    INSERT INTO order_items (
                        order_id, product_id, quantity, unit_price, total_price
                    ) VALUES (
                        :order_id, :product_id, :quantity, :unit_price, :total_price
                    )
                ");
                
                $stmt->execute([
                    'order_id' => $orderId,
                    'product_id' => $item['product_id'],
                    'quantity' => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'total_price' => $item['total_price']
                ]);
            }
            
            // Vider le panier
            $stmt = $this->pdo->prepare("
                DELETE FROM cart WHERE user_id = :user_id
            ");
            $stmt->execute(['user_id' => $data['user_id']]);
            
            $this->pdo->commit();
            return $orderId;
            
        } catch (Exception $e) {
            $this->pdo->rollBack();
            error_log("Erreur création commande : " . $e->getMessage());
            throw $e;
        }
    }
    
    /**
     * Génère un numéro de commande unique
     */
    private function generateOrderNumber() {
        $prefix = date('Ymd');
        $random = strtoupper(substr(uniqid(), -5));
        return $prefix . $random;
    }
    
    /**
     * Met à jour le statut de paiement d'une commande
     */
    public function updateOrderPaymentStatus($orderId, $status, $paymentId = null) {
        $stmt = $this->pdo->prepare("
            UPDATE orders 
            SET payment_status = :status,
                payment_id = :payment_id,
                status = CASE 
                    WHEN :status = 'paid' THEN 'processing'
                    ELSE status 
                END
            WHERE id = :order_id
        ");
        
        return $stmt->execute([
            'status' => $status,
            'payment_id' => $paymentId,
            'order_id' => $orderId
        ]);
                status = CASE 
                    WHEN :status = 'paid' THEN 'processing'
                    ELSE status 
                END
            WHERE id = :order_id
        ");
        
        return $stmt->execute([
            'status' => $status,
            'payment_id' => $paymentId,
            'order_id' => $orderId
        ]);
    }
    
    /**
     * Met à jour les stocks après une commande
     */
private function updateProductStock($orderId) {
    $stmt = $this->pdo->prepare("
        UPDATE products p
        JOIN order_items oi ON p.id = oi.product_id
        SET p.stock_quantity = p.stock_quantity - oi.quantity
        WHERE oi.order_id = :order_id
    ");
    
    return $stmt->execute(['order_id' => $orderId]);
}

public function processCreditCardPayment($paymentData) {
    try {
        // Log de l'activité
                Security::logActivity('payment_completed', 'Paiement CB réussi', $_SESSION['user_id']);
                
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'transaction_id' => $transactionId
                ];
            } else {
                throw new Exception("Échec du traitement de la carte bancaire");
            }
            
        } catch (Exception $e) {
            error_log("Erreur paiement CB : " . $e->getMessage());
            throw $e;
        }
    }
    
    public function processMobileWalletPayment($walletData) {
        try {
            // Vérification de la compatibilité mobile
            if (!$this->isMobileDevice()) {
                throw new Exception("Paiement mobile non disponible sur cet appareil");
            }
            
            // Génération du numéro de commande
            $orderNumber = $this->generateOrderNumber();
            
            // Création de la commande en base
            $orderId = $this->createOrder($walletData, $orderNumber);
            
            // Simulation du paiement mobile (Apple Pay, Google Pay, etc.)
            $transactionId = $this->simulateMobileWalletPayment($walletData);
            
            if ($transactionId) {
                // Mise à jour de la commande
                $this->updateOrderStatus($orderId, 'paid', ['transaction_id' => $transactionId]);
                
                // Mise à jour du stock
                $this->updateProductStock($orderId);
                
                // Log de l'activité
                Security::logActivity('payment_completed', 'Paiement mobile réussi', $_SESSION['user_id']);
                
                return [
                    'success' => true,
                    'order_id' => $orderId,
                    'order_number' => $orderNumber,
                    'transaction_id' => $transactionId
                ];
            } else {
                throw new Exception("Échec du paiement mobile");
            }
            
        } catch (Exception $e) {
            error_log("Erreur paiement mobile : " . $e->getMessage());
            throw $e;
        }
    }
    
    private function createOrder($orderData, $orderNumber) {
        $stmt = $this->pdo->prepare("
            INSERT INTO orders (order_number, user_id, total_amount, subtotal, tax_amount, shipping_amount, 
                              payment_method, shipping_address, billing_address, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')
        ");
        
        $stmt->execute([
            $orderNumber,
            $_SESSION['user_id'],
            $orderData['total_amount'],
            $orderData['subtotal'],
            $orderData['tax_amount'] ?? 0,
            $orderData['shipping_amount'] ?? 0,
            $orderData['payment_method'],
            json_encode($orderData['shipping_address']),
            json_encode($orderData['billing_address'])
        ]);
        
        $orderId = $this->pdo->lastInsertId();
        
        // Ajout des articles de la commande
        foreach ($orderData['items'] as $item) {
            $stmt = $this->pdo->prepare("
                INSERT INTO order_items (order_id, product_id, product_name, product_sku, quantity, price, total) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $orderId,
                $item['product_id'],
                $item['product_name'],
                $item['product_sku'],
                $item['quantity'],
                $item['price'],
                $item['total']
            ]);
        }
        
        return $orderId;
    }
    
    private function preparePayPalData($orderData, $orderNumber) {
        $items = [];
        foreach ($orderData['items'] as $item) {
            $items[] = [
                'name' => $item['product_name'],
                'sku' => $item['product_sku'],
                'price' => number_format($item['price'], 2, '.', ''),
                'currency' => 'MAD',
                'quantity' => $item['quantity']
            ];
        }
        
        return [
            'intent' => 'sale',
            'payer' => [
                'payment_method' => 'paypal'
            ],
            'transactions' => [
                [
                    'amount' => [
                        'total' => number_format($orderData['total_amount'], 2, '.', ''),
                        'currency' => 'MAD',
                        'details' => [
                            'subtotal' => number_format($orderData['subtotal'], 2, '.', ''),
                            'tax' => number_format($orderData['tax_amount'] ?? 0, 2, '.', ''),
                            'shipping' => number_format($orderData['shipping_amount'] ?? 0, 2, '.', '')
                        ]
                    ],
                    'description' => 'Commande ' . $orderNumber,
                    'item_list' => [
                        'items' => $items
                    ]
                ]
            ],
            'redirect_urls' => [
                'return_url' => SITE_URL . '/payment-success.php',
                'cancel_url' => SITE_URL . '/payment-cancel.php'
            ]
        ];
    }
    
    private function createPayPalOrderAPI($data) {
        $accessToken = $this->getPayPalAccessToken();
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . '/v1/payments/payment');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 201) {
            throw new Exception("Erreur création commande PayPal : " . $response);
        }
        
        return json_decode($response, true);
    }
    
    private function executePayPalPaymentAPI($orderId, $payerId) {
        $accessToken = $this->getPayPalAccessToken();
        
        $data = [
            'payer_id' => $payerId
        ];
        
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . '/v1/payments/payment/' . $orderId . '/execute');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Authorization: Bearer ' . $accessToken
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new Exception("Erreur exécution paiement PayPal : " . $response);
        }
        
        return json_decode($response, true);
    }
    
    private function getPayPalAccessToken() {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $this->paypalUrl . '/v1/oauth2/token');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, 'grant_type=client_credentials');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
            'Accept-Language: en_US'
        ]);
        curl_setopt($ch, CURLOPT_USERPWD, $this->paypalClientId . ':' . $this->paypalClientSecret);
        
        $response = curl_exec($ch);
        curl_close($ch);
        
        $data = json_decode($response, true);
        return $data['access_token'];
    }
    
    // The following methods were duplicates and have been removed to resolve the "Unexpected 'private'" error.
    
    private function simulateCreditCardPayment($paymentData) {
        // Simulation du traitement CB
        // En production, utiliser Stripe, Square, ou un autre processeur
        return 'CB_' . uniqid() . '_' . time();
    }
    
    private function simulateMobileWalletPayment($walletData) {
        // Simulation du paiement mobile
        return 'MOBILE_' . uniqid() . '_' . time();
    }
    
    private function isMobileDevice() {
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        return preg_match('/(android|iphone|ipad|mobile)/i', $userAgent);
    }
    
    public function getOrderById($orderId) {
        $stmt = $this->pdo->prepare("
            SELECT o.*, u.first_name, u.last_name, u.email 
            FROM orders o 
            JOIN users u ON o.user_id = u.id 
            WHERE o.id = ?
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetch();
    }
    
    public function getOrderItems($orderId) {
        $stmt = $this->pdo->prepare("\
            SELECT oi.*, p.id as product_id, p.name as product_name, pi.file_path AS image_url\
            FROM order_items oi \
            LEFT JOIN products p ON oi.product_id = p.id \
            LEFT JOIN product_images pi ON pi.product_id = p.id AND pi.is_primary = 1 \
            WHERE oi.order_id = ?\
        ");
        $stmt->execute([$orderId]);
        return $stmt->fetchAll();
    }
    
    public function getUserOrders($userId) {
        $stmt = $this->pdo->prepare("
            SELECT * FROM orders 
            WHERE user_id = ? 
            ORDER BY created_at DESC
        ");
        $stmt->execute([$userId]);
        return $stmt->fetchAll();
    }
?>
