<?php
require_once 'src/utils/JwtHandler.php';

class StockApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create" => ['admin', 'technician', 'Quality Checker'],
            "read" => ['admin', 'technician', 'Quality Checker'],
            "readAll" => ['admin', 'technician', 'Quality Checker'],
            "update" => ['admin', 'technician', 'Quality Checker'],
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

        $missing = $this->validateFields($data, ['item_id', 'quantity', 'location', 'min_threshold', 'last_updated']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Check if item_id exists in inventory_item
        $conn = DatabaseConnection::getConnection();
        $stmt = $conn->prepare("SELECT 1 FROM inventory_item WHERE item_id = :item_id");
        $stmt->bindParam(":item_id", $data['item_id']);
        $stmt->execute();
        if (!$stmt->fetch()) {
            return [
                'message' => 'Invalid item_id: No such item in inventory_item table.',
                'status' => 'error'
            ];
        }

        // Use correct parameter order and names for Stock constructor
        $stock = new Stock(
            null, // stock_id (auto-increment)
            $data['item_id'],
            $data['quantity'],
            $data['location'],
            $data['min_threshold'],
            $data['last_updated']
        );

        $success = $stock->create();
        if ($success) {
            return [
                'message' => 'Stock created successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to create Stock',
                'status' => 'error'
            ];
        }
    }

    public function read($data) {
        // Check if $data is null or not an array
        if ($data === null || !is_array($data)) {
            return [
                'message' => 'Invalid or missing JSON body. Please provide stock_id.',
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

        $missing = $this->validateFields($data, ['stock_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $stock = new Stock($data['stock_id']);
        $result = $stock->read();
        if ($result) {
            return [
                'message' => 'Stock retrieved successfully',
                'status' => 'success',
                'data' => $result
            ];
        } else {
            return [
                'message' => 'Failed to retrieve Stock',
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

        $missing = $this->validateFields($data, ['stock_id', 'item_id', 'quantity', 'location', 'min_threshold', 'last_updated']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $stock = new Stock(
            null, // stock_id will be set in the constructor
            $data['item_id'],
            $data['quantity'],
            $data['location'],
            $data['min_threshold'],
            $data['last_updated']
        );

        $success = $stock->update();
        if ($success) {
            return [
                'message' => 'Stock updated successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to update Stock',
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

        $missing = $this->validateFields($data, ['stock_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Only use stock_id for delete
        $stock = new Stock($data['stock_id']);
        $success = $stock->delete();
        if ($success) {
            return [
                'message' => 'Stock deleted successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to delete Stock',
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

        // Get all stock items
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM stock ORDER BY stock_id";
        $stmt = $conn->prepare($sql);
        $stmt->execute();
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if ($results) {
            return [
                'message' => 'All stock items retrieved successfully',
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ];
        } else {
            return [
                'message' => 'No stock items found',
                'status' => 'success',
                'data' => [],
                'count' => 0
            ];
        }
    }

}