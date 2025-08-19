<?php
class Service_ReportingApi extends ApiResourceBase {
public function __construct() {
    $this->setRoles([
        "create_service_report" => ["technician", "admin"],
        "view_service_reports" => ["technician", "admin"],
        "update_service_reports" => ["technician", "admin"],
        "delete_service_report" => ["technician", "admin"],
        "view_all_service_reports" => ["technician", "admin"],
        "get_technician_clusters" => ["technician", "admin"],
        "get_done_branches" => ["technician", "admin"]
    ]);
}

public function create_service_report($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "status" => "error",
            "message" => "Invalid Authentication Token"
        ];
    }
    
    // Try different possible key names for user ID
    $user_id = null;
    if (isset($user['user_id'])) {
        $user_id = $user['user_id'];
    } elseif (isset($user['id'])) {
        $user_id = $user['id'];
    } elseif (isset($user['uid'])) {
        $user_id = $user['uid'];
    }
    
    if (!$user_id) {
        return [
            "status" => "Unauthorized",
            "message" => "User ID not found in authentication token",
            "status_code"=> 403
        ];
    }
    $roleName = isset($user['role_name'])? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
    if(!$this->checkRoles($roleName, 'create_service_report')) {
        return [
            'status' => 'error',
            'message' => 'You do not have permission to create a service report'
        ];
    }
    $missing = $this->validateFields($data, [
        'branch_id',
        'client_id',
        
        'device_type',
        'service_date',
        'service_type',
        'service_notes',
        'quarter'
        
    ]);
    if(!empty($missing)) {
        return [
            'status' => 'error',
            'message' => 'Missing fields: ' . implode(', ', $missing)
        ];
    }
    
    // Validate device_type
    $validDeviceTypes = ['teller_scanner', 'chdm'];
    if (!in_array(strtolower($data['device_type']), $validDeviceTypes)) {
        return [
            'status' => 'error',
            'message' => 'Invalid device_type. Allowed values: ' . implode(', ', $validDeviceTypes)
        ];
    }
    
    // Convert quarter string to integer (Q1->1, Q2->2, Q3->3, Q4->4)
    $quarter = $data['quarter'];
    if (is_string($quarter) && preg_match('/^Q([1-4])$/i', $quarter, $matches)) {
        $quarter = (int)$matches[1];
    } elseif (!is_numeric($quarter) || $quarter < 1 || $quarter > 4) {
        return [
            'status' => 'error',
            'message' => 'Invalid quarter value. Use Q1, Q2, Q3, Q4 or 1, 2, 3, 4'
        ];
    }
    
    $serviceReport = new Service_Reporting(
        null,
        $data['branch_id'],
        $data['client_id'],
        $user_id,
        $data['device_type'],
        $data['service_date'],
        $data['service_type'],
        $data['service_notes'],
        $data['created_at'],
        $data['teller_scanner_serial']?? null,
        $data['chdm_serial']?? null,
        $quarter
        );
    $success = $serviceReport->create();
    if($success) {
        // Debug logging
        error_log("Service report created successfully. Now identifying cluster for branch {$data['branch_id']}, user {$user_id}, quarter {$quarter}");
        
        // Identify which cluster the branch belongs to before removing it
        $clusterInfo = $this->getClusterForBranch($data['branch_id'], $user_id, $quarter);
        $clusterId = null;
        
        if ($clusterInfo) {
            $clusterId = $clusterInfo['cluster_id'];
            error_log("Identified cluster {$clusterId} containing branch {$data['branch_id']} for user {$user_id} in quarter {$quarter}");
        } else {
            error_log("No cluster found containing branch {$data['branch_id']} for user {$user_id} in quarter {$quarter}");
        }
        
        if (class_exists('ClusterTechnician')) {
            // Remove the serviced branch from technician clusters
            $branchRemovalResult = ClusterTechnician::removeBranchFromCluster(
                $data['branch_id'], 
                $user_id, 
                $quarter
            );
            
            if ($branchRemovalResult && is_array($branchRemovalResult)) {
                if (!$clusterId) {
                    $clusterId = $branchRemovalResult['cluster_id'];
                }
                error_log("Successfully removed branch {$data['branch_id']} from cluster {$clusterId} for user {$user_id} in quarter {$quarter}");
            } else {
                error_log("Failed to remove branch {$data['branch_id']} from clusters for user {$user_id} in quarter {$quarter}");
            }
        }
        
        $message = 'Service report created successfully, branch removed from cluster assignments and added to completed branches';
        $responseData = ['status' => 'success', 'message' => $message];
        
        // Include cluster information in the response if available
        if ($clusterInfo) {
            $message .= " (Cluster ID: {$clusterInfo['cluster_id']}, Quarter: Q{$clusterInfo['quarter']})";
            $responseData['message'] = $message;
            $responseData['cluster_info'] = $clusterInfo;
        } elseif ($clusterId) {
            $message .= " (Cluster ID: {$clusterId})";
            $responseData['message'] = $message;
        }
        
        return $responseData;
    } else {
        return [
            'status' => 'error',
            'message' => 'Failed to create service report'
        ];
    }
}

public function view_service_reports($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "message"=> "Invalid or expired authentication token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'view_service_reports')) {
        return [
            "message" => "Unauthorized access. Admin or Technician access required",
            "status"=> "error",
        ];
    }

    if(!isset($data['keyword'])|| trim($data['keyword']) === "") {
        return [
            "message"=> "view_service_reports keyword is required",
            "status"=> "error",
        ];
    }

    $keyword = $data['keyword'];
    $serviceReporting = new Service_Reporting();
    $results = $serviceReporting->search($keyword);

    if($results) {
        return [
            "status" => "success",
            "message" => "Service reports retrieved successfully",
            "data" => $results
        ];
    } else {
        return [
            "message" => "No service reports found",
            "status" => "error",
        ];
    }
}
public function update_service_reports($data) {
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "message"=> "Invalid or expired authentication token. Please log in again.",
            "status"=> "error",
        ];
}
   if(!isset($data['service_id'])) {
    return [
        'message'=> 'Missing service_id',
        'status'=> 'error'
        ];
   }
    $service = new Service_Reporting();
    $service->service_id = $data['service_id'];

    $success = $service->update_service_fields($data);

    if ($success) {
        return [
            "status" => "success", 
            "message" => "Service report updated"
        ];

    } else {

        return [
            "status" => "error",
             "message" => "Update failed or no fields provided"
            ];
    }
}

 public function delete_service_report($data){
    $user = $this->getAuthenticatedUser();
    if(!$user) {
        return [
            "message"=> "Invalid or expired authentication token. Please log in again.",
            "status"=> "error",
        ];
    }
    if(!$this->checkRoles($user['role_name'], 'delete_service_report')) {
        return [
            "message" => "Unauthorized access. Admin or Technician access required",
            "status"=> "error",
        ];
    }
    $missing = $this->validateFields($data, ['service_id']);
    if(!empty($missing)) {
        return [
            "message" => "Missing fields: " . implode(', ', $missing),
            "status" => "error"
        ];
    }
    $service = new Service_Reporting($data['service_id']);
    $success = $service->delete();
    if ($success) {
        return [
            "status" => "success",
            "message" => "Service report deleted successfully"
        ];
    } else {
        return [
            "status" => "error",
            "message" => "Failed to delete service report or service_id not found"
        ];
 }
}
  public function view_all_service_reports($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) {
            return [
                "message"=> "Invalid or expired authentication token. Please log in again.",
                "status"=> "error",
            ];
        }
        if(!$this->checkRoles($user['role_name'], 'view_all_service_reports')) {
            return [
                "message" => "Unauthorized access. Admin or Technician access required",
                "status"=> "error",
            ];
        }

        $results = Service_Reporting::readAllWithDetails();

        if ($results !== false) {
            if (empty($results)) {
                return [
                    "message" => "No service reports found",
                    "status" => "success",
                    "data" => []
                ];
            }
            return [
                "status" => "success",
                "message" => "Service reports retrieved successfully",
                "data" => $results
            ];
        } else {
            return [
                "status" => "error",
                "message" => "A database error occurred."
            ];
        }
    }

    /**
     * Get cluster information for a specific branch and technician
     * @param int $branchId The branch ID
     * @param int $userId The technician's user ID
     * @param int $quarter The quarter (required for proper cluster identification)
     * @return array|null Cluster information or null if not found
     */
    private function getClusterForBranch($branchId, $userId, $quarter = null) {
        if (!class_exists('ClusterTechnician')) {
            return null;
        }
        
        error_log("getClusterForBranch: Looking for branch {$branchId}, user {$userId}, quarter {$quarter}");
        
        $technicianClusters = ClusterTechnician::getClustersByTechnician($userId);
        
        if (!$technicianClusters) {
            error_log("No clusters found for technician {$userId}");
            return null;
        }
        
        error_log("Found " . count($technicianClusters) . " total clusters for technician {$userId}");
        
        foreach ($technicianClusters as $cluster) {
            error_log("Checking cluster {$cluster['cluster_id']} with quarter {$cluster['quarter']} (looking for quarter {$quarter})");
            
            // Match by quarter - this is crucial for proper cluster identification
            if ($quarter !== null && $cluster['quarter'] != $quarter) {
                error_log("Skipping cluster {$cluster['cluster_id']} - quarter mismatch ({$cluster['quarter']} != {$quarter})");
                continue;
            }
            
            $clusterBranches = $cluster['cluster_branches'];
            if (is_array($clusterBranches)) {
                $branchIds = array_map(function($branch) {
                    return isset($branch['branch_id']) ? $branch['branch_id'] : 'unknown';
                }, $clusterBranches);
                error_log("Cluster {$cluster['cluster_id']} contains branches: " . implode(', ', $branchIds));
                
                foreach ($clusterBranches as $branch) {
                    if (isset($branch['branch_id']) && $branch['branch_id'] == $branchId) {
                        error_log("Found branch {$branchId} in cluster {$cluster['cluster_id']} with quarter {$cluster['quarter']}");
                        return [
                            'cluster_id' => $cluster['cluster_id'],
                            'quarter' => $cluster['quarter'],
                            'routine_id' => $cluster['routine_id'],
                            'date' => $cluster['date'],
                            'total_branches' => count($clusterBranches),
                            'cluster_branches' => $clusterBranches,
                            'target_branch_id' => $branchId
                        ];
                    }
                }
            } else {
                error_log("Invalid cluster_branches data for cluster {$cluster['cluster_id']}");
            }
        }
        
        error_log("Branch {$branchId} not found in any cluster for user {$userId} with quarter {$quarter}");
        return null;
    }

    /**
     * Get all clusters for a technician with detailed information
     * @param array $data Should contain user_id and optionally quarter
     * @return array API response
     */
    public function get_technician_clusters($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) {
            return [
                "status" => "error",
                "message" => "Invalid Authentication Token"
            ];
        }
        
        $roleName = isset($user['role_name'])? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
        if(!$this->checkRoles($roleName, 'view_service_reports')) {
            return [
                'status' => 'error',
                'message' => 'You do not have permission to view cluster information'
            ];
        }
        
        // Use authenticated user's ID if not provided
        $userId = isset($data['user_id']) ? $data['user_id'] : $user['user_id'];
        $quarter = isset($data['quarter']) ? $data['quarter'] : null;
        
        if (!class_exists('ClusterTechnician')) {
            return [
                'status' => 'error',
                'message' => 'ClusterTechnician class not available'
            ];
        }
        
        $clusters = ClusterTechnician::getClustersByTechnician($userId);
        
        if (!$clusters) {
            return [
                'status' => 'success',
                'message' => 'No clusters found for this technician',
                'data' => []
            ];
        }
        
        // Filter by quarter if specified
        if ($quarter !== null) {
            $clusters = array_filter($clusters, function($cluster) use ($quarter) {
                return $cluster['quarter'] == $quarter;
            });
        }
        
        // Add additional information to each cluster
        $enhancedClusters = array_map(function($cluster) {
            $cluster['total_branches'] = is_array($cluster['cluster_branches']) ? count($cluster['cluster_branches']) : 0;
            $cluster['quarter_display'] = 'Q' . $cluster['quarter'];
            return $cluster;
        }, $clusters);
        
        return [
            'status' => 'success',
            'message' => 'Clusters retrieved successfully',
            'data' => array_values($enhancedClusters)
        ];
    }

    /**
     * Get completed branches for a technician
     * @param array $data Should contain user_id (optional) and cluster_id (optional)
     * @return array API response
     */
    public function get_done_branches($data) {
        $user = $this->getAuthenticatedUser();
        if(!$user) {
            return [
                "status" => "error",
                "message" => "Invalid Authentication Token"
            ];
        }
        
        $roleName = isset($user['role_name'])? $user['role_name'] : (isset($user['role']['role_name']) ? $user['role']['role_name'] : null);
        if(!$this->checkRoles($roleName, 'get_done_branches')) {
            return [
                'status' => 'error',
                'message' => 'You do not have permission to view completed branches'
            ];
        }
        
        // Use authenticated user's ID if not provided
        $userId = null;
        if (isset($data['user_id'])) {
            $userId = $data['user_id'];
        } elseif (isset($user['user_id'])) {
            $userId = $user['user_id'];
        } elseif (isset($user['id'])) {
            $userId = $user['id'];
        } elseif (isset($user['uid'])) {
            $userId = $user['uid'];
        }
        
        if (!$userId) {
            return [
                'status' => 'error',
                'message' => 'User ID not found in authentication token'
            ];
        }
        
        $clusterId = isset($data['cluster_id']) ? $data['cluster_id'] : null;
        
        if (!class_exists('ClusterTechnician')) {
            return [
                'status' => 'error',
                'message' => 'ClusterTechnician class not available'
            ];
        }
        
        if ($clusterId) {
            // Get done branches for specific cluster
            $doneBranchesResult = ClusterTechnician::getDoneBranches($clusterId, $userId);
            
            if ($doneBranchesResult === false) {
                return [
                    'status' => 'error',
                    'message' => 'Database error occurred while retrieving completed branches'
                ];
            }
            
            if (empty($doneBranchesResult)) {
                return [
                    'status' => 'success',
                    'message' => 'No completed branches found for this cluster',
                    'data' => [
                        'cluster_id' => $clusterId,
                        'user_id' => $userId,
                        'done_branches' => [],
                        'total_completed' => 0
                    ]
                ];
            }
            
            return [
                'status' => 'success',
                'message' => 'Completed branches retrieved successfully',
                'data' => [
                    'cluster_id' => $clusterId,
                    'user_id' => $userId,
                    'technician_name' => $doneBranchesResult['technician_name'],
                    'technician_email' => $doneBranchesResult['technician_email'],
                    'done_branches' => $doneBranchesResult['done_branches'],
                    'total_completed' => count($doneBranchesResult['done_branches'])
                ]
            ];
        } else {
            // Get all done branches for user across all clusters
            $allDoneBranchesResult = ClusterTechnician::getAllDoneBranchesByUser($userId);
            
            if ($allDoneBranchesResult === false) {
                return [
                    'status' => 'error',
                    'message' => 'Database error occurred while retrieving completed branches'
                ];
            }
            
            if (empty($allDoneBranchesResult)) {
                return [
                    'status' => 'success',
                    'message' => 'No completed branches found',
                    'data' => [
                        'user_id' => $userId,
                        'total_clusters_with_completions' => 0,
                        'total_branches_completed' => 0,
                        'clusters' => []
                    ]
                ];
            }
            
            // Calculate totals
            $clusters = $allDoneBranchesResult['clusters'];
            $totalClusters = count($clusters);
            $totalBranches = 0;
            foreach ($clusters as $cluster) {
                $totalBranches += $cluster['total_completed'];
            }
            
            return [
                'status' => 'success',
                'message' => 'All completed branches retrieved successfully',
                'data' => [
                    'user_id' => $userId,
                    'technician_name' => $allDoneBranchesResult['technician_name'],
                    'technician_email' => $allDoneBranchesResult['technician_email'],
                    'total_clusters_with_completions' => $totalClusters,
                    'total_branches_completed' => $totalBranches,
                    'clusters' => $clusters
                ]
            ];
        }
    }
}