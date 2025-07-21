<?php
require_once 'src/utils/JwtHandler.php';

class RoleApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([
           
            "create_role" => ["admin"],
            "read_role" => ["admin"],
            "delete_role" => ["admin"],
            "update_role" => ["admin"],
            "get_by_id"=> ["admin"],
            "getall"=> ["admin"],
            "assign_role"=> ["admin"],
            "get_all_roleIds" => ["admin"]
            
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
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);

        if (!$this->checkRoles($roleName, 'create_role')) {
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
       $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);

       if (!$this->checkRoles($roleName, "read_role")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        
        $role = new Role();
        $result = $role->read();
        
        if($result) {
            return [
                "status" => "success",
                "message" => "Roles retrieved successfully",
                "data" => $result
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No roles found"
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
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);

        if (!$this->checkRoles($roleName, "delete_role")) {
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
    public function get_by_id($data){
        $user = $this -> getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);

        if (!$this->checkRoles($roleName, "get_by_id")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['user_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $role = new Role();
        $success = $role->get_by_user_id($data["user_id"]);
        if($success){
            return [
                "status" => "success",
                "data" => $role
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to retrieve role by user ID."
            ];
        }
    }
    
    public function getall($data){
        $user = $this -> getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
        if (!$this->checkRoles($roleName, "getall")) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required . $roleName"
            ];
        }
        $missing = $this->validateFields($data, ['role_name', 'limit', 'page']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $result = Role::get_all($data['role_name'], $data['limit'], $data['page']);
        if($result) {
            return [
                "status" => "success",
                "data" => $result['data'],
                "page" => $result['page'],
                "limit" => $result['limit']
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No roles found."
            ];
        }

    }
    public function update_role($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
        if (!$this->checkRoles($roleName, 'update_role')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['role_id', 'role_name', 'description']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $role = new Role($data['role_id'], $data['role_name'], $data['description']);
        $success = $role->update();
        if ($success) {
            return [
                "status" => "success",
                "message" => "Role updated successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to update role."
            ];
        }
    }
    public function assign_role($data){
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
        if (!$this->checkRoles($roleName, 'update_role')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['user_id', 'role_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        $role = new Role($data['role_id']);
        $row = $role -> read();
        if (!$row) {
            return [
                "status" => "error",
                "message" => "Role not found"
            ];
        }
        $user = new User($data['user_id']);
        $userExists = $user->read();
        if (!$userExists) {
            return [
                "status" => "error",
                "message" => "User not found"
            ];
        }
        $result = $user->assign_role($data['role_id']);
        if ($result) {
            return [
                "status" => "success",
                "message" => "Role assigned successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to assign role."
            ];
        }


    }
    public function get_all_roleIds() {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
        if (!$this->checkRoles($roleName, 'get_all_roleIds')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $result = Role::get_all_roleIds();
        if ($result) {
            return [
                "status" => "success",
                "data" => $result
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No role IDs found."
            ];
        }
    }
}