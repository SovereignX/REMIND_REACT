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

if (!$data) {
    http_response_code(400);
    echo json_encode(["success" => false, "message" => "Invalid request data"]);
    exit;
}
    
    // Validate required fields
    $email = sanitize($data['email'] ?? '');
    $password = trim($data['password'] ?? ''); // No need to sanitize password
    $confirm = trim($data['confirm'] ?? ''); // No need to sanitize password
    $nom = sanitize($data['nom'] ?? '');
    $prenom = sanitize($data['prenom'] ?? '');
    
    $errors = [];
    
    if (empty($email)) {
        $errors[] = "L'email est requis";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Format d'email invalide";
    }
    
    if (empty($password)) {
        $errors[] = "Mot de passe requis";
    } elseif (strlen($password) < 8) {
        $errors[] = "Le mot de passe doit contenir au moins 8 caractères";
    }
    
    if ($password !== $confirm) {
        $errors[] = "Les mots de passe ne correspondent pas";
    }
    
    if (empty($nom)) {
        $errors[] = "Le nom est requis";
    }
    
    if (empty($prenom)) {
        $errors[] = "Le prénom est requis";
    }
    
    if (!empty($errors)) {
        http_response_code(400);
        echo json_encode(["success" => false, "errors" => $errors]);
        exit;
    }
    
    try {
        // Get database connection
        $db = getConnection();
        
        // Check if email already exists
        $req = $db->prepare("SELECT id FROM users WHERE email = ?");
        $req->execute([$email]);
        if ($req->fetch()) {
            http_response_code(409); // Conflict
            echo json_encode(["success" => false, "message" => "Email déjà utilisé."]);
            exit;
        }
        
        // Hash password and insert user
        $hash = password_hash($password, PASSWORD_DEFAULT);
        
        $req = $db->prepare("INSERT INTO users (email, password, nom, prenom) VALUES (?, ?, ?, ?)");
        $req->execute([$email, $hash, $nom, $prenom]);
        
        $userId = $db->lastInsertId();
        
        http_response_code(201); // Created
        echo json_encode([
            "success" => true,
            "message" => "Enregistrement réussi",
            "userId" => $userId,
            "userInfo" => [
                "email" => $email,
                "nom" => $nom,
                "prenom" => $prenom
            ]
        ]);
        
    } catch(PDOException $e) {
        http_response_code(500);
        echo json_encode(["success" => false, "message" => "Database error: " . $e->getMessage()]);
    }
    ?>
    
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