<?php

require_once __DIR__ . '/../src/database/Database.php';
require_once __DIR__ . '/../src/models/Task.php';

class CrudTest
{
    private Task $task;

    public function __construct()
    {
        $db = new Database(':memory:');
        $db->initialize();
        $this->task = new Task($db);
    }

    public function runTests(): void
    {
        echo "Running CRUD Tests...\n";
        
        $this->testCreate();
        $this->testRead();
        $this->testUpdate();
        $this->testDelete();
        
        echo "All CRUD tests passed!\n";
    }

    private function testCreate(): void
    {
        $task = $this->task->create(['title' => 'Test Task']);
        assert($task['title'] === 'Test Task');
        assert($task['status'] === 'pending');
        echo "Create test passed\n";
    }

    private function testRead(): void
    {
        $created = $this->task->create(['title' => 'Read Test']);
        $found = $this->task->findById($created['id']);
        assert($found['title'] === 'Read Test');
        echo "Read test passed\n";
    }

    private function testUpdate(): void
    {
        $created = $this->task->create(['title' => 'Update Test']);
        $updated = $this->task->update($created['id'], ['title' => 'Updated']);
        assert($updated['title'] === 'Updated');
        echo " Update test passed\n";
    }

    private function testDelete(): void
    {
        $created = $this->task->create(['title' => 'Delete Test']);
        $deleted = $this->task->delete($created['id']);
        assert($deleted === true);
        echo " Delete test passed\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    (new CrudTest())->runTests();
}