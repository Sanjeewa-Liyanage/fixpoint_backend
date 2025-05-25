<?php
class RoleApi extends ApiResourceBase{
    public function __construct(){
        $this-> setRoles([
           
            "create_role" => ["admin"],
            
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
    
}