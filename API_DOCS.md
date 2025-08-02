# Task Manager API Documentation

This document provides examples of how to interact with the Task Manager API.

## Endpoints

### 1. Create a Task

- **Method**: `POST`
- **Endpoint**: `/tasks`
- **Description**: Creates a new task.

**cURL Example:**
```bash
curl -X POST http://localhost:8080/tasks -H "Content-Type: application/json" -d '{"title": "My New Task", "description": "A description for the task."}'
```

**Sample Response:**
```json
{
  "id": 1,
  "title": "My New Task",
  "description": "A description for the task.",
  "status": "pending",
  "created_at": "2025-08-02T14:50:00+03:00"
}
```

### 2. Get All Tasks

- **Method**: `GET`
- **Endpoint**: `/tasks`
- **Description**: Retrieves all tasks.

**cURL Example:**
```bash
curl http://localhost:8080/tasks
```

### 3. Get Task by ID

- **Method**: `GET`
- **Endpoint**: `/tasks/{id}`
- **Description**: Retrieves a single task by its ID.

**cURL Example:**
```bash
curl http://localhost:8080/tasks/1
```

### 4. Update a Task

- **Method**: `PUT`
- **Endpoint**: `/tasks/{id}`
- **Description**: Updates an existing task.

**cURL Example:**
```bash
curl -X PUT http://localhost:8080/tasks/1 -H "Content-Type: application/json" -d '{"title": "Updated Task Title", "status": "completed"}'
```
