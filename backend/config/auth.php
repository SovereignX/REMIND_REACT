<?php
/**
 * Configuration de l'authentification
 * Gère la récupération de l'utilisateur connecté
 */

/**
 * Récupère l'ID de l'utilisateur connecté
 * 
 * @return int|null ID de l'utilisateur ou null si non connecté
 */
function getUserId() {
    // Méthode 1: Via session PHP
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    if (isset($_SESSION['user_id']) && is_numeric($_SESSION['user_id'])) {
        return (int)$_SESSION['user_id'];
    }
    
    // Méthode 2: Via header Authorization (Bearer token)
    // Si vous utilisez JWT ou un autre système de token
    if (isset($_SERVER['HTTP_AUTHORIZATION'])) {
        $authHeader = $_SERVER['HTTP_AUTHORIZATION'];
        
        // Format: "Bearer <token>"
        if (preg_match('/Bearer\s+(.*)$/i', $authHeader, $matches)) {
            $token = $matches[1];
            // TODO: Valider le token JWT et extraire l'user_id
            // $userId = validateToken($token);
            // return $userId;
        }
    }
    
    // Méthode 3: Via cookie (alternative)
    if (isset($_COOKIE['user_id']) && is_numeric($_COOKIE['user_id'])) {
        // ATTENTION: Valider aussi un token de session sécurisé
        // Cette méthode seule n'est PAS sécurisée
        // return (int)$_COOKIE['user_id'];
    }
    
    return null;
}

/**
 * Vérifie si un utilisateur est connecté
 * 
 * @return bool
 */
function isAuthenticated() {
    return getUserId() !== null;
}

/**
 * Récupère les informations complètes de l'utilisateur connecté
 * 
 * @return array|null Informations utilisateur ou null
 */
function getCurrentUser() {
    $userId = getUserId();
    
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
    if (!isAuthenticated()) {
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
 * 
 * @param int $userId
 * @param array $userInfo Informations supplémentaires (optionnel)
 */
function setAuthenticatedUser($userId, $userInfo = []) {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    $_SESSION['user_id'] = (int)$userId;
    
    if (!empty($userInfo)) {
        $_SESSION['user_email'] = $userInfo['email'] ?? null;
        $_SESSION['user_name'] = ($userInfo['prenom'] ?? '') . ' ' . ($userInfo['nom'] ?? '');
    }
    
    // Régénérer l'ID de session pour la sécurité
    session_regenerate_id(true);
}

/**
 * Déconnecte l'utilisateur
 */
function logout() {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Détruire toutes les variables de session
    $_SESSION = [];
    
    // Détruire le cookie de session
    if (isset($_COOKIE[session_name()])) {
        setcookie(session_name(), '', time() - 3600, '/');
    }
    
    // Détruire la session
    session_destroy();
}
?>
