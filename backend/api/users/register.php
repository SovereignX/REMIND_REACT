<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers (the function call is already in cors.php, but adding it explicitly won't hurt)
addCorsHeaders();

header("Content-Type: application/json");

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

try {
    // Process form data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        echo json_encode(["success" => false, "message" => "Requête invalide."]);
        exit;
    }
    
    $email = htmlspecialchars(trim($data['email'] ?? ''));
    $password = htmlspecialchars(trim($data['password'] ?? ''));
    $confirm = htmlspecialchars(trim($data['confirm'] ?? ''));
    $nom = htmlspecialchars(trim($data['nom'] ?? ''));
    $prenom = htmlspecialchars(trim($data['prenom'] ?? ''));
    
    if (!$email || !$password || !$confirm || !$nom || !$prenom) {
        echo json_encode(["success" => false, "message" => "Champs manquants."]);
        exit;
    }
    
    if ($password !== $confirm) {
        echo json_encode(["success" => false, "message" => "Les mots de passe ne correspondent pas."]);
        exit;
    }
    
    // Vérifie si l'email existe déjà
    $req = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $req->execute([$email]);
    if ($req->fetch()) {
        echo json_encode(["success" => false, "message" => "Email déjà utilisé."]);
        exit;
    }
    
    // Insertion utilisateur
    $hash = password_hash($password, PASSWORD_DEFAULT);
    
    $req = $pdo->prepare("INSERT INTO users (email, password, nom, prenom) VALUES (?, ?, ?, ?)");
    $req->execute([$email, $hash, $nom, $prenom]);
    
    echo json_encode(["success" => true, "user" => [
        "email" => $email,
        "nom" => $nom,
        "prenom" => $prenom
    ]]);
} catch (PDOException $e) {
    error_log("Database error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Erreur de connexion à la base de données: " . $e->getMessage()]);
    exit;
} catch (Exception $e) {
    error_log("General error: " . $e->getMessage());
    echo json_encode(["success" => false, "message" => "Une erreur est survenue: " . $e->getMessage()]);
    exit;
}
?>