<?php

error_reporting(E_ALL);
ini_set('display_errors', 1);

header('Content-Type: application/json');

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once __DIR__ . '/../src/utils/Router.php';
require_once __DIR__ . '/../src/database/Database.php';
require_once __DIR__ . '/../src/models/Task.php';
require_once __DIR__ . '/../src/controllers/TaskController.php';

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

$router = new Router();

$router->addRoute('POST', '/tasks', [$taskController, 'create']);
$router->addRoute('GET', '/tasks', [$taskController, 'getAll']);
$router->addRoute('GET', '/tasks/{id}', [$taskController, 'getById']);
$router->addRoute('PUT', '/tasks/{id}', [$taskController, 'update']);

$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];

$router->route($method, $uri);