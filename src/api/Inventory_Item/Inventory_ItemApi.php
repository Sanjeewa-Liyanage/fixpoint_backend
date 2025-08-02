    
<?php
require_once 'src/utils/JwtHandler.php';

class Inventory_ItemApi extends ApiResourceBase  {
    public function __construct() {
        $this->setRoles([
            "create" => ['admin','technician','Quality Checker'],
            "read" => ['admin', 'technician', 'Quality Checker'],
            "readAll" => ['admin', 'technician', 'Quality Checker'],
            "update" => ['admin', 'technician', 'Quality Checker'],
            "delete" => ['admin'],
            "search" => ['admin', 'technician', 'Quality Checker']
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

        $missing = $this->validateFields($data, ['item_name', 'category', 'description', 'manufacturer', 'created_at']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }
        // Use correct parameter order and names for Inventory_Item constructor

        $inventory_Item = new Inventory_Item(
            null, // item_id (auto-increment)
            $data['item_name'],
            $data['category'],
            $data['description'],
            $data['manufacturer'],
            $data['created_at']
        );

        $success = $inventory_Item->create();
        if ($success) {
            return [
                'message' => 'Inventory Item created successfully',
                'status' => 'success'
            ];
    } else {
        return [
            'message' => 'Failed to create Inventory Item',
            'status' => 'error'
        ];
    }
    
}

public function read($data) {
    // Debug: Check what data is received
    if ($data === null || !is_array($data)) {
        return [
            'message' => 'Invalid or missing JSON body. Please provide item_id.',
            'status' => 'error'
        ];
    }
    
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
    
   $missing = $this->validateFields($data, ['item_id']);
    if (!empty($missing)) {
        return [
            'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
            'status' => 'error'
        ];
    }

    $inventory_Item = new Inventory_Item($data['item_id']);
    $result = $inventory_Item->read();

    if ($result) {
        return [
            'message' => 'Inventory Item retrieved successfully',
            'status' => 'success',
            'data' => $result
        ];
    } else {
        return [
            'message' => 'Inventory Item not found with ID: ' . $data['item_id'],
            'status' => 'error'
        ];
    }
}

public function update($data){
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

        $missing = $this->validateFields($data, [ 'item_name', 'category', 'description', 'manufacturer', 'created_at']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $inventory_Item = new Inventory_Item(
           null, // item_id (auto-increment)
            $data['item_name'],
            $data['category'],
            $data['description'],
            $data['manufacturer'],
            $data['created_at']
        );
        $success = $inventory_Item->update();
        if ($success) {
            return [
                'message' => 'Inventory Item updated successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to update Inventory Item',
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

        $missing = $this->validateFields($data, ['item_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $inventory_Item = new Inventory_Item($data['item_id']);
        $success = $inventory_Item->delete();
        if ($success) {
            return [
                'message' => 'Inventory Item deleted successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to delete Inventory Item',
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

        // Get all inventory items
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM inventory_item ORDER BY item_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
            return [
                'message' => 'All inventory items retrieved successfully',
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ];
        } else {
            return [
                'message' => 'No inventory items found',
                'status' => 'success',
                'data' => [],
                'count' => 0
            ];
        }
    }
    public function search($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'search')) {
            return [
                'message' => 'Unauthorized: Access denied',
                'status' => 'error'
            ];
        }
        if (!isset($data['query']) || empty($data['query'])) {
            return [
                'message' => 'Missing search query.',
                'status' => 'error'
            ];
        }
        $results = Inventory_Item::search($data['query']);
        return [
            'message' => 'Search completed',
            'status' => 'success',
            'data' => $results,
            'count' => count($results)
        ];
    }
}
