<?php
require_once __DIR__ . '/../config/config.php';

class Captcha {
    private $siteKey;
    private $secretKey;
    
    public function __construct() {
        $this->siteKey = RECAPTCHA_SITE_KEY;
        $this->secretKey = RECAPTCHA_SECRET_KEY;
    }
    
    public function render() {
        if (!CAPTCHA_ENABLED) {
            return '';
        }
        
        return '
        <div class="g-recaptcha" data-sitekey="' . $this->siteKey . '"></div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        ';
    }
    
    public function verify($response) {
        if (!CAPTCHA_ENABLED) {
            return true;
        }
        
        if (empty($response)) {
            return false;
        }
        
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        $data = [
            'secret' => $this->secretKey,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === FALSE) {
            return false;
        }
        
        $result = json_decode($result, true);
        return isset($result['success']) && $result['success'] === true;
    }
    
    public function renderInvisible() {
        if (!CAPTCHA_ENABLED) {
            return '';
        }
        
        return '
        <div class="g-recaptcha" 
             data-sitekey="' . $this->siteKey . '" 
             data-callback="onSubmit" 
             data-size="invisible">
        </div>
        <script src="https://www.google.com/recaptcha/api.js" async defer></script>
        ';
    }
}

class Security {
    public static function generateCSRFToken() {
        if (!isset($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }
    
    public static function verifyCSRFToken($token) {
        return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }
    
    public static function sanitizeInput($data) {
        if (is_array($data)) {
            return array_map([self::class, 'sanitizeInput'], $data);
        }
        return htmlspecialchars(strip_tags(trim($data)), ENT_QUOTES, 'UTF-8');
    }
    
    public static function validateEmail($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
    
    public static function validatePassword($password) {
        // Au moins 8 caractères, 1 majuscule, 1 minuscule, 1 chiffre
        return preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)[a-zA-Z\d@$!%*?&]{8,}$/', $password);
    }
    
    public static function rateLimit($action, $limit = 5, $window = 300) {
        $key = $action . '_' . $_SERVER['REMOTE_ADDR'];
        
        if (!isset($_SESSION['rate_limit'])) {
            $_SESSION['rate_limit'] = [];
        }
        
        $now = time();
        
        // Nettoyer les anciennes entrées
        if (isset($_SESSION['rate_limit'][$key])) {
            $_SESSION['rate_limit'][$key] = array_filter(
                $_SESSION['rate_limit'][$key],
                function($timestamp) use ($now, $window) {
                    return ($now - $timestamp) < $window;
                }
            );
        } else {
            $_SESSION['rate_limit'][$key] = [];
        }
        
        // Vérifier la limite
        if (count($_SESSION['rate_limit'][$key]) >= $limit) {
            return false;
        }
        
        // Ajouter la nouvelle tentative
        $_SESSION['rate_limit'][$key][] = $now;
        
        return true;
    }
    
    public static function logActivity($action, $description = '', $userId = null) {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("
                INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) 
                VALUES (?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $userId,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? '',
                $_SERVER['HTTP_USER_AGENT'] ?? ''
            ]);
        } catch (PDOException $e) {
            error_log("Erreur lors de l'enregistrement de l'activité : " . $e->getMessage());
        }
    }
    
    public static function checkMaintenanceMode() {
        global $pdo;
        
        try {
            $stmt = $pdo->prepare("SELECT setting_value FROM site_settings WHERE setting_key = 'maintenance_mode'");
            $stmt->execute();
            $result = $stmt->fetch();
            
            if ($result && $result['setting_value'] === 'true') {
                // Vérifier si l'utilisateur est admin
                if (!isset($_SESSION['is_admin']) || !$_SESSION['is_admin']) {
                    http_response_code(503);
                    include 'maintenance.php';
                    exit;
                }
            }
        } catch (PDOException $e) {
            error_log("Erreur lors de la vérification du mode maintenance : " . $e->getMessage());
        }
    }
    
    public static function generateSecureToken($length = 32) {
        return bin2hex(random_bytes($length));
    }
    
    public static function hashPassword($password) {
        return password_hash($password, PASSWORD_DEFAULT);
    }
    
    public static function verifyPassword($password, $hash) {
        return password_verify($password, $hash);
    }
    
    public static function encryptData($data, $key = null) {
        if ($key === null) {
            $key = ENCRYPTION_KEY;
        }
        
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    public static function decryptData($encryptedData, $key = null) {
        if ($key === null) {
            $key = ENCRYPTION_KEY;
        }
        
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    public static function validateFileUpload($file, $allowedTypes = null, $maxSize = null) {
        if ($allowedTypes === null) {
            $allowedTypes = ALLOWED_IMAGE_TYPES;
        }
        
        if ($maxSize === null) {
            $maxSize = MAX_FILE_SIZE;
        }
        
        // Vérifier les erreurs d'upload
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception("Erreur lors de l'upload du fichier");
        }
        
        // Vérifier la taille
        if ($file['size'] > $maxSize) {
            throw new Exception("Le fichier est trop volumineux");
        }
        
        // Vérifier le type MIME
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        
        $allowedMimes = [
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp'
        ];
        
        $extension = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($extension, $allowedTypes) || !isset($allowedMimes[$extension])) {
            throw new Exception("Type de fichier non autorisé");
        }
        
        if ($mimeType !== $allowedMimes[$extension]) {
            throw new Exception("Le type MIME du fichier ne correspond pas à son extension");
        }
        
        return true;
    }
    
    public static function generateFilename($originalName, $prefix = '') {
        $extension = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
        $filename = $prefix . uniqid() . '_' . time() . '.' . $extension;
        return $filename;
    }
}
?>
