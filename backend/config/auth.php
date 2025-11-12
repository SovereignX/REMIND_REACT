<?php
/**
 * Authentication configuration
 * This file is deprecated, use session.php instead
 * Kept for compatibility with old endpoints
 */

require_once __DIR__ . '/session.php';

/**
 * Get authenticated user ID
 * @deprecated Use getAuthUserId() from session.php
 */
function getUserId() {
    return getAuthUserId();
}

/**
 * Check if a user is authenticated
 * @deprecated Use isAuthUser() from session.php
 */
function isAuthenticated() {
    return isAuthUser();
}

/**
 * Get complete authenticated user information
 * @deprecated Use getAuthUserInfo() from session.php
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
            "SELECT user_id, email_address, last_name, first_name, created_at 
             FROM users 
             WHERE user_id = :user_id 
             LIMIT 1"
        );
        $req->bindParam(':user_id', $userId, PDO::PARAM_INT);
        $req->execute();
        
        $user = $req->fetch(PDO::FETCH_ASSOC);
        
        if ($user) {
            $user['user_id'] = (int)$user['user_id'];
            // Add backward compatibility fields
            $user['id'] = $user['user_id'];
            $user['email'] = $user['email_address'];
            $user['nom'] = $user['last_name'];
            $user['prenom'] = $user['first_name'];
            return $user;
        }
        
        return null;
        
    } catch(PDOException $e) {
        error_log("Error getCurrentUser: " . $e->getMessage());
        return null;
    }
}

/**
 * Require authentication (returns 401 if not authenticated)
 */
function requireAuth() {
    if (!isAuthUser()) {
        http_response_code(401);
        echo json_encode([
            'success' => false,
            'error' => 'Authentication required'
        ]);
        exit;
    }
}

/**
 * Set authenticated user in session
 * @deprecated Use setAuthUser() from session.php
 */
function setAuthenticatedUser($userId, $userInfo = []) {
    setAuthUser($userId, $userInfo);
}

/**
 * Log out user
 * @deprecated Use destroyAuthSession() from session.php
 */
function logout() {
    destroyAuthSession();
}
?>