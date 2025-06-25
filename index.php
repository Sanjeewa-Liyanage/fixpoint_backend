<?php
// --- CORS Headers ---
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");

// --- Handle Preflight Requests ---
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit();
}

require_once 'src/utils/imports.php';
require_once 'src/utils/router.php';

session_start();
$router = new Router();
$router->runScript();
