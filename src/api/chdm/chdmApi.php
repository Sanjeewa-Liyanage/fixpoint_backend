<?php
class ChdmApi extends ApiResourceBase{

    public function __construct(){
       $this->setRoles([
        "create_chdm" => ["admin", "quality_checker"],
        "view_passes_chdm" => ["admin", "quality_checker"],
        "view_failed_chdm" => ["admin", "quality_checker"],
        "update_status" => ["admin", "quality_checker"],
        "update_location" => ["admin", "quality_checker"],
        "update_branch_id" => ["admin", "quality_checker"],
        "delete" => ["admin"]
       ]); 
    }

    public function create_chdm($data){
       $user = $this->getAuthenticatedUser();
       if (!$user) {
            return [
                "message" => "Invalid or expired token. Please log in again.",
                "status" => "error"
            ];
        }

        if(!$this->checkRoles($user['role_name'], 'create_chdm')){
            return [
                "message" => "Unauthorized: Admin or Quality Checker access required",
                "status" => "error",
            ];
        }

       $missing = $this->validateFields($data,["serial_no", "state", "location", "description", "tested_date"]);
        
        if (!empty($missing)) {
            return [
                "message" => "Missing required fields: " . implode(", ", $missing),
                "status" => "error"
            ];
        }
        
        $chdm = new Chdm(null, $data['serial_no'], $data['state'], $data['location'], $data['description'], $data['tested_date']);
        $success = $chdm->create();
        
        if($success){
            return [
                "status" => "success",
                "message" => "CHDM created successfully.$success",
            ];
        } else {
            return [
                "message" => "Failed to create CHDM",
                "status" => "error"
            ];
        }
    }
public function view_passes_chdm($data){
   $user = $this->getAuthenticatedUser();
   if (!$user) {
        return [
            "message" => "Invalid or expired token. Please log in again.",
            "status" => "error"
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'view_passes_chdm')){
        return [
            "message" => "Unauthorized: Admin or Quality Checker access required",
            "status" => "error"
        ];
    }
    $missing = $this->validateFields($data,["state"]);
        
        if (!empty($missing)) {
            return [
                "message" => "Missing required fields: " . implode(", ", $missing),
                "status" => "error"
            ];
        }
   $chdm = new Chdm();
   $result = $chdm->read();
   if($result){
    return [
        'status'=> 'success',
        'message'=> 'CHDM records retrieved successfully',
        'data'=> $result,
    ];
   }else{
    return [
        'message'=> 'No CHDM records found ',
        'status'=> 'error'
        ];
   }

   
    } 
    public function view_failed_chdm($data){
       $user = $this->getAuthenticatedUser();
       if (!$user) {
            return [
                "message" => "Invalid or expired token. Please log in again.",
                "status" => "error"
            ];
        }
        if(!$this->checkRoles($user["role_name"], "view_failed_chdm")){
            return [
                "message" => "Unauthorized: Admin or Quality Checker access required",
                "status" => "error"
            ];
        }
         $missing = $this->validateFields($data,["state"]);
        
        if (!empty($missing)) {
            return [
                "message" => "Missing required fields: " . implode(", ", $missing),
                "status" => "error"
            ];
        }
        $chdm = new Chdm();
        $result = $chdm->read_failed();
        if($result){
            return [
                "status"=> "success",
                "message"=> "Failed CHDM records retrieved successfully",
                "data"=> $result
            ];
        } else {
            return [
                "message"=>"No failed CHDM records found",
                "status"=> "error"
            ];
        }
    }

    public function update_status($data) {
       $user = $this->getAuthenticatedUser();
       if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'update_status')){
            return [
                "message"=> "Unauthorized: Admin or Quality Checker access required",
                "status"=> "error"
            ];
        }
        $missing = $this->validateFields($data, ["serial_no", "status",]);

        if (!empty($missing)) {
            return [
                "message"=> "Missing required fields: " . implode(", ", $missing),
                "status"=> "error"
            ];
        }
    
    $chdm = new Chdm($data['serial_no'], null,null, $data['status']);
    $success = $chdm->update_status($data['status']);
    if ($success) {
        return [
            "status" => "success",
            "message" => "CHDM status updated successfully"
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Failed to update CHDM status"
        ];
    }
}

public function update_location($data) {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return [
            "message"=> "Invalid or expired token. Please log in again.",
            "status"=> "error"
        ];
    }
   if(!$this->checkRoles($user['role_name'], 'update_location')){
        return [
            "message"=> "Unauthorized: Admin or Quality Checker access required",
            "status"=> "error"
        ];
    }
    $missing = $this->validateFields($data, ["serial_no", "location"]);

    if (!empty($missing)) {
        return [
            "message"=> "Missing required fields: " . implode(", ", $missing),
            "status"=> "error"
        ];
    }

    $chdm = new Chdm($data['serial_no'], null, null, $data['location']);
    $success = $chdm->update_location($data['location']);
    if ($success) {
        return [
            "status"=> "success",
            "message"=> "CHDM location updated successfully"
        ];
    } else {
        return [
            "message"=> "Failed to update CHDM location",
            "status"=> "error"
        ];
    }
}

public function update_branch_id($data) {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return [
            "message"=> "Invalid or expired token. Please log in again.",
            "status"=> "error"
        ];
    }
    if(!$this->checkRoles($user["role_name"], "update_branch_id")){
        return [
            "message"=> "Unauthorized: Admin or Quality Checker access required",
            "status"=> "error"
        ];
    }
    $missing = $this->validateFields($data, ["serial_no", "branch_id"]);

    if (!empty($missing)) {
        return [
            "message"=> "Missing required fields: " . implode(", ", $missing),
            "status"=> "error"
        ];
    }
    $chdm = new Chdm($data["serial_no"], null,$data["branch_id"]);
    $success = $chdm->update_branch_id($data["branch_id"]);
    if ($success) {
        return [
            "status"=> "success",
            "message"=> "CHDM branch ID updated successfully"
        ];
    } else {
        return [
            "message"=> "Failed to update CHDM branch ID",
            "status"=> "error"
        ]; 
    }
}


    public function delete($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }

        if (!$this->checkRoles($user["role_name"], "delete")){
            return [
                "message"=> "Unauthorized: Admin access required",
                "status"=> "error"
            ];
        }

        $missing = $this->validateFields($data, ["serial_no"]);

        if (!empty($missing)) {
            return [
                "message"=> "Missing required fields: " . implode(", ", $missing),
                "status"=> "error"
            ];
        }
        $chdm = new Chdm($data['serial_no']);
        $success = $chdm->delete();
        if ($success) {
            return [
                "status"=> "success",
                "message"=> "CHDM deleted successfully"
            ];
        } else {
            return [
                "status"=> "error",
                "message"=> "Failed to delete CHDM"
            ];
        }
    }

}


