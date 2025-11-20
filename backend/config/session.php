<?php
/**
 * Gestion centralisée des sessions
 * S'assure que la session est démarrée une seule fois
 */

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    // Configuration sécurisée de la session
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    ini_set('session.use_strict_mode', 1); // Rejeter les IDs non initialisés
    
    session_start();
}

/**
 * Définir l'utilisateur authentifié dans la session
 * 
 * @param int $userId ID de l'utilisateur
 * @param array $userInfo Informations de l'utilisateur
 */
function setAuthUser($userId, $userInfo = []) {
    $_SESSION['user_id'] = (int)$userId;
    $_SESSION['email_address'] = $userInfo['email_address'] ?? null;
    $_SESSION['last_name'] = $userInfo['last_name'] ?? null;
    $_SESSION['first_name'] = $userInfo['first_name'] ?? null;
    
    // Régénérer l'ID de session pour la sécurité
    session_regenerate_id(true);
}

/**
 * Récupérer l'ID de l'utilisateur depuis la session
 * 
 * @return int|null ID de l'utilisateur ou null si non connecté
 */
function getAuthUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Récupérer toutes les informations utilisateur depuis la session
 * 
 * @return array|null Informations utilisateur ou null si non connecté
 */
function getAuthUserInfo() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'user_id' => $_SESSION['user_id'],
        'email_address' => $_SESSION['email_address'] ?? null,
        'last_name' => $_SESSION['last_name'] ?? null,
        'first_name' => $_SESSION['first_name'] ?? null,
    ];
}

/**
 * Vérifier si un utilisateur est authentifié
 * 
 * @return bool True si authentifié, false sinon
 */
function isAuthUser() {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}


// Détruire la session utilisateur
function destroyAuthSession() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}

/**
 * Alias pour la fonction getAuthUserId (compatibilité avec le code existant)
 * 
 * @return int|null ID de l'utilisateur
 */
function getUserId() {
    return getAuthUserId();
}

/**
 * Vérifier l'authentification et renvoyer une erreur 401 si non authentifié
 */
function requireAuth() {
    if (!isAuthUser()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentification requise'
        ]);
        exit;
    }
}
?>