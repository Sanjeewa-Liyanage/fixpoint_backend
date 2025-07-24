<?php 
class ClientApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
            "create_client" => ["admin"],
            "read_client" => ["admin", "technician"],
            "update_client" => ["admin"],
            "delete_client" => ["admin"]
        ]);
    }

    public function create_client($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'create_client')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['name', 'contact_info']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Missing required fields: " . implode(", ", $missing)
            ];
        }

        $client = new Client(null, $data['name'], $data['contact_info'], $data['created_at'] );
        $success = $client->create();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Client created successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to create client."
            ];
        }
    }
public function read_client($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'read_client')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin or Technician access required"
            ];
        }

        $client = new Client();
        $clientData = $client->read();

        if ($clientData) {
            return [
                "status" => "success",
                "data" => $clientData
            ];
        } else {
            return [
                "status" => "error",
                "message" => "No clients found"
            ];
        }
    }

    public function update_client($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'update_client')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['client_id', 'name', 'contact_info']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Missing required fields: " . implode(", ", $missing)
            ];
        }

        $client = new Client($data['client_id'], $data['name'], $data['contact_info']);
        $success = $client->update();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Client updated successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to update client."
            ];
        }
    }

    public function delete_client($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return [
                "status" => "error",
                "message" => "Invalid authentication token"
            ];
        }
        if (!$this->checkRoles($user['role_name'], 'delete_client')) {
            return [
                "status" => "error",
                "message" => "Unauthorized: Admin access required"
            ];
        }
        $missing = $this->validateFields($data, ['client_id']);
        if (!empty($missing)) {
            return [
                "status" => "error",
                "message" => "Missing required fields: " . implode(", ", $missing)
            ];
        }

        $client = new Client($data['client_id']);
        $success = $client->delete();

        if ($success) {
            return [
                "status" => "success",
                "message" => "Client deleted successfully."
            ];
        } else {
            return [
                "status" => "error",
                "message" => "Database error: Failed to delete client."
            ];
        }
    }
}