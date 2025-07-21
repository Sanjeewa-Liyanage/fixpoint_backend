<?php
class Qc_ReportingApi extends ApiResourceBase {
    public function __construct(){
        $this -> setRoles([
            "create_report"=> ["Quality_Checker","admin"],
            "view_pass_reports"=> ["Quality_Checker","admin"],
            "view_failed_reports"=> ["Quality_Checker","admin"],
            "update_result"=> ["Quality_Checker","admin"],
            "update_test_details"=> ["Quality_Checker","admin"],
            "delete_report"=> ["admin"]
        ]); 
    }

    public function create_report($data){
        $user = $this-> getAuthenticatedUser();
        if(!$user){
            return [
                "status"=>"error",
                "message"=> "Invalid Authentication Token"
            ];
        }
        $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name'])? $user['role']['role_name'] : null);
        if(!$this-> checkRoles($roleName, 'create_report')){
            return [
                'status'=> 'error', 
                'message'=> 'You do not have permission to create a report'
            ];

        }
        $missing = $this-> validateFields($data, [
            'chdm_id',
            'qc_officer_id',
            'date',
            'result',
            'remarks',
            'test_details'
        ]);
        if(!empty($missing)){
            return [
                'status'=> 'error',
                'message'=> 'Missing fields: '. implode(', ', $missing)
            ];
        }
        $qcReporting = new Qc_Reporting(
            null, // qc_id will be auto-incremented
            $data['chdm_id'],
            $data['qc_officer_id'],
            $data['date'],
            $data['result'],
            $data['remarks'],
            $data['test_details']
        );
        $success = $qcReporting->create();
        if($success){
            return [
                'status'=> 'success',
                'message'=> 'Quality check report created successfully'
            ];
        } else {
            return [
                'status'=> 'error',
                'message'=> 'Failed to create quality check report'
            ];
        }

    }

    public function view_pass_reports(){
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "message" => "Invalid or expired token. Please log in again.",
                "status" => "error",    
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'view_pass_reports')){
            return [
                "message" => "Unauthorized: Admin or Quality Checker access required",
                "status" => "error"
            ];
        }
        $Qc_Reporting = new Qc_Reporting();
        $result = $Qc_Reporting->read();
        if($result){
            return [
                "status"=> "success",
                "message"=> "Quality check reports retrieved successfully",
                "data"=> $result,
            ];
        } else {
            return [
                "message"=> "No passed quality check reports found",
                "status"=> "error",
                ];
        }

    }
    public function view_failed_reports() {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "message"=> "Invalid or expired token. Please log in again.",
                "status"=> "error",
                ];
    }
    if(!$this->checkRoles($user['role_name'], 'view_failed_reports')){
        return [
            "message"=> "Unauthorized: Admin or Quality Checker access required",
            "status"=> "error"
        ];
    }
    $Qc_Reporting = new Qc_Reporting();
    $result = $Qc_Reporting->read_failed();
    if($result){
        return [
            "status"=> "success",
            "message"=> "Failed quality check reports retrieved successfully",
            "data"=> $result,
        ];
}
 else {
    return [
        "message"=> "No failed quality check reports found",
        "status"=> "error",
    ];
 }
}
  public function update_result($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "message"=> "Invalid or expired token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'update_result')){
        return [
            "message"=> "Unauthorized: Admin or Quality Checker access required",
            "status"=> "error"
        ];
    }
   $missing = $this->validateFields($data, ["chdm_id", "result"]);
    if(!empty($missing)){
        return [
            "message"=> "Missing fields: ". implode(", ", $missing),
            "status"=> "error"
        ];
    }
    $Qc_Reporting = new Qc_Reporting($data['chdm_id'], null, null, $data['result']);
    $success = $Qc_Reporting->update_result($data['result']);
    if($success){
        return [
            "status"=> "success",
            "message"=> "Quality check result updated successfully"
        ];
    } else {
        return [
            "status"=> "error",
            "message"=> "Failed to update quality check result"
        ];
  }
}
public function update_test_details($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "message"=> "Invalid or expired token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user["role_name"], "update_test_details")){
        return [
            "message"=> "Unauthorized: Admin or Quality Checker access required",
            "status"=> "error"
        ];
}
$missing = $this->validateFields($data, ["chdm_id", "test_details"]);

    if(!empty($missing)){
        return [
            "message"=> "Missing fields: ". implode(", ", $missing),
            "status"=> "error"
        ];
    }
    $Qc_Reporting = new Qc_Reporting($data['chdm_id'], null, null, $data['test_details']);
    $success = $Qc_Reporting->update_test_details($data['test_details']);
    if($success){
        return [
            "status"=> "success",
            "message"=> "Test details updated successfully"
        ];
    } else {
        return [
            "status"=> "error",
            "message"=> "Failed to update test details"
        ];
    }
}
public function delete_report($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "message"=> "Invalid or expired token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'delete_report')){
        return [
            "message"=> "Unauthorized: Admin access required",
            "status"=> "error"
        ];
    }
    $missing = $this->validateFields($data, ["chdm_id"]);
    if(!empty($missing)){
        return [
            "message"=> "Missing required fields: ". implode(", ", $missing),
            "status"=> "error"
        ];
    }
    $Qc_Reporting = new Qc_Reporting($data['chdm_id']);
    $success = $Qc_Reporting->delete();
    if($success){
        return [
            "status"=> "success",
            "message"=> "Quality check report deleted successfully"
        ];
    } else {
        return [
            "status"=> "error",
            "message"=> "Failed to delete quality check report"
        ];
    }
}
}