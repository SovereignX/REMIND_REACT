<?php
/**
 * Configuration de la base de données
 * Lit les credentials depuis le fichier .env à la racine du projet
 */

function getConnection() {
    // ============================================
    // CHARGER LE FICHIER .env
    // ============================================
    
    // Chemin vers le .env à la RACINE du projet
    // __DIR__ = backend/config/
    // /../../ = remonte de 2 niveaux → racine du projet
    $envFile = __DIR__ . '/../../.env';
    
    // Vérifier si le fichier existe
    if (file_exists($envFile)) {
        // Lire toutes les lignes du fichier
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        
        // Parcourir chaque ligne
        foreach ($lines as $line) {
            // Ignorer les commentaires (lignes qui commencent par #)
            if (strpos(trim($line), '#') === 0) {
                continue;
            }
            
            // Séparer KEY=value
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $_ENV[trim($key)] = trim($value);
            }
        }
    } else {
        // Si le .env n'existe pas, logger l'erreur
        error_log("Fichier .env introuvable : $envFile");
    }
    
    // ============================================
    // RÉCUPÉRER LES CREDENTIALS
    // ============================================
    
    $host = $_ENV['DB_HOST'] ?? 'localhost';
    $dbname = $_ENV['DB_NAME'] ?? 'remind';
    $username = $_ENV['DB_USER'] ?? 'root';
    $password = $_ENV['DB_PASSWORD'] ?? '';
    
    // ============================================
    // CONNEXION À LA BASE DE DONNÉES
    // ============================================
    
    try {
        $db = new PDO(
            "mysql:host=$host;dbname=$dbname;charset=utf8mb4", 
            $username, 
            $password
        );
        $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        return $db;
        
    } catch(PDOException $e) {
        error_log("Erreur connexion base de données: " . $e->getMessage());
        echo json_encode(["error" => "Connection failed"]);
        exit;
    }
}
?>