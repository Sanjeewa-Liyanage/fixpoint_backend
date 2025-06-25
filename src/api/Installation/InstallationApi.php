<?php
 class InstallationApi extends ApiResourceBase {

    public function __construct() {
        $this->setRoles([
            "create_installation" => ["admin", "technician"],
            "view_complete_installations" => ["admin", "technician"],
            "view_pending_installations" => ["admin", "technician"],
            "update_installation" => ["admin", "technician"],
            "delete_installation" => ["admin"]
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

        $missing = $this->validateFields($data, ["chdm_id", "branch_id", "technician_id", "status", "date", "software_version", "ip_address","notes"]);
        if (!empty($missing)) {
            return [
                "message" => "Missing required fields: " . implode(", ", $missing),
                 "status" => "error"
            ];
        }

        $installation = new Installation(null, $data['chdm_id'], $data['branch_id'], $data['technician_id'], $data['status'], $data['date'], $data['software_version'], $data['ip_address'], $data['notes']);
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
    public function view_complete_installations($data) {
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
         $missing = $this->validateFields($data, [ 'status']);

        if (!empty($missing)) {
            return [
                'message'=> 'Missing required fields: ' . implode(', ', $missing),
                'status'=> 'error'
            ];
        }
        $installation = new Installation();
        $result = $installation->read();
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
    public function view_pending_installations($data) {
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
          $missing = $this->validateFields($data, [ 'status']);

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
    public function update_installation($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message'=> 'Invalid or expired token. Please log in again.',
                'status'=> 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'],'update_installation')) {
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
        $success = $installation->update($data['status']);
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

}
 