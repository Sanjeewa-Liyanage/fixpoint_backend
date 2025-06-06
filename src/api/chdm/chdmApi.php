<?php
class ChdmApi extends ApiResourceBase{

    public function __construct(){
       $this->setRoles([
        "create_chdm" => ["admin", "quality_checker"],
        "view_passes_chdm" => ["admin", "quality_checker"],
        "view_failed_chdm" => ["admin", "quality_checker"],
        "update_status" => ["admin", "quality_checker"],
        "delete" => ["admin"]
       ]); 
    }

    public function create_chdm($data){
        if(!isset($data['serial_no']) || !isset($data['state']) || !isset($data['location']) || !isset($data['description']) || !isset($data['tested_date']) || !isset($data['branch_id'])){
            return [
                "status" => "error",
                "message" => "All fields are required"
            ];
        }
        
        $chdm = new Chdm(null, $data['serial_no'], $data['state'], $data['location'], $data['description'], $data['tested_date'], $data['branch_id']);
        $success = $chdm->create();
        
        if($success){
            return [
                "status" => "success",
                "message" => "CHDM created successfully"
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Failed to create CHDM"
            ];
        }
    }
public function view_passes_chdm(){
   $chdm = new Chdm();
   $result = $chdm->read();
    if($result){
         return [
              "status" => "success",
              "data" => $result
         ];
    } else {
         return [
              "status" => "error",
              "message" => "No passed CHDM records found"
         ];
    }
}

    public function view_failed_chdm(){
        $chdm = new Chdm();
        $result = $chdm->read_failed();
        if($result){
            return [
                "status"=> "success",
                "data"=> $result
            ];
        } else {
            return [
                "status"=> "error",
                "message"=>"No failed CHDM records found"
            ];
        }
    }

    public function update_status($data) {
    if (!isset($data['id']) || !isset($data['status'])) {
        return [
            "status" => "error",
            "message" => "ID and status are required"
        ];
    }
    $chdm = new Chdm($data['id']);
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

    public function delete($data) {
        if (!isset($data['id'])){
            return [
                "status"=> "error",
                "message"=> "ID is required"
            ];
        }
        $chdm = new Chdm($data['id']);
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


