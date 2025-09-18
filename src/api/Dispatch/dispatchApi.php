<?php
class dispatchApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_dispatch" => ["admin", "Quality_Checker"],
            "read_dispatch" => ["admin", "Quality_Checker", "user"],
            "update_dispatch" => ["admin", "Quality_Checker"],
            "delete_dispatch" => ["admin"],
            "readAll" => ["admin", "Quality_Checker"],
            "view_my_dispatches" => ["admin", "Quality_Checker", "user"]
        ]);
    }

    public function create_dispatch($data) {
        // Map frontend field names to backend expected names
        if (isset($data['itemName'])) {
            $data['item_name'] = $data['itemName'];
        }
        if (isset($data['qty'])) {
            $data['quantity'] = $data['qty'];
        }
        if (isset($data['warehouse'])) {
            $data['warehouse_name'] = $data['warehouse'];
        }
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        // Debug: log user array (remove in production)
        // error_log('Authenticated user: ' . print_r($user, true));
        
        // Get user ID from authenticated user
        if (isset($user['user_id'])) {
            $qc_officer_id = $user['user_id'];
        } elseif (isset($user['id'])) {
            $qc_officer_id = $user['id'];
        } elseif (isset($user['uid'])) {
            $qc_officer_id = $user['uid'];
        } else {
            return [
                'status' => 'error',
                'message' => 'Unable to determine user ID from authentication token'
            ];
        }
        $data['qc_officer_id'] = $qc_officer_id;
        if (!$this->checkRoles($user['role_name'], 'create_dispatch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or QC Officer access required"
            ];
        }

        // Accept either item_id or item_name
        $required = ['qc_officer_id', 'warehouse_name', 'quantity', 'date', 'purpose'];
        if (!isset($data['item_id']) && !isset($data['item_name'])) {
            $required[] = 'item_id'; // at least one required
        }
        $missing = $this->validateFields($data, $required);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Missing required fields: " . implode(", ", $missing)
            ];
        }

        $item_id = isset($data['item_id']) ? $data['item_id'] : null;
        if (!$item_id && isset($data['item_name'])) {
            require_once(__DIR__ . '/../../classes/Inventory_Item.php');
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT item_id FROM inventory_item WHERE item_name = :item_name";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":item_name", $data['item_name']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['item_id'])) {
                $item_id = $row['item_id'];
            } else {
                return [
                    "status" => "error",
                    "message" => "Item name not found in inventory."
                ];
            }
        }

        // Check current stock quantity before creating dispatch
        $conn = DatabaseConnection::getConnection();
        $stockSql = "SELECT quantity FROM stock WHERE item_id = :item_id";
        $stockStmt = $conn->prepare($stockSql);
        $stockStmt->bindParam(":item_id", $item_id);
        $stockStmt->execute();
        $stockRow = $stockStmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$stockRow) {
            return [
                "status" => "error",
                "message" => "Item not found in stock."
            ];
        }
        
        $currentStock = $stockRow['quantity'];
        $dispatchQuantity = $data['quantity'];
        
        // Check if enough stock is available
        if ($currentStock < $dispatchQuantity) {
            return [
                "status" => "error",
                "message" => "Insufficient stock. Available: {$currentStock}, Requested: {$dispatchQuantity}"
            ];
        }

        // Begin transaction to ensure data consistency
        $conn->beginTransaction();
        
        try {
            // Create the dispatch
            $dispatch = new dispatch(null, $item_id, $data['qc_officer_id'], $data['warehouse_name'], $data['quantity'], $data['date'], $data['purpose'], 'Dispatched');
            $success = $dispatch->create();

            if (!$success) {
                $conn->rollBack();
                return [
                    "status" => "error",
                    "message" => "Database error: Failed to create dispatch."
                ];
            }

            // Update stock quantity (reduce by dispatch quantity)
            $newQuantity = $currentStock - $dispatchQuantity;
            $updateStockSql = "UPDATE stock SET quantity = :new_quantity WHERE item_id = :item_id";
            $updateStockStmt = $conn->prepare($updateStockSql);
            $updateStockStmt->bindParam(":new_quantity", $newQuantity);
            $updateStockStmt->bindParam(":item_id", $item_id);
            $stockUpdateSuccess = $updateStockStmt->execute();

            if (!$stockUpdateSuccess) {
                $conn->rollBack();
                return [
                    "status" => "error",
                    "message" => "Database error: Failed to update stock quantity."
                ];
            }

            // Commit transaction
            $conn->commit();
            
            return [
                "status" => "success",
                "message" => "Dispatch created successfully. Stock updated from {$currentStock} to {$newQuantity}."
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            return [
                "status" => "error",
                "message" => "Database error: " . $e->getMessage()
            ];
        }
    }

    public function read_dispatch($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'read_dispatch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin, QC Officer, or User access required"
            ];
        }

        // Validate required fields
        $missing = $this->validateFields($data, ['dispatch_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Missing required fields: " . implode(", ", $missing)
            ];
        }

        $dispatch = new dispatch($data['dispatch_id']);
        $result = $dispatch->read();

        if ($result) {
            // Ensure all required fields are present in the response
            $fields = ['dispatch_id', 'item_id', 'qc_officer_id', 'warehouse_name', 'quantity', 'date', 'purpose', 'status'];
            $response = [];
            foreach ($fields as $field) {
                $response[$field] = isset($result[$field]) ? $result[$field] : null;
            }
            return [
                "status" => "success",
                "data" => $response
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to retrieve dispatch."
            ];
        }
    }

    public function update_dispatch($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'update_dispatch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or QC Officer access required"
            ];
        }

        // Accept either item_id or item_name for update
        $item_id = isset($data['item_id']) ? $data['item_id'] : null;
        if (!$item_id && isset($data['item_name'])) {
            require_once(__DIR__ . '/../../classes/Inventory_Item.php');
            $conn = DatabaseConnection::getConnection();
            $sql = "SELECT item_id FROM inventory_item WHERE item_name = :item_name";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(":item_name", $data['item_name']);
            $stmt->execute();
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($row && isset($row['item_id'])) {
                $item_id = $row['item_id'];
            } else {
                return [
                    "status" => "error",
                    "message" => "Item name not found in inventory."
                ];
            }
        }

        // Pass all fields to the constructor, matching update_all pattern
        $dispatch = new dispatch(
            $data['dispatch_id'],
            $item_id,
            $data['qc_officer_id'],
            $data['warehouse_name'],
            $data['quantity'],
            $data['date'],
            $data['purpose'],
            $data['status']
        );
        $success = $dispatch->update();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Dispatch updated successfully with all fields."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to update dispatch."
            ];
        }
    }

    public function delete_dispatch($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'delete_dispatch')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }

        // Validate required fields
        $missing = $this->validateFields($data, ['dispatch_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Missing required fields: " . implode(", ", $missing)
            ];
        }

        $dispatch = new dispatch($data['dispatch_id']);
        $success = $dispatch->delete();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Dispatch deleted successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to delete dispatch."
            ];
        }
    }

    public function readAll($data = null) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'readAll')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        // Get pagination parameters with defaults
        $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
        $limit = isset($data['limit']) ? max(1, min(100, intval($data['limit']))) : 10;

        // Use paginated readAll method from the dispatch model
        $result = dispatch::readAllPaginated($page, $limit);

        if ($result['data'] !== false && is_array($result['data'])) {
            return [
                'status' => 'success',
                'message' => 'Dispatches retrieved successfully',
                'data' => $result['data'],
                'count' => count($result['data']),
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages'],
                'pagination' => [
                    'current_page' => $result['page'],
                    'per_page' => $result['limit'],
                    'total' => $result['total'],
                    'total_pages' => $result['total_pages'],
                    'has_next' => $result['page'] < $result['total_pages'],
                    'has_prev' => $result['page'] > 1
                ]
            ];
        } else {
            return [
                'status' => 'success',
                'message' => 'No dispatches found',
                'data' => [],
                'count' => 0,
                'total' => $result['total'],
                'page' => $result['page'],
                'limit' => $result['limit'],
                'total_pages' => $result['total_pages'],
                'pagination' => [
                    'current_page' => $result['page'],
                    'per_page' => $result['limit'],
                    'total' => $result['total'],
                    'total_pages' => $result['total_pages'],
                    'has_next' => false,
                    'has_prev' => false
                ]
            ];
        }
    }

    public function view_my_dispatches($data = null) {
        $user = $this->getAuthenticatedUser();
        if(!$user){
            return [
                "status" => "error",
                "message" => "Invalid or expired token. Please log in again."
            ];
        }
        
        // Check role permissions
        if(!$this->checkRoles($user['role_name'], 'view_my_dispatches')){
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin, Quality Checker, or User access required"
            ];
        }
        
        // Get user ID from token
        $user_id = null;
        if (isset($user['user_id'])) {
            $user_id = $user['user_id'];
        } elseif (isset($user['id'])) {
            $user_id = $user['id'];
        } elseif (isset($user['uid'])) {
            $user_id = $user['uid'];
        } else {
            return [
                'status' => 'error',
                'message' => 'Unable to determine user ID from authentication token'
            ];
        }
        
        // Get pagination parameters with defaults
        $page = isset($data['page']) ? max(1, intval($data['page'])) : 1;
        $limit = isset($data['limit']) ? max(1, min(100, intval($data['limit']))) : 10;
        
        // Get dispatches for this user with pagination
        $result = dispatch::getDispatchesByUserPaginated($user_id, $page, $limit);
        
        if($result['data'] && count($result['data']) > 0){
            return [
                "status" => "success",
                "message" => "Your dispatches retrieved successfully",
                "data" => $result['data'],
                "count" => count($result['data']),
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "user_id" => $user_id,
                "user_info" => [
                    "user_id" => $user_id,
                    "username" => isset($user['username']) ? $user['username'] : 'Unknown',
                    "role" => $user['role_name']
                ],
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => $result['page'] < $result['total_pages'],
                    "has_prev" => $result['page'] > 1
                ]
            ];
        } else {
            return [
                "status" => "success",
                "message" => "No dispatches found for your account",
                "data" => [],
                "count" => 0,
                "total" => $result['total'],
                "page" => $result['page'],
                "limit" => $result['limit'],
                "total_pages" => $result['total_pages'],
                "user_id" => $user_id,
                "user_info" => [
                    "user_id" => $user_id,
                    "username" => isset($user['username']) ? $user['username'] : 'Unknown',
                    "role" => $user['role_name']
                ],
                "pagination" => [
                    "current_page" => $result['page'],
                    "per_page" => $result['limit'],
                    "total" => $result['total'],
                    "total_pages" => $result['total_pages'],
                    "has_next" => false,
                    "has_prev" => false
                ]
            ];
        }
    }
}