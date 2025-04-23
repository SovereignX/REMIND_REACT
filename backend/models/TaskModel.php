<?php
class Task {
    private $tasks;
    
    public function __construct() {
        if (!isset($_SESSION['tasks'])) {
            $_SESSION['tasks'] = [];
        }
        $this->tasks = &$_SESSION['tasks'];
    }
    
    public function getAllTasks() {
        return $this->tasks;
    }
    
    public function createTask($data) {
        $task = [
            'id' => uniqid(),
            'title' => $data['title'],
            'duration' => (int)$data['duration'],
            'method_type' => $data['method_type'],
            'created_at' => date('Y-m-d H:i:s')
        ];
        
        $this->tasks[] = $task;
        return true;
    }
    
    public function getTask($id) {
        foreach ($this->tasks as $task) {
            if ($task['id'] === $id) {
                return $task;
            }
        }
        return null;
    }
    
    public function deleteTask($id) {
        foreach ($this->tasks as $key => $task) {
            if ($task['id'] === $id) {
                unset($this->tasks[$key]);
                $this->tasks = array_values($this->tasks); // Reindex array
                return true;
            }
        }
        return false;
    }
}
