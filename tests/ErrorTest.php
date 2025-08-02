<?php

require_once __DIR__ . '/../src/database/Database.php';
require_once __DIR__ . '/../src/models/Task.php';
require_once __DIR__ . '/../src/controllers/TaskController.php';

class ErrorTest
{
    private Task $task;
    private TaskController $controller;

    public function __construct()
    {
        $db = new Database(':memory:');
        $db->initialize();
        $this->task = new Task($db);
        $this->controller = new TaskController($this->task);
    }

    public function runTests(): void
    {
        echo "Running Error Tests...\n";
        
        $this->testValidationErrors();
        $this->testNotFoundErrors();
        $this->testInvalidIdErrors();
        
        echo "All error tests passed!\n";
    }

    private function testValidationErrors(): void
    {
        try {
            $this->task->create([]);
            assert(false, 'Should throw error');
        } catch (InvalidArgumentException $e) {
            assert(strpos($e->getMessage(), 'Title is required') !== false);
        }
        echo "Validation error test passed\n";
    }

    private function testNotFoundErrors(): void
    {
        $output = $this->capture('getById', [999]);
        $response = json_decode($output, true);
        assert($response['error'] === true);
        assert($response['code'] === 'NOT_FOUND');
        echo "Not found error test passed\n";
    }

    private function testInvalidIdErrors(): void
    {
        $output = $this->capture('getById', [-1]);
        $response = json_decode($output, true);
        assert($response['error'] === true);
        assert($response['code'] === 'INVALID_ID');
        echo "Invalid ID error test passed\n";
    }

    private function capture(string $method, array $params = []): string
    {
        ob_start();
        call_user_func_array([$this->controller, $method], $params);
        return ob_get_clean();
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    (new ErrorTest())->runTests();
}