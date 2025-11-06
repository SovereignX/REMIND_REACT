<?php
/**
 * Utilitaires pour la gestion des jours
 * Fichier : backend/utils/days.php
 */

// Mapping des jours français (pour debug et logs uniquement)
const DAYS_FR = [
    0 => 'Lundi',
    1 => 'Mardi',
    2 => 'Mercredi',
    3 => 'Jeudi',
    4 => 'Vendredi',
    5 => 'Samedi',
    6 => 'Dimanche'
];

/**
 * Convertir un nom de jour français en index
 * Utile uniquement pour la migration ou rétrocompatibilité
 * 
 * @param string $dayName Nom du jour en français
 * @return int|null Index du jour (0-6) ou null si invalide
 */
function dayNameToIndex($dayName) {
    $mapping = array_flip(DAYS_FR);
    return $mapping[$dayName] ?? null;
}

/**
 * Convertir un index en nom de jour français
 * Utile pour debug ou logs serveur
 * 
 * @param int $index Index du jour (0-6)
 * @return string Nom du jour ou 'Inconnu'
 */
function dayIndexToName($index) {
    return DAYS_FR[$index] ?? 'Inconnu';
}

/**
 * Valider un index de jour
 * 
 * @param mixed $index Valeur à valider
 * @return bool True si l'index est valide (0-6)
 */
function isValidDayIndex($index) {
    return is_numeric($index) && $index >= 0 && $index <= 6;
}

/**
 * Obtenir le nom du jour actuel (0-6)
 * 0 = Lundi, 6 = Dimanche
 * 
 * @return int Index du jour actuel
 */
function getCurrentDayIndex() {
    // PHP: 1 (lundi) à 7 (dimanche)
    // Notre système: 0 (lundi) à 6 (dimanche)
    $phpDayIndex = date('N');
    return $phpDayIndex - 1;
}
?>