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


