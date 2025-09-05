<?php
class Qc_ReportingApi extends ApiResourceBase {
    public function __construct(){
        $this -> setRoles([
            "create_report"=> ["Quality_Checker","admin"],
            "view_pass_reports"=> ["Quality_Checker","admin"],
            "view_failed_reports"=> ["Quality_Checker","admin"],
            "update_result"=> ["Quality_Checker","admin"],
            "update_test_details"=> ["Quality_Checker","admin"],
            "update_report"=> ["Quality_Checker","admin"],
            "delete_report"=> ["admin"],
            "read"=> ["Quality_Checker","admin"]
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
            'serial_no',
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

        // Validate result value
        if(!in_array(strtolower($data['result']), ['passed', 'failed'])){
            return [
                'status'=> 'error',
                'message'=> 'Result must be either "Passed" or "Failed"'
            ];
        }

        // Get chdm_id by serial number
        $qcReporting = new Qc_Reporting();
        $chdm_id = $qcReporting->getChdmIdBySerial($data['serial_no']);
        if(!$chdm_id){
            return [
                'status'=> 'error',
                'message'=> 'No CHDM found with serial number: ' . $data['serial_no']
            ];
        }

        // Check if a report already exists for this chdm_id
        $conn = DatabaseConnection::getConnection();
        $stmt = $conn->prepare("SELECT qc_id FROM quality_check WHERE chdm_id = :chdm_id");
        $stmt->bindParam(':chdm_id', $chdm_id);
        $stmt->execute();
        if ($stmt->fetch(PDO::FETCH_ASSOC)) {
            return [
                'status'=> 'error',
                'message'=> 'A report already exists for this serial number.'
            ];
        }

        // Debug: Log user array structure (remove this after testing)
        error_log("User array structure: " . print_r($user, true));

        // Get user ID from authenticated user
        if (isset($user['user_id'])) {
            $qc_officer_id = $user['user_id'];
        } elseif (isset($user['id'])) {
            $qc_officer_id = $user['id'];
        } elseif (isset($user['uid'])) {
            $qc_officer_id = $user['uid'];
        } else {
            return [
                'status'=> 'error',
                'message'=> 'Unable to determine user ID from authentication token'
            ];
        }

        // Create the QC report
        $qcReporting = new Qc_Reporting(
            null, // qc_id will be auto-incremented
            $chdm_id,
            $qc_officer_id,
            $data['date'],
            ucfirst(strtolower($data['result'])), // Ensure proper case (Passed/Failed)
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
        $result = $Qc_Reporting->read_passed();
        if($result && count($result) > 0){
            return [
                "status"=> "success",
                "message"=> "Quality check reports retrieved successfully",
                "data"=> $result,
            ];
        } else {
            return [
                "message"=> "No passed quality check reports found or no data available",
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
  
public function update_report($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "status"=> "error",
            "message"=> "Invalid or expired token. Please log in again."
        ];
    }
    
    $roleName = isset($user['role_name']) ? $user['role_name'] : (isset($user['role']['role_name'])? $user['role']['role_name'] : null);
    if(!$this->checkRoles($roleName, 'update_report')){
        return [
            "status"=> "error",
            "message"=> "Unauthorized: Admin or Quality Checker access required"
        ];
    }
    
    $missing = $this->validateFields($data, ["chdm_id", "result", "remarks", "test_details"]);
    if(!empty($missing)){
        return [
            "status"=> "error",
            "message"=> "Missing required fields: ". implode(", ", $missing)
        ];
    }

    // Validate result value
    if(!in_array(strtolower($data['result']), ['passed', 'failed'])){
        return [
            'status'=> 'error',
            'message'=> 'Result must be either "Passed" or "Failed"'
        ];
    }

    // Use the provided chdm_id directly
    $chdm_id = $data['chdm_id'];
    
    // Verify that the CHDM exists
    $conn = DatabaseConnection::getConnection();
    $chdmCheckSql = "SELECT id FROM chdm WHERE id = :chdm_id";
    $chdmCheckStmt = $conn->prepare($chdmCheckSql);
    $chdmCheckStmt->bindParam(':chdm_id', $chdm_id);
    $chdmCheckStmt->execute();
    $chdmExists = $chdmCheckStmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$chdmExists){
        return [
            'status'=> 'error',
            'message'=> 'No CHDM found with ID: ' . $data['chdm_id']
        ];
    }

    // Check if QC report exists for this CHDM
    $conn = DatabaseConnection::getConnection();
    $checkSql = "SELECT qc_id FROM quality_check WHERE chdm_id = :chdm_id";
    $checkStmt = $conn->prepare($checkSql);
    $checkStmt->bindParam(':chdm_id', $chdm_id);
    $checkStmt->execute();
    $existingReport = $checkStmt->fetch(PDO::FETCH_ASSOC);
    
    if(!$existingReport){
        return [
            'status'=> 'error',
            'message'=> 'No QC report found for CHDM ID: ' . $data['chdm_id']
        ];
    }

    // Create QC Reporting object with updated data
    $qcReporting = new Qc_Reporting(
        null,
        $chdm_id,
        null, // qc_officer_id not needed for update
        null, // date not needed for update
        ucfirst(strtolower($data['result'])), // Ensure proper case (Passed/Failed)
        $data['remarks'],
        $data['test_details']
    );
    
    $success = $qcReporting->update();
    if($success){
        return [
            "status"=> "success",
            "message"=> "Quality check report updated successfully"
        ];
    } else {
        return [
            "status"=> "error",
            "message"=> "Failed to update quality check report"
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
    $missing = $this->validateFields($data, ["serial_no", "test_details"]);
    if(!empty($missing)){
        return [
            "message"=> "Missing fields: ". implode(", ", $missing),
            "status"=> "error"
        ];
    }

    // Optionally allow updating result if provided
    $updateResult = false;
    $resultValue = null;
    if (isset($data['result'])) {
        if(!in_array(strtolower($data['result']), ['passed', 'failed'])){
            return [
                'status'=> 'error',
                'message'=> 'Result must be either "Passed" or "Failed"'
            ];
        }
        $updateResult = true;
        $resultValue = ucfirst(strtolower($data['result']));
    }

    // Get chdm_id by serial number
    $qcReporting = new Qc_Reporting();
    $chdm_id = $qcReporting->getChdmIdBySerial($data['serial_no']);
    if(!$chdm_id){
        return [
            'status'=> 'error',
            'message'=> 'No CHDM found with serial number: ' . $data['serial_no']
        ];
    }

    // Get today's date in Sri Lankan time zone
    $dt = new DateTime('now', new DateTimeZone('Asia/Colombo'));
    $today = $dt->format('Y-m-d');

    // Update test_details, result (if provided), and date
    $conn = DatabaseConnection::getConnection();
    $fields = "test_details = :test_details, date = :date";
    if ($updateResult) {
        $fields .= ", result = :result";
    }
    $sql = "UPDATE quality_check SET $fields WHERE chdm_id = :chdm_id";
    $stmt = $conn->prepare($sql);
    $stmt->bindParam(':test_details', $data['test_details']);
    $stmt->bindParam(':date', $today);
    if ($updateResult) {
        $stmt->bindParam(':result', $resultValue);
    }
    $stmt->bindParam(':chdm_id', $chdm_id);
    $success = $stmt->execute();

    if($success){
        return [
            "status"=> "success",
            "message"=> "Test details updated successfully",
            "date_updated" => $today
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
    $missing = $this->validateFields($data, ["serial_no"]);
    if(!empty($missing)){
        return [
            "message"=> "Missing required fields: ". implode(", ", $missing),
            "status"=> "error"
        ];
    }

    // Get chdm_id by serial number
    $qcReporting = new Qc_Reporting();
    $chdm_id = $qcReporting->getChdmIdBySerial($data['serial_no']);
    if(!$chdm_id){
        return [
            'status'=> 'error',
            'message'=> 'No CHDM found with serial number: ' . $data['serial_no']
        ];
    }

    $qcReporting = new Qc_Reporting(null, $chdm_id);
    $success = $qcReporting->delete();
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

public function read() {
    $user = $this->getAuthenticatedUser();
    if(!$user){
        return [
            "status" => "error",
            "message" => "Invalid or expired token. Please log in again."
        ];
    }
    // Allow both Quality_Checker and admin roles to access
    if(!$this->checkRoles($user['role_name'], 'view_pass_reports')){
        return [
            "status" => "error",
            "message" => "Unauthorized: Admin or Quality Checker access required"
        ];
    }
    $Qc_Reporting = new Qc_Reporting();
    $result = $Qc_Reporting->read();
    if($result && count($result) > 0){
        return [
            "status" => "success",
            "message" => "Quality check reports retrieved successfully",
            "data" => $result
        ];
    } else {
        return [
            "status" => "error",
            "message" => "No quality check reports found or no data available"
        ];
    }
}
}