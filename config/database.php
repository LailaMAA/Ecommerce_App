<?php
// Establish database connection if not already connected
global $pdo;

if (!isset($pdo)) {
    try {
        $pdo = new PDO(
            "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]
        );
    } catch (PDOException $e) {
        error_log("Database connection failed: " . $e->getMessage());
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
            exit;
        }
        throw $e;
    }
}

try {
    $pdo = new PDO(
        "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4",
        DB_USER,
        DB_PASS,
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false
        ]
    );
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if (!headers_sent()) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'Erreur de connexion à la base de données']);
        exit;
    }
    throw $e;
}

// Configuration du site
define('SITE_URL', 'http://localhost/workspace');
define('SITE_NAME', 'E-Commerce Multimédia');

// Configuration des images
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'gif']);

// Configuration PayPal
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret');
define('PAYPAL_MODE', 'sandbox'); // ou 'live' pour la production

// Configuration Captcha
define('RECAPTCHA_SITE_KEY', 'your_recaptcha_site_key');
define('RECAPTCHA_SECRET_KEY', 'your_recaptcha_secret_key');

// Configuration biométrique
define('BIOMETRIC_ENABLED', true);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch(PDOException $e) {
    die("Erreur de connexion à la base de données : " . $e->getMessage());
}
?>
