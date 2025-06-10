<?php
require_once 'src/utils/JwtHandler.php';

class RoleApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([
           
            "create_role" => ["admin"],
            "read_role" => ["admin"],
            "delete_role" => ["admin"],
            "update_role" => ["admin"]
            
        ]);
        
    }    
    
    public function create_role($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return[
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'create_role')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }

        $missing = $this->validateFields($data,['role_name', 'description']);
        if(!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $role = new Role(null, $data['role_name'], $data['description']);
        $success = $role->create();

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
       $user = $this->getAuthenticatedUser();
       if(!$user){
        return [
            "status" => "error",
            "message" => "Invalid authentication token"
        ];
       }
       if (!$this->checkRoles($user["role_name"], "read_role")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        
        $missing = $this->validateFields($data, ['role_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
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

    public function delete_role($data){
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user["role_name"],"delete_role")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['role_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }

        $role = new Role($data['role_id']);
        $success = $role->delete();
        if ($success) {
            return [
                "status" => "success",
                "message" => "Role deleted successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to delete role."
            ];
        }
    }
    
    
}