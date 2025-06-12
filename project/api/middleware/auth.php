<?php
class AuthMiddleware {
    
    public function __construct() {
        // Configuración de cookies para cross-origin
        session_set_cookie_params([
            'lifetime' => 86400,
            'path' => '/',
            'domain' => 'localhost', // Cambiar en producción
            'secure' => false,       // true en producción con HTTPS
            'httponly' => true,
            'samesite' => 'None'     // Requerido para cross-origin
        ]);
        
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function authenticate() {
        // Permitir preflight OPTIONS
        if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
            return true;
        }

        if (!isset($_SESSION['user'])) {
            http_response_code(401);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Unauthorized - No active session']);
            exit();
        }

        return $_SESSION['user'];
    }

    public function requireRole($requiredRole) {
        $user = $this->authenticate();
        
        if ($user['role'] !== $requiredRole) {
            http_response_code(403);
            header('Content-Type: application/json');
            echo json_encode(['message' => 'Forbidden - Insufficient permissions']);
            exit();
        }
    }
}