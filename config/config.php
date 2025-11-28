<?php
// Configuration principale du projet
define('SITE_NAME', 'E-Commerce Multimédia Maroc');
define('SITE_URL', 'http://localhost/workspace');
define('SITE_VERSION', '1.0.0');

// Configuration base de données
define('DB_HOST', 'localhost');
define('DB_NAME', 'ecommerce_multimedia');
define('DB_USER', 'root');
define('DB_PASS', '');

// Configuration sécurité
define('JWT_SECRET', 'your_jwt_secret_key_here');
define('ENCRYPTION_KEY', 'your_encryption_key_here');

// Configuration reCAPTCHA
define('CAPTCHA_ENABLED', true);
define('RECAPTCHA_SITE_KEY', '6LeOT_UrAAAAAHrIbwL4nuGogUs55QC9rODQa10r');
define('RECAPTCHA_SECRET_KEY', '6LeOT_UrAAAAAGwgxuX8FnWIsI2tIu9rV2WCVX_F');

// Configuration fichiers
define('UPLOAD_PATH', 'uploads/');
define('MAX_FILE_SIZE', 5 * 1024 * 1024); // 5MB
define('ALLOWED_IMAGE_TYPES', ['jpg', 'jpeg', 'png', 'gif', 'webp']);

// Configuration PayPal
define('PAYPAL_CLIENT_ID', 'your_paypal_client_id');
define('PAYPAL_CLIENT_SECRET', 'your_paypal_client_secret');
define('PAYPAL_MODE', 'sandbox'); // 'sandbox' ou 'live'

// Configuration biométrique
define('BIOMETRIC_ENABLED', true);
define('BIOMETRIC_TIMEOUT', 300); // 5 minutes

// Configuration JpGraph
define('JPGRAPH_PATH', 'libs/jpgraph/');

// Configuration email
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your_email@gmail.com');
define('SMTP_PASSWORD', 'your_app_password');

// Configuration du site
define('ITEMS_PER_PAGE', 12);
define('CART_SESSION_TIMEOUT', 3600); // 1 heure

// Erreurs: ne pas afficher les erreurs PHP dans les réponses (les consigner dans le log)
ini_set('display_errors', '0');
ini_set('display_startup_errors', '0');
error_reporting(E_ALL);
ini_set('log_errors', '1');

// Connexion à la base de données
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
} catch(PDOException $e) {
    error_log("Erreur de connexion à la base de données : " . $e->getMessage());
    die("Erreur de connexion à la base de données");
}

// Démarrage de session
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// Fonction d'autoload pour les classes
spl_autoload_register(function ($class) {
    $directories = [
        'classes/',
        'models/',
        'controllers/',
        'libs/'
    ];
    
    foreach ($directories as $directory) {
        $file = $directory . $class . '.php';
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Fonctions utilitaires globales
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

function generateCSRFToken() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function redirect($url) {
    header("Location: " . $url);
    exit();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        redirect('login.php');
    }
}

function formatPrice($price) {
    return number_format($price, 2, ',', ' ') . ' DH';
}

function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    global $pdo;
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    return $stmt->fetch();
}

// Gestion des erreurs
set_error_handler(function($severity, $message, $file, $line) {
    error_log("Erreur PHP [$severity]: $message dans $file ligne $line");
});

// Configuration du fuseau horaire
date_default_timezone_set('Africa/Casablanca');
?>
