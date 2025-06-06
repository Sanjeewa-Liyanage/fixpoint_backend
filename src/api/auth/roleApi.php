<?php
require_once 'src/utils/JwtHandler.php';

class RoleApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([
           
            "create_role" => ["admin"],
            "read_role" => ["admin", "user"],
            
        ]);
    }    
    
    public function create_role($data) {
    // Step 1: Decode JWT token from the Authorization header
    $tokenData = JwtHandler::getTokenFromHeader();

    // Step 2: Check if token is valid
    if (!$tokenData['valid']) {
        return [
            "status" => "error",
            "message" => "Invalid or expired token. Please log in again."
        ];
    }

    // Step 3: Extract user details from decoded token
    $user = $tokenData['data'];

    // Step 4: Check if the logged-in user is an admin
    // Example checking role name
if (!isset($user['role_name']) || $user['role_name'] !== 'admin') {
    return [
        "status" => "error",
        "message" => "Unauthorized: Admin access required"
    ];
}


    // Step 5: Validate required inputs
    if (empty($data['role_name']) || empty($data['description'])) {
        return [
            "status" => "error",
            "message" => "Both 'role_name' and 'description' are required."
        ];
    }

    // Step 6: Create the new Role object and save it
    $role = new Role(null, $data['role_name'], $data['description']);
    $success = $role->create();

    // Step 7: Return the result
    if ($success) {
        return [
            "status" => "success",
            "message" => "Role created successfully."
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Database error: Failed to create role."
        ];
    }
}

    
    public function read_role($data) {
        // Verify JWT token
        $tokenData = JwtHandler::getTokenFromHeader();
        
        if (!$tokenData['valid']) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        
        if(!isset($data['role_id'])) {
            return [
                "status" => "error",
                "message" => "role_id is required"
            ];
        }
        
        $role = new Role($data['role_id']);
        $success = $role->read();
        
        if($success) {
            return [
                "status" => "success",
                "data" => $role
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to read role"
            ];
        }
    }
    
}