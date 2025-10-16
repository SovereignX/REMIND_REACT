<?php
/**
 * API de connexion utilisateur
 * Exemple d'utilisation correcte du CORS
 */

// 1. TOUJOURS inclure le CORS en premier
require_once '../../config/cors.php';
require_once '../../config/database.php';

// 2. Définir le type de contenu
header("Content-Type: application/json; charset=UTF-8");

// 3. Fonction de validation et nettoyage
function sanitizeInput($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// 4. Fonction de réponse JSON
function sendResponse($success, $data = [], $httpCode = 200) {
    http_response_code($httpCode);
    echo json_encode(array_merge(['success' => $success], $data));
    exit;
}

// 5. Récupérer et valider les données
$json = file_get_contents("php://input");
$data = json_decode($json, true);

// Vérifier que le JSON est valide
if (json_last_error() !== JSON_ERROR_NONE) {
    sendResponse(false, [
        'message' => 'Données JSON invalides'
    ], 400);
}

// Vérifier les champs requis
if (!isset($data["email"]) || !isset($data["password"])) {
    sendResponse(false, [
        'message' => 'Email et mot de passe requis'
    ], 400);
}

$email = sanitizeInput($data["email"]);
$password = trim($data["password"]);

// Validation de l'email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    sendResponse(false, [
        'message' => 'Format d\'email invalide'
    ], 400);
}

// 6. Traitement de la base de données
try {
    $db = getConnection();
    
    // Requête préparée pour éviter les injections SQL
    $stmt = $db->prepare(
        "SELECT id, email, password, nom, prenom 
         FROM users 
         WHERE email = :email 
         LIMIT 1"
    );
    $stmt->bindParam(':email', $email, PDO::PARAM_STR);
    $stmt->execute();
    
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Vérifier l'utilisateur et le mot de passe
    if (!$user || !password_verify($password, $user["password"])) {
        sendResponse(false, [
            'message' => 'Email ou mot de passe incorrect'
        ], 401);
    }
    
    // Succès - Ne jamais renvoyer le mot de passe
    unset($user['password']);
    
    sendResponse(true, [
        'message' => 'Connexion réussie',
        'userId' => $user["id"],
        'userInfo' => [
            'email' => $user["email"],
            'nom' => $user["nom"],
            'prenom' => $user["prenom"]
        ]
    ], 200);
    
} catch(PDOException $e) {
    // En production, ne pas exposer les détails de l'erreur
    error_log("Erreur de connexion: " . $e->getMessage());
    
    sendResponse(false, [
        'message' => 'Erreur serveur'
    ], 500);
}
?>