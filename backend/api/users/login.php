<?php
require_once '../../config/cors.php';
require_once '../../config/database.php';

// Add CORS headers
addCorsHeaders();

header("Content-Type: application/json");

// Sanitize function to clean user input
function sanitize($data) {
    return htmlspecialchars(trim($data), ENT_QUOTES, 'UTF-8');
}

// Get JSON request data
$data = json_decode(file_get_contents("php://input"), true);

// Check if email and password are set
if (!isset($data["email"]) || !isset($data["password"])) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Email and password are required"]);
    exit;
}

$email = sanitize($data["email"]);
$password = trim($data["password"]); // No need to sanitize password

try {
    // Get database connection
    $db = getConnection();
    
    // Prepare and execute query to fetch user
    $req = $db->prepare("SELECT id, email, password, nom, prenom FROM users WHERE email = ?");
    $req->execute([$email]);
    $user = $req->fetch(PDO::FETCH_ASSOC);
    
    if (!$user || !password_verify($password, $user["password"])) {
        http_response_code(401);
        echo json_encode(["success" => false, "message" => "Invalid credentials"]);
        exit;
    }
    
    echo json_encode([
        "success" => true, 
        "userId" => $user["id"],
        "userInfo" => [
            "email" => $user["email"],
            "nom" => $user["nom"],
            "prenom" => $user["prenom"]
        ]
    ]);
    
} catch(PDOException $e) {
    http_response_code(500);
    echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
}
?>
