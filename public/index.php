<?php

// Set error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Set content type header
header('Content-Type: application/json');

// Enable CORS for development (optional)
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include required classes
require_once __DIR__ . '/../src/utils/Router.php';
require_once __DIR__ . '/../src/database/Database.php';
require_once __DIR__ . '/../src/models/Task.php';
require_once __DIR__ . '/../src/controllers/TaskController.php';

// Initialize database and models
try {
    $database = new Database(__DIR__ . '/../storage/database.sqlite');
    $database->initialize();
    $taskModel = new Task($database);
    $taskController = new TaskController($taskModel);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => true,
        'message' => 'Database initialization failed',
        'code' => 'DATABASE_ERROR'
    ]);
    exit();
}

// Initialize router
$router = new Router();

// Define routes with TaskController methods
$router->addRoute('POST', '/tasks', [$taskController, 'create']);
$router->addRoute('GET', '/tasks', [$taskController, 'getAll']);
$router->addRoute('GET', '/tasks/{id}', [$taskController, 'getById']);
$router->addRoute('PUT', '/tasks/{id}', [$taskController, 'update']);

// Get request method and URI
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

// Route the request
$router->route($method, $uri);