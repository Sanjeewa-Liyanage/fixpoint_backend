<?php 
class RoutineApi extends ApiResourceBase{

   public function __construct() {
        $this->setRoles([
            
            'get' => ['admin', 'Technician', ],
            
        ]);
    }
    public function get($data) {
        $user = $this->getAuthenticatedUser();
        if (!$user || !$this->checkRoles($user['role_name'], 'get')) {
            return ['status' => 'error', 'message' => 'Unauthorized'];
        }

        $missing = $this->validateFields($data, ['branch_id','radius']);
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
        $result = Routine::getNearby($lat, $lng, $radius_km);

        try {
            $planned_date = isset($data['planned_date']) ? $data['planned_date'] : null;
            $status = "pending"; 
            $description = isset($data['description']) ? $data['description'] : null;
            $response_count = count($result);
            $branches = $result;

            $insertSql = "INSERT INTO routines (planned_date, status, description, response_count, branches) VALUES (:planned_date, :status, :description, :response_count, :branches)";
            $insertStmt = $conn->prepare($insertSql);
            $insertStmt->bindValue(':planned_date', $planned_date);
            $insertStmt->bindValue(':status', $status);
            $insertStmt->bindValue(':description', $description);
            $insertStmt->bindValue(':response_count', $response_count);
            $insertStmt->bindValue(':branches', json_encode($branches));
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


    
}