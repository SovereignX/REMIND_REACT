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
    // Vérifier la longueur minimale
    if (strlen($password) < $minLength) {
        return [
            'valid' => false,
            'error' => "Le mot de passe doit contenir au moins $minLength caractères"
        ];
    }
    
    // Vérifier la présence d'au moins une majuscule
    if (!preg_match('/[A-Z]/', $password)) {
        return [
            'valid' => false,
            'error' => "Le mot de passe doit contenir au moins une majuscule"
        ];
    }
    
    // Vérifier la présence d'au moins une minuscule
    if (!preg_match('/[a-z]/', $password)) {
        return [
            'valid' => false,
            'error' => "Le mot de passe doit contenir au moins une minuscule"
        ];
    }
    
    // Vérifier la présence d'au moins un chiffre
    if (!preg_match('/[0-9]/', $password)) {
        return [
            'valid' => false,
            'error' => "Le mot de passe doit contenir au moins un chiffre"
        ];
    }
    
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

/**
 * Nettoie le titre d'un événement
 * Supprime les balises HTML mais garde les caractères normaux
 * 
 * @param string $title Le titre à nettoyer
 * @return string Le titre nettoyé
 */
function cleanEventTitle($title) {
    // Trim les espaces
    $title = trim($title);
    
    // Supprimer TOUTES les balises HTML
    $title = strip_tags($title);
    
    // Supprimer les caractères de contrôle
    $title = preg_replace('/[\x00-\x1F\x7F]/u', '', $title);
    
    // Limiter les espaces multiples
    $title = preg_replace('/\s+/', ' ', $title);
    
    return $title;
}
?>