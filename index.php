<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Get the request URI and clean it
$request_uri = $_SERVER['REQUEST_URI'];
$request_path = parse_url($request_uri, PHP_URL_PATH);
$request_path = rtrim($request_path, '/');

// Handle playground routes
if (strpos($request_path, '/playground') === 0) {
    // Remove /playground from the path
    $playground_path = substr($request_path, 11); // Remove '/playground'
    
    // If it's just /playground or /playground/, load the playground index
    if (empty($playground_path) || $playground_path === '/') {
        if (file_exists('src/playground/index.php')) {
            include 'src/playground/index.php';
            exit();
        }
    }
    
    // Try to serve the specific playground file
    $playground_file = 'src/playground' . $playground_path;
    if (file_exists($playground_file)) {
        if (pathinfo($playground_file, PATHINFO_EXTENSION) === 'php') {
            include $playground_file;
            exit();
        } else {
            // Serve static file
            $mime_type = mime_content_type($playground_file);
            header("Content-Type: $mime_type");
            readfile($playground_file);
            exit();
        }
    }
    
    // Fallback to playground index
    if (file_exists('src/playground/index.php')) {
        include 'src/playground/index.php';
        exit();
    }
}

// Handle root path - show a simple status page
if ($request_path === '' || $request_path === '/') {
    include 'default.php';
    exit();
}

// Handle API routes through the router
require_once 'src/utils/imports.php';
require_once 'src/utils/router.php';

session_start();
$router = new Router();
$router->runScript();
