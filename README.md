# Task Manager API

This is a simple RESTful API for managing tasks. The application is fully containerized using Docker for easy setup and local development.

## Features

- Create, retrieve, and update tasks.
- Basic validation and error handling.
- Containerized with Docker, PHP 8.1, and Apache.
- Uses a SQLite database for storage.

## Project Structure

```
/
├── docker/                # Docker configuration files
│   ├── apache/            # Apache virtual host config
│   ├── bin/               # Entrypoint script for the container
│   └── php/               # Custom PHP configuration (php.ini)
├── public/                # Web server public root
│   ├── .htaccess          # Apache URL rewriting rules
│   └── index.php          # Application entrypoint (front controller)
├── src/                   # Application source code
│   ├── controllers/       # Request handlers
│   ├── database/          # Database connection and migrations
│   ├── models/            # Business logic and data access
│   └── utils/             # Utility classes (Router, Validator)
├── storage/               # Storage for persistent files
│   └── database/          # SQLite database file is stored here
├── tests/                 # Test scripts
│   └── api_test.php       # Basic API test script
├── .gitignore             # Files and directories to be ignored by Git
├── API_DOCS.md            # Standalone API documentation
├── Dockerfile             # Defines the Docker image for the application
├── docker-compose.yml     # Defines the Docker services for local development
├── README.md              # This file
└── task-manager.postman_collection.json # Postman collection for API testing
```

## Installation and Setup

### Prerequisites

- [Docker](https://docs.docker.com/get-docker/)
- [Docker Compose](https://docs.docker.com/compose/install/) (usually included with Docker Desktop)

### Instructions

1.  **Clone the repository:**
    ```bash
    git clone <repository-url>
    cd task-manager
    ```

2.  **Build and start the containers:**
    ```bash
    docker compose up --build -d
    ```

3.  **Access the application:**
    The API will be running and accessible at `http://localhost:8080`.

## Docker Usage

-   **Start the application:**
    ```bash
    docker compose up -d
    ```

-   **Stop the application:**
    ```bash
    docker compose down
    ```

-   **View container logs:**
    ```bash
    docker compose logs -f app
    ```

-   **Rebuild the Docker image:**
    ```bash
    docker compose up --build -d
    ```

## API Documentation

This section provides examples of how to interact with the Task Manager API.

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
  "created_at": "2025-08-02T15:00:00+03:00"
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

## Testing

A basic PHP test script is included to exercise the API endpoints.

-   **Run tests on the host machine** (requires PHP and cURL):
    ```bash
    php tests/api_test.php
    ```

-   **Run tests inside the Docker container:**
    ```bash
    docker compose exec app php tests/api_test.php
    ```

## Troubleshooting

-   **500 Internal Server Error / "Readonly database"**

    This error indicates a file permission issue where the web server cannot write to the SQLite database file. The `entrypoint.sh` script is designed to fix this automatically by setting the correct ownership for the `storage` directory at runtime. If the problem persists, ensure that the user running Docker has the necessary permissions to manage files in the project directory.

-   **404 Not Found on API endpoints**

    This usually means that Apache's URL rewriting is not working correctly. The project includes a `.htaccess` file in the `public` directory to handle this. Ensure this file is present and that `mod_rewrite` is enabled in Apache (it is enabled by default in the `Dockerfile`).
