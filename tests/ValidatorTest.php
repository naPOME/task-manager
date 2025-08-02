<?php

require_once __DIR__ . '/../src/utils/Validator.php';

class ValidatorTest
{
    private $validator;

    public function __construct()
    {
        $this->validator = new Validator();
    }

    public function runAllTests(): void
    {
        echo "Running Validator Tests...\n\n";

        $tests = [
            'testValidateTaskDataForCreation',
            'testValidateTaskDataForUpdate',
            'testValidateTaskDataWithInvalidTitle',
            'testValidateTaskDataWithInvalidDescription',
            'testValidateTaskDataWithInvalidStatus',
            'testValidateId',
            'testValidateStatusFilter',
            'testIsValidJson',
            'testSanitizeData'
        ];

        $passed = 0;
        $failed = 0;

        foreach ($tests as $test) {
            try {
                $this->$test();
                echo "✓ $test passed\n";
                $passed++;
            } catch (Exception $e) {
                echo "✗ $test failed: " . $e->getMessage() . "\n";
                $failed++;
            }
        }

        echo "\nValidator Test Results: $passed passed, $failed failed\n";
        
        if ($failed > 0) {
            exit(1);
        }
    }

    public function testValidateTaskDataForCreation(): void
    {
        $validData = [
            'title' => 'Test Task',
            'description' => 'Test Description',
            'status' => 'pending'
        ];

        $errors = $this->validator->validateTaskData($validData, false);
        $this->assertEmpty($errors, 'Valid data should not produce errors');

        $minimalData = ['title' => 'Test Task'];
        $errors = $this->validator->validateTaskData($minimalData, false);
        $this->assertEmpty($errors, 'Minimal valid data should not produce errors');
    }

    public function testValidateTaskDataForUpdate(): void
    {
        $updateData = [
            'title' => 'Updated Task',
            'description' => 'Updated Description',
            'status' => 'in-progress'
        ];

        $errors = $this->validator->validateTaskData($updateData, true);
        $this->assertEmpty($errors, 'Valid update data should not produce errors');

        $emptyData = [];
        $errors = $this->validator->validateTaskData($emptyData, true);
        $this->assertEmpty($errors, 'Empty update data should be valid');
        $partialData = ['title' => 'New Title'];
        $errors = $this->validator->validateTaskData($partialData, true);
        $this->assertEmpty($errors, 'Partial update data should be valid');
    }

    public function testValidateTaskDataWithInvalidTitle(): void
    {
        $data = ['title' => ''];
        $errors = $this->validator->validateTaskData($data, false);
        $this->assertNotEmpty($errors, 'Empty title should produce error');
        $this->assertArrayHasKey('title', $errors, 'Should have title error');
        $this->assertStringContains('required', $errors['title']);

        // Missing title for creation
        $data = [];
        $errors = $this->validator->validateTaskData($data, false);
        $this->assertArrayHasKey('title', $errors, 'Should have title error for missing title');

        
        $data = ['title' => str_repeat('a', 256)];
        $errors = $this->validator->validateTaskData($data, false);
        $this->assertArrayHasKey('title', $errors, 'Should have title error for long title');
        $this->assertStringContains('255 characters', $errors['title']);

        // Empty title for update
        $data = ['title' => ''];
        $errors = $this->validator->validateTaskData($data, true);
        $this->assertArrayHasKey('title', $errors, 'Should have title error for empty title in update');
    }

    public function testValidateTaskDataWithInvalidDescription(): void
    {
        
        $data = [
            'title' => 'Test Task',
            'description' => str_repeat('a', 1001)
        ];
        $errors = $this->validator->validateTaskData($data, false);
        $this->assertArrayHasKey('description', $errors, 'Should have description error');
        $this->assertStringContains('1000 characters', $errors['description']);
    }

    public function testValidateTaskDataWithInvalidStatus(): void
    {
        
        $data = [
            'title' => 'Test Task',
            'status' => 'invalid-status'
        ];
        $errors = $this->validator->validateTaskData($data, false);
        $this->assertArrayHasKey('status', $errors, 'Should have status error');
        $this->assertStringContains('Invalid status', $errors['status']);

        
        $data = ['status' => 'invalid-status'];
        $errors = $this->validator->validateTaskData($data, true);
        $this->assertArrayHasKey('status', $errors, 'Should have status error for update');
    }

    public function testValidateId(): void
    {
        
        $this->assertNull($this->validator->validateId(1), 'Valid ID should return null');
        $this->assertNull($this->validator->validateId('123'), 'Numeric string ID should be valid');

        
        $error = $this->validator->validateId(0);
        $this->assertNotNull($error, 'Zero ID should be invalid');
        $this->assertStringContains('positive integer', $error);

        $error = $this->validator->validateId(-1);
        $this->assertNotNull($error, 'Negative ID should be invalid');

        $error = $this->validator->validateId('abc');
        $this->assertNotNull($error, 'Non-numeric ID should be invalid');

        $error = $this->validator->validateId('');
        $this->assertNotNull($error, 'Empty ID should be invalid');
    }

    public function testValidateStatusFilter(): void
    {
        
        $this->assertNull($this->validator->validateStatusFilter('pending'), 'Valid status should return null');
        $this->assertNull($this->validator->validateStatusFilter('in-progress'), 'Valid status should return null');
        $this->assertNull($this->validator->validateStatusFilter('completed'), 'Valid status should return null');
        $this->assertNull($this->validator->validateStatusFilter(null), 'Null status should be valid');

        
        $error = $this->validator->validateStatusFilter('invalid-status');
        $this->assertNotNull($error, 'Invalid status should return error');
        $this->assertStringContains('Invalid status filter', $error);
    }

    public function testIsValidJson(): void
    {
        
        $this->assertTrue($this->validator->isValidJson('{}'), 'Empty object should be valid JSON');
        $this->assertTrue($this->validator->isValidJson('[]'), 'Empty array should be valid JSON');
        $this->assertTrue($this->validator->isValidJson('{"key": "value"}'), 'Object should be valid JSON');
        $this->assertTrue($this->validator->isValidJson('"string"'), 'String should be valid JSON');
        $this->assertTrue($this->validator->isValidJson('123'), 'Number should be valid JSON');

        
        $this->assertFalse($this->validator->isValidJson('{'), 'Incomplete object should be invalid JSON');
        $this->assertFalse($this->validator->isValidJson('{"key": }'), 'Incomplete key-value should be invalid JSON');
        $this->assertFalse($this->validator->isValidJson('undefined'), 'Undefined should be invalid JSON');
        $this->assertFalse($this->validator->isValidJson(''), 'Empty string should be invalid JSON');
    }

    public function testSanitizeData(): void
    {
        $data = [
            'title' => '  Test Task  ',
            'description' => '  Test Description  ',
            'status' => '  pending  ',
            'number' => 123
        ];

        $sanitized = $this->validator->sanitizeData($data);

        $this->assertEquals('Test Task', $sanitized['title'], 'Title should be trimmed');
        $this->assertEquals('Test Description', $sanitized['description'], 'Description should be trimmed');
        $this->assertEquals('pending', $sanitized['status'], 'Status should be trimmed');
        $this->assertEquals(123, $sanitized['number'], 'Numbers should remain unchanged');
    }

    private function assertEmpty($value, $message = ''): void
    {
        if (!empty($value)) {
            throw new Exception($message ?: 'Value should be empty');
        }
    }

    private function assertNotEmpty($value, $message = ''): void
    {
        if (empty($value)) {
            throw new Exception($message ?: 'Value should not be empty');
        }
    }

    private function assertArrayHasKey($key, $array, $message = ''): void
    {
        if (!array_key_exists($key, $array)) {
            throw new Exception($message ?: "Array should have key '$key'");
        }
    }

    private function assertNull($value, $message = ''): void
    {
        if ($value !== null) {
            throw new Exception($message ?: 'Value should be null');
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

    private function assertFalse($value, $message = ''): void
    {
        if ($value !== false) {
            throw new Exception($message ?: 'Value should be false');
        }
    }

    private function assertEquals($expected, $actual, $message = ''): void
    {
        if ($expected !== $actual) {
            throw new Exception($message ?: "Expected '$expected', got '$actual'");
        }
    }

    private function assertStringContains($needle, $haystack, $message = ''): void
    {
        if (strpos($haystack, $needle) === false) {
            throw new Exception($message ?: "String '$haystack' should contain '$needle'");
        }
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    $test = new ValidatorTest();
    $test->runAllTests();
}