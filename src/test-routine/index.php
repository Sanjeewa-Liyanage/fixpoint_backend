<!DOCTYPE html>
<html>
<head>
    <title>Technician Clusters Map</title>
    <style>
        body { font-family: sans-serif; }
        #map { height: 80vh; width: 100%; border: 1px solid #ccc; }
        #form-container { margin-bottom: 1em; padding: 1em; background-color: #f0f0f0; border-radius: 5px; }
        #status { margin-top: 1em; font-weight: bold; }
        #controls-container { margin-bottom: 1em; padding: 0.5em; }
        .route-button { margin-right: 10px; padding: 8px 15px; border: none; color: white; cursor: pointer; border-radius: 4px; font-weight: bold; }
    </style>
</head>
<body>
    <h2>Technician Clusters Map</h2>
    <p>Click any colored marker on the map to set it as the starting point for its route.</p>

    <div id="form-container">
        <form id="routineForm">
            <label for="routine_id">Routine ID:</label>
            <input type="number" id="routine_id" name="routine_id" required>
            <label for="auth_token">Authorization:</label>
            <input type="text" id="auth_token" name="auth_token" placeholder="Bearer ..." style="width:300px">
            <label for="endpoint">Endpoint:</label>
            <input type="text" id="endpoint" name="endpoint" value="http://localhost:8000/fixpoint-be-php/routine/routine/technicianCount" style="width:400px">
            <button type="submit">Load Clusters</button>
        </form>
        <div id="status"></div>
    </div>

    <div id="controls-container">
        Select a technician to view their optimized route:
    </div>

    <div id="map"></div>

    <script>
    let map;
    let directionsRenderers = [];
    let markers = [];
    let clusterStore = {};
    let startEndMarker = null;

    const colors = ["#e6194b", "#3cb44b", "#4363d8", "#f58231", "#911eb4", "#46f0f0", "#f032e6", "#bcf60c", "#fabebe", "#008080", "#e6beff", "#9a6324"];

    function clearMap() {
        clearRoutes();
        markers.forEach(m => m.setMap(null));
        markers = [];
        startEndMarker = null;
        document.getElementById('status').innerHTML = '';
        document.getElementById('controls-container').innerHTML = 'Select a technician to view their optimized route:';
        clusterStore = {};
    }

    function clearRoutes() {
        directionsRenderers.forEach(dr => dr.setMap(null));
        directionsRenderers = [];
    }

    function findMarkerByBranchId(branch_id) {
        return markers.find(m => m.get('branch_id') === branch_id);
    }

    function displayClusters(clustersData) {
        clearMap();
        clusterStore = clustersData;
        let firstBranch = null;
        let colorIndex = 0;
        
        for (const [clusterName, clusterObj] of Object.entries(clustersData)) {
            const branches = clusterObj.branches;
            if (branches.length === 0) continue;
            const clusterColor = colors[colorIndex % colors.length];
            colorIndex++;
            if (!firstBranch) firstBranch = branches[0];
            addMarkersForCluster(branches, clusterColor, clusterName);
            createRouteButton(clusterName, clusterColor);
        }

        if (firstBranch) {
            map.setCenter({lat: parseFloat(firstBranch.latitude), lng: parseFloat(firstBranch.longitude)});
            map.setZoom(12);
        }
    }

    function createRouteButton(clusterName, color) {
        const controlsDiv = document.getElementById('controls-container');
        const button = document.createElement('button');
        button.textContent = `Show Default Route: ${clusterName}`;
        button.className = 'route-button';
        button.style.backgroundColor = color;
        button.onclick = () => showSpecificRoute(clusterName, color); // No start ID, so it shows the default
        controlsDiv.appendChild(button);
    }
    
    // --- START OF MODIFIED FUNCTIONS ---

    function showSpecificRoute(clusterName, color, startBranchId = null) {
        clearRoutes();
        
        if (startEndMarker) {
            const oldClusterColor = startEndMarker.get('clusterColor');
            startEndMarker.setIcon({
                path: google.maps.SymbolPath.CIRCLE,
                scale: 7,
                fillColor: oldClusterColor,
                fillOpacity: 0.9,
                strokeWeight: 1.5,
                strokeColor: '#333'
            });
            startEndMarker.setZIndex(null);
            startEndMarker = null;
        }
        
        const clusterData = clusterStore[clusterName];
        if (!clusterData || !clusterData.branches || clusterData.branches.length === 0) return;

        let branchesToDraw = [...clusterData.branches];

        if (startBranchId) {
            const startIndex = branchesToDraw.findIndex(b => b.branch_id === startBranchId);
            if (startIndex > 0) {
                const newStartBranch = branchesToDraw.splice(startIndex, 1)[0];
                branchesToDraw.unshift(newStartBranch);
            }
        }

        const firstBranch = branchesToDraw[0];
        const markerToUpdate = findMarkerByBranchId(firstBranch.branch_id);

        if (markerToUpdate) {
            markerToUpdate.setIcon({
                path: google.maps.SymbolPath.FORWARD_CLOSED_ARROW,
                scale: 8,
                rotation: 45,
                fillColor: 'white',
                fillOpacity: 1.0,
                strokeWeight: 2,
                strokeColor: 'black'
            });
            markerToUpdate.setZIndex(google.maps.Marker.MAX_ZINDEX + 1);
            startEndMarker = markerToUpdate;
        }
        
        drawOptimizedRoute(branchesToDraw, color, clusterName);
    }

    function addMarkersForCluster(branches, color, clusterName) {
        branches.forEach((branch) => {
            const marker = new google.maps.Marker({
                position: { lat: parseFloat(branch.latitude), lng: parseFloat(branch.longitude) },
                map: map,
                title: `Click to set as start for: ${clusterName}\n(${branch.name})`,
                icon: {
                    path: google.maps.SymbolPath.CIRCLE,
                    scale: 7,
                    fillColor: color,
                    fillOpacity: 0.9,
                    strokeWeight: 1.5,
                    strokeColor: '#333'
                }
            });
            marker.set('branch_id', branch.branch_id);
            marker.set('clusterName', clusterName);
            marker.set('clusterColor', color);
            
            marker.addListener('click', () => {
                showSpecificRoute(clusterName, color, branch.branch_id);
                const infowindow = new google.maps.InfoWindow({
                    content: `<b>${clusterName}</b><br>${branch.name}<br><i>Route recalculated from here.</i>`
                });
                infowindow.open(map, marker);
            });
            markers.push(marker);
        });
    }

    // --- END OF MODIFIED FUNCTIONS ---

    function drawOptimizedRoute(branches, color, clusterName) {
        if (branches.length < 2) return;
        const directionsService = new google.maps.DirectionsService();
        const directionsRenderer = new google.maps.DirectionsRenderer({
            suppressMarkers: true,
            polylineOptions: { strokeColor: color, strokeOpacity: 0.8, strokeWeight: 5, zIndex: 1 }
        });
        directionsRenderer.setMap(map);
        directionsRenderers.push(directionsRenderer);
        const originPoint = { lat: parseFloat(branches[0].latitude), lng: parseFloat(branches[0].longitude) };
        const waypoints = branches.slice(1).map(branch => ({
            location: { lat: parseFloat(branch.latitude), lng: parseFloat(branch.longitude) },
            stopover: true
        }));
        const request = {
            origin: originPoint,
            destination: originPoint,
            waypoints: waypoints,
            optimizeWaypoints: true,
            travelMode: google.maps.TravelMode.DRIVING
        };
        directionsService.route(request, (result, status) => {
            if (status === 'OK') {
                directionsRenderer.setDirections(result);
                updateRouteDetails(result, clusterName, color);
            } else {
                console.error(`Directions request failed for ${clusterName} due to ${status}`);
                alert(`Could not calculate optimized route for ${clusterName}: ${status}`);
            }
        });
    }
    
    function updateRouteDetails(result, clusterName, color) {
        let totalDistanceMeters = 0;
        let totalDurationSeconds = 0;
        const route = result.routes[0];
        route.legs.forEach(leg => {
            totalDistanceMeters += leg.distance.value;
            totalDurationSeconds += leg.duration.value;
        });
        const distanceKm = (totalDistanceMeters / 1000).toFixed(1);
        const durationMinutes = Math.round(totalDurationSeconds / 60);
        const statusDiv = document.getElementById('status');
        const header = statusDiv.querySelector('strong');
        statusDiv.innerHTML = header ? header.outerHTML + '<br>' : '';
        statusDiv.innerHTML += `
            <div style="color:${color}; margin-top: 10px;">
                <strong>Current Route: ${clusterName}</strong><br>
                Starting from: ${route.legs[0].start_address}<br>
                Distance: ${distanceKm} km, Estimated Travel Time: ${durationMinutes} mins.
            </div>`;
    }

    function initMap() {
        map = new google.maps.Map(document.getElementById('map'), {
            zoom: 10,
            center: {lat: 6.9271, lng: 79.8612},
            mapTypeControl: false
        });
    }

    document.getElementById('routineForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const routineId = document.getElementById('routine_id').value;
        const authToken = document.getElementById('auth_token').value;
        const endpoint = document.getElementById('endpoint').value;
        const statusDiv = document.getElementById('status');
        statusDiv.textContent = 'Loading cluster data...';

        fetch(endpoint, {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                ...(authToken ? { 'Authorization': authToken } : {})
            },
            body: JSON.stringify({ routine_id: routineId })
        })
        .then(res => {
            if (!res.ok) {
                return res.text().then(text => { throw new Error(`Network error: ${res.status} ${res.statusText} - ${text}`) });
            }
            return res.json();
        })
        .then(data => {
            let clusters = null, techCount = 0;
            if (data && data.clusters) {
                clusters = data.clusters;
                techCount = data.technician_count;
            } else if (data && data.data && data.data.clusters) {
                 clusters = data.data.clusters;
                 techCount = data.data.technician_count;
            } else if (data && data.technician_count && data.technician_count.clusters) {
                 clusters = data.technician_count.clusters;
                 techCount = data.technician_count.technician_count;
            }
            if (clusters && Object.keys(clusters).length > 0) {
                statusDiv.innerHTML = `<strong>Total Technicians: ${techCount || Object.keys(clusters).length}</strong><br>`;
                displayClusters(clusters);
            } else {
                statusDiv.textContent = 'Error: Could not find valid cluster data in server response.';
                clearMap();
            }
        })
        .catch(err => {
            statusDiv.textContent = 'Error: ' + err.message;
            console.error(err);
            clearMap();
        });
    });

    </script>
    <!-- IMPORTANT: Replace YOUR_API_KEY with your actual Google Maps API Key -->
    <script src="https://maps.googleapis.com/maps/api/js?key=AIzaSyAsKW1D7v1veULUSuqvfI3sH82XAMa3qN0&callback=initMap&libraries=routes" async defer></script>
</body>
</html>