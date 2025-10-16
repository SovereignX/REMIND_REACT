import { useMemo } from "react";
import { usePomodoroTimer } from "../hooks/TimerPomodoro";
import CircleTimer from "../common/CircleTimer";
import "./Pomodoro.css";
import Button from "../common/Button";

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

  return (
    <div className="page-container pomodoro-container">
      <h2>Cycle {cycle} sur 4</h2>
      <h1>{phase}</h1>

      <CircleTimer percentage={percentage} text={formatTime(secondsLeft)} />

      <Button className="pomodoro-button" onClick={toggleTimer}>
        {isRunning ? "Pause" : "Démarrer"}
      </Button>
      <Button
        className="sound-toggle-button"
        onClick={toggleSound}
        aria-label={soundEnabled ? "Désactiver le son" : "Activer le son"}
      >
        {soundEnabled ? "🔊" : "🔇"}
      </Button>
    </div>
  );
}
