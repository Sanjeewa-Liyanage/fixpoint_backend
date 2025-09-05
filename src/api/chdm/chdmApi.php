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
        "delete" => ["admin"],
        "search_chdm" => ["admin", "quality_checker"],
<<<<<<< HEAD
        
        "assign_for_branch" => ["admin", "quality_checker"],
=======


>>>>>>> 7bf43b2a97b1759408a94830ecae1670d647bbd2
        "view_all_chdm" => ["admin", "quality_checker","technician"],
        "update_all_chdm" => ["admin", "quality_checker"],

        "get_not_assigned" => ["admin", "quality_checker","technician"]
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
   // $missing = $this->validateFields($data,["state"]);
        
      //  if (!empty($missing)) {
          //  return [
           //     "message" => "Missing required fields: " . implode(", ", $missing),
             //   "status" => "error"
          //  ];
      //  }
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
    $success = $chdm->Update_location($data['location']);
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
    $success = $chdm->Update_branch_id($data["branch_id"]);
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
    
          // Check if CHDM is assigned in installation table
         $conn = DatabaseConnection::getConnection();
         $checkSql = "SELECT * FROM installation WHERE chdm_id = (SELECT id FROM chdm WHERE serial_no = :serial_no)";
         $stmt = $conn->prepare($checkSql);
         $stmt->bindParam(':serial_no', $data['serial_no']);
         $stmt->execute();

       if ($stmt->rowCount() > 0) {
          return [
            "message"=> "Cannot delete CHDM. It is already assigned to an installation.",
            "status"=> "error"
        ];
    }

        $chdm = new Chdm(null, $data['serial_no']);
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

public function assignForBranch($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }

        if (!$this->checkRoles($user["role_name"], "assign_for_branch")){
            return [
                "message"=> "Unauthorized: Admin access required",
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

        $chdm = new Chdm(null, $data['serial_no'], null, null, null, null, $data['branch_id']);
        $success = $chdm->assignForBranch($data['serial_no'], $data['branch_id']);
        
        if ($success) {
            return [
                "status"=> "success",
                "message"=> "CHDM assigned to branch successfully"
            ];
        } else {
            return [
                "message"=> "Failed to assign CHDM to branch",
                "status"=> "error"
            ];
        }
    }
    public function search_chdm($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }

        if (!$this->checkRoles($user["role_name"], "search_chdm")){
            return [
                "message"=> "Unauthorized: Admin & Quality Checker access required",
                "status"=> "error"
            ];
        }

        $missing = $this->validateFields($data, ["keyword"]);
        if (!empty($missing)) {
            return [
                "message"=> "Missing required fields: " . implode(", ", $missing),
                "status"=> "error"
            ];
        }

        $keyword = $data["keyword"];
        if (trim($keyword) === "") {
            // Return all failed records if keyword is empty or whitespace
            $result = (new Chdm())->read_failed();
        } else {
            $result = Chdm::searchFailedChdm($keyword);
        }
        if ($result) {
            return [
                "status"=> "success",
                "message"=> "CHDM records found",
                "data"=> $result
            ];
        } else {
            return [
                "message"=> "No CHDM records found",
                "status"=> "error"
            ];
        }
    }


    public function view_all_chdm($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }

        if (!$this->checkRoles($user["role_name"], "view_all_chdm")){
            return [
                "message"=> "Unauthorized: Admin & Quality Checker access required",
                "status"=> "error"
            ];
        }

        $chdm = new Chdm();
        $result = $chdm->readAll();
        if ($result) {
            return [
                "status"=> "success",
                "message"=> "All CHDM records retrieved successfully",
                "data"=> $result
            ];
        } else {
            return [
                "message"=> "No CHDM records found",
                "status"=> "error"
            ];
        }
    }
    public function get_not_assigned() {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }
        $chdm = new Chdm();
        $result = $chdm::getNotAssigned();
        if ($result) {
            return [
                "status"=> "success",
                "message"=> "CHDM records not assigned to any branch retrieved successfully",
                "data"=> $result
            ];
        } else {
            return [
                "message"=> "No CHDM records found",
                "status"=> "error"
            ];
        }
    }


  public function update_all_chdm($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error"
            ];
        }

        if (!$this->checkRoles($user["role_name"], "update_all_chdm")){
            return [
                "message"=> "Unauthorized: Admin & Quality Checker access required",
                "status"=> "error"
            ];
        }

        $missing = $this->validateFields($data, ["id"]);
        if (!empty($missing)) {
            return [
                "message"=> "Missing required fields: " . implode(", ", $missing),
                "status"=> "error"
            ];
        }

         // Check if this CHDM is already assigned
    $conn = DatabaseConnection::getConnection();
    $checkSql = "SELECT * FROM installation WHERE chdm_id = :chdm_id";
    $stmt = $conn->prepare($checkSql);
    $stmt->bindParam(":chdm_id", $data['id']);
    $stmt->execute();

    if ($stmt->rowCount() > 0) {
        return [
            "message" => "Cannot update CHDM. It is already assigned to an installation.",
            "status" => "error"
        ];
    }

        $chdm = new Chdm();
        $allChdm = $chdm->readAll();
        $existingChdm = null;

        foreach ($allChdm as $inst) {
            if ($inst['id'] == $data['id']) {
                $existingChdm = $inst;
                break;
            }
        }

        $chdm = new Chdm(
            $data['id'],
            isset($data['serial_no']) ? $data['serial_no'] : $existingChdm['serial_no'],
            isset($data['state']) ? $data['state'] : $existingChdm['state'],
            isset($data['location']) ? $data['location'] : $existingChdm['location'],
            isset($data['description']) ? $data['description'] : $existingChdm['description'],
            isset($data['tested_date']) ? $data['tested_date'] : $existingChdm['tested_date'],
            
        );
        
        $success = $chdm->update_all();
        
        if ($success) {
            return [
                "status"=> "success",
                "message"=> "CHDM record updated successfully"
            ];
        } else {
            return [
                "message"=> "Failed to update CHDM record",
                "status"=> "error"
            ];
        }
    }

    
}


