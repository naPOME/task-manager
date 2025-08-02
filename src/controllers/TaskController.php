<?php

require_once __DIR__ . '/../models/Task.php';
require_once __DIR__ . '/../utils/Validator.php';

class TaskController
{
    private Task $taskModel;
    private Validator $validator;

    public function __construct(Task $taskModel)
    {
        $this->taskModel = $taskModel;
        $this->validator = new Validator();
    }

    /**
     * Handle POST /tasks - Create a new task
     */
    public function create(): void
    {
        try {
            $requestData = $this->getRequestBody();
            
            if ($requestData === null) {
                $this->sendError(400, 'Invalid JSON in request body', 'INVALID_JSON');
                return;
            }

            // Sanitize input data
            $requestData = $this->validator->sanitizeData($requestData);

            // Validate task data
            $validationErrors = $this->validator->validateTaskData($requestData, false);
            if (!empty($validationErrors)) {
                $this->sendValidationError($validationErrors);
                return;
            }

            // Create the task
            $task = $this->taskModel->create($requestData);
            $this->sendResponse(201, $task);

        } catch (InvalidArgumentException $e) {
            $this->sendError(400, $e->getMessage(), 'VALIDATION_ERROR');
        } catch (PDOException $e) {
            error_log('Database error during task creation: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Database error occurred', 'DATABASE_ERROR');
        } catch (Exception $e) {
            error_log('Unexpected error during task creation: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Internal server error', 'INTERNAL_SERVER_ERROR');
        }
    }

    /**
     * Handle GET /tasks - Get all tasks with optional filtering
     */
    public function getAll(): void
    {
        try {
            $filters = [];
            
            // Check for status filter in query parameters
            if (isset($_GET['status'])) {
                $statusError = $this->validator->validateStatusFilter($_GET['status']);
                if ($statusError !== null) {
                    $this->sendError(400, $statusError, 'INVALID_FILTER');
                    return;
                }
                $filters['status'] = $_GET['status'];
            }

            $tasks = $this->taskModel->findAll($filters);
            $this->sendResponse(200, $tasks);

        } catch (InvalidArgumentException $e) {
            $this->sendError(400, $e->getMessage(), 'VALIDATION_ERROR');
        } catch (PDOException $e) {
            error_log('Database error during task retrieval: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Database error occurred', 'DATABASE_ERROR');
        } catch (Exception $e) {
            error_log('Unexpected error during task retrieval: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Internal server error', 'INTERNAL_SERVER_ERROR');
        }
    }

    /**
     * Handle GET /tasks/{id} - Get a specific task by ID
     */
    public function getById(int $id): void
    {
        try {
            // Validate ID
            $idError = $this->validator->validateId($id);
            if ($idError !== null) {
                $this->sendError(400, $idError, 'INVALID_ID');
                return;
            }

            $task = $this->taskModel->findById($id);
            
            if ($task === null) {
                $this->sendError(404, 'Task not found', 'NOT_FOUND');
                return;
            }

            $this->sendResponse(200, $task);

        } catch (InvalidArgumentException $e) {
            $this->sendError(400, $e->getMessage(), 'VALIDATION_ERROR');
        } catch (PDOException $e) {
            error_log('Database error during task retrieval by ID: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Database error occurred', 'DATABASE_ERROR');
        } catch (Exception $e) {
            error_log('Unexpected error during task retrieval by ID: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Internal server error', 'INTERNAL_SERVER_ERROR');
        }
    }

    /**
     * Handle PUT /tasks/{id} - Update an existing task
     */
    public function update(int $id): void
    {
        try {
            // Validate ID
            $idError = $this->validator->validateId($id);
            if ($idError !== null) {
                $this->sendError(400, $idError, 'INVALID_ID');
                return;
            }

            $requestData = $this->getRequestBody();
            
            if ($requestData === null) {
                $this->sendError(400, 'Invalid JSON in request body', 'INVALID_JSON');
                return;
            }

            // Sanitize input data
            $requestData = $this->validator->sanitizeData($requestData);

            // Validate task data for update
            $validationErrors = $this->validator->validateTaskData($requestData, true);
            if (!empty($validationErrors)) {
                $this->sendValidationError($validationErrors);
                return;
            }

            // Update the task
            $task = $this->taskModel->update($id, $requestData);
            
            if ($task === null) {
                $this->sendError(404, 'Task not found', 'NOT_FOUND');
                return;
            }

            $this->sendResponse(200, $task);

        } catch (InvalidArgumentException $e) {
            $this->sendError(400, $e->getMessage(), 'VALIDATION_ERROR');
        } catch (PDOException $e) {
            error_log('Database error during task update: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Database error occurred', 'DATABASE_ERROR');
        } catch (Exception $e) {
            error_log('Unexpected error during task update: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendError(500, 'Internal server error', 'INTERNAL_SERVER_ERROR');
        }
    }

    /**
     * Get and parse request body as JSON
     */
    private function getRequestBody(): ?array
    {
        $input = file_get_contents('php://input');
        
        if (empty($input)) {
            return [];
        }

        if (!$this->validator->isValidJson($input)) {
            return null;
        }

        $data = json_decode($input, true);
        return is_array($data) ? $data : null;
    }

    /**
     * Send JSON response
     */
    private function sendResponse(int $statusCode, $data): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }
        
        echo json_encode($data, JSON_PRETTY_PRINT);
    }

    /**
     * Send error response
     */
    private function sendError(int $statusCode, string $message, string $errorCode = null): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }
        
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $errorCode ?? $this->getErrorCode($statusCode)
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Send validation error response
     */
    private function sendValidationError(array $errors): void
    {
        if (!headers_sent()) {
            http_response_code(400);
            header('Content-Type: application/json');
        }
        
        echo json_encode([
            'error' => true,
            'message' => 'Validation failed',
            'code' => 'VALIDATION_ERROR',
            'details' => $errors
        ], JSON_PRETTY_PRINT);
    }

    /**
     * Get error code based on HTTP status
     */
    private function getErrorCode(int $statusCode): string
    {
        switch ($statusCode) {
            case 400:
                return 'BAD_REQUEST';
            case 404:
                return 'NOT_FOUND';
            case 405:
                return 'METHOD_NOT_ALLOWED';
            case 500:
                return 'INTERNAL_SERVER_ERROR';
            default:
                return 'UNKNOWN_ERROR';
        }
    }
}