<?php
require_once 'config/config.php';
require_once 'models/User.php';
require_once 'classes/Security.php';

// Vérification du mode maintenance
Security::checkMaintenanceMode();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Vérification du rate limiting
    if (!Security::rateLimit('login', 5, 300)) {
        $error = "Trop de tentatives de connexion. Veuillez attendre 5 minutes.";
    } else {
        try {
            // Vérification du token CSRF
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Token de sécurité invalide");
            }
            
            $email = Security::sanitizeInput($_POST['email'] ?? '');
            $password = $_POST['password'] ?? '';
            $rememberMe = isset($_POST['remember_me']);
            
            if (empty($email) || empty($password)) {
                throw new Exception("Email et mot de passe requis");
            }
            
            // Connexion de l'utilisateur
            $user = new User($pdo);
            $userData = $user->login($email, $password, $rememberMe);
            
            // Log de l'activité
            Security::logActivity('user_login', 'Connexion utilisateur', $userData['id']);
            
            // Redirection selon le type d'authentification requis
            if ($userData['biometric_enabled']) {
                $_SESSION['biometric_required'] = true;
                redirect('biometric-auth.php');
            } else {
                redirect('dashboard.php');
            }
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            Security::logActivity('login_failed', 'Échec connexion: ' . $e->getMessage());
        }
    }
}

$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Connexion - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="assets/css/style.css" rel="stylesheet">
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-store"></i> <?php echo SITE_NAME; ?>
            </a>
            <div class="navbar-nav ms-auto">
                <a class="nav-link" href="register.php">Inscription</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-sign-in-alt"></i> Connexion
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
                        
                        <form method="POST" action="" novalidate>
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-envelope"></i>
                                    </span>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="password" class="form-label">Mot de passe</label>
                                <div class="input-group">
                                    <span class="input-group-text">
                                        <i class="fas fa-lock"></i>
                                    </span>
                                    <input type="password" class="form-control" id="password" name="password" required>
                                    <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                        <i class="fas fa-eye"></i>
                                    </button>
                                </div>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="remember_me" name="remember_me">
                                <label class="form-check-label" for="remember_me">
                                    Se souvenir de moi
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-sign-in-alt"></i> Se connecter
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p><a href="forgot-password.php">Mot de passe oublié ?</a></p>
                            <p>Pas encore de compte ? <a href="register.php">S'inscrire</a></p>
                        </div>
                        
                        <!-- Authentification biométrique -->
                        <div class="mt-4 p-3 bg-light rounded">
                            <h6><i class="fas fa-fingerprint"></i> Connexion biométrique</h6>
                            <p class="small text-muted mb-2">
                                Activez l'authentification biométrique pour un accès plus sécurisé
                            </p>
                            <button type="button" class="btn btn-outline-primary btn-sm" id="enableBiometric">
                                <i class="fas fa-fingerprint"></i> Activer
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/login.js"></script>
</body>
</html>
