<?php
// Simple test endpoint to verify routing
if (isset($_GET['test'])) {
    header('Content-Type: application/json');
    echo json_encode([
        'status' => 'success',
        'message' => 'Routing is working!',
        'request_uri' => $_SERVER['REQUEST_URI'],
        'method' => $_SERVER['REQUEST_METHOD'],
        'timestamp' => date('Y-m-d H:i:s')
    ]);
    exit();
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Fix Point Backend</title>
</head>
<body>
    <h1>Fix Point Backend API</h1>
    <p>API is running. Current time: <?php echo date('Y-m-d H:i:s'); ?></p>
    <p>Request URI: <?php echo $_SERVER['REQUEST_URI']; ?></p>
    <p><a href="?test=1">Test API Response</a></p>
    <p><a href="/playground/">Visit Playground</a></p>
</body>
</html>
