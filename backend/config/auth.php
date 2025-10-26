<?php
/**
 * Configuration de l'authentification
 * Ce fichier est déprécié, utiliser session.php à la place
 * Conservé pour compatibilité avec les anciens endpoints
 */

require_once __DIR__ . '/session.php';

/**
 * Récupère l'ID de l'utilisateur connecté
 * @deprecated Utiliser getAuthUserId() de session.php
 */
function getUserId() {
    return getAuthUserId();
}

/**
 * Vérifie si un utilisateur est connecté
 * @deprecated Utiliser isAuthUser() de session.php
 */
function isAuthenticated() {
    return isAuthUser();
}

/**
 * Récupère les informations complètes de l'utilisateur connecté
 * @deprecated Utiliser getAuthUserInfo() de session.php
 */
function getCurrentUser() {
    $userId = getAuthUserId();
    
    if (!$userId) {
        return null;
    }
    
    try {
        require_once __DIR__ . '/database.php';
        $db = getConnection();
        
        $req = $db->prepare(
            "SELECT id, email, nom, prenom, created_at 
             FROM users 
             WHERE id = :id 
             LIMIT 1"
        );
        $req->bindParam(':id', $userId, PDO::PARAM_INT);
        $req->execute();
        
        $user = $req->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['id'] = (int)$user['id'];
            return $user;
        }
        
        return null;
        
    } catch(PDOException $e) {
        error_log("Erreur getCurrentUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Requiert une authentification (renvoie 401 si non connecté)
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

/**
 * Définit l'utilisateur connecté en session
 * @deprecated Utiliser setAuthUser() de session.php
 */
function setAuthenticatedUser($userId, $userInfo = []) {
    setAuthUser($userId, $userInfo);
}

/**
 * Déconnecte l'utilisateur
 * @deprecated Utiliser destroyAuthSession() de session.php
 */
function logout() {
    destroyAuthSession();
}
