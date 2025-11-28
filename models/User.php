<?php
require_once 'config/config.php';

class User {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function register($data) {
        // Vérification des données
        $required_fields = ['email', 'password', 'first_name', 'last_name'];
        foreach ($required_fields as $field) {
            if (empty($data[$field])) {
                throw new Exception("Le champ $field est requis");
            }
        }
        
        // Vérification de l'email
        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Adresse email invalide");
        }
        
        // Vérification de la force du mot de passe
        if (strlen($data['password']) < 8) {
            throw new Exception("Le mot de passe doit contenir au moins 8 caractères");
        }
        
        // Vérification si l'email existe déjà
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$data['email']]);
        if ($stmt->fetch()) {
            throw new Exception("Cette adresse email est déjà utilisée");
        }
        
        // Hash du mot de passe
        $hashedPassword = password_hash($data['password'], PASSWORD_DEFAULT);
        
        // Génération du token de vérification
        $verificationToken = bin2hex(random_bytes(32));
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (email, password, first_name, last_name, phone, 
                                 address, city, postal_code, country, verification_token) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            
            $stmt->execute([
                $data['email'],
                $hashedPassword,
                $data['first_name'],
                $data['last_name'],
                $data['phone'] ?? null,
                $data['address'] ?? null,
                $data['city'] ?? null,
                $data['postal_code'] ?? null,
                $data['country'] ?? 'Maroc',
                $verificationToken
            ]);
            
            $userId = $this->pdo->lastInsertId();
            
            // Envoi de l'email de vérification
            $this->sendVerificationEmail($data['email'], $verificationToken);
            
            return $userId;
            
        } catch (PDOException $e) {
            error_log("Erreur lors de l'inscription : " . $e->getMessage());
            throw new Exception("Erreur lors de l'inscription");
        }
    }
    
    public function login($email, $password, $rememberMe = false) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user || !password_verify($password, $user['password'])) {
            throw new Exception("Email ou mot de passe incorrect");
        }
        
        if (!$user['email_verified']) {
            throw new Exception("Veuillez vérifier votre adresse email avant de vous connecter");
        }
        
        // Mise à jour de la dernière connexion
        $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
        $stmt->execute([$user['id']]);
        
        // Création de la session
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['user_email'] = $user['email'];
        $_SESSION['user_name'] = $user['first_name'] . ' ' . $user['last_name'];
        
        // Session biométrique si activée
        if ($user['biometric_enabled']) {
            $_SESSION['biometric_required'] = true;
        }
        
        // Cookie "Se souvenir de moi"
        if ($rememberMe) {
            $token = bin2hex(random_bytes(32));
            $expires = time() + (30 * 24 * 60 * 60); // 30 jours
            
            setcookie('remember_token', $token, $expires, '/', '', false, true);
            
            // Sauvegarde en base
            $stmt = $this->pdo->prepare("
                INSERT INTO user_sessions (user_id, session_token, expires_at) 
                VALUES (?, ?, FROM_UNIXTIME(?))
            ");
            $stmt->execute([$user['id'], $token, $expires]);
        }
        
        return $user;
    }
    
    public function logout() {
        // Suppression du cookie "Se souvenir de moi"
        if (isset($_COOKIE['remember_token'])) {
            $stmt = $this->pdo->prepare("DELETE FROM user_sessions WHERE session_token = ?");
            $stmt->execute([$_COOKIE['remember_token']]);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // Destruction de la session
        session_destroy();
    }
    
    public function verifyEmail($token) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE verification_token = ?");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Token de vérification invalide");
        }
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET email_verified = TRUE, verification_token = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$user['id']]);
        
        return true;
    }
    
    public function resetPassword($email) {
        $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Aucun compte trouvé avec cette adresse email");
        }
        
        $resetToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + 3600); // 1 heure
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET reset_token = ?, reset_token_expires = ? 
            WHERE id = ?
        ");
        $stmt->execute([$resetToken, $expiresAt, $user['id']]);
        
        // Envoi de l'email de réinitialisation
        $this->sendPasswordResetEmail($email, $resetToken);
        
        return true;
    }
    
    public function updatePassword($token, $newPassword) {
        $stmt = $this->pdo->prepare("
            SELECT id FROM users 
            WHERE reset_token = ? AND reset_token_expires > NOW()
        ");
        $stmt->execute([$token]);
        $user = $stmt->fetch();
        
        if (!$user) {
            throw new Exception("Token de réinitialisation invalide ou expiré");
        }
        
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET password = ?, reset_token = NULL, reset_token_expires = NULL 
            WHERE id = ?
        ");
        $stmt->execute([$hashedPassword, $user['id']]);
        
        return true;
    }
    
    public function enableBiometric($userId, $biometricData) {
        // Chiffrement des données biométriques
        $encryptedData = $this->encryptBiometricData($biometricData);
        
        $stmt = $this->pdo->prepare("
            UPDATE users 
            SET biometric_data = ?, biometric_enabled = TRUE 
            WHERE id = ?
        ");
        $stmt->execute([$encryptedData, $userId]);
        
        return true;
    }
    
    public function verifyBiometric($userId, $biometricData) {
        $stmt = $this->pdo->prepare("SELECT biometric_data FROM users WHERE id = ? AND biometric_enabled = TRUE");
        $stmt->execute([$userId]);
        $user = $stmt->fetch();
        
        if (!$user) {
            return false;
        }
        
        $storedData = $this->decryptBiometricData($user['biometric_data']);
        
        // Comparaison des données biométriques (simplifiée)
        return $this->compareBiometricData($storedData, $biometricData);
    }
    
    public function getUserById($userId) {
        $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }
    
    public function updateProfile($userId, $data) {
        $allowedFields = ['first_name', 'last_name', 'phone', 'address', 'city', 'postal_code', 'country'];
        $updateFields = [];
        $values = [];
        
        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateFields[] = "$field = ?";
                $values[] = $data[$field];
            }
        }
        
        if (empty($updateFields)) {
            throw new Exception("Aucune donnée à mettre à jour");
        }
        
        $values[] = $userId;
        
        $sql = "UPDATE users SET " . implode(', ', $updateFields) . " WHERE id = ?";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($values);
        
        return true;
    }
    
    private function sendVerificationEmail($email, $token) {
        $verificationUrl = SITE_URL . "/verify-email.php?token=" . $token;
        $subject = "Vérification de votre compte - " . SITE_NAME;
        $message = "
            <h2>Bienvenue sur " . SITE_NAME . " !</h2>
            <p>Merci de vous être inscrit. Veuillez cliquer sur le lien ci-dessous pour vérifier votre compte :</p>
            <p><a href='$verificationUrl'>Vérifier mon compte</a></p>
            <p>Si le lien ne fonctionne pas, copiez et collez cette URL dans votre navigateur :</p>
            <p>$verificationUrl</p>
        ";
        
        $this->sendEmail($email, $subject, $message);
    }
    
    private function sendPasswordResetEmail($email, $token) {
        $resetUrl = SITE_URL . "/reset-password.php?token=" . $token;
        $subject = "Réinitialisation de votre mot de passe - " . SITE_NAME;
        $message = "
            <h2>Réinitialisation de mot de passe</h2>
            <p>Vous avez demandé la réinitialisation de votre mot de passe.</p>
            <p>Cliquez sur le lien ci-dessous pour créer un nouveau mot de passe :</p>
            <p><a href='$resetUrl'>Réinitialiser mon mot de passe</a></p>
            <p>Ce lien expire dans 1 heure.</p>
            <p>Si vous n'avez pas demandé cette réinitialisation, ignorez cet email.</p>
        ";
        
        $this->sendEmail($email, $subject, $message);
    }
    
    private function sendEmail($to, $subject, $message) {
        // Configuration SMTP (à adapter selon votre serveur)
        $headers = [
            'From: noreply@' . parse_url(SITE_URL, PHP_URL_HOST),
            'Reply-To: contact@' . parse_url(SITE_URL, PHP_URL_HOST),
            'Content-Type: text/html; charset=UTF-8',
            'X-Mailer: PHP/' . phpversion()
        ];
        
        // En production, utilisez une bibliothèque comme PHPMailer
        mail($to, $subject, $message, implode("\r\n", $headers));
    }
    
    private function encryptBiometricData($data) {
        $key = ENCRYPTION_KEY;
        $iv = random_bytes(16);
        $encrypted = openssl_encrypt($data, 'AES-256-CBC', $key, 0, $iv);
        return base64_encode($iv . $encrypted);
    }
    
    private function decryptBiometricData($encryptedData) {
        $key = ENCRYPTION_KEY;
        $data = base64_decode($encryptedData);
        $iv = substr($data, 0, 16);
        $encrypted = substr($data, 16);
        return openssl_decrypt($encrypted, 'AES-256-CBC', $key, 0, $iv);
    }
    
    private function compareBiometricData($stored, $provided) {
        // Implémentation simplifiée - en réalité, utilisez des algorithmes spécialisés
        return hash_equals($stored, $provided);
    }
}
?>
