<?php
function addCorsHeaders() {
    // Définir l'origine autorisée (à adapter selon votre configuration)
    $allowedOrigins = [
        'http://localhost:5173',  // Vite dev server
        'http://localhost:3000',  // Alternative
        'http://localhost:8000',  // Serveur PHP
    ];
    
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';
    
    // Vérifier si l'origine est autorisée
    if (in_array($origin, $allowedOrigins)) {
        header("Access-Control-Allow-Origin: $origin");
    } else {
        // Pour le développement, autoriser toutes les origines
        // En production, commentez cette ligne
        header("Access-Control-Allow-Origin: *");
    }
    
    // Headers CORS essentiels
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With");
    header("Access-Control-Allow-Credentials: true");
    header("Access-Control-Max-Age: 86400"); // 24 heures
    
    // Gérer les requêtes preflight OPTIONS
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204); // No Content
        exit(0);
    }
}

// Appliquer automatiquement les headers
addCorsHeaders();
?>