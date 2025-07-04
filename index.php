<?php

header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    header("HTTP/1.1 200 OK");
    exit();
}

// Handle root path - show a simple status page
$request_uri = $_SERVER['REQUEST_URI'];
if ($request_uri === '/' || $request_uri === '') {
    include 'default.php';
    exit();
}

require_once 'src/utils/imports.php';
require_once 'src/utils/router.php';

session_start();
$router = new Router();
$router->runScript();
