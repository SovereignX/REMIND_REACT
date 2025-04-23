<?php

require_once '/../models/AuthenticationModel.php';

class AuthenticationController {
    private $model;

    public function __construct($db) {
        $this->model = new AuthenticationModel($db);
    }

    public function registerUser($nom, $prenom, $email, $password) {
        if ($this->model->emailExists($email)) {
            return ['success' => false, 'message' => 'Email déjà utilisé.'];
        }

        $userId = $this->model->insertUser($nom, $prenom, $email, $password);
        if (!$userId) {
            return ['success' => false, 'message' => 'Erreur lors de l\'inscription.'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $userId,
                'nom' => $nom,
                'prenom' => $prenom,
                'email' => $email
            ]
        ];
    }

    public function loginUser($email, $password) {
        $user = $this->model->getUserByEmail($email);
        if (!$user) {
            return ['success' => false, 'message' => 'Email incorrect.'];
        }

        if (!password_verify($password, $user['password'])) {
            return ['success' => false, 'message' => 'Mot de passe incorrect.'];
        }

        return [
            'success' => true,
            'user' => [
                'id' => $user['id'],
                'nom' => $user['nom'],
                'prenom' => $user['prenom'],
                'email' => $user['email']
            ]
        ];
    }
}
