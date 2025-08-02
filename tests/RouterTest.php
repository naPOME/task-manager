<?php

require_once __DIR__ . '/../src/utils/Router.php';

class RouterTest
{
    private Router $router;

    public function __construct()
    {
        $this->router = new Router();
        $this->router->addRoute('GET', '/tasks', function() { echo 'tasks'; });
        $this->router->addRoute('GET', '/tasks/{id}', function($id) { echo "task:$id"; });
    }

    public function runTests(): void
    {
        echo "Running Router Tests...\n";
        
        $this->testValidRoutes();
        $this->testNotFound();
        $this->testMethodNotAllowed();
        
        echo "All router tests passed!\n";
    }

    private function testValidRoutes(): void
    {
        $output = $this->capture('GET', '/tasks');
        assert($output === 'tasks');
        
        $output = $this->capture('GET', '/tasks/123');
        assert($output === 'task:123');
        echo " Valid routes test passed\n";
    }

    private function testNotFound(): void
    {
        $output = $this->capture('GET', '/invalid');
        $response = json_decode($output, true);
        assert($response['code'] === 'ROUTE_NOT_FOUND');
        echo "Not found test passed\n";
    }

    private function testMethodNotAllowed(): void
    {
        $output = $this->capture('POST', '/tasks/123');
        $response = json_decode($output, true);
        assert($response['code'] === 'METHOD_NOT_ALLOWED');
        echo "Method not allowed test passed\n";
    }

    private function capture(string $method, string $uri): string
    {
        ob_start();
        $this->router->route($method, $uri);
        return ob_get_clean();
    }
}

if (basename(__FILE__) === basename($_SERVER['SCRIPT_NAME'])) {
    (new RouterTest())->runTests();
}