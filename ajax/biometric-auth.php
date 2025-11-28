<?php
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../classes/Security.php';
require_once __DIR__ . '/../models/User.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Méthode non autorisée']);
    exit;
}

// Vérification de l'authentification
if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Non authentifié']);
    exit;
}

try {
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $biometricData = $input['biometric_data'] ?? '';
    
    if (!$action || !$biometricData) {
        throw new Exception('Action et données biométriques requises');
    }
    
    $user = new User($pdo);
    
    if ($action === 'enable') {
        // Activation de l'authentification biométrique
        $user->enableBiometric($_SESSION['user_id'], $biometricData);
        
        Security::logActivity('biometric_enabled', 'Authentification biométrique activée', $_SESSION['user_id']);
        
        echo json_encode([
            'success' => true,
            'message' => 'Authentification biométrique activée avec succès !'
        ]);
        
    } elseif ($action === 'verify') {
        // Vérification biométrique
        if ($user->verifyBiometric($_SESSION['user_id'], $biometricData)) {
            $_SESSION['biometric_verified'] = true;
            $_SESSION['biometric_verified_at'] = time();
            
            Security::logActivity('biometric_verified', 'Authentification biométrique réussie', $_SESSION['user_id']);
            
            echo json_encode([
                'success' => true,
                'message' => 'Authentification biométrique réussie',
                'redirect' => $_SESSION['redirect_after_biometric'] ?? 'dashboard.php'
            ]);
        } else {
            throw new Exception('Authentification biométrique échouée');
        }
        
    } else {
        throw new Exception('Action non reconnue');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
