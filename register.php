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
    if (!Security::rateLimit('register', 3, 300)) {
        $error = "Trop de tentatives d'inscription. Veuillez attendre 5 minutes.";
    } else {
        try {
            // Vérification du token CSRF
            if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
                throw new Exception("Token de sécurité invalide");
            }
            
            // Vérification du captcha
            $captcha = new Captcha();
            if (!$captcha->verify($_POST['g-recaptcha-response'] ?? '')) {
                throw new Exception("Vérification captcha échouée");
            }
            
            // Validation des données
            $data = [
                'email' => Security::sanitizeInput($_POST['email'] ?? ''),
                'password' => $_POST['password'] ?? '',
                'password_confirm' => $_POST['password_confirm'] ?? '',
                'first_name' => Security::sanitizeInput($_POST['first_name'] ?? ''),
                'last_name' => Security::sanitizeInput($_POST['last_name'] ?? ''),
                'phone' => Security::sanitizeInput($_POST['phone'] ?? ''),
                'address' => Security::sanitizeInput($_POST['address'] ?? ''),
                'city' => Security::sanitizeInput($_POST['city'] ?? ''),
                'postal_code' => Security::sanitizeInput($_POST['postal_code'] ?? ''),
                'country' => Security::sanitizeInput($_POST['country'] ?? 'France')
            ];
            
            // Validation des champs requis
            if (empty($data['email']) || empty($data['password']) || 
                empty($data['first_name']) || empty($data['last_name'])) {
                throw new Exception("Tous les champs obligatoires doivent être remplis");
            }
            
            // Validation de l'email
            if (!Security::validateEmail($data['email'])) {
                throw new Exception("Adresse email invalide");
            }
            
            // Validation du mot de passe
            if (!Security::validatePassword($data['password'])) {
                throw new Exception("Le mot de passe doit contenir au moins 8 caractères avec une majuscule, une minuscule et un chiffre");
            }
            
            // Vérification de la confirmation du mot de passe
            if ($data['password'] !== $data['password_confirm']) {
                throw new Exception("Les mots de passe ne correspondent pas");
            }
            
            // Inscription de l'utilisateur
            $user = new User($pdo);
            $userId = $user->register($data);
            
            // Log de l'activité
            Security::logActivity('user_register', 'Nouvel utilisateur inscrit', $userId);
            
            $success = "Inscription réussie ! Un email de vérification a été envoyé à votre adresse.";
            
        } catch (Exception $e) {
            $error = $e->getMessage();
            Security::logActivity('register_failed', 'Échec inscription: ' . $e->getMessage());
        }
    }
}

$csrfToken = Security::generateCSRFToken();
$captcha = new Captcha();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inscription - <?php echo SITE_NAME; ?></title>
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
                <a class="nav-link" href="login.php">Connexion</a>
            </div>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-user-plus"></i> Inscription
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
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="first_name" class="form-label">Prénom *</label>
                                        <input type="text" class="form-control" id="first_name" name="first_name" 
                                               value="<?php echo htmlspecialchars($_POST['first_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="last_name" class="form-label">Nom *</label>
                                        <input type="text" class="form-control" id="last_name" name="last_name" 
                                               value="<?php echo htmlspecialchars($_POST['last_name'] ?? ''); ?>" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Adresse email *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Mot de passe *</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="form-text">
                                            Minimum 8 caractères avec majuscule, minuscule et chiffre
                                        </div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="password_confirm" class="form-label">Confirmer le mot de passe *</label>
                                        <input type="password" class="form-control" id="password_confirm" name="password_confirm" required>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="phone" class="form-label">Téléphone</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                            </div>
                            
                            <div class="mb-3">
                                <label for="address" class="form-label">Adresse</label>
                                <textarea class="form-control" id="address" name="address" rows="2"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="city" class="form-label">Ville</label>
                                        <input type="text" class="form-control" id="city" name="city" 
                                               value="<?php echo htmlspecialchars($_POST['city'] ?? ''); ?>">
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="postal_code" class="form-label">Code postal</label>
                                        <input type="text" class="form-control" id="postal_code" name="postal_code" 
                                               value="<?php echo htmlspecialchars($_POST['postal_code'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="country" class="form-label">Pays</label>
                                <select class="form-select" id="country" name="country">
                                    <option value="Maroc" <?php echo ($_POST['country'] ?? 'Maroc') === 'Maroc' ? 'selected' : ''; ?>>Maroc</option>
                                    <option value="France" <?php echo ($_POST['country'] ?? '') === 'France' ? 'selected' : ''; ?>>France</option>
                                    <option value="Belgique" <?php echo ($_POST['country'] ?? '') === 'Belgique' ? 'selected' : ''; ?>>Belgique</option>
                                    <option value="Suisse" <?php echo ($_POST['country'] ?? '') === 'Suisse' ? 'selected' : ''; ?>>Suisse</option>
                                    <option value="Canada" <?php echo ($_POST['country'] ?? '') === 'Canada' ? 'selected' : ''; ?>>Canada</option>
                                </select>
                            </div>
                            
                            <!-- Captcha -->
                            <div class="mb-3">
                                <?php echo $captcha->render(); ?>
                            </div>
                            
                            <div class="mb-3 form-check">
                                <input type="checkbox" class="form-check-input" id="terms" required>
                                <label class="form-check-label" for="terms">
                                    J'accepte les <a href="terms.php" target="_blank">conditions d'utilisation</a> et la <a href="privacy.php" target="_blank">politique de confidentialité</a>
                                </label>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-user-plus"></i> S'inscrire
                                </button>
                            </div>
                        </form>
                        
                        <div class="text-center mt-3">
                            <p>Déjà un compte ? <a href="login.php">Se connecter</a></p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/register.js"></script>
</body>
</html>
