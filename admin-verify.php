<?php
// admin-verify.php
// Petit script local pour marquer un utilisateur comme vérifié.
// USAGE (local only):
// http://localhost/Workspace/admin-verify.php?secret=CHANGE_ME&email=user@example.com
// ou
// http://localhost/Workspace/admin-verify.php?secret=CHANGE_ME&token=THE_TOKEN

require_once __DIR__ . '/config/config.php';

// Security checks: only allow requests from localhost
$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1', 'localhost'])) {
    http_response_code(403);
    echo 'Forbidden: access allowed only from localhost.';
    exit;
}

// Simple secret to avoid accidental use. Change this value before using.
$ADMIN_SECRET = 'CHANGE_ME_SECRET';

$provided = $_GET['secret'] ?? '';
if ($provided !== $ADMIN_SECRET) {
    http_response_code(401);
    echo 'Missing or invalid secret. Edit admin-verify.php and set $ADMIN_SECRET before use.';
    exit;
}

$email = $_GET['email'] ?? null;
$token = $_GET['token'] ?? null;

if (!$email && !$token) {
    echo "Usage:\n";
    echo "  ?secret=...&email=user@example.com  -> mark by email\n";
    echo "  ?secret=...&token=THE_TOKEN         -> mark by token\n";
    exit;
}

try {
    if ($email) {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE email = ?");
        $stmt->execute([$email]);
        $count = $stmt->rowCount();
        echo "Updated rows: " . $count . "\n";
        if ($count) echo "User with email $email marked as verified.";
        else echo "No user found with email $email or already verified.";
        exit;
    }

    if ($token) {
        $stmt = $pdo->prepare("UPDATE users SET email_verified = 1, verification_token = NULL WHERE verification_token = ?");
        $stmt->execute([$token]);
        $count = $stmt->rowCount();
        echo "Updated rows: " . $count . "\n";
        if ($count) echo "User with token marked as verified.";
        else echo "No user found with that token or already verified.";
        exit;
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo 'Database error: ' . $e->getMessage();
    exit;
}

?>
