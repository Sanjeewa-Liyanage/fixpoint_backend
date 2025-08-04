<?php
require_once 'src/utils/JwtHandler.php';

class Teller_ScannerApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create" => ['admin','technician'],
            "read" => [ 'admin','technician'],
            "readAll" => ['admin','technician'],
            "update" => ['admin','technician'],
            "updateAll" => ['admin','technician'],
            "delete" => ['admin'],
            "searchTellerScanner" => ['admin', 'technician']

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

    public function searchTellerScanner($data) {
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

        $missing = $this->validateFields($data, ['keyword']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $results = Teller_Scanner::searchTellerScanner($data['keyword']);
        if ($results) {
            return [
                'message' => 'Search results retrieved successfully',
                'status' => 'success',
                'data' => $results
            ];
        } else {
            return [
                'message' => 'No results found for the given keyword',
                'status' => 'success',
                'data' => []
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
            $conn = DatabaseConnection::getConnection();
            foreach ($results as &$scanner) {
                if (isset($scanner['branch_id'])) {
                    $sql = "SELECT * FROM branch WHERE branch_id = :branch_id";
                    $stmt = $conn->prepare($sql);
                    $stmt->bindParam(":branch_id", $scanner['branch_id']);
                    $stmt->execute();
                    $branch = $stmt->fetch(PDO::FETCH_ASSOC);
                    $scanner['branch'] = $branch ? $branch : null;
                } else {
                    $scanner['branch'] = null;
                }
            }
            unset($scanner);
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
    
    public function updateAll($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }

        if (!$this->checkRoles($user['role_name'], 'updateAll')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }

        // Accept single object or array of objects
        if (is_array($data) && isset($data['scanner_id'])) {
            // Single object as associative array
            $data = [$data];
        } elseif (!is_array($data)) {
            // Not an array or object
            return [
                'message' => 'Invalid input: data must be an array of records or a single object',
                'status' => 'error',
                'results' => []
            ];
        } elseif (array_keys($data) === range(0, count($data) - 1)) {
            // Array of objects
            // Do nothing
        } elseif (isset($data['scanner_id'])) {
            // Single object as associative array
            $data = [$data];
        } else {
            return [
                'message' => 'Invalid input: data must be an array of records or a single object with scanner_id',
                'status' => 'error',
                'results' => []
            ];
        }

        $results = [];
        foreach ($data as $record) {
            if (!is_array($record) || !isset($record['scanner_id'])) {
                $results[] = [
                    'scanner_id' => isset($record['scanner_id']) ? $record['scanner_id'] : null,
                    'status' => 'error',
                    'message' => 'Missing scanner_id or invalid record format',
                    'updated_data' => $record
                ];
                continue;
            }
            // Always use the instance update method for partial updates
            $scanner = new Teller_Scanner(
                $record['scanner_id'],
                isset($record['serial_number']) ? $record['serial_number'] : null,
                isset($record['model']) ? $record['model'] : null,
                isset($record['status']) ? $record['status'] : null,
                isset($record['branch_id']) ? $record['branch_id'] : null,
                isset($record['remarks']) ? $record['remarks'] : null,
                isset($record['manufactured_date']) ? $record['manufactured_date'] : null,
                isset($record['warranty_expiry']) ? $record['warranty_expiry'] : null
            );
            $success = $scanner->update();
            // If branch_name is provided and branch_id is set, update the branch name in the branch table
            if ($success && isset($record['branch_id']) && isset($record['branch_name']) && !empty(trim($record['branch_name']))) {
                $conn = DatabaseConnection::getConnection();
                $sql = "UPDATE branch SET branch_name = :branch_name WHERE branch_id = :branch_id";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(":branch_name", $record['branch_name']);
                $stmt->bindParam(":branch_id", $record['branch_id']);
                $stmt->execute();
            }
            // Fetch the updated record from the database if update was successful
            $updatedData = $record;
            if ($success) {
                $updatedScanner = new Teller_Scanner($record['scanner_id']);
                $updated = $updatedScanner->read();
                if ($updated) {
                    // Add branch info (including branch name) if branch_id exists
                    if (isset($updated['branch_id'])) {
                        $conn = DatabaseConnection::getConnection();
                        $sql = "SELECT * FROM branch WHERE branch_id = :branch_id";
                        $stmt = $conn->prepare($sql);
                        $stmt->bindParam(":branch_id", $updated['branch_id']);
                        $stmt->execute();
                        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
                        $updated['branch'] = $branch ? $branch : null;
                        $updated['branch_name'] = $branch && isset($branch['branch_name']) ? $branch['branch_name'] : null;
                    } else {
                        $updated['branch'] = null;
                        $updated['branch_name'] = null;
                    }
                    $updatedData = $updated;
                }
            }
            $results[] = [
                'scanner_id' => $record['scanner_id'],
                'status' => $success ? 'success' : 'error',
                'message' => $success ? 'Updated successfully' : 'Update failed',
                'updated_data' => $updatedData
            ];
        }

        $successCount = count(array_filter($results, function($r) { return $r['status'] === 'success'; }));
        $errorCount = count($results) - $successCount;
        return [
            'message' => $errorCount === 0 ? 'All Teller Scanners updated successfully' : 'Update all operation completed with some errors.',
            'status' => $errorCount === 0 ? 'success' : ($successCount > 0 ? 'partial' : 'error'),
            'results' => $results
        ];
    }
}