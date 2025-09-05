<?php
require_once 'src/utils/ApiResourceBase.php';
require_once 'src/classes/BackupMachine.php';

class BackupMachineApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_backup_machine" => ["admin","technician"],
            "get_backup_machine" => ["admin","technician"],
            "update_backup_machine" => ["admin","technician"],
            "delete_backup_machine" => ["admin"],
            "get_all_backup_machines" => ["admin","technician"],
            "get_backup_machines_by_status" => ["admin","technician"],
            "search_backup_machines" => ["admin","technician"]
        ]);
    }

    public function create_backup_machine($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'create_backup_machine')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['serial_no', 'model', 'status']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Validate status
        $validStatuses = ['available', 'in_use', 'maintenance', 'retired'];
        if (!in_array($data['status'], $validStatuses)) {
            return [
                'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses),
                'status' => 'error'
            ];
        }

        $backupMachine = new BackupMachine();
        $backupMachine->serial_no = $data['serial_no'];
        $backupMachine->model = $data['model'];
        $backupMachine->status = $data['status'];

        if ($backupMachine->create()) {
            return [
                'message' => 'Backup machine created successfully',
                'status' => 'success',
                'data' => [
                    'backup_id' => $backupMachine->backup_id,
                    'serial_no' => $backupMachine->serial_no,
                    'model' => $backupMachine->model,
                    'status' => $backupMachine->status
                ]
            ];
        } else {
            return [
                'message' => 'Failed to create backup machine',
                'status' => 'error'
            ];
        }
    }

    public function get_backup_machine($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_backup_machine')) {
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

        $backupMachine = new BackupMachine();
        $backupMachine->backup_id = $data['backup_id'];

        if ($backupMachine->read()) {
            return [
                'message' => 'Backup machine found',
                'status' => 'success',
                'data' => [
                    'backup_id' => $backupMachine->backup_id,
                    'serial_no' => $backupMachine->serial_no,
                    'model' => $backupMachine->model,
                    'status' => $backupMachine->status
                ]
            ];
        } else {
            return [
                'message' => 'Backup machine not found',
                'status' => 'error'
            ];
        }
    }

    public function update_backup_machine($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'update_backup_machine')) {
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

        // Check if at least one field to update is provided
        $updatableFields = ['serial_no', 'model', 'status'];
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

        // Validate status if provided
        if (isset($data['status'])) {
            $validStatuses = ['available', 'in_use', 'maintenance', 'retired'];
            if (!in_array($data['status'], $validStatuses)) {
                return [
                    'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses),
                    'status' => 'error'
                ];
            }
        }

        $backupMachine = new BackupMachine();
        $backupMachine->backup_id = $data['backup_id'];

        // First read the existing data
        if (!$backupMachine->read()) {
            return [
                'message' => 'Backup machine not found',
                'status' => 'error'
            ];
        }

        // Update fields
        if (isset($data['serial_no'])) {
            $backupMachine->serial_no = $data['serial_no'];
        }
        if (isset($data['model'])) {
            $backupMachine->model = $data['model'];
        }
        if (isset($data['status'])) {
            $backupMachine->status = $data['status'];
        }

        if ($backupMachine->update()) {
            return [
                'message' => 'Backup machine updated successfully',
                'status' => 'success',
                'data' => [
                    'backup_id' => $backupMachine->backup_id,
                    'serial_no' => $backupMachine->serial_no,
                    'model' => $backupMachine->model,
                    'status' => $backupMachine->status
                ]
            ];
        } else {
            return [
                'message' => 'Failed to update backup machine',
                'status' => 'error'
            ];
        }
    }

    public function delete_backup_machine($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'delete_backup_machine')) {
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

        $backupMachine = new BackupMachine();
        $backupMachine->backup_id = $data['backup_id'];

        if ($backupMachine->delete()) {
            return [
                'message' => 'Backup machine deleted successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Backup machine not found or could not be deleted',
                'status' => 'error'
            ];
        }
    }

    public function get_all_backup_machines($data = null) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_all_backup_machines')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $backupMachines = BackupMachine::getAll();
        
        return [
            'message' => 'Backup machines retrieved successfully',
            'status' => 'success',
            'data' => $backupMachines
        ];
    }

    public function get_backup_machines_by_status($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'get_backup_machines_by_status')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['status']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Validate status
        $validStatuses = ['available', 'in_use', 'maintenance', 'retired'];
        if (!in_array($data['status'], $validStatuses)) {
            return [
                'message' => 'Invalid status. Must be one of: ' . implode(', ', $validStatuses),
                'status' => 'error'
            ];
        }

        $backupMachines = BackupMachine::getByStatus($data['status']);
        
        return [
            'message' => 'Backup machines retrieved successfully',
            'status' => 'success',
            'data' => $backupMachines
        ];
    }

    public function search_backup_machines($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Authentication required',
                'status' => 'error'
            ];
        }
        if(!$this->checkRoles($user['role_name'],'search_backup_machines')) {
            return [
                'message' => 'Unauthorized',
                'status' => 'error'
            ];
        }
        $missing = $this->validateFields($data, ['serial_no']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $backupMachines = BackupMachine::searchBySerialNo($data['serial_no']);
        
        if (!empty($backupMachines)) {
            return [
                'message' => 'Backup machines found',
                'status' => 'success',
                'data' => $backupMachines
            ];
        } else {
            return [
                'message' => 'No backup machines found matching the search criteria',
                'status' => 'success',
                'data' => []
            ];
        }
    }
}
