/**
 * Utilitaires pour la gestion des dates et des jours
 * Fichier : frontend/src/utils/dateUtils.js
 */

/**
 * Configuration des jours de la semaine
 * Index 0-6 : Lundi à Dimanche
 */
export const DAYS = [
  'Lundi',
  'Mardi',
  'Mercredi',
  'Jeudi',
  'Vendredi',
  'Samedi',
  'Dimanche'
];

/**
 * Obtenir le nom d'un jour à partir de son index
 * @param {number} dayIndex - Index du jour (0-6)
 * @returns {string} Nom du jour
 */
export function getDayName(dayIndex) {
  return DAYS[dayIndex] || 'Inconnu';
}

/**
 * Obtenir l'index d'un jour à partir de son nom
 * @param {string} dayName - Nom du jour
 * @returns {number} Index du jour (0-6) ou -1 si non trouvé
 */
export function getDayIndex(dayName) {
  return DAYS.indexOf(dayName);
}

/**
 * Valider un index de jour
 * @param {number} dayIndex - Index à valider
 * @returns {boolean}
 */
export function isValidDayIndex(dayIndex) {
  return Number.isInteger(dayIndex) && dayIndex >= 0 && dayIndex <= 6;
}

/**
 * Formater une plage horaire pour l'affichage
 * @param {string} startTime - Heure de début (format HH:MM)
 * @param {number} duration - Durée en heures
 * @returns {string} Plage formatée (ex: "09:00 - 11:00")
 */
export function formatTimeRange(startTime, duration) {
  const [hours, minutes] = startTime.split(':').map(Number);
  const startMinutes = hours * 60 + minutes;
  const endMinutes = startMinutes + Math.round(duration * 60);
  
  const endHours = Math.floor(endMinutes / 60);
  const endMins = endMinutes % 60;
  
  return `${startTime} - ${String(endHours).padStart(2, '0')}:${String(endMins).padStart(2, '0')}`;
}

/**
 * Formater une durée pour l'affichage
 * @param {number} duration - Durée en heures
 * @returns {string} Durée formatée (ex: "2h", "1h30")
 */
export function formatDuration(duration) {
  const hours = Math.floor(duration);
  const minutes = Math.round((duration - hours) * 60);
  
  if (hours === 0) {
    return `${minutes}min`;
  } else if (minutes === 0) {
    return `${hours}h`;
  } else {
    return `${hours}h${minutes}`;
  }
}

/**
 * Obtenir le jour actuel (0-6)
 * @returns {number} Index du jour actuel
 */
export function getCurrentDayIndex() {
  // JavaScript: 0 (dimanche) à 6 (samedi)
  // Notre système: 0 (lundi) à 6 (dimanche)
  const jsDay = new Date().getDay();
  // Convertir: dimanche (0) devient 6, lundi (1) devient 0, etc.
  return jsDay === 0 ? 6 : jsDay - 1;
}