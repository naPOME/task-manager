<?php

require_once __DIR__ . '/../src/utils/Router.php';
require_once __DIR__ . '/../src/controllers/TaskController.php';
require_once __DIR__ . '/../src/models/Task.php';
require_once __DIR__ . '/../src/database/Database.php';

class GetTaskByIdRouterTest
{
    private $database;
    private $task;
    private $controller;
    private $router;
    private $testDbPath;

    public function __construct()
    {
        $this->testDbPath = __DIR__ . '/../storage/test_router_database.sqlite';
        $this->setUp();
    }

    private function setUp(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
        $this->database = new Database($this->testDbPath);
        $this->task = new Task($this->database);
        $this->controller = new TaskController($this->task);
        
        
        $this->router = new Router();
        $this->router->addRoute('GET', '/tasks/{id}', [$this->controller, 'getById']);
    }

    private function tearDown(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function runAllTests(): void
    {
        echo "Running GET /tasks/{id} Router Tests...\n\n";

        $tests = [
            'testRouterWithValidId',
            'testRouterWithInvalidId',
            'testRouterWithNonNumericId'
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                $this->setUp(); 
                $this->$test();
                echo " $test passed\n";
                $passed++;
            } catch (Exception $e) {
                echo "$test failed: " . $e->getMessage() . "\n";
                $failed++;
            } finally {
                $this->tearDown();
            }
        }

        echo "\nGET /tasks/{id} Router Test Results: $passed passed, $failed failed\n";
        
        if ($failed > 0) {
            exit(1);
        }
    }

    public function testRouterWithValidId(): void
    {
        $createdTask = $this->task->create([
            'title' => 'Router Test Task',
            'description' => 'Testing through router',
            'status' => 'pending'
        ]);

        ob_start();
        $this->router->route('GET', "/tasks/{$createdTask['id']}");
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertNotNull($response, 'Response should not be null');
        $this->assertEquals($createdTask['id'], $response['id'], 'ID should match');
        $this->assertEquals('Router Test Task', $response['title'], 'Title should match');
    }

    public function testRouterWithInvalidId(): void
    {
        $this->task->create(['title' => 'Test Task', 'status' => 'pending']);

        ob_start();
        $this->router->route('GET', '/tasks/999');
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response, 'Response should contain error');
        $this->assertTrue($response['error'], 'Error flag should be true');
        $this->assertEquals('Task not found', $response['message'], 'Should return task not found message');
    }

    public function testRouterWithNonNumericId(): void
    {
        ob_start();
        $this->router->route('GET', '/tasks/abc');
        $output = ob_get_clean();
        
        $response = json_decode($output, true);
        
        $this->assertArrayHasKey('error', $response, 'Response should contain error');
        $this->assertTrue($response['error'], 'Error flag should be true');
        $this->assertEquals('Route not found', $response['message'], 'Should return route not found message');
        $this->assertEquals('ROUTE_NOT_FOUND', $response['code'], 'Should return ROUTE_NOT_FOUND error code');
    }

    
    private function assertEquals($expected, $actual, $message = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected '$expected', got '$actual'");
        }
    }

    private function assertNotNull($value, $message = ''): void
    {
        if ($value === null) {
            throw new Exception($message ?: 'Value should not be null');
        }
    }

    private function assertArrayHasKey($key, $array, $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new Exception($message ?: "Array should have key '$key'");
        }
    }

    private function assertTrue($value, $message = ''): void
    {
        if ($value !== true) {
            throw new Exception($message ?: 'Value should be true');
        }
    }
}


if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new GetTaskByIdRouterTest();
    $test->runAllTests();
}