<?php

class Routine extends Model {
    public $routine_id;
    public $planned_date;
    public $status;
    public $description;
    public $response_count;
    public $branches; // array of branch data

    public function __construct($routine_id = null, $planned_date = null, $status = 'pending', $description = null, $response_count = 0, $branches = []) {
        $this->routine_id = $routine_id;
        $this->planned_date = $planned_date;
        $this->status = $status;
        $this->description = $description;
        $this->response_count = $response_count;
        $this->branches = $branches;
    }

    public static function getNearby($lat, $lng, $radius_km = 10) {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT branch_id,client_id,name, address, latitude, longitude
                FROM branch
                WHERE ST_DWithin(
                    location,
                    ST_SetSRID(ST_MakePoint(:lng, :lat), 4326)::geography,
                    :radius
                )";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':lat', $lat);
        $stmt->bindParam(':lng', $lng);
        $stmt->bindValue(':radius', $radius_km * 1000); // convert to meters
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function create() {
        // Implement the logic to create a routine in the database
    }

    public function read() {
        $conn = DatabaseConnection::getConnection();
        $sql = "SELECT * FROM routines WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $this->routine_id);
        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->routine_id = $result['id'];
        $this->planned_date = $result['planned_date'];
        $this->status = $result['status'];
        $this->description = $result['description'];
        $this->response_count = $result['response_count'];
        $this->branches = json_decode($result['branches'], true);
        return $result['id'] !== null;
    }

    public function update() {
        //update the routine as accepted by the admin
        $conn = DatabaseConnection::getConnection();
        $sql = "UPDATE routines SET status = :status WHERE id = :id";
        $stmt = $conn->prepare($sql);
        $stmt->bindParam(':id', $this->routine_id);
        $stmt->bindParam(':status', $this->status);
        return $stmt->execute();
    }

    public function delete() {
        // Implement the logic to delete a routine from the database
    }
    
    



    
    //routine algorithms
    
    public function decideTechnicianCount($maxBranchesPerTech = 6, $maxDistancePerDayKm = 30) {
        if (empty($this->branches) || count($this->branches) == 0) {
            return [
                'technician_count' => 0,
                'clusters' => []
            ];
        }
        
        $branches = $this->branches;
        $branchCount = count($branches);
        
        // Helper to calculate Haversine distance
        $haversine = function($lat1, $lon1, $lat2, $lon2) {
            $earthRadius = 6371; // km
            $dLat = deg2rad($lat2 - $lat1);
            $dLon = deg2rad($lon2 - $lon1);
            $a = sin($dLat/2) * sin($dLat/2) +
                cos(deg2rad($lat1)) * cos(deg2rad($lat2)) *
                sin($dLon/2) * sin($dLon/2);
            $c = 2 * atan2(sqrt($a), sqrt(1-$a));
            return $earthRadius * $c;
        };
        
        // Calculate optimal number of clusters
        $estimatedClusters = max(1, ceil($branchCount / $maxBranchesPerTech));
        
        // Initialize clusters using spread-out starting points
        
        $centroids = $this->initializeCentroids($branches, $estimatedClusters);
        $clusters = $this->optimizeClustersWithCentroids($branches, $centroids, $maxBranchesPerTech, $maxDistancePerDayKm, $haversine);

        // Format clusters for output
        $namedClusters = [];
        $technicianCount = 0;
        foreach ($clusters as $i => $cluster) {
            if (!empty($cluster)) {
                $technicianCount++;
                $clusterData = [];
                foreach ($cluster as $branchIdx) {
                    $clusterData[] = [
                        'branch_id' => $branches[$branchIdx]['branch_id'],
                        'name' => $branches[$branchIdx]['name'],
                        'latitude' => $branches[$branchIdx]['latitude'],
                        'longitude' => $branches[$branchIdx]['longitude']
                    ];
                }
                $namedClusters['Cluster ' . $technicianCount] = $clusterData;
            }
        }
        
        return [
            'technician_count' => $technicianCount,
            'clusters' => $namedClusters
        ];
    }
    private function calculateCentroid($branches, $branchIndexes) {
    $latSum = 0;
    $lonSum = 0;
    $count = count($branchIndexes);
    if ($count === 0) return null;

    foreach ($branchIndexes as $i) {
        $latSum += $branches[$i]['latitude'];
        $lonSum += $branches[$i]['longitude'];
    }

    return [
        'latitude' => $latSum / $count,
        'longitude' => $lonSum / $count
    ];
}

    private function initializeCentroids($branches, $numClusters) {
    $step = floor(count($branches) / $numClusters);
    $centroids = [];

    for ($i = 0; $i < $numClusters; $i++) {
        $index = min($i * $step, count($branches) - 1);
        $centroids[] = [
            'latitude' => $branches[$index]['latitude'],
            'longitude' => $branches[$index]['longitude']
        ];
    }

    return $centroids;
}

    
    private function optimizeClusters($branches, $initialClusters, $maxBranchesPerTech, $maxDistancePerDayKm, $haversine) {
        $branchCount = count($branches);
        $numClusters = count($initialClusters);
        $clusters = $initialClusters;
        $maxIterations = 10;
        
        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $newClusters = array_fill(0, $numClusters, []);
            $changed = false;
            
            // Assign each branch to the best cluster
            for ($branchIdx = 0; $branchIdx < $branchCount; $branchIdx++) {
                $bestCluster = -1;
                $minCost = INF;
                
                for ($clusterIdx = 0; $clusterIdx < $numClusters; $clusterIdx++) {
                    // Check if adding this branch would violate constraints
                    $tempCluster = $newClusters[$clusterIdx];
                    $tempCluster[] = $branchIdx;
                    
                    if (count($tempCluster) > $maxBranchesPerTech) {
                        continue; // Skip if cluster would be too large
                    }
                    
                    $clusterCost = $this->calculateClusterCost($branches, $tempCluster, $haversine);
                    
                    if ($clusterCost <= $maxDistancePerDayKm && $clusterCost < $minCost) {
                        $minCost = $clusterCost;
                        $bestCluster = $clusterIdx;
                    }
                }
                
                // If no valid cluster found, create a new one or assign to least costly
                if ($bestCluster === -1) {
                    $minCost = INF;
                    for ($clusterIdx = 0; $clusterIdx < $numClusters; $clusterIdx++) {
                        if (count($newClusters[$clusterIdx]) < $maxBranchesPerTech) {
                            $tempCluster = $newClusters[$clusterIdx];
                            $tempCluster[] = $branchIdx;
                            $cost = $this->calculateClusterCost($branches, $tempCluster, $haversine);
                            if ($cost < $minCost) {
                                $minCost = $cost;
                                $bestCluster = $clusterIdx;
                            }
                        }
                    }
                }
                
                if ($bestCluster !== -1) {
                    $newClusters[$bestCluster][] = $branchIdx;
                    
                    // Check if assignment changed
                    $oldCluster = -1;
                    foreach ($clusters as $idx => $cluster) {
                        if (in_array($branchIdx, $cluster)) {
                            $oldCluster = $idx;
                            break;
                        }
                    }
                    if ($oldCluster !== $bestCluster) {
                        $changed = true;
                    }
                }
            }
            
            $clusters = $newClusters;
            
            // If no changes, converged
            if (!$changed) {
                break;
            }
        }
        
        return $clusters;
    }
    //optimized
    private function optimizeClustersWithCentroids($branches, $initialCentroids, $maxBranchesPerTech, $maxDistancePerDayKm, $haversine) {
    $branchCount = count($branches);
    $numClusters = count($initialCentroids);
    $maxIterations = 10;

    $centroids = $initialCentroids;
    $clusters = array_fill(0, $numClusters, []);

    for ($iter = 0; $iter < $maxIterations; $iter++) {
        $newClusters = array_fill(0, $numClusters, []);
        $changed = false;

        for ($i = 0; $i < $branchCount; $i++) {
            $minDist = INF;
            $closest = -1;

            for ($j = 0; $j < $numClusters; $j++) {
                if (count($newClusters[$j]) >= $maxBranchesPerTech) continue;
                $dist = $haversine(
                    $branches[$i]['latitude'], $branches[$i]['longitude'],
                    $centroids[$j]['latitude'], $centroids[$j]['longitude']
                );
                if ($dist < $minDist) {
                    $minDist = $dist;
                    $closest = $j;
                }
            }

            if ($closest !== -1) {
                $newClusters[$closest][] = $i;
            }
        }

        // Recalculate centroids
        $newCentroids = [];
        for ($j = 0; $j < $numClusters; $j++) {
            $newCentroids[$j] = $this->calculateCentroid($branches, $newClusters[$j]);
        }

        // Check convergence
        for ($j = 0; $j < $numClusters; $j++) {
            if ($centroids[$j]['latitude'] !== $newCentroids[$j]['latitude'] ||
                $centroids[$j]['longitude'] !== $newCentroids[$j]['longitude']) {
                $changed = true;
            }
        }

        $centroids = $newCentroids;
        $clusters = $newClusters;

        if (!$changed) break;
    }

    // Remove empty clusters
    return array_values(array_filter($clusters, fn($c) => count($c) > 0));
}

    
    private function calculateClusterCost($branches, $clusterBranches, $haversine) {
        if (count($clusterBranches) <= 1) {
            return 0;
        }
        
        // Calculate total distance for optimal route through cluster
        $minRoute = $this->findOptimalRoute($branches, $clusterBranches, $haversine);
        return $minRoute;
    }
    
    private function findOptimalRoute($branches, $clusterBranches, $haversine) {
        $n = count($clusterBranches);
        if ($n <= 1) return 0;
        if ($n == 2) {
            return $haversine(
                $branches[$clusterBranches[0]]['latitude'], $branches[$clusterBranches[0]]['longitude'],
                $branches[$clusterBranches[1]]['latitude'], $branches[$clusterBranches[1]]['longitude']
            );
        }
        
        // For small clusters, use exact TSP solution
        if ($n <= 8) {
            return $this->solveTSP($branches, $clusterBranches, $haversine);
        }
        
        // For larger clusters, use nearest neighbor heuristic
        return $this->nearestNeighborTSP($branches, $clusterBranches, $haversine);
    }
    
    private function solveTSP($branches, $clusterBranches, $haversine) {
        $n = count($clusterBranches);
        $distances = [];
        
        // Build distance matrix
        for ($i = 0; $i < $n; $i++) {
            for ($j = 0; $j < $n; $j++) {
                if ($i == $j) {
                    $distances[$i][$j] = 0;
                } else {
                    $distances[$i][$j] = $haversine(
                        $branches[$clusterBranches[$i]]['latitude'], $branches[$clusterBranches[$i]]['longitude'],
                        $branches[$clusterBranches[$j]]['latitude'], $branches[$clusterBranches[$j]]['longitude']
                    );
                }
            }
        }
        
        // Simple nearest neighbor for now (can be improved with dynamic programming for small n)
        return $this->nearestNeighborFromMatrix($distances);
    }
    
    private function nearestNeighborTSP($branches, $clusterBranches, $haversine) {
        $n = count($clusterBranches);
        $visited = array_fill(0, $n, false);
        $current = 0;
        $visited[0] = true;
        $totalDistance = 0;
        
        for ($i = 1; $i < $n; $i++) {
            $nearest = -1;
            $minDist = INF;
            
            for ($j = 0; $j < $n; $j++) {
                if (!$visited[$j]) {
                    $dist = $haversine(
                        $branches[$clusterBranches[$current]]['latitude'], $branches[$clusterBranches[$current]]['longitude'],
                        $branches[$clusterBranches[$j]]['latitude'], $branches[$clusterBranches[$j]]['longitude']
                    );
                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $nearest = $j;
                    }
                }
            }
            
            if ($nearest !== -1) {
                $visited[$nearest] = true;
                $totalDistance += $minDist;
                $current = $nearest;
            }
        }
        
        return $totalDistance;
    }
    
    private function nearestNeighborFromMatrix($distances) {
        $n = count($distances);
        $visited = array_fill(0, $n, false);
        $current = 0;
        $visited[0] = true;
        $totalDistance = 0;
        
        for ($i = 1; $i < $n; $i++) {
            $nearest = -1;
            $minDist = INF;
            
            for ($j = 0; $j < $n; $j++) {
                if (!$visited[$j] && $distances[$current][$j] < $minDist) {
                    $minDist = $distances[$current][$j];
                    $nearest = $j;
                }
            }
            
            if ($nearest !== -1) {
                $visited[$nearest] = true;
                $totalDistance += $minDist;
                $current = $nearest;
            }
        }
        
        return $totalDistance;
    }
}