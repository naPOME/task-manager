<?php

require_once __DIR__ . '/../database/Database.php';

class Task
{
    private $database;
    private $connection;
    
    // Valid status values
    private const VALID_STATUSES = ['pending', 'in-progress', 'completed'];

    public function __construct(Database $database)
    {
        $this->database = $database;
        $this->connection = $database->getConnection();
    }

    /**
     * Create a new task
     * 
     * @param array $data Task data (title, description, status)
     * @return array Created task with ID and timestamps
     * @throws Exception If validation fails or database error occurs
     */
    public function create(array $data): array
    {
        // Validate required fields
        if (empty($data['title'])) {
            throw new InvalidArgumentException('Title is required');
        }

        // Set default status if not provided
        $status = $data['status'] ?? 'pending';
        
        // Validate status
        if (!$this->validateStatus($status)) {
            throw new InvalidArgumentException('Invalid status. Must be one of: ' . implode(', ', self::VALID_STATUSES));
        }

        // Validate title length
        if (strlen($data['title']) > 255) {
            throw new InvalidArgumentException('Title must not exceed 255 characters');
        }

        // Validate description length if provided
        if (isset($data['description']) && strlen($data['description']) > 1000) {
            throw new InvalidArgumentException('Description must not exceed 1000 characters');
        }

        try {
            $sql = "INSERT INTO tasks (title, description, status) VALUES (:title, :description, :status)";
            $stmt = $this->connection->prepare($sql);
            
            $stmt->bindParam(':title', $data['title']);
            $description = $data['description'] ?? null;
            $stmt->bindParam(':description', $description);
            $stmt->bindParam(':status', $status);
            
            $stmt->execute();
            
            $taskId = $this->connection->lastInsertId();
            
            // Return the created task
            return $this->findById((int)$taskId);
            
        } catch (PDOException $e) {
            throw new Exception('Failed to create task: ' . $e->getMessage());
        }
    }

    /**
     * Find all tasks with optional filtering
     * 
     * @param array $filters Optional filters (status)
     * @return array Array of tasks
     * @throws Exception If database error occurs
     */
    public function findAll(array $filters = []): array
    {
        try {
            $sql = "SELECT * FROM tasks";
            $params = [];
            
            // Add status filter if provided
            if (!empty($filters['status'])) {
                if (!$this->validateStatus($filters['status'])) {
                    throw new InvalidArgumentException('Invalid status filter. Must be one of: ' . implode(', ', self::VALID_STATUSES));
                }
                $sql .= " WHERE status = :status";
                $params[':status'] = $filters['status'];
            }
            
            $sql .= " ORDER BY created_at DESC";
            
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            $tasks = $stmt->fetchAll();
            
            // Format all tasks
            return array_map([$this, 'formatTask'], $tasks);
            
        } catch (PDOException $e) {
            throw new Exception('Failed to retrieve tasks: ' . $e->getMessage());
        }
    }

    /**
     * Find a task by ID
     * 
     * @param int $id Task ID
     * @return array|null Task data or null if not found
     * @throws Exception If database error occurs
     */
    public function findById(int $id): ?array
    {
        try {
            $sql = "SELECT * FROM tasks WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            $task = $stmt->fetch();
            
            if ($task === false) {
                return null;
            }
            
            return $this->formatTask($task);
            
        } catch (PDOException $e) {
            throw new Exception('Failed to retrieve task: ' . $e->getMessage());
        }
    }

    /**
     * Update an existing task
     * 
     * @param int $id Task ID
     * @param array $data Updated task data
     * @return array|null Updated task data or null if not found
     * @throws Exception If validation fails or database error occurs
     */
    public function update(int $id, array $data): ?array
    {
        // Check if task exists
        $existingTask = $this->findById($id);
        if ($existingTask === null) {
            return null;
        }

        // Validate data if provided
        if (isset($data['title'])) {
            if (empty($data['title'])) {
                throw new InvalidArgumentException('Title cannot be empty');
            }
            if (strlen($data['title']) > 255) {
                throw new InvalidArgumentException('Title must not exceed 255 characters');
            }
        }

        if (isset($data['description']) && strlen($data['description']) > 1000) {
            throw new InvalidArgumentException('Description must not exceed 1000 characters');
        }

        if (isset($data['status']) && !$this->validateStatus($data['status'])) {
            throw new InvalidArgumentException('Invalid status. Must be one of: ' . implode(', ', self::VALID_STATUSES));
        }

        try {
            // Build dynamic update query
            $updateFields = [];
            $params = [':id' => $id];
            
            if (isset($data['title'])) {
                $updateFields[] = 'title = :title';
                $params[':title'] = $data['title'];
            }
            
            if (isset($data['description'])) {
                $updateFields[] = 'description = :description';
                $params[':description'] = $data['description'];
            }
            
            if (isset($data['status'])) {
                $updateFields[] = 'status = :status';
                $params[':status'] = $data['status'];
            }

            // Always update the updated_at timestamp
            $updateFields[] = 'updated_at = :updated_at';
            $params[':updated_at'] = date('Y-m-d H:i:s');
            
            // If no fields to update, return existing task
            if (empty($updateFields)) {
                return $existingTask;
            }
            
            $sql = "UPDATE tasks SET " . implode(', ', $updateFields) . " WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->execute($params);
            
            // Return updated task
            return $this->findById($id);
            
        } catch (PDOException $e) {
            throw new Exception('Failed to update task: ' . $e->getMessage());
        }
    }

    /**
     * Delete a task by ID
     * 
     * @param int $id Task ID
     * @return bool True if deleted, false if not found
     * @throws Exception If database error occurs
     */
    public function delete(int $id): bool
    {
        try {
            $sql = "DELETE FROM tasks WHERE id = :id";
            $stmt = $this->connection->prepare($sql);
            $stmt->bindParam(':id', $id, PDO::PARAM_INT);
            $stmt->execute();
            
            return $stmt->rowCount() > 0;
            
        } catch (PDOException $e) {
            throw new Exception('Failed to delete task: ' . $e->getMessage());
        }
    }

    /**
     * Validate task status
     * 
     * @param string $status Status to validate
     * @return bool True if valid, false otherwise
     */
    private function validateStatus(string $status): bool
    {
        return in_array($status, self::VALID_STATUSES, true);
    }

    /**
     * Format task data for API response
     * 
     * @param array $row Raw database row
     * @return array Formatted task data
     */
    private function formatTask(array $row): array
    {
        return [
            'id' => (int)$row['id'],
            'title' => $row['title'],
            'description' => $row['description'],
            'status' => $row['status'],
            'created_at' => $row['created_at'],
            'updated_at' => $row['updated_at']
        ];
    }

    /**
     * Get valid status values
     * 
     * @return array Array of valid status values
     */
    public function getValidStatuses(): array
    {
        return self::VALID_STATUSES;
    }
}