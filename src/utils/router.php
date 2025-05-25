<?php

class Router
{
    private $resource = null;
    private $action = null;
    private $group = null;
    private $method = null;
    private $authRole = null;
    private $data = [];

    public function __construct()
    {
        $this->parseURL();
        // $this->authRole = $this->parseSession();
        $this->authRole = 'admin';
        $this->method = $_SERVER['REQUEST_METHOD'];
        if ($this->method === 'GET') {
            $this->data = $_GET;
        } else {
            $this->data = json_decode(file_get_contents('php://input'), true);
        }
    }

    private function parseURL()
    {
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
        $url = explode('/', $path);
        $this->group = isset($url[2]) ? $url[2] : null;
        $this->resource = isset($url[3]) ? $url[3] : null;
        $this->action = isset($url[4]) ? $url[4] : null;
    }

    private function parseSession()
    {
        if (isset($_SESSION['role'])) {
            return $_SESSION['role'];
        } else {
            return null;
        }
    }

    public function runScript()
    {
        $path = $this->getScriptPath();
        header('Content-Type: application/json');
        if (file_exists($path)) {
            $this->runExistingScript($path);
        } else {
            $this->sendNotFoundResponse($path);
        }

        $this->sanitizeData($this->data);
    }

    private function getScriptPath()
    {
        return 'src/api/' . $this->group . '/' . $this->resource . 'Api.php';
    }

    private function runExistingScript($path)
    {
//        require_once($path);
        $className = ucfirst($this->resource).'Api';
        $class = new $className();
        if (method_exists($class, 'checkRoles')) {
            $this->handleRoleCheck($class);
        } else {
            $this->sendUnauthorizedResponse();
        }
    }

    private function handleRoleCheck($class)
    {
        try{
            if (!$class->checkRoles($this->authRole, $this->action)) {
                $this->sendForbiddenResponse();
            } else {
                $response = $class->{$this->action}($this->data);
                echo json_encode($response);
            }
        } catch (Exception $e) {
            echo json_encode([
                'message' => $e->getMessage(),
                'status' => 'error'
            ]);
        }
    }

    private function sendForbiddenResponse()
    {
        http_response_code(403);
        echo json_encode([
            'message' => 'Forbidden',
            'role' => $this->authRole,
            'action' => $this->action
        ]);
    }

    private function sendUnauthorizedResponse()
    {
        http_response_code(401);
        echo json_encode([
            'message' => 'Unauthorized',
            'role' => $this->authRole
        ]);
    }

    private function sendNotFoundResponse($msg)
    {
        http_response_code(404);
        echo json_encode([
            'message' => 'Resource not found',
            'data' => $msg
        ]);
    }

    private function sanitizeData($data)
        {
            foreach ($data as $key => $value) {
                if (is_array($value)) {
                    $data[$key] =$value;
                } else {
                    $data[$key] = htmlspecialchars(strip_tags($value));
                }
            }
            $this->data=  $data;
        }
}
