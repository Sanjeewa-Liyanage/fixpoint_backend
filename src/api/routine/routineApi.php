<?php 
class RoutineApi extends ApiResourceBase{

   public function __construct() {
        $this->setRoles([
            'get' => ['admin', 'technician', ],
            'read' => ['admin', 'technician', ],
            'update' => ['admin', ],
            'technicianCount' => ['admin', 'technician'],
            'getAll' => ['admin', 'technician'],
            'delete' => ['admin'],
            'getClusters' => ['admin', 'technician'],
            'assignTechnician' => ['admin'],
            'getTechnicianClusters' => ['admin', 'technician'],
        ]);
    }
    public function get($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'get')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['branch_id','radius','quarter']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT latitude, longitude FROM branch WHERE branch_id = :branch_id";
        $stmt = $conn->prepare($sql);
        $stmt->bindValue(':branch_id', $data['branch_id']);
        $stmt->execute();
        $branch = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$branch) {
            return ['status' => 'error', 'message' => 'Branch not found'];
        }


        $lat = $branch['latitude'];
        $lng = $branch['longitude'];
        $radius_km = isset($data['radius']) ? floatval($data['radius']) : 10;
        $quarter = $data['quarter'];

        // Get all branches that already have service done for this quarter
        $excludeSql = "SELECT DISTINCT branch_id FROM service WHERE quarter = :quarter";
        $excludeStmt = $conn->prepare($excludeSql);
        $excludeStmt->bindValue(':quarter', $quarter);
        $excludeStmt->execute();
        $excludedBranches = $excludeStmt->fetchAll(PDO::FETCH_COLUMN, 0);

        // Get nearby branches
        $nearbyBranches = Routine::getNearby($lat, $lng, $radius_km);

        // Exclude branches that already have service for this quarter
        $result = array_values(array_filter($nearbyBranches, function($branch) use ($excludedBranches) {
            return !in_array($branch['branch_id'], $excludedBranches);
        }));

        try {
            $planned_date = isset($data['planned_date']) ? $data['planned_date'] : null;
            $status = "pending"; 
            $description = isset($data['description']) ? $data['description'] : null;
            $response_count = count($result);
            $branches = $result;

            $insertSql = "INSERT INTO routines (planned_date, status, description, response_count, branches, quarter) VALUES (:planned_date, :status, :description, :response_count, :branches, :quarter)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bindValue(':planned_date', $planned_date);
            $insertStmt->bindValue(':status', $status);
            $insertStmt->bindValue(':description', $description);
            $insertStmt->bindValue(':response_count', $response_count);
            $insertStmt->bindValue(':branches', json_encode($branches));
            $insertStmt->bindValue(':quarter', $quarter);
            $insertStmt->execute();
        } catch (Exception $e) {
            return ['status' => 'error', 'message' => 'Database error: ' . $e->getMessage()];
        }

        return [
            'status' => 'success',
            'count' => count($result),
            'branches' => $result
        ];
    }

    public function read($data){
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'read')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['routine_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $routine = new Routine($data['routine_id']);
        if (!$routine->read()) {
            return ['status' => 'error', 'message' => 'Routine not found'];
        }

        return [
            'status' => 'success',
            'routine' => $routine
        ];
    }
    public function update($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'update')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['routine_id', 'status']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $routine = new Routine($data['routine_id']);
        if (!$routine->read()) {
            return ['status' => 'error', 'message' => 'Routine not found'];
        }

        $routine->status = $data['status'];
        if ($routine->update()) {
            return ['status' => 'success', 'message' => 'Routine updated successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to update routine'];
        }
    }
    public function technicianCount($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'technicianCount')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }
        $missing = $this->validateFields($data, ['routine_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }
        $routine = new Routine($data['routine_id']);
        if (!$routine->read()) {
            return ['status' => 'error', 'message' => 'Routine not found'];
        }
        $count = $routine->decideTechnicianCount();
        
        // Save the clusters data to the database
        $saved = ClusterTechnician::saveClusters($data['routine_id'], $count);
        
        if (!$saved) {
            return [
                'status' => 'error',
                'message' => 'Failed to save cluster data',
                'technician_count' => $count
            ];
        }
        
        return [
            'status' => 'success',
            'message' => 'Technician count calculated and clusters saved successfully',
            'technician_count' => $count
        ];
    }
    public function getAll($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'getAll')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $routines = Routine::getAllRoutines();
        return [
            'status' => 'success',
            'routines' => $routines
        ];
    }
    public function delete($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'delete')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['routine_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $routine = new Routine($data['routine_id']);
        if (!$routine->read()) {
            return ['status' => 'error', 'message' => 'Routine not found'];
        }

        if ($routine->deleteRoutine()) {
            return ['status' => 'success', 'message' => 'Routine deleted successfully'];
        } else {
            return ['status' => 'error', 'message' => 'Failed to delete routine'];
        }
    }
    
    public function getClusters($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'read')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['routine_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $clusters = ClusterTechnician::getClustersByRoutineId($data['routine_id']);
        
        if ($clusters === false) {
            return ['status' => 'error', 'message' => 'No clusters found for this routine or an error occurred'];
        }

        return [
            'status' => 'success',
            'clusters' => $clusters
        ];
    }
    
    public function assignTechnician($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'assignTechnician')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['cluster_id', 'user_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $success = ClusterTechnician::assignTechnician($data['cluster_id'], $data['user_id']);
        
        if (!$success) {
            return ['status' => 'error', 'message' => 'Failed to assign technician to cluster'];
        }

        return [
            'status' => 'success',
            'message' => 'Technician assigned to cluster successfully'
        ];
    }
    
    public function getTechnicianClusters($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }
        
        // If user is a technician, they can only see their own clusters
        if ($user['role_name'] === 'technician') {
            $userId = isset($user['user_id']) ? $user['user_id'] : (isset($user['id']) ? $user['id'] : null);
            if (!$userId) {
                return ['status' => 'error', 'message' => 'User ID not found in authentication token'];
            }
            $data['user_id'] = $userId;
        } else if (!$this->checkRoles($user['role_name'], 'getTechnicianClusters')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['user_id']);
        if (!empty($missing)) {
            return ['status' => 'error', 'message' => 'Missing: ' . implode(', ', $missing)];
        }

        $clusters = ClusterTechnician::getClustersByTechnician($data['user_id']);
        
        if ($clusters === false) {
            return [
                'status' => 'success', 
                'message' => 'No clusters found for this technician',
                'clusters' => []
            ];
        }

        return [
            'status' => 'success',
            'clusters' => $clusters
        ];
    }
}