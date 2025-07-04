<?php
require_once 'src/utils/JwtHandler.php';

class UtilitiesApi extends ApiResourceBase {
    public function __construct() {
        $this->setRoles([
           "create" => [ 'admin','technician', 'Quality Checker'],
           "read" => ['admin', 'technician', 'Quality Checker'],
              "readAll" => ['admin', 'technician', 'Quality Checker'],
           "update" => ['admin', 'technician', 'Quality Checker'],
              "delete" => ['admin']
        ]);
    }

    public function create($data) {
        //pass user to get authenticated user in api resource base
        $user = $this-> getAuthenticatedUser();
        // check the user token is valid or invalid 
        if(!$user){
            return [
                'message' => 'Invalid or expired token. Please log in again.',
                'status' => 'error'
            ];
        }
        // Check if the user has the required role to create a utility
        if(!$this->checkRoles($user['role_name'], 'create')) {
            return [
                'message' => 'Unauthorized:  access required',
                'status' => 'error'
            ];
        }

        //get the missing data 
        $missing = $this -> validateFields($data, ['utility_name', 'category', 'description', 'download_link', 'created_at']);
        // Check if any required fields are missing  
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        // Use correct parameter order and names for Utilities constructor
        $utility = new Utilities(
            null, // utility_id (auto-increment)
            $data['utility_name'],
            $data['description'],
            $data['category'],
            $data['download_link'],
            $data['created_at']
        );
        $success = $utility->create();

        if ($success) {
            return [
                'message' => 'Utility created successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to create Utility',
                'status' => 'error'
            ];
        }
    }

    public function read($data = null) {
        
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

        $missing = $this->validateFields($data, ['utility_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $utility = new Utilities($data['utility_id']);
        $result = $utility->read();
        if ($result) {
            return [
                'message' => 'Utility fetched successfully',
                'status' => 'success',
                'data' => $result
            ];
        } else {
            return [
                'message' => 'Utility not found',
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

        $missing = $this->validateFields($data, ['utility_id', 'utility_name', 'description', 'category', 'download_link', 'created_at']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

        $utility = new Utilities(
            $data['utility_id'],
            $data['utility_name'],
            $data['description'],
            $data['category'],
            $data['download_link'],
            $data['created_at']
        );

        $success = $utility->update();
        if ($success) {
            return [
                'message' => 'Utility updated successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to update Utility',
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

        $missing = $this->validateFields($data, ['utility_id']);
        if (!empty($missing)) {
            return [
                'message' => 'Invalid Request. Missing fields: ' . implode(', ', $missing),
                'status' => 'error'
            ];
        }

       $utility = new Utilities($data['utility_id']);
        $success = $utility->delete();
        if ($success) {
            return [
                'message' => 'Utility deleted successfully',
                'status' => 'success'
            ];
        } else {
            return [
                'message' => 'Failed to delete Utility',
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

        // Get all utilities records
        $results = Utilities::readAll();

        if ($results) {
            return [
                'message' => 'All Utilities retrieved successfully',
                'status' => 'success',
                'data' => $results,
                'count' => count($results)
            ];
        } else {
            return [
                'message' => 'No Utilities found',
                'status' => 'success',
                'data' => [],
                'count' => 0
            ];
        }
    }
}
