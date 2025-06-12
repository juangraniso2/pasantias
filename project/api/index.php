<?php
// Iniciar sesión primero para asegurar que los headers de sesión no interfieran con CORS
session_start();

// Cargar variables de entorno al principio
if (file_exists(__DIR__ . '/.env')) {
    $lines = file(__DIR__ . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

// Incluir archivos de configuración y rutas
require_once __DIR__ . '/config/cors.php';
require_once __DIR__ . '/routes/auth.php';
require_once __DIR__ . '/routes/forms.php';
require_once __DIR__ . '/routes/responses.php';

// Configurar manejo de errores básico
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});

// Buffer de salida para capturar posibles errores antes de los headers
ob_start();

// Manejar CORS
handleCORS();

// Configurar tipo de contenido JSON
header('Content-Type: application/json; charset=UTF-8');

// Obtener método y URI de la solicitud
$method = $_SERVER['REQUEST_METHOD'];
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Limpiar la URI (remover /api si está presente)
$uri = preg_replace('#^/api#', '', $uri);

// Función para enviar respuestas JSON consistentes
function sendResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit();
}

// Enrutamiento principal
try {
    // Limpiar buffer antes de enviar respuestas
    ob_end_clean();

    if (preg_match('#^/auth/(.+)$#', $uri, $matches)) {
        $authRoutes = new AuthRoutes();
        $authRoutes->handleRequest($method, '/' . $matches[1]);
        
    } elseif (preg_match('#^/forms/([^/]+)/responses$#', $uri, $matches)) {
        $responsesRoutes = new ResponsesRoutes();
        $responsesRoutes->handleRequest($method, '/form-responses', $matches[1]);
        
    } elseif (preg_match('#^/forms/([^/]+)$#', $uri, $matches)) {
        $formsRoutes = new FormsRoutes();
        $formsRoutes->handleRequest($method, '/forms', $matches[1]);
        
    } elseif ($uri === '/forms') {
        $formsRoutes = new FormsRoutes();
        $formsRoutes->handleRequest($method, '/forms');
        
    } elseif (preg_match('#^/responses/([^/]+)$#', $uri, $matches)) {
        $responsesRoutes = new ResponsesRoutes();
        $responsesRoutes->handleRequest($method, '/responses', null, $matches[1]);
        
    } elseif ($uri === '/responses' || $uri === '/responses/import') {
        $responsesRoutes = new ResponsesRoutes();
        $responsesRoutes->handleRequest($method, $uri);
        
    } else {
        sendResponse(['success' => false, 'message' => 'API endpoint not found'], 404);
    }
    
} catch (Exception $e) {
    // Limpiar buffer en caso de error
    ob_end_clean();
    
    error_log("API Error: " . $e->getMessage() . "\n" . $e->getTraceAsString());
    sendResponse([
        'success' => false,
        'message' => 'Internal server error',
        'error' => $e->getMessage()
    ], 500);
}
?>