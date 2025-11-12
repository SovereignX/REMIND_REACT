<?php
/**
 * Centralized session management
 * Ensures session is started only once
 */

// Start session if not already active
if (session_status() === PHP_SESSION_NONE) {
    // Secure session configuration
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

/**
 * Set authenticated user in session
 */
function setAuthUser($userId, $userInfo = []) {
    $_SESSION['user_id'] = (int)$userId;
    
    // Support both old and new field names
    $_SESSION['email_address'] = $userInfo['email_address'] ?? $userInfo['email'] ?? null;
    $_SESSION['last_name'] = $userInfo['last_name'] ?? $userInfo['nom'] ?? null;
    $_SESSION['first_name'] = $userInfo['first_name'] ?? $userInfo['prenom'] ?? null;
    
    // Backward compatibility - also store old field names
    $_SESSION['user_email'] = $_SESSION['email_address'];
    $_SESSION['user_nom'] = $_SESSION['last_name'];
    $_SESSION['user_prenom'] = $_SESSION['first_name'];
    
    // Regenerate session ID for security
    session_regenerate_id(true);
}

/**
 * Get user ID from session
 */
function getAuthUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Get all user info from session
 */
function getAuthUserInfo() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'user_id' => $_SESSION['user_id'],
        'email' => $_SESSION['email_address'] ?? $_SESSION['user_email'] ?? null,
        'email_address' => $_SESSION['email_address'] ?? $_SESSION['user_email'] ?? null,
        'nom' => $_SESSION['last_name'] ?? $_SESSION['user_nom'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? $_SESSION['user_nom'] ?? null,
        'prenom' => $_SESSION['first_name'] ?? $_SESSION['user_prenom'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? $_SESSION['user_prenom'] ?? null,
    ];
}

/**
 * Check if a user is authenticated
 */
function isAuthUser() {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Destroy user session
 */
function destroyAuthSession() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}
?>