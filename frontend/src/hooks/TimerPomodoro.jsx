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

  // Derived state
  const totalDuration =
    phase === "Work"
      ? DURATIONS.WORK
      : phase === "Pause Courte"
      ? DURATIONS.SHORT_BREAK
      : DURATIONS.LONG_BREAK;

  // Handle timer countdown
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

  // Set up next timer cycle
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

  // Format time as MM:SS
  const formatTime = useCallback((seconds) => {
    const minutes = Math.floor(seconds / 60);
    const secs = seconds % 60;
    return `${String(minutes).padStart(2, "0")}:${String(secs).padStart(
      2,
      "0"
    )}`;
  }, []);

  // Toggle timer on/off
  const toggleTimer = useCallback(() => {
    setIsRunning((prev) => !prev);
  }, []);

  // Reset timer to initial state
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
