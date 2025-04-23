<?php

class AuthenticationModel {
    private $db;

    public function __construct($db) {
        $this->db = $db;
    }

    public function emailExists($email) {
        $stmt = $this->db->prepare("SELECT COUNT(*) FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetchColumn() > 0;
    }

    public function insertUser($nom, $prenom, $email, $hashedPassword) {
        $stmt = $this->db->prepare("INSERT INTO utilisateurs (nom, prenom, email, password) VALUES (?, ?, ?, ?)");
        if ($stmt->execute([$nom, $prenom, $email, $hashedPassword])) {
            return $this->db->lastInsertId();
        }
        return false;
    }

    public function getUserByEmail($email) {
        $stmt = $this->db->prepare("SELECT id, nom, prenom, email, password FROM utilisateurs WHERE email = ?");
        $stmt->execute([$email]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}
