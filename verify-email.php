<?php
require_once __DIR__ . '/config/config.php';

// Page simple pour vérifier l'email via le token envoyé par email
$message = '';
try {
    if (empty($_GET['token'])) {
        throw new Exception('Token manquant');
    }

    $token = $_GET['token'];

    // Le modèle User est autoloadé via config
    $userModel = new User($pdo);
    $userModel->verifyEmail($token);

    $message = 'Votre adresse email a été vérifiée avec succès. Vous pouvez maintenant vous connecter.';
} catch (Exception $e) {
    $message = 'Erreur de vérification : ' . $e->getMessage();
}
?>
<!doctype html>
<html lang="fr">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width,initial-scale=1">
    <title>Vérification email - <?php echo SITE_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-body text-center">
                        <?php if (strpos($message, 'succès') !== false): ?>
                            <h3 class="text-success">Vérification réussie</h3>
                            <p><?php echo htmlspecialchars($message); ?></p>
                            <a href="login.php" class="btn btn-primary">Se connecter</a>
                        <?php else: ?>
                            <h3 class="text-danger">Vérification échouée</h3>
                            <p><?php echo htmlspecialchars($message); ?></p>
                            <a href="register.php" class="btn btn-secondary">Retour à l'inscription</a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
