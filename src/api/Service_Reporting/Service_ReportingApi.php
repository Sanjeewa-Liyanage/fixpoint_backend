<?php
class Service_ReportingApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_service_report" => ["Technician", "admin"],
            "view_service_reports" => ["Technician", "admin"],
            "update_service_reports" => ["Technician", "admin"],
            "delete_service_report" => ["Technician", "admin"]
        ]);
    }
public function create_service_report($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "status" => "error",
            "message" => "Invalid Authentication Token"
        ];
    }
    $roleName = isset($user['role_name'])? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
    if(!$this->checkRoles($roleName, 'create_service_report')) {
        return [
            'status' => 'error',
            'message' => 'You do not have permission to create a service report'
        ];
    }
    $missing = $this->validateFields($data, [
        'branch_id',
        'client_id',
        'user_id',
        'device_type',
        'service_date',
        'service_type',
        'service_notes',
        'created_at'
        
    ]);
    if(!empty($missing)) {
        return [
            'status' => 'error',
            'message' => 'Missing fields: ' . implode(', ', $missing)
        ];
    }
    $serviceReport = new Service_Reporting(
        null,
        $data['branch_id'],
        $data['client_id'],
        $data['user_id'],
        $data['device_type'],
        $data['service_date'],
        $data['service_type'],
        $data['service_notes'],
        $data['created_at'],
        $data['teller_scanner_serial']?? null,
        $data['chdm_serial']?? null
        );
    $success = $serviceReport->create();
    if($success) {
        return [
            'status' => 'success',
            'message' => 'Service report created successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to create service report'
        ];
    }
}

public function view_service_reports($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "message"=> "Invalid or expired authentication token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'view_service_reports')) {
        return [
            "message" => "Unauthorized access. Admin or Technician access required",
            "status"=> "error",
        ];
    }

    if(!isset($data['keyword'])|| trim($data['keyword']) === "") {
        return [
            "message"=> "view_service_reports keyword is required",
            "status"=> "error",
        ];
    }

    $keyword = $data['keyword'];
    $serviceReporting = new Service_Reporting();
    $results = $serviceReporting->search($keyword);

    if($results) {
        return [
            "status" => "success",
            "message" => "Service reports retrieved successfully",
            "data" => $results
        ];
    } else {
        return [
            "message" => "No service reports found",
            "status" => "error",
        ];
    }
}
public function update_service_reports($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "message"=> "Invalid or expired authentication token. Please log in again.",
            "status"=> "error",
        ];
}
   if(!isset($data['service_id'])) {
    return [
        'message'=> 'Missing service_id',
        'status'=> 'error'
        ];
   }
    $service = new Service_Reporting();
    $service->service_id = $data['service_id'];

    $success = $service->update_service_fields($data);

    if ($success) {
        return [
            "status" => "success", 
            "message" => "Service report updated"
        ];

    } else {

        return [
            "status" => "error",
             "message" => "Update failed or no fields provided"
            ];
    }
}

 public function delete_service_report($data){
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "message"=> "Invalid or expired authentication token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'delete_service_report')) {
        return [
            "message" => "Unauthorized access. Admin or Technician access required",
            "status"=> "error",
        ];
    }
    $missing = $this->validateFields($data, ['service_id']);
    if(!empty($missing)) {
        return [
            "message" => "Missing fields: " . implode(', ', $missing),
            "status" => "error"
        ];
    }
    $service = new Service_Reporting($data['service_id']);
    $success = $service->delete();
    if ($success) {
        return [
            "status" => "success",
            "message" => "Service report deleted successfully"
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Failed to delete service report or service_id not found"
        ];
 }
}
}