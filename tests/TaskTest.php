<?php

require_once __DIR__ . '/../src/models/Task.php';
require_once __DIR__ . '/../src/database/Database.php';

class TaskTest
{
    private $database;
    private $task;
    private $testDbPath;

    public function __construct()
    {
        $this->testDbPath = __DIR__ . '/../storage/test_database.sqlite';
        $this->setUp();
    }

    private function setUp(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
        $this->database = new Database($this->testDbPath);
        $this->task = new Task($this->database);
    }

    private function tearDown(): void
    {
        if (file_exists($this->testDbPath)) {
            unlink($this->testDbPath);
        }
    }

    public function runAllTests(): void
    {
        echo "Running Task Model Tests...\n\n";

        $tests = [
            'testCreateTask',
            'testCreateTaskWithInvalidData',
            'testFindAllTasks',
            'testFindAllTasksWithStatusFilter',
            'testFindAllTasksWithInvalidStatusFilter',
            'testFindTaskById',
            'testFindTaskByIdNotFound',
            'testUpdateTask',
            'testUpdateTaskNotFound',
            'testUpdateTaskWithInvalidData',
            'testDeleteTask',
            'testDeleteTaskNotFound',
            'testValidateStatus',
            'testFormatTask'
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                $this->setUp();
                $this->$test();
                echo "✓ $test passed\n";
                $passed++;
            } catch (Exception $e) {
                echo "✗ $test failed: " . $e->getMessage() . "\n";
                $failed++;
            } finally {
                $this->tearDown();
            }
        }

        echo "\nTest Results: $passed passed, $failed failed\n";
        
        if ($failed > 0) {
            exit(1);
        }
    }

    public function testCreateTask(): void
    {
        $data = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending'
        ];

        $result = $this->task->create($data);

        $this->assertNotNull($result, 'Created task should not be null');
        $this->assertEquals(1, $result['id'], 'Task ID should be 1');
        $this->assertEquals('Test Task', $result['title'], 'Title should match');
        $this->assertEquals('Test Description', $result['description'], 'Description should match');
        $this->assertEquals('pending', $result['status'], 'Status should match');
        $this->assertNotNull($result['created_at'], 'Created at should be set');
        $this->assertNotNull($result['updated_at'], 'Updated at should be set');
    }

    public function testCreateTaskWithInvalidData(): void
    {
        try {
            $this->task->create(['title' => '']);
            $this->fail('Should throw exception for empty title');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Title is required', $e->getMessage());
        }

        try {
            $this->task->create(['title' => 'Test', 'status' => 'invalid']);
            $this->fail('Should throw exception for invalid status');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Invalid status', $e->getMessage());
        }

        try {
            $this->task->create(['title' => str_repeat('a', 256)]);
            $this->fail('Should throw exception for title too long');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Title must not exceed 255 characters', $e->getMessage());
        }

        try {
            $this->task->create(['title' => 'Test', 'description' => str_repeat('a', 1001)]);
            $this->fail('Should throw exception for description too long');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Description must not exceed 1000 characters', $e->getMessage());
        }
    }

    public function testFindAllTasks(): void
    {
        $this->task->create(['title' => 'Task 1', 'status' => 'pending']);
        $this->task->create(['title' => 'Task 2', 'status' => 'in-progress']);
        $this->task->create(['title' => 'Task 3', 'status' => 'completed']);

        $result = $this->task->findAll();

        $this->assertEquals(3, count($result), 'Should return 3 tasks');
        $this->assertEquals('Task 3', $result[0]['title'], 'Should be ordered by created_at DESC');
        $this->assertEquals('Task 2', $result[1]['title'], 'Should be ordered by created_at DESC');
        $this->assertEquals('Task 1', $result[2]['title'], 'Should be ordered by created_at DESC');
    }

    public function testFindAllTasksWithStatusFilter(): void
    {
        $this->task->create(['title' => 'Task 1', 'status' => 'pending']);
        $this->task->create(['title' => 'Task 2', 'status' => 'in-progress']);
        $this->task->create(['title' => 'Task 3', 'status' => 'pending']);

        $result = $this->task->findAll(['status' => 'pending']);

        $this->assertEquals(2, count($result), 'Should return 2 pending tasks');
        $this->assertEquals('pending', $result[0]['status'], 'All tasks should have pending status');
        $this->assertEquals('pending', $result[1]['status'], 'All tasks should have pending status');
    }

    public function testFindAllTasksWithInvalidStatusFilter(): void
    {
        try {
            $this->task->findAll(['status' => 'invalid']);
            $this->fail('Should throw exception for invalid status filter');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Invalid status filter', $e->getMessage());
        }
    }

    public function testFindTaskById(): void
    {
        $created = $this->task->create(['title' => 'Test Task', 'status' => 'pending']);
        $result = $this->task->findById($created['id']);

        $this->assertNotNull($result, 'Task should be found');
        $this->assertEquals($created['id'], $result['id'], 'IDs should match');
        $this->assertEquals('Test Task', $result['title'], 'Titles should match');
    }

    public function testFindTaskByIdNotFound(): void
    {
        $result = $this->task->findById(999);
        $this->assertNull($result, 'Should return null for non-existent task');
    }

    public function testUpdateTask(): void
    {
        $created = $this->task->create(['title' => 'Original Title', 'status' => 'pending']);
        
        sleep(1);
        
        $updateData = [
            'title' => 'Updated Title',
            'description' => 'Updated Description',
            'status' => 'in-progress'
        ];
        
        $result = $this->task->update($created['id'], $updateData);

        $this->assertNotNull($result, 'Updated task should not be null');
        $this->assertEquals('Updated Title', $result['title'], 'Title should be updated');
        $this->assertEquals('Updated Description', $result['description'], 'Description should be updated');
        $this->assertEquals('in-progress', $result['status'], 'Status should be updated');
        
        $this->assertTrue(strtotime($result['updated_at']) >= strtotime($created['created_at']), 'Updated at should be >= created at');
    }

    public function testUpdateTaskNotFound(): void
    {
        $result = $this->task->update(999, ['title' => 'Updated']);
        $this->assertNull($result, 'Should return null for non-existent task');
    }

    public function testUpdateTaskWithInvalidData(): void
    {
        $created = $this->task->create(['title' => 'Test Task']);

        try {
            $this->task->update($created['id'], ['title' => '']);
            $this->fail('Should throw exception for empty title');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Title cannot be empty', $e->getMessage());
        }

        try {
            $this->task->update($created['id'], ['status' => 'invalid']);
            $this->fail('Should throw exception for invalid status');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContains('Invalid status', $e->getMessage());
        }
    }

    public function testDeleteTask(): void
    {
        $created = $this->task->create(['title' => 'Test Task']);
        $result = $this->task->delete($created['id']);

        $this->assertTrue($result, 'Delete should return true');
        
        $found = $this->task->findById($created['id']);
        $this->assertNull($found, 'Task should no longer exist');
    }

    public function testDeleteTaskNotFound(): void
    {
        $result = $this->task->delete(999);
        $this->assertFalse($result, 'Delete should return false for non-existent task');
    }

    public function testValidateStatus(): void
    {
        $validStatuses = $this->task->getValidStatuses();
        $this->assertEquals(['pending', 'in-progress', 'completed'], $validStatuses, 'Valid statuses should match expected values');
    }

    public function testFormatTask(): void
    {
        $created = $this->task->create(['title' => 'Test Task', 'description' => 'Test Description']);
        
        $this->assertIsInt($created['id'], 'ID should be integer');
        $this->assertIsString($created['title'], 'Title should be string');
        $this->assertIsString($created['description'], 'Description should be string');
        $this->assertIsString($created['status'], 'Status should be string');
        $this->assertIsString($created['created_at'], 'Created at should be string');
        $this->assertIsString($created['updated_at'], 'Updated at should be string');
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

    private function assertNull($value, $message = ''): void
    {
        if ($value !== null) {
            throw new Exception($message ?: 'Value should be null');
        }
    }

    private function assertTrue($value, $message = ''): void
    {
        if ($value !== true) {
            throw new Exception($message ?: 'Value should be true');
        }
    }

    private function assertFalse($value, $message = ''): void
    {
        if ($value !== false) {
            throw new Exception($message ?: 'Value should be false');
        }
    }

    private function assertIsInt($value, $message = ''): void
    {
        if (!is_int($value)) {
            throw new Exception($message ?: 'Value should be integer');
        }
    }

    private function assertIsString($value, $message = ''): void
    {
        if (!is_string($value)) {
            throw new Exception($message ?: 'Value should be string');
        }
    }

    private function assertStringContains($needle, $haystack, $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String '$haystack' should contain '$needle'");
        }
    }

    private function assertNotEquals($expected, $actual, $message = ''): void
    {
        if ($expected === $actual) {
            throw new Exception($message ?: "Values should not be equal");
        }
    }

    private function fail($message): void
    {
        throw new Exception($message);
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new TaskTest();
    $test->runAllTests();
}