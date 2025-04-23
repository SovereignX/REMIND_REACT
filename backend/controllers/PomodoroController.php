<?php
// controllers/PomodoroController.php

require_once('../app/models/PomodoroModel.php');

class PomodoroController {
    private $model;
    
    public function __construct() {
        $this->model = new PomodoroModel();
    }
    
    public function showPomodoro() {
        // Récupère les données initiales de la session Pomodoro
        $data = $this->model->getTimerData();
        // Inclut la vue et lui transmet les données
        require_once('../app/views/pomodoro.php');
    }
}

// Instanciation et appel de la méthode index du contrôleur
$controller = new PomodoroController();
$controller->showPomodoro();
?>
