<?php
class RoleApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([
           
            "create_role" => ["admin"],
            "read_role" => ["admin", "user"],
            
        ]);
    }
    public function create_role ($data){
        if(!isset($data['role_name'])||!isset($data['description'])){
            return [
                "status" => "error",
                "message" => "role_name and description are required"
            ];
        }
        $role = new Role(null, $data['role_name'], $data['description']);
        $success = $role->create();
        if($success){
            return [
                "status" => "success",
                "message" => "Role created successfully"
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to create role"
            ];
        }
    }
    public function read_role($data){
        if(!isset($data['role_id'])){
            return [
                "status" => "error",
                "message" => "role_id is required"
            ];
        }
        $role = new Role($data['role_id']);
        $success = $role->read();
        if($success){
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