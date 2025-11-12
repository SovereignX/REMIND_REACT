<?php
/**
 * API - Get authenticated user profile
 * GET /backend/api/users/profile.php
 */

require_once '../../config/cors.php';
require_once '../../config/database.php';
require_once '../../config/session.php';

header("Content-Type: application/json; charset=UTF-8");

/**
 * Helper function for JSON responses
 */
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// Check if user is authenticated
if (!isAuthUser()) {
    sendResponse(false, ['error' => 'Not authenticated'], 401);
}

$userId = getAuthUserId();

try {
    $db = getConnection();
    
    // Get complete user information
    $req = $db->prepare(
        "SELECT user_id, email_address, last_name, first_name, created_at 
         FROM users 
         WHERE user_id = :user_id 
         LIMIT 1"
    );
    $req->bindParam(':user_id', $userId, PDO::PARAM_INT);
    $req->execute();
    
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        // User no longer exists in database
        destroyAuthSession();
        sendResponse(false, ['error' => 'User not found'], 404);
    }
    
    // Never send back the password
    unset($user['password_hash']);
    
    // Convert ID to integer
    $user['user_id'] = (int)$user['user_id'];
    
    // Add backward compatibility fields
    $user['id'] = $user['user_id'];
    $user['email'] = $user['email_address'];
    $user['nom'] = $user['last_name'];
    $user['prenom'] = $user['first_name'];
    
    sendResponse(true, [
        'user' => $user
    ], 200);
    
} catch(PDOException $e) {
    error_log("Error profile: " . $e->getMessage());
    sendResponse(false, ['error' => 'Server error'], 500);
}
?>