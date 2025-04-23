<?php include 'header.php'; ?>

<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Pomodoro Timer - Focus & Break</title>
  <style>
      main {
      display: flex;
      flex-direction: column;
      justify-content: center;
      align-items: center;/* Ajuster selon la hauteur de header + footer */
      padding: 172px 0;
      background-color: rgba(236, 234, 234, 0.94);;
      
    }
    .container {
  text-align: center;
  background: #fff;
  padding: 2rem;
  border-radius: 8px;
  box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
}

h1 {
  margin-bottom: 1rem;
}

/* CERCLE DE PROGRESSION */
.progress-ring-container {
  position: relative;
  display: inline-block;
}

.progress-ring {
  transform: rotate(-90deg); /* Pour que le cercle “parte” du haut */
}

/* Cercle “fond” (derrière le cercle principal).
   Optionnel : on peut rajouter un cercle plus clair en arrière-plan */
.progress-ring circle + circle {
  stroke: #eee;
}

/* Au centre du cercle : le temps */
.timer-display {
  position: absolute;
  top: 50%;
  left: 50%;
  transform: translate(-50%, -50%);
  font-size: 2rem;
}

/* PHASE (Focus, Pause, etc.) */
.phase-display {
  font-size: 1.3rem;
  margin: 1rem 0;
}

/* CYCLE ACTUEL */
.cycle-display {
  font-size: 1.2rem;
  margin-bottom: 1rem;
}

/* BOUTONS */
.controls button {
  font-size: 1rem;
  padding: 0.75rem 1.5rem;
  margin: 0.5rem;
  cursor: pointer;
  border: none;
  border-radius: 5px;
  transition: 0.2s;
}

#startBtn {
  background-color: #2ecc71;
  color: #fff;
}

#pauseBtn {
  background-color: #f39c12;
  color: #fff;
}

#resetBtn {
  background-color: #e74c3c;
  color: #fff;
}

.controls button:disabled {
  background-color: #cccccc;
  cursor: not-allowed;
}
  </style>
  <main>
  <div class="container">
    <h1>Minuteur Pomodoro</h1>

    <!-- Cercle de progression (SVG) -->
    <div class="progress-ring-container">
      <svg class="progress-ring" width="220" height="220">
        <!-- On place le cercle au centre (cx, cy) avec un rayon r -->
        <circle
          class="progress-ring__circle"
          stroke="#3498db"
          stroke-width="10"
          fill="transparent"
          r="100"
          cx="110"
          cy="110"
        />
      </svg>
      <!-- Affichage du temps au milieu du cercle -->
      <div id="timerDisplay" class="timer-display">00:00</div>
    </div>

    <!-- Indication de la phase en cours -->
    <div id="phaseDisplay" class="phase-display">Focus</div>

    <!-- Affichage du cycle actuel -->
    <div id="cycleDisplay" class="cycle-display">
      Cycle : <span id="currentCycle">1</span> / 4
    </div>

    <!-- Boutons de contrôle -->
    <div class="controls">
      <button id="startBtn">Démarrer</button>
      <button id="pauseBtn" disabled>Pause</button>
      <button id="resetBtn" disabled>Réinitialiser</button>
    </div>
  </div>
  </main>
  <script>
// ------------------
// CONFIG POMODORO
// ------------------
const FOCUS_TIME = 1 * 60;     // 25 minutes de focus
const SHORT_BREAK = 1 * 60;     // 5 minutes de pause courte
const LONG_BREAK = 20 * 60;     // 20 minutes de pause longue
const TOTAL_CYCLES = 4;         // nombre de cycles focus/pause avant la longue pause

// ------------------
// VARIABLES D'ÉTAT
// ------------------
let currentCycle = 1;           // Cycle affiché (1 à 4)
let timeLeft = FOCUS_TIME;      // Temps restant dans la phase en cours (en sec)
let isFocus = true;             // Vrai si on est en focus, faux si on est en pause
let timer = null;               // La référence du setInterval
let isRunning = false;          // Indique si le minuteur est en cours
let completedCycles = 0;        // Nombre de cycles focus achevés

// Pour gérer l'animation du cercle
let totalDuration = FOCUS_TIME; // Durée totale de la phase courante
// On calcule le périmètre du cercle (circumference) pour l'animation
const circle = document.querySelector('.progress-ring__circle');
const radius = circle.r.baseVal.value; 
const circumference = 2 * Math.PI * radius;

// On initialise le dasharray et dashoffset
circle.style.strokeDasharray = circumference;
circle.style.strokeDashoffset = circumference;

// ------------------
// DOM ELEMENTS
// ------------------
const timerDisplay = document.getElementById('timerDisplay');
const phaseDisplay = document.getElementById('phaseDisplay');
const cycleDisplay = document.getElementById('currentCycle');
const startBtn = document.getElementById('startBtn');
const pauseBtn = document.getElementById('pauseBtn');
const resetBtn = document.getElementById('resetBtn');

// ------------------
// FONCTIONS
// ------------------

/**
 * Met à jour l'affichage du temps (format mm:ss)
 */
function updateTimerDisplay(seconds) {
  const minutes = Math.floor(seconds / 60);
  const secs = seconds % 60;
  timerDisplay.textContent = `${String(minutes).padStart(2, '0')}:${String(secs).padStart(2, '0')}`;
}

/**
 * Met à jour le cercle de progression en fonction du temps restant
 */
function updateCircleProgress(seconds) {
  // fraction = tempsÉcoulé / tempsTotal
  // offset = circonférence * fraction
  // => plus le temps s'écoule, plus le cercle est “mangé”
  const fraction = (totalDuration - seconds) / totalDuration;
  const offset = circumference * fraction;
  circle.style.strokeDashoffset = offset;
}

/**
 * Met à jour l'affichage de la phase (Focus / Pause / Pause Longue)
 */
function updatePhaseDisplay() {
  if (isFocus) {
    phaseDisplay.textContent = "Focus";
  } else {
    // Pour différencier pause courte vs pause longue,
    // on peut se baser sur la durée (timeLeft), ou
    // sur completedCycles, ou une autre variable.
    if (timeLeft === LONG_BREAK) {
      phaseDisplay.textContent = "Pause Longue";
    } else {
      phaseDisplay.textContent = "Pause";
    }
  }
}

/**
 * Lance le timer
 */
function startTimer() {
  if (!isRunning) {
    isRunning = true;
    startBtn.disabled = true;
    pauseBtn.disabled = false;
    resetBtn.disabled = false;

    timer = setInterval(() => {
      timeLeft--;
      updateTimerDisplay(timeLeft);
      updateCircleProgress(timeLeft);

      if (timeLeft <= 0) {
        clearInterval(timer);

        // On passe à la phase suivante
        if (isFocus) {
          // On vient de terminer une phase de focus
          completedCycles++;
          if (completedCycles < TOTAL_CYCLES) {
            // Phase pause courte
            isFocus = false;
            timeLeft = SHORT_BREAK;
            totalDuration = SHORT_BREAK;
          } else {
            // On a fini 4 focus => pause longue
            isFocus = false;
            timeLeft = LONG_BREAK;
            totalDuration = LONG_BREAK;
            // On remet à zéro le compteur de focus complétés
            completedCycles = 0;
          }
        } else {
          // On vient de terminer une pause
          if (currentCycle < TOTAL_CYCLES) {
            // On incrémente le cycle si c'était une pause courte
            // (Après la pause longue, on reset plus bas)
            currentCycle++;
          } else {
            // Si on était au 4e cycle, on repasse à 1
            currentCycle = 1;
          }
          
          // Nouvelle phase focus
          isFocus = true;
          timeLeft = FOCUS_TIME;
          totalDuration = FOCUS_TIME;
        }

        // Mise à jour de l'affichage cycle / phase
        cycleDisplay.textContent = currentCycle;
        updatePhaseDisplay();
        updateTimerDisplay(timeLeft);
        
        // Réinitialiser l'offset du cercle pour la nouvelle phase
        circle.style.strokeDashoffset = circumference;

        // On relance automatiquement le timer
        startTimer();
      }
    }, 1000);
  }
}

/**
 * Met le timer en pause
 */
function pauseTimer() {
  if (isRunning) {
    clearInterval(timer);
    isRunning = false;
    startBtn.disabled = false;
    pauseBtn.disabled = true;
  }
}

/**
 * Réinitialise le timer
 */
function resetTimer() {
  clearInterval(timer);
  isRunning = false;

  // Reset des variables
  currentCycle = 1;
  completedCycles = 0;
  isFocus = true;
  timeLeft = FOCUS_TIME;
  totalDuration = FOCUS_TIME;

  // Reset de l'affichage
  updateTimerDisplay(timeLeft);
  cycleDisplay.textContent = currentCycle;
  phaseDisplay.textContent = "Focus";
  
  // Reset du cercle
  circle.style.strokeDashoffset = circumference;

  // Boutons
  startBtn.disabled = false;
  pauseBtn.disabled = true;
  resetBtn.disabled = true;
}

// ------------------
// ÉCOUTEURS
// ------------------
startBtn.addEventListener('click', startTimer);
pauseBtn.addEventListener('click', pauseTimer);
resetBtn.addEventListener('click', resetTimer);

// Initialisation au chargement
updateTimerDisplay(timeLeft);
updatePhaseDisplay();
circle.style.strokeDashoffset = circumference;

  </script>
</body>
</html>


<?php include 'footer.php'; ?>
