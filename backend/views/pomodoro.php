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
      padding: 149px 0;
      background-color: rgba(236, 234, 234, 0.94);;
      
    }
    h1 {
      margin-bottom: 10px;
      color: #333;
    }
    /* Affichage de l'information de cycle */
    #cycleInfo {
      margin-bottom: 20px;
      font-size: 1.2em;
      color: #555;
    }
    .panels {
      width: 90%;
      max-width: 1200px;
      display: flex;
      justify-content: center;
      gap: 20px;
      flex-wrap: wrap;
      margin-bottom: 20px;
    }
    .panel {
      background:rgba(255, 255, 255, 0.77);
      border: 2px solid #ddd;
      border-radius: 10px;
      width: 50%;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.1);
      position: relative;
      transition: border-color 0.3s, background 0.3s;
    }
    .panel.active {
      border-color: #b4a7d6;
      background:rgba(255, 255, 255, 0.77);
    }
    .panel h2 {
      margin-bottom: 15px;
      color: #555;
    }
    .circle-container {
      position: relative;
      width: 200px;
      height: 200px;
      margin: 0 auto 15px;
    }
    .circle {
      width: 100%;
      height: 100%;
      border-radius: 50%;
      background: conic-gradient(#28a745 0deg, #ddd 0deg);
    }
    .timer-text {
      position: absolute;
      top: 50%;
      left: 50%;
      transform: translate(-50%, -50%);
      font-size: 2em;
      color: white;
      text-shadow: 1px 1px 1px rgba(0,0,0,0.5);
      font-weight: bold;
    }
    button {
      padding: 10px 20px;
      font-size: 16px;
      border: none;
      border-radius: 5px;
      background: #b4a7d6;
      color: #fff;
      cursor: pointer;
      margin-top: 20px;
      text-shadow: 1.2px 1.2px #363434a6;
      border: 0.5px solid hsla(0, 4%, 15%, 0.151);
      box-shadow: 1.5px 1.5px 1px rgba(0, 0, 0, 0.37);
      font-size: 16px;
    }
    button:disabled {
      background: #aaa;
      cursor: not-allowed;
    }
    /* Masque le panneau de pause tant que le timer de travail n'est pas terminé */
    #breakPanel {
      display: none;
    }
  </style>
  <main>
    <!-- Affichage de l'information sur le cycle -->
    <div id="cycleInfo">Cycle : 1 sur 4</div>
    <div class="panels">
      <div id="workPanel" class="panel active">
        <h2>Restez concentré sur votre tâche</h2>
        <div class="circle-container">
          <div id="workCircle" class="circle"></div>
          <div id="workTimer" class="timer-text">25:00</div>
        </div>
      </div>
      <div id="breakPanel" class="panel">
        <h2>N'oubliez pas de prendre une pause</h2>
        <div class="circle-container">
          <div id="breakCircle" class="circle"></div>
          <div id="breakTimer" class="timer-text">05:00</div>
        </div>
      </div>
    </div>
    <button id="start">Démarrer</button>
    <button id="reset" style="display:none;">Réinitialiser</button>
  </main>
  <script>
 const workTime = 0.1;       // 25 minutes de travail
    const breakTime = 0.1;       // 5 minutes de pause courte
    const longBreakTime = 1;  // 20 minutes de pause longue

    // Conversion en secondes
    let workSeconds = workTime * 60;
    let breakSeconds = breakTime * 60; // sera réinitialisé selon le cycle
    let longBreakSeconds = longBreakTime * 60; // pour la pause longue

    let timerInterval;
    let isWork = true;  // true = phase de travail, false = phase de pause
    let isRunning = false;
    let cycleCount = 0; // Compte le nombre de cycles terminés (cycle = travail + pause)

    // Références aux éléments DOM
    const workPanel = document.getElementById('workPanel');
    const breakPanel = document.getElementById('breakPanel');
    const workTimerEl = document.getElementById('workTimer');
    const breakTimerEl = document.getElementById('breakTimer');
    const workCircle = document.getElementById('workCircle');
    const breakCircle = document.getElementById('breakCircle');
    const startButton = document.getElementById('start');
    const resetButton = document.getElementById('reset');
    const cycleInfoEl = document.getElementById('cycleInfo');

    // Formatage du temps en mm:ss
    function formatTime(seconds) {
      let m = Math.floor(seconds / 60);
      let s = seconds % 60;
      return (m < 10 ? "0" + m : m) + ":" + (s < 10 ? "0" + s : s);
    }

    // Mise à jour du cercle en utilisant un dégradé conique
    // Le paramètre "color" permet de changer la couleur selon la phase.
    function updateCircle(circleEl, remaining, total, color) {
      let angle = (remaining / total) * 360;
      circleEl.style.background = `conic-gradient(${color} ${angle}deg, #ddd ${angle}deg)`;
      circleEl.style.border = `2px solid ${angle > 45 ? color : 'red'}`;
    }

    // Mise à jour de l'affichage du cycle de façon dynamique
    function updateCycleInfo() {
      let phaseText = isWork ? "Restez concentré sur votre tâche" : "Prenez une pause";
      if (cycleCount < 4) {
        cycleInfoEl.textContent = "Cycle : " + (cycleCount + 1) + " sur 4 ";
      } else {
        cycleInfoEl.textContent = "Tous les cycles sont terminés !";
      }
    }

    // Initialisation de l'affichage
    function init() {
      workTimerEl.textContent = formatTime(workSeconds);
      breakTimerEl.textContent = formatTime(breakSeconds);
      updateCircle(workCircle, workSeconds, workTime * 60, "#b4a7d6"); // Couleur pour la phase travail
      updateCircle(breakCircle, breakSeconds, breakTime * 60, "#a7d6b4"); // Couleur pour la phase pause
      // On affiche uniquement le panneau de travail au départ
      workPanel.style.display = "block";
      breakPanel.style.display = "none";
      updateCycleInfo();
    }

    window.onload = init;

    // Fonction de démarrage du timer
    function startTimer() {
      if (isRunning) return; // Évite de lancer plusieurs intervalles
      isRunning = true;
      startButton.style.display = "none";
      resetButton.style.display = "inline-block";

      timerInterval = setInterval(() => {
        if (isWork) {
          workSeconds--;
          if (workSeconds < 0) {
            // Fin de la phase de travail : passage à la phase de pause.
            // Si c'est le 4ème cycle (cycleCount === 3), on lance la pause longue.
            if (cycleCount === 3) {
              breakSeconds = longBreakSeconds;
            } else {
              breakSeconds = breakTime * 60;
            }
            isWork = false;
            workPanel.style.display = "none";
            breakPanel.style.display = "block";
          } else {
            workTimerEl.textContent = formatTime(workSeconds);
            updateCircle(workCircle, workSeconds, workTime * 60, "#b4a7d6");
          }
        } else {
          breakSeconds--;
          if (breakSeconds < 0) {
            // Fin de la phase de pause : incrémente le nombre de cycles terminés
            cycleCount++;
            if (cycleCount < 4) {
              workSeconds = workTime * 60;
              isWork = true;
              workPanel.style.display = "block";
              breakPanel.style.display = "none";
            } else {
              clearInterval(timerInterval);
              alert("Tous les cycles sont terminés !");
              isRunning = false;
              startButton.style.display = "inline-block";
            }
          } else {
            breakTimerEl.textContent = formatTime(breakSeconds);
            // Pour la pause, on choisit la couleur en fonction du type de pause.
            if (cycleCount === 3) {
              updateCircle(breakCircle, breakSeconds, longBreakSeconds, "#d6b4a7"); // Pause longue
            } else {
              updateCircle(breakCircle, breakSeconds, breakTime * 60, "#a7d6b4"); // Pause courte
            }
          }
        }
        // Mise à jour dynamique de l'information sur le cycle
        updateCycleInfo();
      }, 1000);
    }

    startButton.addEventListener('click', startTimer);

    resetButton.addEventListener('click', () => {
      clearInterval(timerInterval);
      workSeconds = workTime * 60;
      breakSeconds = breakTime * 60;
      isWork = true;
      isRunning = false;
      cycleCount = 0;
      init();
      startButton.style.display = "inline-block";
      resetButton.style.display = "none";
    });
  </script>
</body>
</html>


<?php include 'footer.php'; ?>
