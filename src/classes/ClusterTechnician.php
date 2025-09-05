<?php

class ClusterTechnician extends Model {
    public $cluster_id;
    public $user_id;
    public $routine_id;
    public $date;
    public $quarter;
    public $cluster_branches;

    public function __construct($cluster_id = null, $user_id = null, $routine_id = null, $date = null, $quarter = null, $cluster_branches = []) {
        $this->cluster_id = $cluster_id;
        $this->user_id = $user_id;
        $this->routine_id = $routine_id;
        $this->date = $date;
        $this->quarter = $quarter;
        $this->cluster_branches = $cluster_branches;
    }

    /**
     * Create a new cluster technician record
     * @return bool Success status
     */
    public function create() {
        $conn = DatabaseConnection::getConnection();
        $sql = "INSERT INTO technician_cluster (user_id, routine_id, date, quarter, cluster_branches) 
                VALUES (:user_id, :routine_id, :date, :quarter, :cluster_branches)";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':routine_id', $this->routine_id);
        $stmt->bindParam(':date', $this->date);
        $stmt->bindParam(':quarter', $this->quarter);
        
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
            $this->quarter = $result['quarter'];
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
                    quarter = :quarter,
                    cluster_branches = :cluster_branches 
                WHERE cluster_id = :cluster_id";
        
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':cluster_id', $this->cluster_id);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':routine_id', $this->routine_id);
        $stmt->bindParam(':date', $this->date);
        $stmt->bindParam(':quarter', $this->quarter);
        
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
     * @return bool Success status
     */
    public static function saveClusters($routineId, $clustersData) {
        if (!isset($clustersData['clusters']) || !is_array($clustersData['clusters'])) {
            return false;
        }

        $conn = DatabaseConnection::getConnection();
        
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Get the quarter from the routine
            $routineSql = "SELECT quarter FROM routines WHERE id = :routine_id";
            $routineStmt = $conn->prepare($routineSql);
            $routineStmt->bindParam(':routine_id', $routineId);
            $routineStmt->execute();
            $routineData = $routineStmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$routineData) {
                $conn->rollBack();
                return false;
            }
            
            $quarter = $routineData['quarter'];
            
            // Delete any existing clusters for this routine
            $deleteSql = "DELETE FROM technician_cluster WHERE routine_id = :routine_id";
            $deleteStmt = $conn->prepare($deleteSql);
            $deleteStmt->bindParam(':routine_id', $routineId);
            $deleteStmt->execute();
            
            // Insert each cluster
            $currentDate = date('Y-m-d');
            foreach ($clustersData['clusters'] as $technicianLabel => $clusterInfo) {
                if (isset($clusterInfo['branches']) && is_array($clusterInfo['branches'])) {
                    $cluster = new ClusterTechnician(
                        null,
                        null, // user_id is null initially until assigned to a technician
                        $routineId,
                        $currentDate,
                        $quarter,
                        $clusterInfo['branches']
                    );
                    
                    if (!$cluster->create()) {
                        // If any insertion fails, rollback the transaction
                        $conn->rollBack();
                        return false;
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
            return false;
        }
    }

    /**
     * Get all clusters for a specific routine
     * @param int $routineId The routine ID
     * @return array|bool Array of clusters or false on failure
     */
    public static function getClustersByRoutineId($routineId) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM technician_cluster WHERE routine_id = :routine_id";
        
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
                    'routine_id' => $row['routine_id'],
                    'date' => $row['date'],
                    'quarter' => $row['quarter'],
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
        
        // Debug logging
        error_log("assignTechnician called with clusterId: " . var_export($clusterId, true) . ", userId: " . var_export($userId, true));
        
        try {
            $conn->beginTransaction();
            
            // Update the cluster assignment
            $sql = "UPDATE technician_cluster SET user_id = :user_id WHERE cluster_id = :cluster_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':cluster_id', $clusterId);
            $stmt->bindParam(':user_id', $userId);
            $result = $stmt->execute();
            
            if ($result) {
                // Get cluster details for notification
                $clusterSql = "SELECT cluster_branches FROM technician_cluster WHERE cluster_id = :cluster_id";
                $clusterStmt = $conn->prepare($clusterSql);
                $clusterStmt->bindParam(':cluster_id', $clusterId);
                $clusterStmt->execute();
                $cluster = $clusterStmt->fetch(PDO::FETCH_ASSOC);
                
                if ($cluster) {
                    $branches = json_decode($cluster['cluster_branches'], true);
                    $branchCount = is_array($branches) ? count($branches) : 0;
                    
                    error_log("About to create notification for userId: " . var_export($userId, true) . ", clusterId: " . var_export($clusterId, true) . ", branchCount: " . $branchCount);
                    
                    // Create notification (check if Notification class exists)
                    if (class_exists('Notification')) {
                        $notificationResult = Notification::createClusterAssignmentNotification($userId, $clusterId, $branchCount);
                        if (!$notificationResult) {
                            error_log("Failed to create notification for user {$userId}, cluster {$clusterId}");
                        } else {
                            error_log("Successfully created notification for user {$userId}, cluster {$clusterId}");
                        }
                    } else {
                        error_log("Notification class not found when trying to create cluster assignment notification");
                    }
                }
            }
            
            $conn->commit();
            return $result;
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error assigning technician: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get clusters assigned to a specific technician
     * @param int $userId The user/technician ID
     * @param int $page Page number for pagination
     * @param int $limit Number of items per page
     * @return array|bool Array of clusters with pagination info or false on failure
     */
    public static function getClustersByTechnician($userId, $page = 1, $limit = 10) {
        $conn = DatabaseConnection::getConnection();
        
        // Calculate offset
        $offset = ($page - 1) * $limit;
        
        // Get total count for pagination info
        $countSql = "SELECT COUNT(*) as total FROM technician_cluster WHERE user_id = :user_id";
        $countStmt = $conn->prepare($countSql);
        $countStmt->bindParam(':user_id', $userId);
        $countStmt->execute();
        $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
        
        // Get paginated results
        $sql = "SELECT * FROM technician_cluster WHERE user_id = :user_id ORDER BY cluster_id DESC LIMIT :limit OFFSET :offset";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':user_id', $userId);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        
        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if ($results) {
            $clusters = [];
            foreach ($results as $row) {
                $clusters[] = [
                    'cluster_id' => $row['cluster_id'],
                    'user_id' => $row['user_id'],
                    'routine_id' => $row['routine_id'],
                    'date' => $row['date'],
                    'quarter' => $row['quarter'],
                    'cluster_branches' => json_decode($row['cluster_branches'], true)
                ];
            }
            
            $totalPages = ceil($totalCount / $limit);
            
            return [
                'clusters' => $clusters,
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => $totalPages,
                    'total_count' => (int)$totalCount,
                    'per_page' => $limit,
                    'has_next' => $page < $totalPages,
                    'has_previous' => $page > 1
                ]
            ];
        }
        
        return [
            'clusters' => [],
            'pagination' => [
                'current_page' => $page,
                'total_pages' => 0,
                'total_count' => 0,
                'per_page' => $limit,
                'has_next' => false,
                'has_previous' => false
            ]
        ];
    }
    
    /**
     * Remove a branch from clusters after service completion and add to done_clusters table
     * @param int $branchId The branch ID that was serviced
     * @param int $userId The technician's user ID who completed the service
     * @param int $quarter The quarter for which the service was completed
     * @return array|bool Array with success status and cluster_id, or false on failure
     */
    public static function removeBranchFromCluster($branchId, $userId, $quarter) {
        $conn = DatabaseConnection::getConnection();
        
        try {
            $conn->beginTransaction();
            
            // Debug logging
            error_log("removeBranchFromCluster: Looking for branch {$branchId}, user {$userId}, quarter {$quarter}");
            
            // Find clusters assigned to this technician for the specific quarter
            if ($quarter !== null) {
                $sql = "SELECT cluster_id, cluster_branches, quarter FROM technician_cluster WHERE user_id = :user_id AND quarter = :quarter";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':user_id', $userId);
                $stmt->bindParam(':quarter', $quarter);
            } else {
                $sql = "SELECT cluster_id, cluster_branches, quarter FROM technician_cluster WHERE user_id = :user_id AND quarter IS NULL";
                $stmt = $conn->prepare($sql);
                $stmt->bindParam(':user_id', $userId);
            }
            
            $stmt->execute();
            $clusters = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            error_log("Found " . count($clusters) . " clusters for user {$userId} with quarter {$quarter}");
            
            $modifiedClusterId = null;
            $servicedBranch = null;
            
            foreach ($clusters as $cluster) {
                error_log("Checking cluster {$cluster['cluster_id']} with quarter {$cluster['quarter']}");
                
                $clusterBranches = json_decode($cluster['cluster_branches'], true);
                if (!is_array($clusterBranches)) {
                    error_log("Invalid cluster_branches data for cluster {$cluster['cluster_id']}");
                    continue;
                }
                
                // Debug: Log all branches in this cluster
                $branchIds = array_map(function($branch) {
                    return isset($branch['branch_id']) ? $branch['branch_id'] : 'unknown';
                }, $clusterBranches);
                error_log("Cluster {$cluster['cluster_id']} contains branches: " . implode(', ', $branchIds));
                
                // Find the serviced branch and extract its data
                foreach ($clusterBranches as $branch) {
                    if (isset($branch['branch_id']) && $branch['branch_id'] == $branchId) {
                        $servicedBranch = $branch;
                        break;
                    }
                }
                
                // Check if the branch exists in this cluster and remove it
                $updatedBranches = array_filter($clusterBranches, function($branch) use ($branchId) {
                    return isset($branch['branch_id']) && $branch['branch_id'] != $branchId;
                });
                
                // If branches were removed, update the cluster
                if (count($updatedBranches) !== count($clusterBranches)) {
                    $modifiedClusterId = $cluster['cluster_id'];
                    error_log("Found and removing branch {$branchId} from cluster {$modifiedClusterId}");
                    
                    // Add the completed branch to done_clusters table
                    if ($servicedBranch) {
                        self::addToDoneClusters($modifiedClusterId, $userId, $servicedBranch);
                    }
                    
                    if (empty($updatedBranches)) {
                        // If no branches left, delete the cluster
                        $deleteSql = "DELETE FROM technician_cluster WHERE cluster_id = :cluster_id";
                        $deleteStmt = $conn->prepare($deleteSql);
                        $deleteStmt->bindParam(':cluster_id', $cluster['cluster_id']);
                        $deleteStmt->execute();
                        
                        error_log("Deleted empty cluster {$cluster['cluster_id']} for user {$userId} after servicing branch {$branchId} in quarter {$quarter}");
                    } else {
                        // Update cluster with remaining branches
                        $updateSql = "UPDATE technician_cluster SET cluster_branches = :cluster_branches WHERE cluster_id = :cluster_id";
                        $updateStmt = $conn->prepare($updateSql);
                        $updateStmt->bindParam(':cluster_id', $cluster['cluster_id']);
                        $updatedBranchesJson = json_encode(array_values($updatedBranches));
                        $updateStmt->bindParam(':cluster_branches', $updatedBranchesJson);
                        $updateStmt->execute();
                        
                        error_log("Updated cluster {$cluster['cluster_id']} for user {$userId} - removed branch {$branchId} in quarter {$quarter}");
                    }
                    break; // Found and processed the cluster containing the branch
                } else {
                    error_log("Branch {$branchId} not found in cluster {$cluster['cluster_id']}");
                }
            }
            
            if ($modifiedClusterId === null) {
                error_log("Branch {$branchId} not found in any cluster for user {$userId} with quarter {$quarter}");
            }
            
            $conn->commit();
            return [
                'success' => true,
                'cluster_id' => $modifiedClusterId
            ];
            
        } catch (Exception $e) {
            $conn->rollBack();
            error_log("Error removing branch from cluster: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Add a completed branch to the done_clusters table
     * @param int $clusterId The cluster ID
     * @param int $userId The user ID
     * @param array $branchData The branch data to add
     * @return bool Success status
     */
    private static function addToDoneClusters($clusterId, $userId, $branchData) {
        $conn = DatabaseConnection::getConnection();
        
        try {
            // Check if there's already a record for this cluster and user
            $checkSql = "SELECT done_id, done_branches FROM done_clusters WHERE cluster_id = :cluster_id AND user_id = :user_id";
            $checkStmt = $conn->prepare($checkSql);
            $checkStmt->bindParam(':cluster_id', $clusterId);
            $checkStmt->bindParam(':user_id', $userId);
            $checkStmt->execute();
            
            $existingRecord = $checkStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($existingRecord) {
                // Update existing record by adding the new branch to done_branches array
                $doneBranches = json_decode($existingRecord['done_branches'], true);
                if (!is_array($doneBranches)) {
                    $doneBranches = [];
                }
                
                // Add completion timestamp to branch data using Colombo time
                $currentTime = $conn->query("SELECT CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Colombo'")->fetchColumn();
                $branchData['completed_at'] = $currentTime;
                $doneBranches[] = $branchData;
                
                $updateSql = "UPDATE done_clusters SET done_branches = :done_branches, done_at = CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Colombo' WHERE done_id = :done_id";
                $updateStmt = $conn->prepare($updateSql);
                $updateStmt->bindParam(':done_id', $existingRecord['done_id']);
                $doneBranchesJson = json_encode($doneBranches);
                $updateStmt->bindParam(':done_branches', $doneBranchesJson);
                $result = $updateStmt->execute();
                
                error_log("Updated done_clusters record for cluster {$clusterId}, user {$userId} - added branch {$branchData['branch_id']}");
                
            } else {
                // Create new record
                $currentTime = $conn->query("SELECT CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Colombo'")->fetchColumn();
                $branchData['completed_at'] = $currentTime;
                $doneBranches = [$branchData];
                
                $insertSql = "INSERT INTO done_clusters (cluster_id, user_id, done_branches, done_at) VALUES (:cluster_id, :user_id, :done_branches, CURRENT_TIMESTAMP AT TIME ZONE 'Asia/Colombo')";
                $insertStmt = $conn->prepare($insertSql);
                $insertStmt->bindParam(':cluster_id', $clusterId);
                $insertStmt->bindParam(':user_id', $userId);
                $doneBranchesJson = json_encode($doneBranches);
                $insertStmt->bindParam(':done_branches', $doneBranchesJson);
                $result = $insertStmt->execute();
                
                error_log("Created new done_clusters record for cluster {$clusterId}, user {$userId} - added branch {$branchData['branch_id']}");
            }
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Error adding to done_clusters: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get completed branches for a specific cluster and user
     * @param int $clusterId The cluster ID
     * @param int $userId The user ID
     * @return array|bool Array of completed branches with technician info or false on failure
     */
    public static function getDoneBranches($clusterId, $userId) {
        $conn = DatabaseConnection::getConnection();
        
        try {
            $sql = "SELECT dc.done_branches, dc.done_at AT TIME ZONE 'Asia/Colombo' as done_at, u.username as technician_name, u.email as technician_email 
                    FROM done_clusters dc 
                    LEFT JOIN users u ON dc.user_id = u.user_id 
                    WHERE dc.cluster_id = :cluster_id AND dc.user_id = :user_id";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':cluster_id', $clusterId);
            $stmt->bindParam(':user_id', $userId);
            $stmt->execute();
            
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if ($result) {
                $doneBranches = json_decode($result['done_branches'], true);
                return [
                    'done_branches' => $doneBranches,
                    'done_at' => $result['done_at'],
                    'technician_name' => $result['technician_name'],
                    'technician_email' => $result['technician_email']
                ];
            }
            
            return [];
            
        } catch (Exception $e) {
            error_log("Error getting done branches: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get all completed branches for a user across all clusters
     * @param int $userId The user ID
     * @param int $page Page number for pagination
     * @param int $limit Number of items per page
     * @return array|bool Array of completed clusters with technician info and pagination or false on failure
     */
    public static function getAllDoneBranchesByUser($userId, $page = 1, $limit = 10) {
        $conn = DatabaseConnection::getConnection();
        
        try {
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Get total count for pagination info
            $countSql = "SELECT COUNT(*) as total FROM done_clusters WHERE user_id = :user_id";
            $countStmt = $conn->prepare($countSql);
            $countStmt->bindParam(':user_id', $userId);
            $countStmt->execute();
            $totalCount = $countStmt->fetch(PDO::FETCH_ASSOC)['total'];
            
            // Get paginated results
            $sql = "SELECT dc.cluster_id, dc.done_branches, dc.done_at AT TIME ZONE 'Asia/Colombo' as done_at, u.username as technician_name, u.email as technician_email 
                    FROM done_clusters dc 
                    LEFT JOIN users u ON dc.user_id = u.user_id 
                    WHERE dc.user_id = :user_id 
                    ORDER BY dc.done_at DESC
                    LIMIT :limit OFFSET :offset";
            $stmt = $conn->prepare($sql);
            $stmt->bindParam(':user_id', $userId);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
            $stmt->execute();
            
            $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            $totalPages = ceil($totalCount / $limit);
            
            if ($results) {
                $doneClusters = [];
                $technicianName = null;
                $technicianEmail = null;
                
                foreach ($results as $row) {
                    $doneBranches = json_decode($row['done_branches'], true);
                    
                    // Get technician info from first record (should be same for all)
                    if (!$technicianName) {
                        $technicianName = $row['technician_name'];
                        $technicianEmail = $row['technician_email'];
                    }
                    
                    $doneClusters[] = [
                        'cluster_id' => $row['cluster_id'],
                        'done_branches' => $doneBranches,
                        'done_at' => $row['done_at'],
                        'total_completed' => is_array($doneBranches) ? count($doneBranches) : 0
                    ];
                }
                
                return [
                    'technician_name' => $technicianName,
                    'technician_email' => $technicianEmail,
                    'clusters' => $doneClusters,
                    'pagination' => [
                        'current_page' => $page,
                        'total_pages' => $totalPages,
                        'total_count' => (int)$totalCount,
                        'per_page' => $limit,
                        'has_next' => $page < $totalPages,
                        'has_previous' => $page > 1
                    ]
                ];
            }
            
            return [
                'technician_name' => null,
                'technician_email' => null,
                'clusters' => [],
                'pagination' => [
                    'current_page' => $page,
                    'total_pages' => 0,
                    'total_count' => 0,
                    'per_page' => $limit,
                    'has_next' => false,
                    'has_previous' => false
                ]
            ];
            
        } catch (Exception $e) {
            error_log("Error getting all done branches: " . $e->getMessage());
            return false;
        }
    }
}
