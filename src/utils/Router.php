<?php

class Router {
    private array $routes = [];

    /**
     * Add a route to the router
     */
    public function addRoute(string $method, string $pattern, callable $handler): void {
        $this->routes[] = [
            'method' => strtoupper($method),
            'pattern' => $pattern,
            'handler' => $handler
        ];
    }

    /**
     * Route the incoming request
     */
    public function route(string $method, string $uri): void {
        $method = strtoupper($method);
        $matchedRoute = $this->matchRoute($method, $uri);

        if ($matchedRoute === null) {
            // Check if the route exists for other methods (405 vs 404)
            if ($this->routeExistsForOtherMethods($method, $uri)) {
                $this->sendMethodNotAllowedResponse($method, $uri);
            } else {
                $this->sendNotFoundResponse();
            }
            return;
        }

        // Extract parameters and call handler
        $handler = $matchedRoute['handler'];
        $params = $matchedRoute['params'];

        try {
            call_user_func_array($handler, $params);
        } catch (Exception $e) {
            // Log the error for debugging
            error_log('Router error: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
            $this->sendErrorResponse(500, 'Internal server error');
        }
    }

    /**
     * Parse URI and extract path components
     */
    private function parseUri(string $uri): array {
        // Remove query string
        $uri = parse_url($uri, PHP_URL_PATH);
        
        // Remove leading and trailing slashes, then split
        $uri = trim($uri, '/');
        
        if (empty($uri)) {
            return [];
        }
        
        return explode('/', $uri);
    }

    /**
     * Match a route against the current request
     */
    private function matchRoute(string $method, string $uri): ?array {
        $uriParts = $this->parseUri($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            $patternParts = $this->parseUri($route['pattern']);
            $params = [];

            // Check if the number of parts match
            if (count($uriParts) !== count($patternParts)) {
                continue;
            }

            $match = true;
            for ($i = 0; $i < count($patternParts); $i++) {
                $patternPart = $patternParts[$i];
                $uriPart = $uriParts[$i];

                // Check for parameter placeholder (e.g., {id})
                if (preg_match('/^{(.+)}$/', $patternPart, $matches)) {
                    $paramName = $matches[1];
                    
                    // Validate that parameter is numeric for ID parameters
                    if ($paramName === 'id' && !is_numeric($uriPart)) {
                        $match = false;
                        break;
                    }
                    
                    $params[] = $paramName === 'id' ? (int)$uriPart : $uriPart;
                } else if ($patternPart !== $uriPart) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return [
                    'handler' => $route['handler'],
                    'params' => $params
                ];
            }
        }

        return null;
    }

    /**
     * Send 404 Not Found response
     */
    private function sendNotFoundResponse(): void {
        if (!headers_sent()) {
            http_response_code(404);
            header('Content-Type: application/json');
        }
        echo json_encode([
            'error' => true,
            'message' => 'Route not found',
            'code' => 'ROUTE_NOT_FOUND'
        ]);
    }

    /**
     * Check if route exists for other HTTP methods
     */
    private function routeExistsForOtherMethods(string $currentMethod, string $uri): bool {
        $uriParts = $this->parseUri($uri);

        foreach ($this->routes as $route) {
            if ($route['method'] === $currentMethod) {
                continue;
            }

            $patternParts = $this->parseUri($route['pattern']);

            // Check if the number of parts match
            if (count($uriParts) !== count($patternParts)) {
                continue;
            }

            $match = true;
            for ($i = 0; $i < count($patternParts); $i++) {
                $patternPart = $patternParts[$i];
                $uriPart = $uriParts[$i];

                // Check for parameter placeholder (e.g., {id})
                if (preg_match('/^{(.+)}$/', $patternPart, $matches)) {
                    $paramName = $matches[1];
                    
                    // Validate that parameter is numeric for ID parameters
                    if ($paramName === 'id' && !is_numeric($uriPart)) {
                        $match = false;
                        break;
                    }
                } else if ($patternPart !== $uriPart) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get allowed methods for a specific URI
     */
    private function getAllowedMethods(string $uri): array {
        $uriParts = $this->parseUri($uri);
        $allowedMethods = [];

        foreach ($this->routes as $route) {
            $patternParts = $this->parseUri($route['pattern']);

            // Check if the number of parts match
            if (count($uriParts) !== count($patternParts)) {
                continue;
            }

            $match = true;
            for ($i = 0; $i < count($patternParts); $i++) {
                $patternPart = $patternParts[$i];
                $uriPart = $uriParts[$i];

                // Check for parameter placeholder (e.g., {id})
                if (preg_match('/^{(.+)}$/', $patternPart, $matches)) {
                    $paramName = $matches[1];
                    
                    // Validate that parameter is numeric for ID parameters
                    if ($paramName === 'id' && !is_numeric($uriPart)) {
                        $match = false;
                        break;
                    }
                } else if ($patternPart !== $uriPart) {
                    $match = false;
                    break;
                }
            }

            if ($match) {
                $allowedMethods[] = $route['method'];
            }
        }

        return array_unique($allowedMethods);
    }

    /**
     * Send 405 Method Not Allowed response
     */
    private function sendMethodNotAllowedResponse(string $method, string $uri): void {
        $allowedMethods = $this->getAllowedMethods($uri);
        
        if (!headers_sent()) {
            http_response_code(405);
            header('Content-Type: application/json');
            header('Allow: ' . implode(', ', $allowedMethods));
        }
        
        echo json_encode([
            'error' => true,
            'message' => "Method $method not allowed for this endpoint",
            'code' => 'METHOD_NOT_ALLOWED',
            'allowed_methods' => $allowedMethods
        ]);
    }

    /**
     * Send error response
     */
    private function sendErrorResponse(int $statusCode, string $message): void {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }
        
        $errorCode = match($statusCode) {
            400 => 'BAD_REQUEST',
            404 => 'NOT_FOUND',
            405 => 'METHOD_NOT_ALLOWED',
            500 => 'INTERNAL_SERVER_ERROR',
            default => 'SERVER_ERROR'
        };
        
        echo json_encode([
            'error' => true,
            'message' => $message,
            'code' => $errorCode
        ]);
    }
}