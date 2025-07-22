<?php
 class InstallationApi extends ApiResourceBase {

    public function __construct() {
        $this->setRoles([
            "create_installation" => ["admin", "technician"],
            "view_complete_installations" => ["admin", "technician"],
            "view_pending_installations" => ["admin", "technician"],
            "update_status" => ["admin", "technician"],
            "update_software_version" => ["admin", "technician"],
            "update_completion_date" => ["admin", "technician"],
            "update_notes" => ["admin", "technician"],
            "delete_installation" => ["admin"],
            "view_all_installations" => ["admin"],
            "update_all_installations" => ["admin"],
        ]);
    }
    public function create_installation($data) {
       $user = $this->getAuthenticatedUser();
       if (!$user) {
           return [
               "message" => "Invalid or expired token. Please log in again.",
               "status" => "error"
           ];
       }

         if (!$this->checkRoles($user['role_name'], 'create_installation')) {
            return [
                "message" => "Unauthorized: Admin or Technician access required",
                "status" => "error"
            ];
        }

        $missing = $this->validateFields($data, ["branch_id", "status", "date", "software_version", "ip_address", "notes", "serial_no"]);
        if (!empty($missing)) {
            return [
                "message" => "Missing required fields: " . implode(", ", $missing),
                 "status" => "error"
            ];
        }

        // Get technician_id from JWT token
        error_log("User data: " . print_r($user, true));

        // Try different possible key names for user ID
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
                "message" => "Unable to identify technician from token",
                "status" => "error"
            ];
        }

        // Find chdm_id from not assigned chdm list by serial_no
        $chdm_id = null;
        if (!empty($data['serial_no'])) {
            if (!class_exists('Chdm')) {
                include_once __DIR__ . '/../../classes/Chdm.php';
            }
            $notAssignedChdms = call_user_func(['Chdm', 'getNotAssigned']);
            if (is_array($notAssignedChdms)) {
                foreach ($notAssignedChdms as $chdm) {
                    if (isset($chdm['serial_no']) && $chdm['serial_no'] == $data['serial_no']) {
                        // Use 'id' or 'chdm_id' depending on your schema
                        $chdm_id = isset($chdm['id']) ? $chdm['id'] : (isset($chdm['chdm_id']) ? $chdm['chdm_id'] : null);
                        break;
                    }
                }
            }
        }

        if ($chdm_id === null) {
            return [
                "message" => "No not assigned CHDM found with the given serial_no.",
                "status" => "error"
            ];
        }

        $installation = new Installation(
            null,
            $chdm_id,
            $data['branch_id'],
            $technician_id,
            $data['status'],
            $data['date'],
            null,
            $data['software_version'],
            $data['ip_address'],
            $data['notes'],
            $data['serial_no']
        );
        $success = $installation->create();

        if ($success) {
            // Assign the CHDM to the branch in chdm table
            if (!class_exists('Chdm')) {
                include_once __DIR__ . '/../../classes/Chdm.php';
            }
            $assignSuccess = false;
            if (method_exists('Chdm', 'assignForBranch')) {
                $assignSuccess = call_user_func(['Chdm', 'assignForBranch'], $data['serial_no'], $data['branch_id']);
            }
            if ($assignSuccess) {
                return [
                    "status" => "success",
                    "message" => "Installation created and CHDM assigned to branch successfully"
                ];
            } else {
                return [
                    "status" => "success",
                    "message" => "Installation created, but failed to assign CHDM to branch."
                ];
            }
        } else {
            return [
                "message" => "Failed to create installation.",
                "status" => "error",
            ];
        }
    }
    public function view_complete_installations() {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message'=> 'Invalid or expired token. Please log in again.',
                'status'=> 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'view_complete_installations')) {
            return [
                'message'=> 'Unauthorized: Admin or Technician access required',
                'status'=> 'error'
            ];
        }
        //  $missing = $this->validateFields($data, [ 'status']);

        // if (!empty($missing)) {
        //     return [
        //         'message'=> 'Missing required fields: ' . implode(', ', $missing),
        //         'status'=> 'error'
        //     ];
        // }
        $installation = new Installation();
        $result = $installation->read_with_technician();
        if ($result) {
            return [
                'status'=> 'success',
                'message'=> 'Completed installations retrieved successfully',
                'data'=> $result,
            ];
        } else {
            return [
                'message'=> 'Failed to retrieve installations',
                'status'=> 'error'
                ];
        }
    }
    public function view_pending_installations() {
       $user = $this->getAuthenticatedUser();
       if (!$user) {
            return [
                'message'=> 'Invalid or expired token. Please log in again.',
                'status'=> 'error'
            ];
        }
        
        if (!$this->checkRoles($user['role_name'], 'view_pending_installations')) {
            return [
                'message'=> 'Unauthorized: Admin or Technician access required',
                'status'=> 'error'
            ];
        }

        $installation = new Installation();
        $result = $installation->read_pending();
        if ($result) {
            return [
                'status'=> 'success',
                'message'=> 'pending installations retrieved successfully',
                'data'=> $result
                ];
        } else {
            return [
                'message'=> 'Failed to retrieve pending installations',
                'status'=> 'error'
            ]; 
        }
    }
    public function update_status($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message'=> 'Invalid or expired token. Please log in again.',
                'status'=> 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'],'update_status')) {
            return [
                'message'=> 'Unauthorized: Admin or Technician access required',
                'status'=> 'error'
            ];
        }
        $missing = $this->validateFields($data, ['installation_id', 'status']);

        if (!empty($missing)) {
            return [
                'message'=> 'Missing required fields: ' . implode(', ', $missing),
                'status'=> 'error'
            ];
        }
        $installation = new Installation($data['installation_id'],null,$data['status']);
        $success = $installation->update_status($data['status']);
        if ($success) {
            return [
                'status'=> 'success',
                'message'=> 'Installation updated successfully'
                ];
        } else {
            return [
                'status'=> 'error',
                'message'=> 'Failed to update installation'
            ];
    }
 }

 public function update_software_version($data) {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return[
            'message' => 'Invalid or expired token. Please log in again.',
            'status' => 'error'
        ];
    }
    if (!$this->checkRoles($user['role_name'],'update_software_version')) {
        return [
            'message' => 'Unauthorized: Admin or Technician access required',
            'status' => 'error'
        ];
    }
    $missing = $this->validateFields($data, ['installation_id', 'software_version']);

    if (!empty($missing)) {
        return [
            'message' => 'Missing required fields: ' . implode(', ', $missing),
            'status' => 'error'
        ];
    }

    $installation = new Installation($data['installation_id']);
    $success = $installation->software_version($data['software_version']);
    if ($success) {
        return [
            'status' => 'success',
            'message' => 'Installation software version updated successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update installation software version'
        ];
    }
}

public function update_completion_date($data) {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return [
            'message' => 'Invalid or expired token. Please log in again.',
            'status' => 'error'
        ];
    }
    if (!$this->checkRoles($user['role_name'], 'update_completion_date')) {
        return [
            'message' => 'Unauthorized: Admin or Technician access required',
            'status' => 'error'
        ];
    }
    $missing = $this->validateFields($data, ['installation_id', 'completion_date']);

    if (!empty($missing)) {
        return [
            'message' => 'Missing required fields: ' . implode(', ', $missing),
            'status' => 'error'
        ];
    }

    $installation = new Installation($data['installation_id']);
    $success = $installation->completion_date($data['completion_date']);
    if ($success) {
        return [
            'status' => 'success',
            'message' => 'Installation completion date updated successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update installation completion date'
        ];
    }
}

public function update_notes($data) {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return [
            'message' => 'Invalid or expired token. Please log in again.',
            'status' => 'error'
        ];
    }
    if (!$this->checkRoles($user['role_name'], 'update_notes')) {
        return [
            'message' => 'Unauthorized: Admin or Technician access required',
            'status' => 'error'
        ];
    }
    $missing = $this->validateFields($data, ['installation_id', 'notes']);

    if (!empty($missing)) {
        return [
            'message' => 'Missing required fields: ' . implode(', ', $missing),
            'status' => 'error'
        ];
    }

    $installation = new Installation($data['installation_id']);
    $success = $installation->notes($data['notes']);
    if ($success) {
        return [
            'status' => 'success',
            'message' => 'Installation notes updated successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update installation notes'
        ];
    }
}

public function delete_installation($data) {
   $user = $this->getAuthenticatedUser();
   if (!$user) {
    return [
        'message' => 'Invalid or expired token. Please log in again.',
        'status' => 'error'
    ];
}
if (!$this->checkRoles($user['role_name'],'delete_installation')) {
    return [
        'message' => 'Unauthorized: Admin access required',
        'status' => 'error'
    ];
}
$missing = $this->validateFields($data, ['installation_id']);

if (!empty($missing)) {
    return [
        'message' => 'Missing required fields: ' . implode(', ', $missing),
        'status' => 'error'
    ];
}

    $installation = new Installation($data['installation_id']);
    $success = $installation->delete();
    if ($success) {
        return [
            'status' => 'success',
            'message' => 'Installation deleted successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to delete installation'
        ];
    }
}

public function view_all_installations() {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return [
            'message' => 'Invalid or expired token. Please log in again.',
            'status' => 'error'
        ];
    }
    if (!$this->checkRoles($user['role_name'], 'view_all_installations')) {
        return [
            'message' => 'Unauthorized: Admin access required',
            'status' => 'error'
        ];
    }

    $installation = new Installation();
    $result = $installation->read();
    if ($result) {
        return [
            'status' => 'success',
            'message' => 'All installations retrieved successfully',
            'data' => $result
        ];
    } else {
        return [
            'message' => 'Failed to retrieve installations',
            'status' => 'error'
        ];
    }
}
 public function update_all_installations($data) {
    $user = $this->getAuthenticatedUser();
    if (!$user) {
        return [
            'message' => 'Invalid or expired token. Please log in again.',
            'status' => 'error'
        ];
    }
    if (!$this->checkRoles($user['role_name'], 'update_all_installations')) {
        return [
            'message' => 'Unauthorized: Admin access required',
            'status' => 'error'
        ];
    }

    $missing = $this->validateFields($data, ['installation_id']);
    if (!empty($missing)) {
        return [
            'message' => 'Missing required fields: ' . implode(', ', $missing),
            'status' => 'error'
        ];
    }

    // First, get the existing installation data
    $installation = new Installation();
    $allInstallations = $installation->read();
    $existingInstallation = null;
    
    foreach ($allInstallations as $inst) {
        if ($inst['installation_id'] == $data['installation_id']) {
            $existingInstallation = $inst;
            break;
        }
    }
    
    if (!$existingInstallation) {
        return [
            'message' => 'Installation not found',
            'status' => 'error'
        ];
    }
    
    // Create installation object with existing data, then override with new data
    $installation = new Installation(
        $data['installation_id'],
        isset($data['chdm_id']) ? $data['chdm_id'] : $existingInstallation['chdm_id'],
        isset($data['branch_id']) ? $data['branch_id'] : $existingInstallation['branch_id'],
        isset($data['technician_id']) ? $data['technician_id'] : $existingInstallation['technician_id'],
        isset($data['status']) ? $data['status'] : $existingInstallation['status'],
        isset($data['date']) ? $data['date'] : $existingInstallation['date'],
        isset($data['completion_date']) ? $data['completion_date'] : $existingInstallation['completion_date'],
        isset($data['software_version']) ? $data['software_version'] : $existingInstallation['software_version'],
        isset($data['ip_address']) ? $data['ip_address'] : $existingInstallation['ip_address'],
        isset($data['notes']) ? $data['notes'] : $existingInstallation['notes'],
        isset($data['serial_no']) ? $data['serial_no'] : (isset($existingInstallation['serial_no']) ? $existingInstallation['serial_no'] : null)
    );
    
    $success = $installation->update_all();
    
    if ($success) {
        return [
            'status' => 'success',
            'message' => 'Installation updated successfully'
        ];
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to update installation'
        ];
    }
 }
}
 