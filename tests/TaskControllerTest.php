<?php

require_once __DIR__ . '/../src/controllers/TaskController.php';
require_once __DIR__ . '/../src/models/Task.php';
require_once __DIR__ . '/../src/database/Database.php';

class TaskControllerTest
{
    private TaskController $controller;

    public function __construct()
    {
        $db = new Database(':memory:');
        $db->initialize();
        $task = new Task($db);
        $this->controller = new TaskController($task);
    }

    private function tearDown(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function runAllTests(): void
    {
        echo "Running TaskController Tests...\n\n";

        $tests = [
            'testControllerInstantiation',
            'testValidatorIntegration',
            'testRequestBodyParsing',
            'testErrorHandling',
            'testResponseFormatting'
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
                echo " $test failed: " . $e->getMessage() . "\n";
                $failed++;
            } finally {
                $this->tearDown();
            }
        }

        echo "\nTaskController Test Results: $passed passed, $failed failed\n";
        
        if ($failed > 0) {
            exit(1);
        }
    }

    public function testControllerInstantiation(): void
    {
        $this->assertNotNull($this->controller, 'Controller should be instantiated');
        $this->assertInstanceOf(TaskController::class, $this->controller, 'Should be instance of TaskController');
    }

    public function testValidatorIntegration(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $validatorProperty = $reflection->getProperty('validator');
        $validatorProperty->setAccessible(true);
        $validator = $validatorProperty->getValue($this->controller);
        
        $this->assertInstanceOf(Validator::class, $validator, 'Controller should have validator instance');
    }

    public function testRequestBodyParsing(): void
    {
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('getRequestBody');
        $method->setAccessible(true);
        
        
        $this->assertTrue($method->isPrivate(), 'getRequestBody should be private method');
    }

    public function testErrorHandling(): void
    {
        
        $reflection = new ReflectionClass($this->controller);
        $method = $reflection->getMethod('getErrorCode');
        $method->setAccessible(true);
        
        $errorCode = $method->invoke($this->controller, 400);
        $this->assertEquals('BAD_REQUEST', $errorCode, 'Should return correct error code for 400');
        
        $errorCode = $method->invoke($this->controller, 404);
        $this->assertEquals('NOT_FOUND', $errorCode, 'Should return correct error code for 404');
        
        $errorCode = $method->invoke($this->controller, 500);
        $this->assertEquals('INTERNAL_SERVER_ERROR', $errorCode, 'Should return correct error code for 500');
    }

    public function testResponseFormatting(): void
    {
        
        $reflection = new ReflectionClass($this->controller);
        
        $this->assertTrue($reflection->hasMethod('sendResponse'), 'Should have sendResponse method');
        $this->assertTrue($reflection->hasMethod('sendError'), 'Should have sendError method');
        $this->assertTrue($reflection->hasMethod('sendValidationError'), 'Should have sendValidationError method');
        
        
        $sendResponse = $reflection->getMethod('sendResponse');
        $this->assertTrue($sendResponse->isPrivate(), 'sendResponse should be private');
        
        $sendError = $reflection->getMethod('sendError');
        $this->assertTrue($sendError->isPrivate(), 'sendError should be private');
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

    private function assertTrue($value, $message = ''): void
    {
        if ($value !== true) {
            throw new Exception($message ?: 'Value should be true');
        }
    }

    private function assertIsArray($value, $message = ''): void
    {
        if (!is_array($value)) {
            throw new Exception($message ?: 'Value should be array');
        }
    }

    private function assertStringContains($needle, $haystack, $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String '$haystack' should contain '$needle'");
        }
    }

    private function assertInstanceOf($expected, $actual, $message = ''): void
    {
        if (!($actual instanceof $expected)) {
            $actualType = is_object($actual) ? get_class($actual) : gettype($actual);
            throw new Exception($message ?: "Expected instance of '$expected', got '$actualType'");
        }
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new TaskControllerTest();
    $test->runAllTests();
}