<?php
require_once 'src/utils/JwtHandler.php';

class Inventory_ItemApi extends ApiResourceBase  {
    public function __construct() {
        $this->setRoles([
            "create" => ['admin','technician','Quality_Checker'],
            "read" => ['admin', 'technician', 'Quality_Checker'],
            "readAll" => ['admin', 'technician', 'Quality_Checker'],
            "update" => ['admin', 'technician', 'Quality_Checker'],
            "search" => ['admin', 'technician', 'Quality_Checker'],
            "updateAll" => ['admin', 'technician', 'Quality_Checker'],
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

        $missing = $this->validateFields($data, ['item_name', 'category', 'description', 'manufacturer', 'created_at']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $inventory_Item = new Inventory_Item(
            null,
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

        $missing = $this->validateFields($data, ['item_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $inventory_Item = new Inventory_Item($data['item_id']);
        $existing = $inventory_Item->read();
        if (!$existing) {
            return [
                'message' => 'Inventory Item not found with ID: ' . $data['item_id'],
                'status' => 'error'
            ];
        }

        $item_name = isset($data['item_name']) ? $data['item_name'] : $existing['item_name'];
        $category = isset($data['category']) ? $data['category'] : $existing['category'];
        $description = isset($data['description']) ? $data['description'] : $existing['description'];
        $manufacturer = isset($data['manufacturer']) ? $data['manufacturer'] : $existing['manufacturer'];
        $created_at = isset($data['created_at']) ? $data['created_at'] : $existing['created_at'];

        $inventory_Item = new Inventory_Item(
            $data['item_id'],
            $item_name,
            $category,
            $description,
            $manufacturer,
            $created_at
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

    public function updateAll($data = null) {
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

        if (!isset($data['items']) || !is_array($data['items'])) {
            return [
                'message' => 'Invalid data format. Expected an array of items under the "items" key.',
                'status' => 'error'
            ];
        }

        $conn = DatabaseConnection::getConnection();
        $updatedCount = 0;

        foreach ($data['items'] as $item) {
            if (!isset($item['item_id'])) {
                continue;
            }

            $sql = "UPDATE inventory_item SET 
                item_name = :item_name,
                category = :category,
                description = :description,
                manufacturer = :manufacturer,
                created_at = :created_at
                WHERE item_id = :item_id";

            $stmt = $conn->prepare($sql);
            $params = [
                ':item_id' => $item['item_id'],
                ':item_name' => $item['item_name'] ?? null,
                ':category' => $item['category'] ?? null,
                ':description' => $item['description'] ?? null,
                ':manufacturer' => $item['manufacturer'] ?? null,
                ':created_at' => $item['created_at'] ?? null
            ];

            error_log("Attempting update for item_id: " . $item['item_id'] . " with data: " . json_encode($params));
            $success = $stmt->execute($params);

            if (!$success) {
                error_log("Update failed for item_id: " . $item['item_id'] . " SQL error: " . implode(" | ", $stmt->errorInfo()));
            }

            if ($success && $stmt->rowCount() > 0) {
                $updatedCount++;
            }
        }

        return [
            'message' => "$updatedCount inventory item(s) updated successfully",
            'status' => 'success',
            'updated_count' => $updatedCount
        ];
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
