<?php

class Database
{
    private $connection;
    private $databasePath;

    public function __construct(string $databasePath = null)
    {
        $this->databasePath = $databasePath ?? __DIR__ . '/../../storage/database.sqlite';
        $this->initialize();
    }

    public function getConnection(): PDO
    {
        if ($this->connection === null) {
            $this->connect();
        }
        return $this->connection;
    }

    public function initialize(): void
    {
        $this->connect();
        $this->createTables();
    }

    private function connect(): void
    {
        try {
            $storageDir = dirname($this->databasePath);
            if (!is_dir($storageDir)) {
                mkdir($storageDir, 0755, true);
            }

            $dsn = 'sqlite:' . $this->databasePath;
            $this->connection = new PDO($dsn);
            
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            
            $this->connection->exec('PRAGMA foreign_keys = ON');
            
        } catch (PDOException $e) {
            throw new Exception("Database connection failed: " . $e->getMessage());
        }
    }

    private function createTables(): void
    {
        $this->runMigrations();
    }

    private function runMigrations(): void
    {
        $migrationsDir = __DIR__ . '/migrations';
        $migrationFiles = glob($migrationsDir . '/*.sql');
        
        if (empty($migrationFiles)) {
            return;
        }

        foreach ($migrationFiles as $migrationFile) {
            $sql = file_get_contents($migrationFile);
            if ($sql === false) {
                throw new Exception("Could not read migration file: " . $migrationFile);
            }

            try {
                $this->connection->exec($sql);
            } catch (PDOException $e) {
                throw new Exception("Migration failed for file " . basename($migrationFile) . ": " . $e->getMessage());
            }
        }
    }

    public function isConnected(): bool
    {
        try {
            $this->getConnection()->query('SELECT 1');
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Get database file path
     */
    public function getDatabasePath(): string
    {
        return $this->databasePath;
    }
}