<?php

class RepairApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_repair" => ["admin", "technician"],
            "read_repair" => ["admin", "technician", "user"],
            "read_all_repairs" => ["admin", "technician", "user"],
            "read_technician_repairs" => ["admin", "technician"],
            "update_repair" => ["admin", "technician"],
            "update_all" => ["admin", "technician"],
            "delete_repair" => ["admin"]
        ]);
    }

    public function create_repair($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'create_repair')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or Technician access required"
            ];
        }



        // Use branch_id directly from input
        if (!isset($data['branch_id'])) {
            return [
                "status" => "error",
                "message" => "Missing branch_id"
            ];
        }
        $branch_id = $data['branch_id'];

        // Get technician_id from user token
        $technician_id = null;
        if (isset($user['user_id'])) {
            $technician_id = $user['user_id'];
        } elseif (isset($user['id'])) {
            $technician_id = $user['id'];
        } elseif (isset($user['uid'])) {
            $technician_id = $user['uid'];
        }
        if (!$technician_id) {
            return [
                "status" => "error",
                "message" => "Technician ID not found in authentication token"
            ];
        }
        $repair = new Repair(null, $data['device_type'], $data['device_id'], $branch_id, $technician_id, $data['start_time'],null,null,null, $data['virtual_support_link']);
        $success = $repair->create();

        if ($success) {
            $emailResult = null;
            // Send virtual support link email
            $branchDetails = Branch::getById($branch_id);
            if ($branchDetails && !empty($branchDetails['email']) && !empty($data['virtual_support_link'])) {
                $technicianName = $user['username'] ?? 'Support Technician';
                
                // Explicitly configure the email service, mirroring the working implementation in Service_ReportingApi
                $connectionString = "endpoint=https://fixpoit-mailler.unitedstates.communication.azure.com/;accesskey=DlCOIqLviNq3RKnhC10g61vOZ46nN3qtE4a3DR5IRke2vLzHJ6jnJQQJ99BHACULyCpQCLZ2AAAAAZCSSthx";
                $senderAddress = "DoNotReply@1150820c-c077-40e5-bf54-90e4e6adcb7e.azurecomm.net";
                \Fixpoint\Utils\AzureEmailService::configure($connectionString, $senderAddress);

                $emailResult = \Fixpoint\Utils\AzureEmailService::sendVirtualSupportLinkEmail(
                    $branchDetails['email'],
                    $branchDetails['contact_person'] ?? 'Branch Contact',
                    $data['virtual_support_link'],
                    $data['device_id'],
                    $technicianName
                );
            }

            $response = [
                "status" => "success",
                "message" => "Repair created successfully."
            ];

            if ($emailResult) {
                $response['email_status'] = $emailResult;
            }

            return $response;
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to create repair."
            ];
        }
    }


    public function read_repair($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'read_repair')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin, Technician or User access required"
            ];
        }

        // Get pagination parameters with defaults
        $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
        $limit = isset($data['limit']) ? max(1, min(100, intval($data['limit']))) : 10;

        $repair = new Repair();
        $result = $repair->read($page, $limit);

        if ($result['data'] && count($result['data']) > 0) {
            return [
                "status" => "success",
                "data" => $result['data'],
                "count" => count($result['data']),
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => $result['page'] < $result['total_pages'],
                    "has_prev" => $result['page'] > 1
                ]
            ];
        } else {
            return [
                "status" => "success",
                "data" => [],
                "count" => 0,
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "message" => "No repair records found.",
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => false,
                    "has_prev" => false
                ]
            ];
        }
    }

    public function read_all_repairs($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'read_all_repairs')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin, Technician or User access required"
            ];
        }

        $repair = new Repair();
        $results = $repair->readAll();

        if ($results && count($results) > 0) {
            return [
                "status" => "success",
                "data" => $results,
                "count" => count($results)
            ];
        } else {
            return [
                "status" => "success",
                "data" => [],
                "count" => 0,
                "message" => "No repair records found."
            ];
        }
    }

    public function read_technician_repairs($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'read_technician_repairs')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or Technician access required"
            ];
        }

        // Get technician_id from user token
        $technician_id = null;
        if (isset($user['user_id'])) {
            $technician_id = $user['user_id'];
        } elseif (isset($user['id'])) {
            $technician_id = $user['id'];
        } elseif (isset($user['uid'])) {
            $technician_id = $user['uid'];
        }

        if (!$technician_id) {
            return [
                "status" => "error",
                "message" => "Technician ID not found in authentication token"
            ];
        }

        // Get pagination parameters with defaults
        $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
        $limit = isset($data['limit']) ? max(1, min(100, intval($data['limit']))) : 10;

        $repair = new Repair();
        $result = $repair->readByTechnician($technician_id, $page, $limit);

        if ($result['data'] && count($result['data']) > 0) {
            return [
                "status" => "success",
                "data" => $result['data'],
                "count" => count($result['data']),
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "technician_id" => $technician_id,
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => $result['page'] < $result['total_pages'],
                    "has_prev" => $result['page'] > 1
                ]
            ];
        } else {
            return [
                "status" => "success",
                "data" => [],
                "count" => 0,
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "message" => "No repair records found for this technician.",
                "technician_id" => $technician_id,
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => false,
                    "has_prev" => false
                ]
            ];
        }
    }



    
    public function update_repair($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'update_repair')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or Technician access required"
            ];
        }

        //  To Pass correct order to Repair constructor
        $repair = new Repair(
            $data['repair_id'],
            null, // device_type 
            null, // device_id 
            null, // branch_id 
            $data['technician_id'],
            null, // start_time 
            $data['end_time'],
            $data['status'],
            $data['summary'],
            $data['virtual_support_link'],
            isset($data['backup_sent']) ? (string)$data['backup_sent'] : 'false',
            isset($data['visit_required']) ? (string)$data['visit_required'] : 'false'
        );
        $success = $repair->update();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Repair updated successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to update repair."
            ];
        }
    }

    public function update_all($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'update_all')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or Technician access required"
            ];
        }
        

        
        // Create repair with all fields
        $repair = new Repair(
            $data['repair_id'],
            $data['device_type'],
            $data['device_id'],
            $data['branch_id'],
            $data['technician_id'],
            $data['start_time'],
            $data['end_time'],
            $data['status'],
            $data['summary'],
            $data['virtual_support_link'],
            isset($data['backup_sent']) ? (string)$data['backup_sent'] : 'false',
            isset($data['visit_required']) ? (string)$data['visit_required'] : 'false'
        );
        
        $success = $repair->updateAll();

        if ($success) {
            $emailResult = null;
            
            // Send repair completion email if status is completed or closed
            if (in_array(strtolower($data['status'] ?? ''), ['completed', 'closed'])) {
                try {
                    error_log("Starting repair completion email process for repair_id: " . ($data['repair_id'] ?? 'NULL'));
                    
                    // Get branch information
                    $branchData = Branch::getById($data['branch_id']);
                    if ($branchData && !empty($branchData['email'])) {
                        error_log("Branch found: " . $branchData['name'] . " - Email: " . $branchData['email']);

                        // Get client information
                        $clientData = null;
                        if (isset($branchData['client_id'])) {
                            $clientData = Client::getById($branchData['client_id']);
                        }
                        if (!$clientData) {
                            // If no client found via branch, create a default client data
                            $clientData = ['name' => $branchData['name'] ?? 'N/A'];
                        }
                        
                        // Get technician information
                        $technicianData = ['username' => $user['username'] ?? 'N/A'];
                        
                        // Prepare repair data for email
                        $repairData = [
                            'repair_id' => $data['repair_id'],
                            'device_type' => $data['device_type'],
                            'device_id' => $data['device_id'],
                            'start_time' => $data['start_time'],
                            'end_time' => $data['end_time'],
                            'status' => $data['status'],
                            'summary' => $data['summary'],
                            'backup_sent' => $data['backup_sent'] ?? 'false',
                            'visit_required' => $data['visit_required'] ?? 'false'
                        ];
                        
                        // Configure email service
                        $connectionString = "endpoint=https://fixpoit-mailler.unitedstates.communication.azure.com/;accesskey=DlCOIqLviNq3RKnhC10g61vOZ46nN3qtE4a3DR5IRke2vLzHJ6jnJQQJ99BHACULyCpQCLZ2AAAAAZCSSthx";
                        $senderAddress = "DoNotReply@1150820c-c077-40e5-bf54-90e4e6adcb7e.azurecomm.net";
                        \Fixpoint\Utils\AzureEmailService::configure($connectionString, $senderAddress);
                        
                        error_log("Sending repair completion email to: " . $branchData['email']);
                        $emailResult = \Fixpoint\Utils\AzureEmailService::sendRepairCompletionEmail(
                            $branchData['email'],
                            $repairData,
                            $branchData,
                            $clientData,
                            $technicianData
                        );

                        error_log("Repair completion email result: " . json_encode($emailResult));
                    } else {
                        error_log("Branch not found or email not available for branch_id: " . ($data['branch_id'] ?? 'NULL'));
                    }
                } catch (\Exception $e) {
                    error_log("Error sending repair completion email: " . $e->getMessage());
                    // Don't fail the update if email fails
                }
            }

            $response = [
                "status" => "success",
                "message" => "Repair updated successfully with all fields."
            ];

            if ($emailResult && isset($emailResult['success'])) {
                $response['email_status'] = $emailResult;
            }

            return $response;
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to update repair."
            ];
        }
    }

    public function delete_repair($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'delete_repair')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }



        $repair = new Repair($data['repair_id']);
        $success = $repair->delete();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Repair deleted successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to delete repair."
            ];
        }
    }

   
}


