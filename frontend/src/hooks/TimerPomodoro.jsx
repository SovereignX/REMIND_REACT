import { useState, useEffect, useRef, useCallback } from "react";

export function usePomodoroTimer() {
  // Configuration
  const DURATIONS = {
    WORK: 25 * 60, // 25 minutes
    SHORT_BREAK: 3 * 60, // 3 minutes
    LONG_BREAK: 15 * 60, // 15 minutes
  };

  // State
  const [secondsLeft, setSecondsLeft] = useState(DURATIONS.WORK);
  const [isRunning, setIsRunning] = useState(false);
  const [cycle, setCycle] = useState(1);
  const [phase, setPhase] = useState("Work");
  const intervalRef = useRef(null);

  // State dérivé
  const totalDuration =
    phase === "Work"
      ? DURATIONS.WORK
      : phase === "Pause Courte"
      ? DURATIONS.SHORT_BREAK
      : DURATIONS.LONG_BREAK;

  // Durée du timer
  useEffect(() => {
    if (isRunning) {
      intervalRef.current = setInterval(() => {
        setSecondsLeft((prev) => {
          if (prev <= 1) {
            clearInterval(intervalRef.current);
            nextCycle();
            return 0;
          }
          return prev - 1;
        });
      }, 1000);
    } else {
      clearInterval(intervalRef.current);
    }

    return () => clearInterval(intervalRef.current);
  }, [isRunning]);

  // Mettre à jour le cycle et la phase
  const nextCycle = useCallback(() => {
    setIsRunning(false);

    if (phase === "Work") {
      if (cycle % 4 === 0) {
        setPhase("Pause Longue");
        setSecondsLeft(DURATIONS.LONG_BREAK);
      } else {
        setPhase("Pause Courte");
        setSecondsLeft(DURATIONS.SHORT_BREAK);
      }
    } else {
      setPhase("Work");
      setSecondsLeft(DURATIONS.WORK);
      if (phase === "Pause Courte" || phase === "Pause Longue") {
        setCycle((c) => c + 1);
      }
    }
  }, [cycle, phase]);

  // Formater le temps en MM:SS
  const formatTime = useCallback((seconds) => {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(minutes).padStart(2, "0")}:${String(secs).padStart(
      2,
      "0"
    )}`;
  }, []);

  // on/off du timer
  const toggleTimer = useCallback(() => {
    setIsRunning((prev) => !prev);
  }, []);

  // Réinitialiser le timer
  const resetTimer = useCallback(() => {
    clearInterval(intervalRef.current);
    setIsRunning(false);
    setPhase("Work");
    setSecondsLeft(DURATIONS.WORK);
    setCycle(1);
  }, []);

  return {
    secondsLeft,
    isRunning,
    phase,
    cycle,
    totalDuration,
    formatTime,
    toggleTimer,
    resetTimer,
    nextCycle,
  };
}
