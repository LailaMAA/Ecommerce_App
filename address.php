<?php
require_once __DIR__ . '/bootstrap.php';
require_once ROOT_PATH . '/classes/Security.php';

// Vérification de l'authentification
requireLogin();

// Type d'adresse (shipping ou billing)
$type = $_GET['type'] ?? 'shipping';
$redirect = $_GET['redirect'] ?? 'profile.php';

// Récupération des adresses existantes
$stmt = $pdo->prepare("
    SELECT * FROM addresses 
    WHERE user_id = ? AND type = ?
    ORDER BY is_default DESC, created_at DESC
");
$stmt->execute([$_SESSION['user_id'], $type]);
$addresses = $stmt->fetchAll();

$error = '';
$success = '';

// Traitement du formulaire
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        // Vérification CSRF
        if (!Security::verifyCSRFToken($_POST['csrf_token'] ?? '')) {
            throw new Exception("Token de sécurité invalide");
        }

        $action = $_POST['action'] ?? '';

        if ($action === 'add' || $action === 'edit') {
            // Validation des données
            $required = ['first_name', 'last_name', 'address_line_1', 'city', 'postal_code', 'country'];
            foreach ($required as $field) {
                if (empty($_POST[$field])) {
                    throw new Exception("Le champ " . str_replace('_', ' ', $field) . " est requis");
                }
            }

            $data = [
                'user_id' => $_SESSION['user_id'],
                'type' => $type,
                'first_name' => trim($_POST['first_name']),
                'last_name' => trim($_POST['last_name']),
                'company' => trim($_POST['company'] ?? ''),
                'address_line_1' => trim($_POST['address_line_1']),
                'address_line_2' => trim($_POST['address_line_2'] ?? ''),
                'city' => trim($_POST['city']),
                'postal_code' => trim($_POST['postal_code']),
                'country' => trim($_POST['country']),
                'phone' => trim($_POST['phone'] ?? ''),
                'is_default' => isset($_POST['is_default']) ? 1 : 0
            ];

            if ($action === 'add') {
                // Si c'est la première adresse ou définie comme par défaut
                if ($data['is_default'] || count($addresses) === 0) {
                    $pdo->prepare("
                        UPDATE addresses 
                        SET is_default = 0 
                        WHERE user_id = ? AND type = ?
                    ")->execute([$_SESSION['user_id'], $type]);
                    $data['is_default'] = 1;
                }

                $columns = implode(', ', array_keys($data));
                $values = implode(', ', array_fill(0, count($data), '?'));
                
                $stmt = $pdo->prepare("
                    INSERT INTO addresses ({$columns}) 
                    VALUES ({$values})
                ");
                $stmt->execute(array_values($data));
                
                $success = "Adresse ajoutée avec succès";
            } else {
                // Modification d'une adresse existante
                $addressId = (int)$_POST['address_id'];
                
                // Vérifier que l'adresse appartient à l'utilisateur
                $stmt = $pdo->prepare("
                    SELECT id FROM addresses 
                    WHERE id = ? AND user_id = ?
                ");
                $stmt->execute([$addressId, $_SESSION['user_id']]);
                if (!$stmt->fetch()) {
                    throw new Exception("Adresse invalide");
                }

                // Si définie comme par défaut, mettre à jour les autres
                if ($data['is_default']) {
                    $pdo->prepare("
                        UPDATE addresses 
                        SET is_default = 0 
                        WHERE user_id = ? AND type = ? AND id != ?
                    ")->execute([$_SESSION['user_id'], $type, $addressId]);
                }

                // Construction de la requête UPDATE
                $updates = [];
                foreach ($data as $key => $value) {
                    if ($key !== 'user_id') { // Ne pas mettre à jour user_id
                        $updates[] = "{$key} = ?";
                    }
                }
                $updateStr = implode(', ', $updates);
                
                $stmt = $pdo->prepare("
                    UPDATE addresses 
                    SET {$updateStr}
                    WHERE id = ? AND user_id = ?
                ");

                $values = array_values($data);
                unset($values[0]); // Retirer user_id
                $values[] = $addressId;
                $values[] = $_SESSION['user_id'];
                
                $stmt->execute($values);
                
                $success = "Adresse mise à jour avec succès";
            }

            // Redirection si demandée
            if ($success && $redirect) {
                redirect($redirect);
            }

        } elseif ($action === 'delete') {
            $addressId = (int)$_POST['address_id'];
            
            // Vérifier que l'adresse appartient à l'utilisateur
            $stmt = $pdo->prepare("
                DELETE FROM addresses 
                WHERE id = ? AND user_id = ?
            ");
            $stmt->execute([$addressId, $_SESSION['user_id']]);
            
            if ($stmt->rowCount() > 0) {
                $success = "Adresse supprimée avec succès";
                if ($redirect) {
                    redirect($redirect);
                }
            } else {
                throw new Exception("Adresse invalide");
            }
        }

    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Génération du token CSRF
$csrfToken = Security::generateCSRFToken();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $type === 'shipping' ? 'Adresse de livraison' : 'Adresse de facturation'; ?> - <?php echo SITE_NAME; ?></title>
    <meta name="csrf-token" content="<?php echo $csrfToken; ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="<?php echo isLoggedIn() ? 'logged-in' : ''; ?>">
    <?php require_once ROOT_PATH . '/includes/header.php'; ?>

    <main class="container py-4">
        <div class="page-header mb-4">
            <h1 class="display-4">
                <?php echo $type === 'shipping' ? 'Adresse de livraison' : 'Adresse de facturation'; ?>
            </h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>">Accueil</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo SITE_URL; ?>/profile.php">Mon compte</a></li>
                    <li class="breadcrumb-item active" aria-current="page">
                        <?php echo $type === 'shipping' ? 'Adresse de livraison' : 'Adresse de facturation'; ?>
                    </li>
                </ol>
            </nav>
        </div>

        <?php if ($error): ?>
            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>

        <?php if ($success): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-8">
                <!-- Formulaire d'ajout/modification d'adresse -->
                <div class="card shadow-sm rounded-3 mb-4">
                    <div class="card-header bg-gradient">
                        <h2 class="h4 mb-0">
                            <i class="fas fa-plus-circle me-2"></i>Nouvelle adresse
                        </h2>
                    </div>
                    <div class="card-body">
                        <form action="address.php?type=<?php echo $type; ?>&redirect=<?php echo urlencode($redirect); ?>" 
                              method="post" class="needs-validation" novalidate>
                            
                            <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                            <input type="hidden" name="action" value="add">
                            
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label for="first_name" class="form-label">Prénom *</label>
                                    <input type="text" class="form-control" id="first_name" name="first_name" required>
                                </div>
                                
                                <div class="col-md-6">
                                    <label for="last_name" class="form-label">Nom *</label>
                                    <input type="text" class="form-control" id="last_name" name="last_name" required>
                                </div>

                                <div class="col-12">
                                    <label for="company" class="form-label">Société</label>
                                    <input type="text" class="form-control" id="company" name="company">
                                </div>

                                <div class="col-12">
                                    <label for="address_line_1" class="form-label">Adresse *</label>
                                    <input type="text" class="form-control" id="address_line_1" name="address_line_1" required>
                                </div>

                                <div class="col-12">
                                    <label for="address_line_2" class="form-label">Complément d'adresse</label>
                                    <input type="text" class="form-control" id="address_line_2" name="address_line_2">
                                </div>

                                <div class="col-md-6">
                                    <label for="postal_code" class="form-label">Code postal *</label>
                                    <input type="text" class="form-control" id="postal_code" name="postal_code" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="city" class="form-label">Ville *</label>
                                    <input type="text" class="form-control" id="city" name="city" required>
                                </div>

                                <div class="col-md-6">
                                    <label for="country" class="form-label">Pays *</label>
                                    <select class="form-select" id="country" name="country" required>
                                        <option value="Maroc" selected>Maroc</option>
                                        <option value="France">France</option>
                                        <option value="Belgique">Belgique</option>
                                        <option value="Suisse">Suisse</option>
                                    </select>
                                </div>

                                <div class="col-md-6">
                                    <label for="phone" class="form-label">Téléphone</label>
                                    <input type="tel" class="form-control" id="phone" name="phone">
                                </div>

                                <div class="col-12">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="is_default" name="is_default">
                                        <label class="form-check-label" for="is_default">
                                            Définir comme adresse par défaut
                                        </label>
                                    </div>
                                </div>
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-2"></i>Enregistrer l'adresse
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Liste des adresses existantes -->
                <div class="card shadow-sm rounded-3">
                    <div class="card-header bg-gradient">
                        <h2 class="h4 mb-0">
                            <i class="fas fa-list me-2"></i>Adresses enregistrées
                        </h2>
                    </div>
                    <div class="card-body">
                        <?php if (empty($addresses)): ?>
                            <p class="text-muted text-center py-4">
                                <i class="fas fa-map-marker-alt fa-3x mb-3"></i><br>
                                Aucune adresse enregistrée
                            </p>
                        <?php else: ?>
                            <?php foreach ($addresses as $address): ?>
                                <div class="address-card mb-3">
                                    <div class="p-3 bg-light rounded-3">
                                        <?php if ($address['is_default']): ?>
                                            <span class="badge bg-primary mb-2">Par défaut</span>
                                        <?php endif; ?>
                                        
                                        <h5 class="mb-1">
                                            <?php echo htmlspecialchars($address['first_name'] . ' ' . $address['last_name']); ?>
                                        </h5>
                                        
                                        <?php if ($address['company']): ?>
                                            <div class="text-muted mb-1">
                                                <?php echo htmlspecialchars($address['company']); ?>
                                            </div>
                                        <?php endif; ?>
                                        
                                        <div class="text-muted small">
                                            <?php echo htmlspecialchars($address['address_line_1']); ?><br>
                                            <?php if ($address['address_line_2']): ?>
                                                <?php echo htmlspecialchars($address['address_line_2']); ?><br>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($address['postal_code'] . ' ' . $address['city']); ?><br>
                                            <?php echo htmlspecialchars($address['country']); ?>
                                            
                                            <?php if ($address['phone']): ?>
                                                <br><?php echo htmlspecialchars($address['phone']); ?>
                                            <?php endif; ?>
                                        </div>
                                        
                                        <div class="mt-3">
                                            <form action="address.php?type=<?php echo $type; ?>&redirect=<?php echo urlencode($redirect); ?>" 
                                                  method="post" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?php echo $csrfToken; ?>">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="address_id" value="<?php echo $address['id']; ?>">
                                                
                                                <button type="submit" class="btn btn-danger btn-sm" 
                                                        onclick="return confirm('Êtes-vous sûr de vouloir supprimer cette adresse ?')">
                                                    <i class="fas fa-trash me-1"></i>Supprimer
                                                </button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </main>

    <?php require_once ROOT_PATH . '/includes/footer.php'; ?>
    
    <script src="assets/js/main.js"></script>
    <script>
    // Validation des formulaires
    (function() {
        'use strict';
        
        const forms = document.querySelectorAll('.needs-validation');
        
        Array.from(forms).forEach(function(form) {
            form.addEventListener('submit', function(event) {
                if (!form.checkValidity()) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    })();
    </script>
</body>
</html>