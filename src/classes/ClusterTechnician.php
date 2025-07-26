<?php

class ClusterTechnician extends Model {
    public $cluster_id;
    public $user_id;
    public $routine_id;
    public $date;
    public $cluster_branches;

    public function __construct($cluster_id = null, $user_id = null, $routine_id = null, $date = null, $cluster_branches = []) {
        $this->cluster_id = $cluster_id;
        $this->user_id = $user_id;
        $this->routine_id = $routine_id;
        $this->date = $date;
        $this->cluster_branches = $cluster_branches;
    }

    /**
     * Create a new cluster technician record
     * @return bool Success status
     */
    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO technician_cluster (user_id, routine_id, date, cluster_branches) 
                VALUES (:user_id, :routine_id, :date, :cluster_branches)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':routine_id', $this->routine_id);
        $stmt->bindParam(':date', $this->date);
        
        // Convert cluster_branches array to JSON for storage
        $branchesJson = json_encode($this->cluster_branches);
        $stmt->bindParam(':cluster_branches', $branchesJson);
        
        $result = $stmt->execute();
        
        if ($result) {
            $this->cluster_id = $conn->lastInsertId();
            return true;
        }
        
        return false;
    }

    /**
     * Read a cluster technician record
     * @return bool Success status
     */
    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM technician_cluster WHERE cluster_id = :cluster_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cluster_id', $this->cluster_id);
        $stmt->execute();
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($result) {
            $this->user_id = $result['user_id'];
            $this->routine_id = $result['routine_id'];
            $this->date = $result['date'];
            $this->cluster_branches = json_decode($result['cluster_branches'], true);
            return true;
        }
        
        return false;
    }

    /**
     * Update a cluster technician record
     * @return bool Success status
     */
    public function update() {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE technician_cluster 
                SET user_id = :user_id, 
                    routine_id = :routine_id, 
                    date = :date, 
                    cluster_branches = :cluster_branches 
                WHERE cluster_id = :cluster_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cluster_id', $this->cluster_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':routine_id', $this->routine_id);
        $stmt->bindParam(':date', $this->date);
        
        // Convert cluster_branches array to JSON for storage
        $branchesJson = json_encode($this->cluster_branches);
        $stmt->bindParam(':cluster_branches', $branchesJson);
        
        return $stmt->execute();
    }

    /**
     * Delete a cluster technician record
     * @return bool Success status
     */
    public function delete() {
        $conn = DatabaseConnection::getConnection();
        $sql = "DELETE FROM technician_cluster WHERE cluster_id = :cluster_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cluster_id', $this->cluster_id);
        
        return $stmt->execute();
    }

    /**
     * Save multiple clusters for a routine
     * @param int $routineId The routine ID
     * @param array $clustersData Array containing technician count and clusters data
     * @return mixed Success status (true) or error array
     */
    public static function saveClusters($routineId, $clustersData) {
        if (!isset($clustersData['clusters']) || !is_array($clustersData['clusters'])) {
            return [
                'error' => true,
                'message' => 'Invalid clusters data format'
            ];
        }

        $conn = DatabaseConnection::getConnection();
        
        // Check if routine already has clusters
        $checkSql = "SELECT COUNT(*) FROM technician_cluster WHERE routine_id = :routine_id";
        $checkStmt = $conn->prepare($checkSql);
        $checkStmt->bindParam(':routine_id', $routineId);
        $checkStmt->execute();
        $count = $checkStmt->fetchColumn();
        
        if ($count > 0) {
            return [
                'error' => true,
                'message' => 'This routine is already clustered'
            ];
        }
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Insert each cluster
            $currentDate = date('Y-m-d');
            foreach ($clustersData['clusters'] as $technicianLabel => $clusterInfo) {
                if (isset($clusterInfo['branches']) && is_array($clusterInfo['branches'])) {
                    $cluster = new ClusterTechnician(
                        null,
                        null, // user_id is null initially until assigned to a technician
                        $routineId,
                        $currentDate,
                        $clusterInfo['branches']
                    );
                    
                    if (!$cluster->create()) {
                        // If any insertion fails, rollback the transaction
                        $conn->rollBack();
                        return [
                            'error' => true,
                            'message' => 'Failed to create cluster'
                        ];
                    }
                }
            }
            
            // Commit the transaction
            $conn->commit();
            return true;
            
        } catch (PDOException $e) {
            // Rollback the transaction if an error occurs
            $conn->rollBack();
            // Log the error (you might want to implement a better logging mechanism)
            error_log("Error saving clusters: " . $e->getMessage());
            return [
                'error' => true,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Get all clusters for a specific routine
     * @param int $routineId The routine ID
     * @return array|bool Array of clusters or false on failure
     */
    public static function getClustersByRoutineId($routineId) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT tc.*, u.username as username, u.email 
                FROM technician_cluster tc 
                LEFT JOIN users u ON tc.user_id = u.user_id 
                WHERE tc.routine_id = :routine_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':routine_id', $routineId);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            $clusters = [];
            foreach ($results as $row) {
                $clusters[] = [
                    'cluster_id' => $row['cluster_id'],
                    'user_id' => $row['user_id'],
                    'username' => $row['username'] ?? 'Unassigned',
                    'email' => $row['email'] ?? '',
                    'routine_id' => $row['routine_id'],
                    'date' => $row['date'],
                    'cluster_branches' => json_decode($row['cluster_branches'], true)
                ];
            }
            return $clusters;
        }
        
        return false;
    }
    
    /**
     * Assign a technician to a cluster
     * @param int $clusterId The cluster ID
     * @param int $userId The user/technician ID
     * @return bool Success status
     */
    public static function assignTechnician($clusterId, $userId) {
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE technician_cluster SET user_id = :user_id WHERE cluster_id = :cluster_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cluster_id', $clusterId);
        $stmt->bindParam(':user_id', $userId);
        
        return $stmt->execute();
    }
    
    /**
     * Get clusters assigned to a specific technician
     * @param int $userId The user/technician ID
     * @return array|bool Array of clusters or false on failure
     */
    public static function getClustersByTechnician($userId) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT tc.*, u.username as username, u.email 
                FROM technician_cluster tc 
                LEFT JOIN users u ON tc.user_id = u.user_id 
                WHERE tc.user_id = :user_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            $clusters = [];
            foreach ($results as $row) {
                $clusters[] = [
                    'cluster_id' => $row['cluster_id'],
                    'user_id' => $row['user_id'],
                    'username' => $row['username'] ?? 'Unassigned',
                    'email' => $row['email'] ?? '',
                    'routine_id' => $row['routine_id'],
                    'date' => $row['date'],
                    'cluster_branches' => json_decode($row['cluster_branches'], true)
                ];
            }
            return $clusters;
        }
        
        return false;
    }
}
