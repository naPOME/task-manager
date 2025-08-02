<?php

class Validator
{
    private array $errors = [];

    /**
     * Validate task data for creation or update
     * 
     * @param array $data Data to validate
     * @param bool $isUpdate Whether this is an update operation
     * @return array Validation errors (empty if valid)
     */
    public function validateTaskData(array $data, bool $isUpdate = false): array
    {
        $this->errors = [];

        // Title validation
        if (!$isUpdate || isset($data['title'])) {
            $this->validateTitle($data['title'] ?? null, !$isUpdate);
        }

        // Description validation
        if (isset($data['description'])) {
            $this->validateDescription($data['description']);
        }

        // Status validation - for creation, don't require if not provided (will default to pending)
        if (isset($data['status'])) {
            $this->validateStatus($data['status'], false);
        }

        return $this->errors;
    }

    /**
     * Validate title field
     */
    private function validateTitle(?string $title, bool $required = true): void
    {
        if ($required && (is_null($title) || trim($title) === '')) {
            $this->errors['title'] = 'Title is required';
            return;
        }

        // For updates, if title is provided but empty, it's invalid
        if (!$required && !is_null($title) && trim($title) === '') {
            $this->errors['title'] = 'Title cannot be empty';
            return;
        }

        if (!is_null($title) && strlen($title) > 255) {
            $this->errors['title'] = 'Title must not exceed 255 characters';
        }
    }

    /**
     * Validate description field
     */
    private function validateDescription(?string $description): void
    {
        if (!is_null($description) && strlen($description) > 1000) {
            $this->errors['description'] = 'Description must not exceed 1000 characters';
        }
    }

    /**
     * Validate status field
     */
    private function validateStatus(?string $status, bool $required = true): void
    {
        $validStatuses = ['pending', 'in-progress', 'completed'];

        if ($required && is_null($status)) {
            $this->errors['status'] = 'Status is required';
            return;
        }

        if (!is_null($status) && !in_array($status, $validStatuses, true)) {
            $this->errors['status'] = 'Invalid status. Must be one of: ' . implode(', ', $validStatuses);
        }
    }

    /**
     * Validate ID parameter
     */
    public function validateId($id): ?string
    {
        if (!is_numeric($id) || (int)$id <= 0) {
            return 'Invalid ID format. ID must be a positive integer';
        }
        return null;
    }

    /**
     * Validate status filter parameter
     */
    public function validateStatusFilter(?string $status): ?string
    {
        if (is_null($status) || trim($status) === '') {
            return null;
        }

        $validStatuses = ['pending', 'in-progress', 'completed'];
        if (!in_array($status, $validStatuses, true)) {
            return 'Invalid status filter. Must be one of: ' . implode(', ', $validStatuses);
        }

        return null;
    }

    /**
     * Check if data is valid JSON
     */
    public function isValidJson(string $json): bool
    {
        json_decode($json);
        return json_last_error() === JSON_ERROR_NONE;
    }

    /**
     * Sanitize input data
     */
    public function sanitizeData(array $data): array
    {
        $sanitized = [];
        
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $sanitized[$key] = trim($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }
}