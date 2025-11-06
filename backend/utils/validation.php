<?php
/**
 * Utilitaires de validation et nettoyage des données
 * Fichier : backend/utils/validation.php
 */

/**
 * Nettoie un nom/prénom en supprimant les caractères dangereux
 * mais en gardant les accents, espaces, tirets, apostrophes
 * 
 * @param string $name Le nom à nettoyer
 * @return string Le nom nettoyé
 */
function cleanName($name) {
    // Trim les espaces au début/fin
    $name = trim($name);
    
    // Supprimer UNIQUEMENT les caractères dangereux (balises HTML, scripts)
    // Mais garder : lettres (avec accents), espaces, tirets, apostrophes
    $name = preg_replace('/[<>{}()\[\]\/\\\\]/', '', $name);
    
    // Supprimer les caractères de contrôle (null bytes, etc.)
    $name = preg_replace('/[\x00-\x1F\x7F]/u', '', $name);
    
    // Limiter les espaces multiples à un seul
    $name = preg_replace('/\s+/', ' ', $name);
    
    return $name;
}

/**
 * Valide et nettoie un email
 * 
 * @param string $email L'email à valider
 * @return string|false L'email nettoyé ou false si invalide
 */
function cleanEmail($email) {
    $email = trim($email);
    $email = strtolower($email);
    
    // Supprimer les caractères dangereux (mais pas ceux valides pour un email)
    $email = filter_var($email, FILTER_SANITIZE_EMAIL);
    
    // Valider que c'est bien un email
    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
        return $email;
    }
    
    return false;
}

/**
 * Valide un mot de passe
 * Accepte TOUS les caractères (car il sera hashé)
 * 
 * @param string $password Le mot de passe
 * @param int $minLength Longueur minimale
 * @return array ['valid' => bool, 'error' => string|null]
 */
function validatePassword($password, $minLength = 8) {
    if (strlen($password) < $minLength) {
        return [
            'valid' => false,
            'error' => "Le mot de passe doit contenir au moins $minLength caractères"
        ];
    }
    
    // Note : On accepte TOUS les caractères dans le mot de passe
    // car il sera hashé (password_hash) et jamais affiché
    
    return ['valid' => true, 'error' => null];
}

/**
 * Échappe du texte pour affichage HTML
 * À utiliser UNIQUEMENT pour l'affichage, pas le stockage
 * 
 * @param string $text Le texte à échapper
 * @return string Le texte échappé
 */
function escapeHtml($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * Valide que le texte ne contient pas de caractères dangereux
 * 
 * @param string $text Le texte à valider
 * @return bool True si valide, false sinon
 */
function containsDangerousChars($text) {
    // Chercher des patterns dangereux
    $dangerousPatterns = [
        '/<script/i',           // Balise script
        '/javascript:/i',       // JavaScript protocol
        '/on\w+\s*=/i',        // Event handlers (onclick, onload, etc.)
        '/<iframe/i',          // Iframe
        '/<object/i',          // Object tag
        '/<embed/i',           // Embed tag
    ];
    
    foreach ($dangerousPatterns as $pattern) {
        if (preg_match($pattern, $text)) {
            return true;
        }
    }
    
    return false;
}
?>
