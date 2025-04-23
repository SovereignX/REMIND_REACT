<?php
// models/PomodoroModel.php

class PomodoroModel {
    public function getTimerData() {
        return [
            'workTime'  => 25 * 60,  // 25 minutes en secondes
            'breakTime' => 5 * 60,   // 5 minutes en secondes
            'sessions'  => 4
        ];
    }
}
?>
