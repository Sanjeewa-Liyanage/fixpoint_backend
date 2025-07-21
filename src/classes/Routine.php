<?php

class Routine extends Model {
    public $routine_id;
    public $planned_date;
    public $status;
    public $description;
    public $response_count;
    public $branches; // array of branch data
  private const GOOGLE_MAPS_API_KEY = 'AIzaSyAsKW1D7v1veULUSuqvfI3sH82XAMa3qN0';
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
    
// In class Routine

public function decideTechnicianCount($maxBranchesPerTech = 5) {
    if (empty($this->branches) || count($this->branches) == 0) {
        return ['technician_count' => 0, 'clusters' => []];
    }

    $branches = $this->branches;
    $branchCount = count($branches);

    // --- Step 1: Initial Geographic Clustering using k-means (Unchanged) ---
    // This provides our high-level geographic territories.
    $estimatedClusters = max(1, ceil($branchCount / $maxBranchesPerTech));
    $haversine = function($lat1, $lon1, $lat2, $lon2) {
        $earthRadius = 6371; // km
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);
        $a = sin($dLat/2) * sin($dLat/2) + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLon/2) * sin($dLon/2);
        $c = 2 * atan2(sqrt($a), sqrt(1-$a));
        return $earthRadius * $c;
    };
    $centroids = $this->initializeCentroids($branches, $estimatedClusters);
    $initialClusters = $this->optimizeClustersWithCentroids($branches, $centroids, $maxBranchesPerTech, $haversine);

    // --- Step 2: Refine clusters, splitting oversized ones locally (THE NEW LOGIC) ---
    // This is the crucial step that prevents route overlap by respecting the initial territories.
    $maxWorkingMinutes = 420; // 7 hours
    $serviceMinutesPerBranch = 40; // Average time spent at each location
    
    // This new function contains the core logic for preventing overlap.
    $finalClustersWithData = $this->refineAndSplitClusters(
        $initialClusters,
        $branches,
        $serviceMinutesPerBranch,
        $maxWorkingMinutes,
        $maxBranchesPerTech
    );


    // --- Step 3: Format the final output ---
    return $this->formatClusterOutput($finalClustersWithData, $branches);
}
// In class Routine

/**
 * NEW: The core of the enhanced algorithm.
 * It iterates through the initial geographic clusters. If a cluster is valid, it's kept.
 * If it's too large, it's split into smaller, valid routes *locally*.
 *
 * @return array An array where each element is itself an array containing 'indices' and 'travel_time'.
 */
private function refineAndSplitClusters($initialClusters, $branches, $serviceMinutesPerBranch, $maxWorkingMinutes, $maxBranchesPerTech) {
    $finalRoutes = [];

    foreach ($initialClusters as $clusterIndices) {
        if (empty($clusterIndices)) {
            continue;
        }

        // Check if this entire geographic cluster can be handled by one technician.
        $travelMinutes = $this->getOptimizedRouteTravelTime($clusterIndices, $branches);
        $serviceMinutes = count($clusterIndices) * $serviceMinutesPerBranch;
        $totalMinutes = $travelMinutes + $serviceMinutes;
        
        $isWithinTimeLimit = $totalMinutes <= $maxWorkingMinutes;
        $isWithinSizeLimit = count($clusterIndices) <= $maxBranchesPerTech;

        if ($isWithinTimeLimit && $isWithinSizeLimit) {
            // This cluster is a perfect, valid route. Add it to our final list.
            $finalRoutes[] = [
                'indices' => $clusterIndices,
                'travel_time' => $travelMinutes // Cache the travel time!
            ];
        } else {
            // This cluster is too big. We must split it.
            // We use a route builder on *only the branches in this cluster*.
            $splitRoutes = $this->buildRoutesFromBranchList(
                $clusterIndices, // <--- Crucially, we only pass in the branches for THIS cluster
                $branches,
                $serviceMinutesPerBranch,
                $maxWorkingMinutes,
                $maxBranchesPerTech
            );
            
            // Add the newly created smaller routes to our final list
            foreach($splitRoutes as $route) {
                $finalRoutes[] = $route; // The route already contains cached travel time
            }
        }
    }

    return $finalRoutes;
}

/**
 * MODIFIED: Formerly groupClustersByTimeConstraint.
 * This function's job is now to take a specific list of branch indices and
 * partition it into one or more valid routes. It's used to split oversized clusters.
 *
 * @param array $branchIndicesToProcess The list of branch indices to form routes from.
 * @return array An array of routes, each with 'indices' and 'travel_time'.
 */
private function buildRoutesFromBranchList($branchIndicesToProcess, $branches, $serviceMinutesPerBranch, $maxWorkingMinutes, $maxBranchesPerTech) {
    $routes = [];
    $unassignedBranchIndices = array_flip($branchIndicesToProcess); // Use keys for fast lookups/deletes

    $haversine = function($lat1, $lon1, $lat2, $lon2) { /* ... haversine code ... */ };

    while (!empty($unassignedBranchIndices)) {
        $newCluster = [];
        // Start with an arbitrary unassigned branch from our list
        $firstBranchIndex = key($unassignedBranchIndices);
        $newCluster[] = $firstBranchIndex;
        unset($unassignedBranchIndices[$firstBranchIndex]);
        
        $lastTravelTime = 0;

        while (!empty($unassignedBranchIndices)) {
            if (count($newCluster) >= $maxBranchesPerTech) {
                break;
            }

            $lastBranchInCluster = end($newCluster);
            // Find the nearest branch from the *remaining* branches in this specific group
            $bestNextBranch = $this->findNearestUnassignedBranch(
                $lastBranchInCluster,
                $unassignedBranchIndices,
                $branches,
                $haversine
            );

            if ($bestNextBranch === -1) {
                break;
            }

            $potentialCluster = array_merge($newCluster, [$bestNextBranch]);
            $travelMinutes = $this->getOptimizedRouteTravelTime($potentialCluster, $branches);
            $serviceMinutes = count($potentialCluster) * $serviceMinutesPerBranch;
            
            if (($travelMinutes + $serviceMinutes) <= $maxWorkingMinutes) {
                $newCluster[] = $bestNextBranch;
                unset($unassignedBranchIndices[$bestNextBranch]);
                $lastTravelTime = $travelMinutes; // Store the last valid travel time
            } else {
                // The route would be too long with this new branch, so we stop building this route.
                break;
            }
        }

        if (!empty($newCluster)) {
            // If we only have the travel time for a cluster of N-1, we need to get the final time for N.
            if (count($newCluster) > 1 && count($newCluster) > count(array_filter([$lastTravelTime])) + 1) {
                 $lastTravelTime = $this->getOptimizedRouteTravelTime($newCluster, $branches);
            }
           
            $routes[] = [
                'indices' => $newCluster,
                'travel_time' => $lastTravelTime
            ];
        }
    }
    
    return $routes;
}
    /**
     * Groups branches into final technician routes, ensuring each route respects the time limit.
     * This is a more robust alternative to the recursive split.
     *
     * @return array An array of final clusters (each cluster is an array of branch indices).
     */
       /**
     * OPTIMIZED VERSION
     * Groups branches into final routes, using Haversine to guess the next best
     * branch and the API only to verify the total time. This dramatically
     * reduces API calls.
     *
     * @return array An array of final clusters.
     */
// Notice the new $maxBranchesPerTech parameter in the function signature
    private function groupClustersByTimeConstraint($initialClusters, $branches, $serviceMinutesPerBranch, $maxWorkingMinutes, $maxBranchesPerTech) {
        $finalClusters = [];
        $unassignedBranchIndices = [];
        
        foreach ($initialClusters as $cluster) {
            foreach ($cluster as $branchIndex) {
                $unassignedBranchIndices[$branchIndex] = true;
            }
        }

        $haversine = function($lat1, $lon1, $lat2, $lon2) {
            // ... haversine code ...
        };

        while (!empty($unassignedBranchIndices)) {
            $newCluster = [];
            $firstBranchIndex = key($unassignedBranchIndices);
            $newCluster[] = $firstBranchIndex;
            unset($unassignedBranchIndices[$firstBranchIndex]);
            
            while (!empty($unassignedBranchIndices)) {
                // *** THE NEW CHECK GOES HERE ***
                // If the cluster is already full, stop looking for more branches.
                if (count($newCluster) >= $maxBranchesPerTech) {
                    break; // Stop adding to this cluster
                }

                $lastBranchInCluster = end($newCluster);
                $bestNextBranch = $this->findNearestUnassignedBranch(
                    $lastBranchInCluster,
                    $unassignedBranchIndices,
                    $branches,
                    $haversine
                );

                if ($bestNextBranch === -1) {
                    break;
                }

                $potentialCluster = array_merge($newCluster, [$bestNextBranch]);

                $travelMinutes = $this->getOptimizedRouteTravelTime($potentialCluster, $branches);
                $serviceMinutes = count($potentialCluster) * $serviceMinutesPerBranch;
                $totalMinutes = $travelMinutes + $serviceMinutes;

                if ($totalMinutes <= $maxWorkingMinutes) {
                    $newCluster[] = $bestNextBranch;
                    unset($unassignedBranchIndices[$bestNextBranch]);
                } else {
                    break;
                }
            }

            if (!empty($newCluster)) {
                $finalClusters[] = $newCluster;
            }
        }
        
        return $finalClusters;
    }

    /**
     * OPTIMIZED HELPER
     * Finds the single nearest unassigned branch to a given branch using Haversine distance.
     * This is very fast as it does not use an API.
     */
    private function findNearestUnassignedBranch($fromBranchIndex, $unassignedIndices, $branches, $haversine) {
        $minDist = INF;
        $bestBranch = -1;
        
        $fromBranch = $branches[$fromBranchIndex];

        foreach (array_keys($unassignedIndices) as $candidateIndex) {
            $candidateBranch = $branches[$candidateIndex];
            $dist = $haversine(
                $fromBranch['latitude'], $fromBranch['longitude'],
                $candidateBranch['latitude'], $candidateBranch['longitude']
            );

            if ($dist < $minDist) {
                $minDist = $dist;
                $bestBranch = $candidateIndex;
            }
        }
        return $bestBranch;
    }
    
    /**
     * Finds the unassigned branch that results in the smallest increase in travel time when added to the current cluster.
     */
    private function findBestBranchToAddToCluster($currentCluster, $unassignedIndices, $branches) {
        $minTravelTime = INF;
        $bestBranch = -1;

        foreach ($unassignedIndices as $index) {
            $tempCluster = array_merge($currentCluster, [$index]);
            $travelTime = $this->getOptimizedRouteTravelTime($tempCluster, $branches);
            if ($travelTime < $minTravelTime) {
                $minTravelTime = $travelTime;
                $bestBranch = $index;
            }
        }
        return $bestBranch;
    }


    /**
     * Gets the optimized travel time for a cluster using Google Directions API.
     *
     * @param array $clusterIndices Array of branch indices.
     * @param array $branches The full list of branches.
     * @return int Total travel time in minutes.
     */
   // In getOptimizedRouteTravelTime()

private function getOptimizedRouteTravelTime($clusterIndices, $branches) {
    if (count($clusterIndices) <= 1) {
        return 0;
    }

    // ... (code to build $coords) ...
    $coords = [];
    foreach ($clusterIndices as $idx) {
        $lat = (float)trim($branches[$idx]['latitude']);
        $lng = (float)trim($branches[$idx]['longitude']);
        $coords[] = $lat . ',' . $lng;
    }

    $origin = array_shift($coords);
    $destination = $origin;
    $waypoints = implode('|', $coords);
    
    $apiKey = self::GOOGLE_MAPS_API_KEY; // Use your key here

    $url = "https://maps.googleapis.com/maps/api/directions/json?origin={$origin}&destination={$destination}&waypoints=optimize:true|{$waypoints}&key={$apiKey}";

    // --- DEBUGGING: LOG THE URL ---
    // Create a file named 'debug.log' in the same directory and make it writable.
    file_put_contents(__DIR__ . '/debug.log', "REQUEST URL: " . $url . "\n\n", FILE_APPEND);

    $responseJson = $this->fetchGoogleApi($url);

    // --- DEBUGGING: LOG THE RESPONSE ---
    file_put_contents(__DIR__ . '/debug.log', "API RESPONSE: " . $responseJson . "\n\n", FILE_APPEND);


    $totalSeconds = $this->parseTravelTimeFromDirections($responseJson);

    return (int) round($totalSeconds / 60);
}

    /**
     * Parses the total travel duration from a Google Directions API response.
     */
    // Replace your existing parseTravelTimeFromDirections with this improved version
    private function parseTravelTimeFromDirections($json) {
        // First, check if the input is a valid string. If not, we can't do anything.
        if (empty($json) || !is_string($json)) {
            return 0;
        }

        $data = json_decode($json, true);

        // Now check if the JSON was valid and has the data we need
        if (json_last_error() !== JSON_ERROR_NONE || !isset($data['routes'][0]['legs'])) {
            // The response was not a valid route, maybe an error from Google.
            // The debug.log file will show the actual error message from Google.
            return 0;
        }

        $totalSeconds = 0;
        foreach ($data['routes'][0]['legs'] as $leg) {
            if (isset($leg['duration']['value'])) {
                $totalSeconds += $leg['duration']['value'];
            }
        }
        return $totalSeconds;
    }
    
    /**
     * Formats the final clusters into a user-friendly output array.
     */
    // In class Routine

/**
 * MODIFIED: To use cached travel times, reducing redundant API calls.
 */
private function formatClusterOutput($finalClustersWithData, $branches) {
    $namedClusters = [];
    $technicianCount = 0;
    
    foreach ($finalClustersWithData as $clusterData) {
        $clusterIndices = $clusterData['indices'];
        if (empty($clusterIndices)) continue;

        $technicianCount++;
        $branchDetails = [];
        foreach ($clusterIndices as $branchIdx) {
            $branchDetails[] = [
                'branch_id' => $branches[$branchIdx]['branch_id'],
                'name' => $branches[$branchIdx]['name'],
                'latitude' => $branches[$branchIdx]['latitude'],
                'longitude' => $branches[$branchIdx]['longitude']
            ];
        }
        
        // *** PERFORMANCE BOOST: Use the pre-calculated travel time ***
        $travelMinutes = $clusterData['travel_time'];
        $serviceMinutes = count($clusterIndices) * 40; // Hardcoded from above
        
        $namedClusters['Technician ' . $technicianCount] = [
            'branches' => $branchDetails,
            'branch_count' => count($branchDetails),
            'estimated_travel_minutes' => $travelMinutes,
            'estimated_service_minutes' => $serviceMinutes,
            'estimated_total_minutes' => $travelMinutes + $serviceMinutes
        ];
    }

    return [
        'technician_count' => $technicianCount,
        'clusters' => $namedClusters
    ];
}

    // --- Helper Functions for Initial K-Means Clustering ---

    private function optimizeClustersWithCentroids($branches, $initialCentroids, $maxBranchesPerTech, $haversine) {
        // ... (This function is good, no changes needed)
        $branchCount = count($branches);
        $numClusters = count($initialCentroids);
        $maxIterations = 20;

        $centroids = $initialCentroids;
        $clusters = array_fill(0, $numClusters, []);

        for ($iter = 0; $iter < $maxIterations; $iter++) {
            $newClusters = array_fill(0, $numClusters, []);
            $assignmentsChanged = false;

            // Assignment step
            for ($i = 0; $i < $branchCount; $i++) {
                $minDist = INF;
                $closestClusterIdx = -1;

                for ($j = 0; $j < $numClusters; $j++) {
                    if (count($newClusters[$j]) >= $maxBranchesPerTech) continue;

                    $dist = $haversine(
                        $branches[$i]['latitude'], $branches[$i]['longitude'],
                        $centroids[$j]['latitude'], $centroids[$j]['longitude']
                    );

                    if ($dist < $minDist) {
                        $minDist = $dist;
                        $closestClusterIdx = $j;
                    }
                }

                if ($closestClusterIdx !== -1) {
                    $newClusters[$closestClusterIdx][] = $i;
                }
            }

            // Update step: Recalculate centroids
            $newCentroids = [];
            for ($j = 0; $j < $numClusters; $j++) {
                $newCentroid = $this->calculateCentroid($branches, $newClusters[$j]);
                if ($newCentroid) {
                     $newCentroids[$j] = $newCentroid;
                     if (
                         !isset($centroids[$j]) ||
                         $centroids[$j]['latitude'] !== $newCentroids[$j]['latitude'] ||
                         $centroids[$j]['longitude'] !== $newCentroids[$j]['longitude']
                     ) {
                         $assignmentsChanged = true;
                     }
                } else {
                    // Keep old centroid if cluster is empty
                    $newCentroids[$j] = $centroids[$j];
                }
            }

            $centroids = $newCentroids;
            $clusters = $newClusters;

            if (!$assignmentsChanged) break;
        }

        return array_values(array_filter($clusters)); // Remove empty clusters
    }

    private function calculateCentroid($branches, $branchIndexes) {
        // ... (This function is good, no changes needed)
        $latSum = 0;
        $lonSum = 0;
        $count = count($branchIndexes);
        if ($count === 0) return null;

        foreach ($branchIndexes as $i) {
            $latSum += $branches[$i]['latitude'];
            $lonSum += $branches[$i]['longitude'];
        }

        return ['latitude' => $latSum / $count, 'longitude' => $lonSum / $count];
    }

    private function initializeCentroids($branches, $numClusters) {
        // ... (This function is good, no changes needed)
        $centroids = [];
        $branchCount = count($branches);
        if ($branchCount == 0) return [];
        
        $step = floor($branchCount / $numClusters);
        for ($i = 0; $i < $numClusters; $i++) {
            $index = min($i * $step, $branchCount - 1);
            $centroids[] = [
                'latitude' => $branches[$index]['latitude'],
                'longitude' => $branches[$index]['longitude']
            ];
        }
        return $centroids;
    }
    
    // Replace your existing fetchGoogleApi with this
    private function fetchGoogleApi($url) {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_USERAGENT, 'YourApp/1.0');
        $output = curl_exec($ch);

        // This is the important part!
        if(curl_errno($ch)){
            // Log the SPECIFIC cURL error to your PHP error log
            error_log('cURL error in Routine class: ' . curl_error($ch));
            curl_close($ch);
            return null; // Return null on failure
        }

        curl_close($ch);
        return $output;
    }
}


