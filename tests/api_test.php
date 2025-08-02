<?php

$baseUrl = 'http://localhost:8080';

function sendRequest($method, $url, $data = null)
{
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);

    if ($data) {
        $payload = json_encode($data);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($payload)
        ]);
    }

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    return ['code' => $httpCode, 'body' => json_decode($response, true)];
}

echo "Running API tests...\n\n";

$createData = ['title' => 'Test Task', 'description' => 'This is a test task.'];
$createResponse = sendRequest('POST', "$baseUrl/tasks", $createData);
echo "1. POST /tasks: {$createResponse['code']}\n";
$taskId = $createResponse['body']['id'] ?? null;

$getAllResponse = sendRequest('GET', "$baseUrl/tasks");
echo "2. GET /tasks: {$getAllResponse['code']}\n";

if ($taskId) {
    $getByIdResponse = sendRequest('GET', "$baseUrl/tasks/{$taskId}");
    echo "3. GET /tasks/{$taskId}: {$getByIdResponse['code']}\n";
}

if ($taskId) {
    $updateData = ['title' => 'Updated Test Task', 'status' => 'completed'];
    $updateResponse = sendRequest('PUT', "$baseUrl/tasks/{$taskId}", $updateData);
    echo "4. PUT /tasks/{$taskId}: {$updateResponse['code']}\n";
}

$notFoundResponse = sendRequest('GET', "$baseUrl/tasks/9999");
echo "5. GET /tasks/9999 (Not Found): {$notFoundResponse['code']}\n";

