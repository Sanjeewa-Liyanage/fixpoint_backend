<!DOCTYPE html>
<html>
<head>
    <title>Technician Clusters Map</title>
    <style>
        #map { height: 90vh; width: 100%; }
        #form-container { margin-bottom: 1em; }
    </style>
</head>
<body>
    <h2>Technician Clusters Map</h2>
    <div id="form-container">
        <form id="routineForm">
            <label for="routine_id">Routine ID:</label>
            <input type="number" id="routine_id" name="routine_id" required>
            <label for="auth_token">Authorization:</label>
            <input type="text" id="auth_token" name="auth_token" placeholder="Bearer ..." style="width:300px">
            <label for="endpoint">Endpoint:</label>
            <input type="text" id="endpoint" name="endpoint" value="/fixpoint-be-php/routine/routine/technicianCount" style="width:400px">
            <button type="submit">Show Clusters</button>
        </form>
        <div id="status"></div>
    </div>
    <div id="map"></div>
    <script>
    let map;
    let polylines = [];
    let markers = [];
    const colors = ["#e6194b", "#3cb44b", "#ffe119", "#4363d8", "#f58231", "#911eb4", "#46f0f0", "#f032e6", "#bcf60c", "#fabebe"];    function clearMap() {
        markers.forEach(m => m.setMap(null));
        polylines.forEach(p => {
            if (p.setMap) {
                p.setMap(null); 
            } else if (p.setDirections) {
                p.setDirections(null); 
            }
        });
        markers = [];
        polylines = [];
        
        // Clear route info from status
        const statusDiv = document.getElementById('status');
        if (statusDiv && statusDiv.innerHTML.includes('Route Details:')) {
            const parts = statusDiv.innerHTML.split('<br><br><strong>Route Details:</strong>');
            statusDiv.innerHTML = parts[0];
        }
    }function displayClusters(clusters) {
        clearMap();
        let first = null;
        let colorIdx = 0;
        for (const [clusterName, branches] of Object.entries(clusters)) {
            const clusterColor = colors[colorIdx % colors.length];
            colorIdx++;
            
            const clusterMarkers = [];
            // Create markers for each branch
            branches.forEach((branch, index) => {
                if (!first) first = branch;
                const marker = new google.maps.Marker({
                    position: {lat: parseFloat(branch.latitude), lng: parseFloat(branch.longitude)},
                    map: map,
                    title: `${branch.name} (ID: ${branch.branch_id})`,
                    icon: {
                        path: google.maps.SymbolPath.CIRCLE,
                        scale: 8,
                        fillColor: clusterColor,
                        fillOpacity: 1,
                        strokeWeight: 2,
                        strokeColor: '#333'
                    },
                    label: {
                        text: (index + 1).toString(),
                        color: '#fff',
                        fontSize: '12px',
                        fontWeight: 'bold'
                    }
                });
                
                const infowindow = new google.maps.InfoWindow({
                    content: `<b>${branch.name}</b><br>ID: ${branch.branch_id}<br>Cluster: ${clusterName}<br>Stop ${index + 1} of ${branches.length}<br><div id="route-info-${branch.branch_id}">Calculating route...</div>`
                });
                marker.addListener('click', function() {
                    infowindow.open(map, marker);
                });
                
                marker.branch_id = branch.branch_id;
                marker.infowindow = infowindow;
                clusterMarkers.push(marker);
            });
            markers.push(...clusterMarkers);
            
            // Draw optimized route using Google Directions API
            if (branches.length > 1) {
                drawRoadRoute(branches, clusterColor, clusterName, clusterMarkers);
            }
        }
        if (first) {
            map.setCenter({lat: parseFloat(first.latitude), lng: parseFloat(first.longitude)});
            map.setZoom(11);
        }
    }
    
    function drawRoadRoute(branches, color, clusterName, clusterMarkers) {
        // Get optimal order first
        const optimalOrder = findOptimalRouteOrder(branches);
        
        // Create waypoints for Directions API
        const waypoints = [];
        for (let i = 1; i < optimalOrder.length - 1; i++) {
            waypoints.push({
                location: new google.maps.LatLng(
                    parseFloat(optimalOrder[i].latitude), 
                    parseFloat(optimalOrder[i].longitude)
                ),
                stopover: true
            });
        }
        
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: true, // We already have custom markers
            polylineOptions: {
                strokeColor: color,
                strokeOpacity: 0.8,
                strokeWeight: 4
            }
        });
        
        const request = {
            origin: new google.maps.LatLng(
                parseFloat(optimalOrder[0].latitude), 
                parseFloat(optimalOrder[0].longitude)
            ),
            destination: new google.maps.LatLng(
                parseFloat(optimalOrder[optimalOrder.length - 1].latitude), 
                parseFloat(optimalOrder[optimalOrder.length - 1].longitude)
            ),
            waypoints: waypoints,
            optimizeWaypoints: true,
            travelMode: google.maps.TravelMode.DRIVING,
            avoidHighways: false,
            avoidTolls: false
        };
        
        directionsService.route(request, function(result, status) {
            if (status === 'OK') {
                directionsRenderer.setDirections(result);
                directionsRenderer.setMap(map);
                polylines.push(directionsRenderer); // Store for cleanup
                
                // Update info windows with actual route distances and times
                updateRouteInfo(result, optimalOrder, clusterName, clusterMarkers);
            } else {
                console.error('Directions request failed due to ' + status);
                // Fallback to straight lines if Directions API fails
                drawStraightLineRoute(optimalOrder, color);
            }
        });
    }
    
    function findOptimalRouteOrder(branches) {
        if (branches.length <= 2) return branches;
        
        // Use nearest neighbor algorithm to find optimal order
        const n = branches.length;
        const visited = new Array(n).fill(false);
        const route = [];
        
        // Start from first branch
        let current = 0;
        visited[0] = true;
        route.push(branches[0]);
        
        // Find nearest unvisited branch at each step
        for (let i = 1; i < n; i++) {
            let nearest = -1;
            let minDist = Infinity;
            
            for (let j = 0; j < n; j++) {
                if (!visited[j]) {
                    const dist = haversineDistance(
                        parseFloat(branches[current].latitude), parseFloat(branches[current].longitude),
                        parseFloat(branches[j].latitude), parseFloat(branches[j].longitude)
                    );
                    if (dist < minDist) {
                        minDist = dist;
                        nearest = j;
                    }
                }
            }
            
            if (nearest !== -1) {
                visited[nearest] = true;
                route.push(branches[nearest]);
                current = nearest;
            }
        }
        
        return route;
    }
    
    function drawStraightLineRoute(branches, color) {
        // Fallback: draw straight lines if Directions API fails
        const path = branches.map(b => ({
            lat: parseFloat(b.latitude), 
            lng: parseFloat(b.longitude)
        }));
        
        const polyline = new google.maps.Polyline({
            path: path,
            geodesic: true,
            strokeColor: color,
            strokeOpacity: 0.6,
            strokeWeight: 3,
            strokeStyle: 'dashed',
            map: map
        });
        polylines.push(polyline);
    }
    
    function updateRouteInfo(directionsResult, orderedBranches, clusterName, clusterMarkers) {
        const route = directionsResult.routes[0];
        let totalDistance = 0;
        let totalTime = 0;
        
        // Calculate total distance and time
        route.legs.forEach(leg => {
            totalDistance += leg.distance.value; // in meters
            totalTime += leg.duration.value; // in seconds
        });
        
        // Convert to readable format
        const totalDistanceKm = (totalDistance / 1000).toFixed(2);
        const totalTimeHours = Math.floor(totalTime / 3600);
        const totalTimeMinutes = Math.floor((totalTime % 3600) / 60);
        const timeString = totalTimeHours > 0 ? 
            `${totalTimeHours}h ${totalTimeMinutes}m` : 
            `${totalTimeMinutes}m`;
        
        // Reconstruct the actual order of branches from the optimized route
        let finalOrderedBranches;
        if (orderedBranches.length <= 2) {
            finalOrderedBranches = orderedBranches;
        } else {
            finalOrderedBranches = [orderedBranches[0]]; // origin
            const waypoints = orderedBranches.slice(1, -1);
            if (route.waypoint_order && route.waypoint_order.length === waypoints.length) {
                for (const i of route.waypoint_order) {
                    finalOrderedBranches.push(waypoints[i]);
                }
            } else {
                // No reordering, use original waypoint order
                finalOrderedBranches.push(...waypoints);
            }
            finalOrderedBranches.push(orderedBranches[orderedBranches.length - 1]); // destination
        }

        // Update info windows and marker labels for all branches in this cluster
        finalOrderedBranches.forEach((branch, index) => {
            const marker = clusterMarkers.find(m => m.branch_id === branch.branch_id);
            if (marker) {
                marker.setLabel({
                    text: (index + 1).toString(),
                    color: '#fff',
                    fontSize: '12px',
                    fontWeight: 'bold'
                });

                const routeSummaryHtml = `
                    <strong>Route Summary:</strong><br>
                    Total Distance: ${totalDistanceKm} km<br>
                    Total Time: ${timeString}
                `;

                marker.infowindow.setContent(
                    `<b>${branch.name}</b><br>ID: ${branch.branch_id}<br>Cluster: ${clusterName}<br>Stop ${index + 1} of ${finalOrderedBranches.length}<br>${routeSummaryHtml}`
                );
            }
        });
        
        // Update status with cluster summary
        const statusDiv = document.getElementById('status');
        const currentStatus = statusDiv.textContent;
        if (!currentStatus.includes('Route Details:')) {
            statusDiv.innerHTML += `<br><br><strong>Route Details:</strong><br>`;
        }
        statusDiv.innerHTML += `${clusterName}: ${totalDistanceKm}km, ${timeString}<br>`;
    }
    
    function calculateOptimalRouteDistance(branches) {
        if (branches.length <= 1) return 0;
        const route = findOptimalRoute(branches);
        let totalDistance = 0;
        for (let i = 0; i < route.length - 1; i++) {
            totalDistance += haversineDistance(route[i].lat, route[i].lng, route[i + 1].lat, route[i + 1].lng);
        }
        return totalDistance;
    }
    
    function findOptimalRoute(branches) {
        if (branches.length <= 1) return branches.map(b => ({lat: parseFloat(b.latitude), lng: parseFloat(b.longitude)}));
        if (branches.length == 2) {
            return [
                {lat: parseFloat(branches[0].latitude), lng: parseFloat(branches[0].longitude)},
                {lat: parseFloat(branches[1].latitude), lng: parseFloat(branches[1].longitude)}
            ];
        }
        
        // Use nearest neighbor TSP heuristic for route optimization
        return nearestNeighborTSP(branches);
    }
    
    function nearestNeighborTSP(branches) {
        const n = branches.length;
        const visited = new Array(n).fill(false);
        const route = [];
        
        // Start from first branch
        let current = 0;
        visited[0] = true;
        route.push({lat: parseFloat(branches[0].latitude), lng: parseFloat(branches[0].longitude)});
        
        // Find nearest unvisited branch at each step
        for (let i = 1; i < n; i++) {
            let nearest = -1;
            let minDist = Infinity;
            
            for (let j = 0; j < n; j++) {
                if (!visited[j]) {
                    const dist = haversineDistance(
                        parseFloat(branches[current].latitude), parseFloat(branches[current].longitude),
                        parseFloat(branches[j].latitude), parseFloat(branches[j].longitude)
                    );
                    if (dist < minDist) {
                        minDist = dist;
                        nearest = j;
                    }
                }
            }
            
            if (nearest !== -1) {
                visited[nearest] = true;
                route.push({lat: parseFloat(branches[nearest].latitude), lng: parseFloat(branches[nearest].longitude)});
                current = nearest;
            }
        }
        
        return route;
    }
    
    function haversineDistance(lat1, lon1, lat2, lon2) {
        const R = 6371; // km
        const dLat = (lat2 - lat1) * Math.PI / 180;
        const dLon = (lon2 - lon1) * Math.PI / 180;
        const a = Math.sin(dLat/2) * Math.sin(dLat/2) +
                Math.cos(lat1 * Math.PI / 180) * Math.cos(lat2 * Math.PI / 180) *
                Math.sin(dLon/2) * Math.sin(dLon/2);
        const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
        return R * c;
    }
    
    function addDirectionArrows(path, color) {
        for (let i = 0; i < path.length - 1; i++) {
            const start = path[i];
            const end = path[i + 1];
            
            // Calculate midpoint for arrow placement
            const midLat = (start.lat + end.lat) / 2;
            const midLng = (start.lng + end.lng) / 2;
            
            // Calculate bearing for arrow direction
            const bearing = calculateBearing(start.lat, start.lng, end.lat, end.lng);
            
            const arrow = new google.maps.Marker({
                position: {lat: midLat, lng: midLng},
                map: map,
                icon: {
                    path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                    scale: 3,
                    fillColor: color,
                    fillOpacity: 0.8,
                    strokeWeight: 1,
                    strokeColor: '#333',
                    rotation: bearing
                }
            });
            markers.push(arrow);
        }
    }
    
    function calculateBearing(lat1, lng1, lat2, lng2) {
        const dLng = (lng2 - lng1) * Math.PI / 180;
        const lat1Rad = lat1 * Math.PI / 180;
        const lat2Rad = lat2 * Math.PI / 180;
        
        const y = Math.sin(dLng) * Math.cos(lat2Rad);
        const x = Math.cos(lat1Rad) * Math.sin(lat2Rad) - Math.sin(lat1Rad) * Math.cos(lat2Rad) * Math.cos(dLng);
        
        return (Math.atan2(y, x) * 180 / Math.PI + 360) % 360;
    }

    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 10,
            center: {lat: 7.0, lng: 80.0} // Default center
        });
    }

    document.getElementById('routineForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const routineId = document.getElementById('routine_id').value;
        const authToken = document.getElementById('auth_token').value;
        const endpoint = document.getElementById('endpoint').value;
        document.getElementById('status').textContent = 'Loading...';
        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(authToken ? { 'Authorization': authToken } : {})
            },
            body: JSON.stringify({ routine_id: routineId })
        })
        .then(res => res.json())
        .then(data => {
            let clusters = data.clusters;
            let techCount = data.technician_count;
            if (data.status === 'success' && !clusters && data.technician_count && data.technician_count.clusters) {
                clusters = data.technician_count.clusters;
                techCount = data.technician_count.technician_count;
            }
            if (data.status === 'success' && clusters) {
                displayClusters(clusters);
                document.getElementById('status').textContent = `Technician count: ${techCount}`;
            } else {
                document.getElementById('status').textContent = data.message || 'No data.';
                clearMap();
            }
        })
        .catch(err => {
            document.getElementById('status').textContent = 'Error: ' + err;
            clearMap();
        });
    });
    </script>
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAsKW1D7v1veULUSuqvfI3sH82XAMa3qN0&callback=initMap&libraries=routes" async defer></script>
</body>
</html>
