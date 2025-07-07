<?php

class RepairApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_repair" => ["admin", "technician"],
            "read_repair" => ["admin", "technician", "user"],
            "read_all_repairs" => ["admin", "technician", "user"],
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

        $missing = $this->validateFields($data, ['device_type', 'device_id', 'branch_id', 'technician_id', 'start_time', 'virtual_support_link']);
         // Ensure all required fields are present
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }

        $repair = new Repair(null, $data['device_type'], $data['device_id'], $data['branch_id'], $data['technician_id'], $data['start_time'],null,null,null, $data['virtual_support_link']);
        $success = $repair->create();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Repair created successfully."
            ];
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

        $repair = new Repair();
        $results = $repair->read();

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
        $missing = $this->validateFields($data, ['repair_id', 'status', 'summary', 'virtual_support_link', 'backup_sent', 'visit_required', 'end_time', 'technician_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        // Pass all fields in correct order to Repair constructor
        $repair = new Repair(
            $data['repair_id'],
            null, // device_type (not updated here)
            null, // device_id (not updated here)
            null, // branch_id (not updated here)
            $data['technician_id'],
            null, // start_time (not updated here)
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
        
        $missing = $this->validateFields($data, [
            'repair_id', 
            'device_type', 
            'device_id', 
            'branch_id', 
            'technician_id', 
            'start_time', 
            'end_time', 
            'status', 
            'summary', 
            'virtual_support_link', 
            'backup_sent', 
            'visit_required'
        ]);
        
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
            ];
        }
        
        // Create repair object with all fields
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
            return [
                "status" => "success",
                "message" => "Repair updated successfully with all fields."
            ];
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

        $missing = $this->validateFields($data, ['repair_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Invalid Request. Missing fields: " . implode(", ", $missing)
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


