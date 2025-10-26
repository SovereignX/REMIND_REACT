<?php
/**
 * Gestion centralisée des sessions
 * Assure qu'une session est démarrée une seule fois
 */

// Démarrer la session si elle n'est pas déjà active
if (session_status() === PHP_SESSION_NONE) {
    // Configuration de session sécurisée
    ini_set('session.cookie_httponly', 1);
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
    
    session_start();
}

/**
 * Définit l'utilisateur connecté en session
 */
function setAuthUser($userId, $userInfo = []) {
    $_SESSION['user_id'] = (int)$userId;
    $_SESSION['user_email'] = $userInfo['email'] ?? null;
    $_SESSION['user_nom'] = $userInfo['nom'] ?? null;
    $_SESSION['user_prenom'] = $userInfo['prenom'] ?? null;
    
    // Régénérer l'ID de session pour la sécurité
    session_regenerate_id(true);
}

/**
 * Récupère l'ID utilisateur de la session
 */
function getAuthUserId() {
    return $_SESSION['user_id'] ?? null;
}

/**
 * Récupère toutes les infos utilisateur de la session
 */
function getAuthUserInfo() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    
    return [
        'id' => $_SESSION['user_id'],
        'email' => $_SESSION['user_email'] ?? null,
        'nom' => $_SESSION['user_nom'] ?? null,
        'prenom' => $_SESSION['user_prenom'] ?? null,
    ];
}

/**
 * Vérifie si un utilisateur est connecté
 */
function isAuthUser() {
    return isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id']);
}

/**
 * Détruit la session utilisateur
 */
function destroyAuthSession() {
    $_SESSION = [];
    
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    session_destroy();
}
