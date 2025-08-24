<?php
require_once 'db_connect.php';

session_start();

class UserAuth {
    private $pdo;

    public function __construct($pdo) {
        $this->pdo = $pdo;
    }

    public function register($username, $email, $password, $fullName) {
        try {
            $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password, full_name) 
                VALUES (?, ?, ?, ?)
            ");
            
            $stmt->execute([$username, $email, $hashedPassword, $fullName]);
            return true;
        } catch (PDOException $e) {
            return false;
        }
    }

    public function login($email, $password) {
        $stmt = $this->pdo->prepare("
            SELECT user_id, password, username 
            FROM users 
            WHERE email = ? AND is_active = 1
        ");
        
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            
            // Update last login
            $stmt = $this->pdo->prepare("
                UPDATE users 
                SET last_login = CURRENT_TIMESTAMP 
                WHERE user_id = ?
            ");
            $stmt->execute([$user['user_id']]);
            
            return true;
        }
        
        return false;
    }

    public function logout() {
        session_destroy();
    }

    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
}

// Usage example:
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $auth = new UserAuth($pdo);
    
    if (isset($_POST['action'])) {
        switch ($_POST['action']) {
            case 'register':
                $success = $auth->register(
                    $_POST['username'],
                    $_POST['email'],
                    $_POST['password'],
                    $_POST['full_name']
                );
                echo json_encode(['success' => $success]);
                break;

            case 'login':
                $success = $auth->login($_POST['email'], $_POST['password']);
                echo json_encode(['success' => $success]);
                break;

            case 'logout':
                $auth->logout();
                echo json_encode(['success' => true]);
                break;
        }
    }
}
?> 
