<?php
class dispatchApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_dispatch" => ["admin", "Quality_Checker"],
            "read_dispatch" => ["admin", "Quality_Checker", "user"],
            "update_dispatch" => ["admin", "Quality_Checker"],
            "delete_dispatch" => ["admin"],
            "readAll" => ["admin", "Quality_Checker", ]
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
        // Try to get user id from possible keys
        $user_id = isset($user['user_id']) ? $user['user_id'] : (isset($user['id']) ? $user['id'] : (isset($user['userId']) ? $user['userId'] : null));
        if (!$user_id) {
            return [
                "status" => "error",
                "message" => "Authenticated user does not have a user_id field"
            ];
        }
        $data['qc_officer_id'] = $user_id;
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

        $dispatch = new dispatch(null, $item_id, $data['qc_officer_id'], $data['warehouse_name'], $data['quantity'], $data['date'], $data['purpose'], 'Dispatched');
        $success = $dispatch->create();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Dispatch created successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to create dispatch."
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

    public function readAll() {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'readAll')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        // Call the static readAll method from the dispatch model.
        // This now returns an array with 'qc_officer_name' instead of 'qc_officer_id'.
        $dispatches = dispatch::readAll();

        // The model already returns a clean array. We can just return it directly.
        // The previous normalization loop was the source of the error.
        if ($dispatches !== false) { // PDO::fetchAll returns false on failure
            return [
                'status' => 'success',
                'data' => $dispatches
            ];
        } else {
            return ['status' => 'error', 'message' => 'Failed to retrieve dispatches or none found.'];
        }
    }
}