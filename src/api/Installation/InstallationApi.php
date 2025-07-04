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

        $missing = $this->validateFields($data, ["chdm_id", "branch_id", "status", "date", "software_version", "ip_address","notes"]);
        if (!empty($missing)) {
            return [
                "message" => "Missing required fields: " . implode(", ", $missing),
                 "status" => "error"
            ];
        }

        // Get technician_id from JWT token
        // Debug: Check what's in the user array
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

        $installation = new Installation(null, $data['chdm_id'], $data['branch_id'], $technician_id, $data['status'], $data['date'], $data['software_version'], $data['ip_address'], $data['notes']);
          $success = $installation->create();

          if ($success) {
            return [
                 "status" => "success",
                "message" => "Installation created successfully"
               
            ];
    }else {
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
      

        if (!empty($missing)) {
            return [
                'message'=> 'Missing required fields: ' . implode(', ', $missing),
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
}
 