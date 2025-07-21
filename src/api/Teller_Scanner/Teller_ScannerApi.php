<?php
require_once 'src/utils/JwtHandler.php';

class Teller_ScannerApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create" => ['admin','technician'],
            "read" => [ 'admin','technician'],
            "readAll" => ['admin','technician'],
            "update" => ['admin','technician'],
            "delete" => ['admin']
        ]);
    }

    public function create($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'create')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }

        $missing = $this->validateFields($data, ['serial_number', 'model', 'status', 'branch_id', 'remarks', 'manufactured_date', 'warranty_expiry']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Use correct parameter order and names for Teller_Scanner constructor
        $scanner = new Teller_Scanner(
            null, // scanner_id (auto-increment)
            $data['serial_number'],
            $data['model'],
            $data['status'],
            $data['branch_id'],
            $data['remarks'],
            $data['manufactured_date'],
            $data['warranty_expiry']
        );

        $success = $scanner->create();
        if ($success) {
            return [
                'message' => 'Teller Scanner created successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to create Teller Scanner',
                'status' => 'error'
            ];
        }
    }

    public function read($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'read')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }

        $missing = $this->validateFields($data, ['scanner_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $teller_scanner = new Teller_Scanner($data['scanner_id']);
        $result = $teller_scanner->read();
        if ($result) {
            return [
                'message' => 'Teller Scanner retrieved successfully',
                'status' => 'success',
                'data' => $result
            ];
        } else {
            return [
                'message' => 'Failed to retrieve Teller Scanner',
                'status' => 'error'
            ];
        }
    }

    public function update($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'update')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }

        $missing = $this->validateFields($data, ['scanner_id', 'serial_number', 'model', 'status', 'branch_id', 'remarks', 'manufactured_date', 'warranty_expiry']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $scanner = new Teller_Scanner(
            $data['scanner_id'],
            $data['serial_number'],
            $data['model'],
            $data['status'],
            $data['branch_id'],
            $data['remarks'],
            $data['manufactured_date'],
            $data['warranty_expiry']
        );

        $success = $scanner->update();
        if ($success) {
            return [
                'message' => 'Teller Scanner updated successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to update Teller Scanner',
                'status' => 'error'
            ];
        }
    }

    public function delete($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'delete')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }

        $missing = $this->validateFields($data, ['scanner_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $scanner = new Teller_Scanner($data['scanner_id']);
$success = $scanner->delete();
            
           

        
        $success = $scanner->delete();
        if ($success) {
            return [
                'message' => 'Teller Scanner deleted successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to delete Teller Scanner',
                'status' => 'error'
            ];
        }
    }

    public function readAll($data = null) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }

        if (!$this->checkRoles($user['role_name'], 'readAll')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }

        // Get all teller scanner records
        $results = Teller_Scanner::readAll();

        if ($results) {
            return [
                'message' => 'All Teller Scanners retrieved successfully',
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ];
        } else {
            return [
                'message' => 'No Teller Scanners found',
                'status' => 'success',
                'data' => [],
                'count' => 0
            ];
        }
    }
}