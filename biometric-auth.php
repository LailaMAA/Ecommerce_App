<?php
require_once 'config/config.php';
require_once 'models/User.php';
require_once 'classes/Security.php';

// Vérification de la session
if (!isLoggedIn()) {
    redirect('login.php');
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification du token CSRF
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token de sécurité invalide");
        }
        
        $action = $_POST['action'] ?? '';
        
        if ($action === 'enable') {
            // Activation de l'authentification biométrique
            $biometricData = $_POST['biometric_data'] ?? '';
            
            if (empty($biometricData)) {
                throw new Exception("Données biométriques requises");
            }
            
            $user = new User($pdo);
            $user->enableBiometric($_SESSION['user_id'], $biometricData);
            
            Security::logActivity('biometric_enabled', 'Authentification biométrique activée', $_SESSION['user_id']);
            $success = "Authentification biométrique activée avec succès !";
            
        } elseif ($action === 'verify') {
            // Vérification biométrique
            $biometricData = $_POST['biometric_data'] ?? '';
            
            if (empty($biometricData)) {
                throw new Exception("Données biométriques requises");
            }
            
            $user = new User($pdo);
            if ($user->verifyBiometric($_SESSION['user_id'], $biometricData)) {
                $_SESSION['biometric_verified'] = true;
                $_SESSION['biometric_verified_at'] = time();
                
                Security::logActivity('biometric_verified', 'Authentification biométrique réussie', $_SESSION['user_id']);
                
                // Redirection vers la page demandée
                $redirect = $_SESSION['redirect_after_biometric'] ?? 'dashboard.php';
                unset($_SESSION['redirect_after_biometric']);
                redirect($redirect);
            } else {
                throw new Exception("Authentification biométrique échouée");
            }
            
        } elseif ($action === 'disable') {
            // Désactivation de l'authentification biométrique
            $stmt = $pdo->prepare("UPDATE users SET biometric_enabled = FALSE, biometric_data = NULL WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            
            Security::logActivity('biometric_disabled', 'Authentification biométrique désactivée', $_SESSION['user_id']);
            $success = "Authentification biométrique désactivée avec succès !";
        }
        
    } catch (Exception $e) {
        $error = $e->getMessage();
        Security::logActivity('biometric_error', 'Erreur biométrique: ' . $e->getMessage(), $_SESSION['user_id']);
    }
}

// Vérification si l'utilisateur a déjà l'authentification biométrique activée
$user = new User($pdo);
$userData = $user->getUserById($_SESSION['user_id']);

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Authentification Biométrique - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
    <style>
        .biometric-container {
            min-height: 400px;
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
        }
        .fingerprint-scanner {
            width: 200px;
            height: 200px;
            border: 3px solid #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 20px 0;
            position: relative;
            animation: pulse 2s infinite;
        }
        .fingerprint-scanner.active {
            animation: scan 1s infinite;
        }
        @keyframes pulse {
            0% { border-color: #007bff; }
            50% { border-color: #28a745; }
            100% { border-color: #007bff; }
        }
        @keyframes scan {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .biometric-status {
            text-align: center;
            margin: 20px 0;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="logout.php">Déconnexion</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-fingerprint"></i> Authentification Biométrique
                        </h3>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-triangle"></i> <?php echo $error; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if ($success): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                            </div>
                        <?php endif; ?>
                        
                        <div class="biometric-container">
                            <?php if ($userData['biometric_enabled']): ?>
                                <!-- Mode vérification -->
                                <div class="biometric-status">
                                    <h4><i class="fas fa-fingerprint"></i> Vérification Biométrique</h4>
                                    <p class="text-muted">Placez votre doigt sur le scanner pour vous authentifier</p>
                                </div>
                                
                                <div class="fingerprint-scanner" id="fingerprintScanner">
                                    <i class="fas fa-fingerprint fa-4x text-primary"></i>
                                </div>
                                
                                <form method="POST" id="biometricForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="verify">
                                    <input type="hidden" name="biometric_data" id="biometricData">
                                </form>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-outline-secondary" onclick="simulateBiometric()">
                                        <i class="fas fa-hand-paper"></i> Simuler Scanner
                                    </button>
                                </div>
                                
                            <?php else: ?>
                                <!-- Mode activation -->
                                <div class="biometric-status">
                                    <h4><i class="fas fa-fingerprint"></i> Activation Biométrique</h4>
                                    <p class="text-muted">Activez l'authentification biométrique pour sécuriser votre compte</p>
                                </div>
                                
                                <div class="fingerprint-scanner" id="fingerprintScanner">
                                    <i class="fas fa-fingerprint fa-4x text-primary"></i>
                                </div>
                                
                                <form method="POST" id="biometricForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="enable">
                                    <input type="hidden" name="biometric_data" id="biometricData">
                                </form>
                                
                                <div class="mt-3">
                                    <button type="button" class="btn btn-primary" onclick="enableBiometric()">
                                        <i class="fas fa-fingerprint"></i> Activer
                                    </button>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Informations de sécurité -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6><i class="fas fa-shield-alt"></i> Sécurité</h6>
                            <ul class="small text-muted mb-0">
                                <li>Vos données biométriques sont chiffrées et stockées de manière sécurisée</li>
                                <li>L'authentification biométrique remplace le mot de passe pour certaines actions</li>
                                <li>Vous pouvez désactiver cette fonctionnalité à tout moment</li>
                            </ul>
                        </div>
                        
                        <?php if ($userData['biometric_enabled']): ?>
                            <div class="mt-3">
                                <form method="POST" onsubmit="return confirm('Êtes-vous sûr de vouloir désactiver l\'authentification biométrique ?')">
                                    <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                    <input type="hidden" name="action" value="disable">
                                    <button type="submit" class="btn btn-outline-danger btn-sm">
                                        <i class="fas fa-times"></i> Désactiver
                                    </button>
                                </form>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Simulation de l'authentification biométrique
        function simulateBiometric() {
            const scanner = document.getElementById('fingerprintScanner');
            scanner.classList.add('active');
            
            // Simulation du scan
            setTimeout(() => {
                const biometricData = generateBiometricData();
                document.getElementById('biometricData').value = biometricData;
                document.getElementById('biometricForm').submit();
            }, 2000);
        }
        
        function enableBiometric() {
            const scanner = document.getElementById('fingerprintScanner');
            scanner.classList.add('active');
            
            // Simulation de l'enregistrement
            setTimeout(() => {
                const biometricData = generateBiometricData();
                document.getElementById('biometricData').value = biometricData;
                document.getElementById('biometricForm').submit();
            }, 2000);
        }
        
        function generateBiometricData() {
            // Génération de données biométriques simulées
            const timestamp = Date.now();
            const randomData = Math.random().toString(36).substring(2, 15);
            return btoa(timestamp + '_' + randomData + '_' + Math.random().toString(36).substring(2, 15));
        }
        
        // Détection des capacités biométriques du navigateur
        if ('credentials' in navigator && 'create' in navigator.credentials) {
            console.log('Authentification biométrique supportée par le navigateur');
        }
        
        // WebAuthn API pour l'authentification biométrique réelle
        if ('PublicKeyCredential' in window) {
            console.log('WebAuthn API disponible');
        }
    </script>
</body>
</html>
