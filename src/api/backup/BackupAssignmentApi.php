<?php
require_once 'src/utils/ApiResourceBase.php';
require_once 'src/classes/BackupAssignment.php';

class BackupAssignmentApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_backup_assignment" => ["admin","technician"],
            "get_backup_assignment" => ["admin","technician"],
            "update_backup_assignment" => ["admin","technician"],
            "delete_backup_assignment" => ["admin"],
            "get_all_backup_assignments" => ["admin","technician"],
            "get_assignments_by_backup" => ["admin","technician"],
            "get_assignments_by_repair" => ["admin","technician"],
            "get_assignments_by_location" => ["admin","technician"],
            "get_active_assignments" => ["admin","technician"]
        ]);
    }

    public function create_backup_assignment($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'create_backup_assignment')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['backup_id', 'repair_id', 'location']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Validate location (0 for office, or valid branch_id)
        if ($data['location'] != 0) {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT branch_id FROM branch WHERE branch_id = :branch_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':branch_id', $data['location']);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'message' => 'Invalid location. Must be 0 (office) or a valid branch_id',
                    'status' => 'error'
                ];
            }
        }

        // Validate backup machine status - cannot assign if already in_use
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT status FROM backup_machine WHERE backup_id = :backup_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':backup_id', $data['backup_id']);
        $stmt->execute();
        $backupMachine = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$backupMachine) {
            return [
                'message' => 'Backup machine not found',
                'status' => 'error'
            ];
        }
        
        if ($backupMachine['status'] === 'in_use') {
            return [
                'message' => 'Cannot assign backup machine. Machine is currently in use.',
                'status' => 'error'
            ];
        }

        $backupAssignment = new BackupAssignment();
        $backupAssignment->backup_id = $data['backup_id'];
        $backupAssignment->repair_id = $data['repair_id'];
        $backupAssignment->location = $data['location'];
        $backupAssignment->sent_date = $data['sent_date'] ?? null;
        $backupAssignment->received_date = $data['received_date'] ?? null;

        if ($backupAssignment->create()) {
            // Update backup machine status to 'in_use'
            $sql = "UPDATE backup_machine SET status = 'in_use' WHERE backup_id = :backup_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':backup_id', $data['backup_id']);
            $stmt->execute();
            
            return [
                'message' => 'Backup assignment created successfully',
                'status' => 'success',
                'data' => [
                    'assignment_id' => $backupAssignment->assignment_id,
                    'backup_id' => $backupAssignment->backup_id,
                    'repair_id' => $backupAssignment->repair_id,
                    'location' => $backupAssignment->location,
                    'sent_date' => $backupAssignment->sent_date,
                    'received_date' => $backupAssignment->received_date
                ]
            ];
        } else {
            return [
                'message' => 'Failed to create backup assignment',
                'status' => 'error'
            ];
        }
    }

    public function get_backup_assignment($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_backup_assignment')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['assignment_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $backupAssignment = new BackupAssignment();
        $backupAssignment->assignment_id = $data['assignment_id'];

        if ($backupAssignment->read()) {
            return [
                'message' => 'Backup assignment found',
                'status' => 'success',
                'data' => [
                    'assignment_id' => $backupAssignment->assignment_id,
                    'backup_id' => $backupAssignment->backup_id,
                    'repair_id' => $backupAssignment->repair_id,
                    'location' => $backupAssignment->location,
                    'sent_date' => $backupAssignment->sent_date,
                    'received_date' => $backupAssignment->received_date,
                    'backup_machine' => $backupAssignment->backup_machine,
                    'repair_details' => $backupAssignment->repair_details,
                    'branch_details' => $backupAssignment->branch_details
                ]
            ];
        } else {
            return [
                'message' => 'Backup assignment not found',
                'status' => 'error'
            ];
        }
    }

    public function update_backup_assignment($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'update_backup_assignment')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['assignment_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Check if at least one field to update is provided
        $updatableFields = ['backup_id', 'repair_id', 'location', 'sent_date', 'received_date'];
        $hasUpdateField = false;
        foreach ($updatableFields as $field) {
            if (isset($data[$field])) {
                $hasUpdateField = true;
                break;
            }
        }

        if (!$hasUpdateField) {
            return [
                'message' => 'No fields to update. Provide at least one of: ' . implode(', ', $updatableFields),
                'status' => 'error'
            ];
        }

        // Validate location if provided
        if (isset($data['location']) && $data['location'] != 0) {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT branch_id FROM branch WHERE branch_id = :branch_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':branch_id', $data['location']);
            $stmt->execute();
            if (!$stmt->fetch(PDO::FETCH_ASSOC)) {
                return [
                    'message' => 'Invalid location. Must be 0 (office) or a valid branch_id',
                    'status' => 'error'
                ];
            }
        }

        $backupAssignment = new BackupAssignment();
        $backupAssignment->assignment_id = $data['assignment_id'];

        // First read the existing data
        if (!$backupAssignment->read()) {
            return [
                'message' => 'Backup assignment not found',
                'status' => 'error'
            ];
        }

        // If backup_id is being changed, validate the new backup machine status
        if (isset($data['backup_id']) && $data['backup_id'] != $backupAssignment->backup_id) {
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT status FROM backup_machine WHERE backup_id = :backup_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':backup_id', $data['backup_id']);
            $stmt->execute();
            $newBackupMachine = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$newBackupMachine) {
                return [
                    'message' => 'New backup machine not found',
                    'status' => 'error'
                ];
            }
            
            if ($newBackupMachine['status'] === 'in_use') {
                return [
                    'message' => 'Cannot assign new backup machine. Machine is currently in use.',
                    'status' => 'error'
                ];
            }
        }

        // Store the old backup_id and received_date for status management
        $oldBackupId = $backupAssignment->backup_id;
        $oldReceivedDate = $backupAssignment->received_date;

        // Update fields
        if (isset($data['backup_id'])) {
            $backupAssignment->backup_id = $data['backup_id'];
        }
        if (isset($data['repair_id'])) {
            $backupAssignment->repair_id = $data['repair_id'];
        }
        if (isset($data['location'])) {
            $backupAssignment->location = $data['location'];
        }
        if (isset($data['sent_date'])) {
            $backupAssignment->sent_date = $data['sent_date'];
        }
        if (isset($data['received_date'])) {
            $backupAssignment->received_date = $data['received_date'];
        }

        if ($backupAssignment->update()) {
            $conn = DatabaseConnection::getConnection();
            
            // Handle backup_id change
            if (isset($data['backup_id']) && $data['backup_id'] != $oldBackupId) {
                // Set old backup machine to available if assignment was active
                if ($oldReceivedDate === null) {
                    $sql = "UPDATE backup_machine SET status = 'available' WHERE backup_id = :backup_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(':backup_id', $oldBackupId);
                    $stmt->execute();
                }
                
                // Set new backup machine to in_use
                $sql = "UPDATE backup_machine SET status = 'in_use' WHERE backup_id = :backup_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':backup_id', $data['backup_id']);
                $stmt->execute();
            }
            
            // If received_date is set, update backup machine status to 'available'
            if (isset($data['received_date']) && $data['received_date'] !== null) {
                $sql = "UPDATE backup_machine SET status = 'available' WHERE backup_id = :backup_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':backup_id', $backupAssignment->backup_id);
                $stmt->execute();
            }
            
            return [
                'message' => 'Backup assignment updated successfully',
                'status' => 'success',
                'data' => [
                    'assignment_id' => $backupAssignment->assignment_id,
                    'backup_id' => $backupAssignment->backup_id,
                    'repair_id' => $backupAssignment->repair_id,
                    'location' => $backupAssignment->location,
                    'sent_date' => $backupAssignment->sent_date,
                    'received_date' => $backupAssignment->received_date
                ]
            ];
        } else {
            return [
                'message' => 'Failed to update backup assignment',
                'status' => 'error'
            ];
        }
    }

    public function delete_backup_assignment($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'delete_backup_assignment')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['assignment_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $backupAssignment = new BackupAssignment();
        $backupAssignment->assignment_id = $data['assignment_id'];

        // Get the assignment details before deleting to check if it was active
        $wasActive = false;
        if ($backupAssignment->read()) {
            $wasActive = ($backupAssignment->received_date === null);
        }

        if ($backupAssignment->delete()) {
            // If the assignment was active (no received_date), make the backup machine available again
            if ($wasActive) {
                $conn = DatabaseConnection::getConnection();
                $sql = "UPDATE backup_machine SET status = 'available' WHERE backup_id = :backup_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':backup_id', $backupAssignment->backup_id);
                $stmt->execute();
            }
            
            return [
                'message' => 'Backup assignment deleted successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Backup assignment not found or could not be deleted',
                'status' => 'error'
            ];
        }
    }

    public function get_all_backup_assignments($data = null) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_all_backup_assignments')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $backupAssignments = BackupAssignment::getAll();

        return [
            'message' => 'Backup assignments retrieved successfully',
            'status' => 'success',
            'data' => $backupAssignments
        ];
    }

    public function get_assignments_by_backup($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_assignments_by_backup')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['backup_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $backupAssignments = BackupAssignment::getByBackupId($data['backup_id']);

        return [
            'message' => 'Backup assignments retrieved successfully',
            'status' => 'success',
            'data' => $backupAssignments
        ];
    }

    public function get_assignments_by_repair($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_assignments_by_repair')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['repair_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $backupAssignments = BackupAssignment::getByRepairId($data['repair_id']);

        return [
            'message' => 'Backup assignments retrieved successfully',
            'status' => 'success',
            'data' => $backupAssignments
        ];
    }

    public function get_assignments_by_location($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_assignments_by_location')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['location']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $backupAssignments = BackupAssignment::getByLocation($data['location']);

        return [
            'message' => 'Backup assignments retrieved successfully',
            'status' => 'success',
            'data' => $backupAssignments
        ];
    }

    public function get_active_assignments($data = null) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_active_assignments')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $backupAssignments = BackupAssignment::getActiveAssignments();

        return [
            'message' => 'Active backup assignments retrieved successfully',
            'status' => 'success',
            'data' => $backupAssignments
        ];
    }
}
