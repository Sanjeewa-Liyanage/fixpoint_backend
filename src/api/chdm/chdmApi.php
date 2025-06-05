<?php
class ChdmApi extends ApiResourceBase{

    public function __construct(){
       $this->setRoles([
        "create_chdm" => ["admin", "quality_checker"],
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
}