<?php
require_once __DIR__ . '/../bootstrap.php';
require_once ROOT_PATH . '/classes/Security.php';

header('Content-Type: application/json');

try {
    // Vérification de l'authentification
    if (!isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Non authentifié']);
        exit;
    }

    // Vérification CSRF
    $csrfToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!Security::verifyCSRFToken($csrfToken ?? '')) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token CSRF invalide']);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM cart 
        WHERE user_id = ?
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch();
    
    echo json_encode([
        'success' => true,
        'count' => (int)$result['count']
    ]);
    
} catch (Throwable $e) {
    error_log($e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Erreur serveur',
        'count' => 0
    ]);
}