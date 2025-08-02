<?php

require_once __DIR__ . '/../src/database/Database.php';

class DatabaseTest
{
    public function runTests(): void
    {
        echo "Running Database Tests...\n";
        
        $db = new Database(':memory:');
        $db->initialize();
        
        $connection = $db->getConnection();
        assert($connection instanceof PDO);
        
        $stmt = $connection->query("SELECT name FROM sqlite_master WHERE type='table' AND name='tasks'");
        assert($stmt->fetch() !== false);
        
        echo "✓ Database tests passed\n";
        echo "✅ All database tests passed!\n";
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    (new DatabaseTest())->runTests();
}