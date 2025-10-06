<?php

// Bootstrap simple pour orchestrateur
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Autoloader
spl_autoload_register(function ($class) {
    $sharedFile = __DIR__ . '/shared/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($sharedFile)) {
        require_once $sharedFile;
        return;
    }
    
    $localFile = __DIR__ . '/' . str_replace('\\', '/', $class) . '.php';
    if (file_exists($localFile)) {
        require_once $localFile;
        return;
    }
});

// Classes simulées
class Request {
    private $data;
    public function __construct($data = []) { $this->data = $data; }
    public function input($key, $default = null) { return $this->data[$key] ?? $default; }
}

class JsonResponse {
    private $data;
    private $status;
    public function __construct($data, $status = 200) { $this->data = $data; $this->status = $status; }
    public function getContent() { http_response_code($this->status); return json_encode($this->data); }
}

function response() {
    return new class { public function json($data, $status = 200) { return new JsonResponse($data, $status); } };
}

// Client HTTP simple
class Http {
    public static function post($url, $data) {
        $options = [
            'http' => [
                'header' => "Content-type: application/json\r\n",
                'method' => 'POST',
                'content' => json_encode($data)
            ]
        ];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        return new class($result, $http_response_header ?? []) {
            private $result;
            private $headers;
            public function __construct($result, $headers) { $this->result = $result; $this->headers = $headers; }
            public function successful() { return $this->result !== false && strpos($this->headers[0] ?? '', '20') !== false; }
            public function json() { return json_decode($this->result, true); }
        };
    }
    
    public static function delete($url) {
        $options = ['http' => ['method' => 'DELETE']];
        $context = stream_context_create($options);
        $result = @file_get_contents($url, false, $context);
        
        return new class($result, $http_response_header ?? []) {
            private $result;
            private $headers;
            public function __construct($result, $headers) { $this->result = $result; $this->headers = $headers; }
            public function successful() { return $this->result !== false && strpos($this->headers[0] ?? '', '20') !== false; }
            public function json() { return json_decode($this->result, true); }
        };
    }
}

function app($class) {
    if ($class === \Application\Services\UserAccountOrchestrator::class) {
        return new \Application\Services\UserAccountOrchestrator();
    }
    throw new Exception("Service not found: $class");
}