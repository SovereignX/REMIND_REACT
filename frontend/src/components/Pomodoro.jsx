import { useMemo } from "react";
import { usePomodoroTimer } from "../hooks/TimerPomodoro";
import CircleTimer from "../common/CircleTimer";
import "./Pomodoro.css";
import Button from "../common/Button";
import { Volume2, VolumeX } from "lucide-react";

export default function Pomodoro() {
  const {
    secondsLeft,
    isRunning,
    phase,
    cycle,
    totalDuration,
    formatTime,
    toggleTimer,
    soundEnabled,
    toggleSound,
  } = usePomodoroTimer();

  // Calculate percentage completion for circle timer
  const percentage = useMemo(() => {
    return ((totalDuration - secondsLeft) / totalDuration) * 100;
  }, [secondsLeft, totalDuration]);

  // Déterminer la classe CSS pour la phase
  const getPhaseClass = () => {
    if (phase === "Travail") return "phase-work";
    if (phase === "Pause") return "phase-break";
    if (phase === "Pause longue") return "phase-longbreak";
    return "";
  };

  return (
    <div className="page-container">
      <div className="pomodoro-container">
        <h2>Cycle {cycle} sur 4</h2>
        <h1 className={getPhaseClass()}>{phase}</h1>

        <CircleTimer percentage={percentage} text={formatTime(secondsLeft)} />

        <div className="pomodoro-controls">
          <button className="pomodoro-button" onClick={toggleTimer}>
            {isRunning ? "Pause" : "Démarrer"}
          </button>

          <button
            className="sound-toggle-button"
            onClick={toggleSound}
            aria-label={soundEnabled ? "Désactiver le son" : "Activer le son"}
          >
            {soundEnabled ? <VolumeX /> : <Volume2 />}
          </button>
        </div>
      </div>
    </div>
  );
}
